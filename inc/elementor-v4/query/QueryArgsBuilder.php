<?php
/**
 * Directorist query arg mapping.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Query;

use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\Traits\Singleton;

class QueryArgsBuilder {
	use Singleton;

	/**
	 * Allowed order by values.
	 *
	 * @var array<int,string>
	 */
	protected array $allowed_orderby_values = [ 'title', 'date', 'rand', 'price' ];

	/**
	 * Allowed pagination types.
	 *
	 * @var array<int,string>
	 */
	protected array $allowed_pagination_types = [ 'numbered', 'infinite_scroll' ];

	/**
	 * Build normalized Directorist query args from Elementor settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	public function build( array $settings = [] ): array {
		$directory_type_ids        = $this->to_int_list( $settings['directory_type_ids'] ?? [] );
		$default_directory_type_id = absint( $settings['default_directory_type_id'] ?? 0 );
		$query_mode                = QueryModeResolver::get_instance()->normalize_query_mode( $settings['query_mode'] ?? 'default' );

		if (
			$default_directory_type_id > 0 &&
			! empty( $directory_type_ids ) &&
			! in_array( $default_directory_type_id, $directory_type_ids, true )
		) {
			$default_directory_type_id = (int) $directory_type_ids[0];
		}

		if ( $default_directory_type_id <= 0 && ! empty( $directory_type_ids ) ) {
			$default_directory_type_id = (int) $directory_type_ids[0];
		}

		$display_mode = sanitize_key( (string) ( $settings['display_mode'] ?? 'default' ) );
		$display_mode = in_array( $display_mode, [ 'slider', 'map_list' ], true ) ? $display_mode : 'default';

		$view_type = 'map_list' === $display_mode
			? sanitize_key( (string) ( $settings['map_list_view_type'] ?? $settings['view_type'] ?? 'grid' ) )
			: sanitize_key( (string) ( $settings['view_type'] ?? 'grid' ) );
		$allowed_view_types = 'map_list' === $display_mode ? [ 'grid', 'list' ] : [ 'grid', 'list', 'map' ];
		$view_type = in_array( $view_type, $allowed_view_types, true ) ? $view_type : 'grid';
		// Directorist's View-as links navigate with ?view=... . Honor that on a
		// normal frontend load; editor previews and AJAX use their own view state.
		if (
			'default' === $display_mode &&
			! EditorContext::get_instance()->is_editor_request() &&
			! wp_doing_ajax() &&
			isset( $_GET['view'] ) &&
			is_scalar( $_GET['view'] )
		) {
			$requested_view = sanitize_key( wp_unslash( (string) $_GET['view'] ) );
			if ( in_array( $requested_view, $allowed_view_types, true ) ) {
				$view_type = $requested_view;
			}
		}

		$order_by = sanitize_key( (string) ( $settings['order_by'] ?? 'date' ) );
		$order_by = in_array( $order_by, $this->allowed_orderby_values, true ) ? $order_by : 'date';

		$order = strtoupper( sanitize_key( (string) ( $settings['order'] ?? 'DESC' ) ) );
		$order = in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';

		$pagination_type = sanitize_key( (string) ( $settings['pagination_type'] ?? 'numbered' ) );
		$pagination_type = in_array( $pagination_type, $this->allowed_pagination_types, true ) ? $pagination_type : 'numbered';

		if ( 'slider' === $display_mode && 'map' === $view_type ) {
			$display_mode = 'default';
		}

		if ( 'map_list' === $display_mode && 'map' === $view_type ) {
			$view_type = 'grid';
		}

		if ( in_array( $display_mode, [ 'slider', 'map_list' ], true ) ) {
			$pagination_type = 'numbered';
		}

		$query_type   = 'regular';
		$listing_ids  = [];
		$category_ids = [];
		$location_ids = [];
		$tag_ids      = [];

		if ( 'custom' === $query_mode ) {
			$query_type   = QueryModeResolver::get_instance()->normalize_query_type( $settings['query_type'] ?? 'regular' );
			$listing_ids  = $this->to_int_list( $settings['listing_ids'] ?? [] );
			$category_ids = $this->to_int_list( $settings['category_ids'] ?? [] );
			$location_ids = $this->to_int_list( $settings['location_ids'] ?? [] );
			$tag_ids      = $this->to_int_list( $settings['tag_ids'] ?? [] );
		}

		return [
			'query_mode'            => $query_mode,
			'query_type'            => $query_type,
			'directory_type_ids'    => $directory_type_ids,
			'default_directory_type'=> $default_directory_type_id,
			'view'                  => $view_type,
			'columns'               => max( 1, absint( $settings['columns'] ?? 3 ) ),
			'listings_per_page'     => max( 1, absint( $settings['listings_per_page'] ?? 6 ) ),
			'orderby'               => $order_by,
			'order'                 => $order,
			'pagination_type'       => $pagination_type,
			'display_mode'          => $display_mode,
			'featured_only'         => $this->to_bool( $settings['featured_only'] ?? false ),
			'popular_only'          => $this->to_bool( $settings['popular_only'] ?? false ),
			'logged_in_user_only'   => $this->to_bool( $settings['logged_in_user_only'] ?? false ),
			'author'                => absint( $settings['author_id'] ?? 0 ),
			'category'              => $category_ids,
			'location'              => $location_ids,
			'tag'                   => $tag_ids,
			'ids'                   => $listing_ids,
			'directorist_elementor_source' => sanitize_key( (string) ( $settings['directorist_elementor_source'] ?? 'listings-loop' ) ),
		];
	}

	/**
	 * Normalize a bool-like value.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	protected function to_bool( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), [ '1', 'true', 'yes', 'on' ], true );
		}

		return false;
	}

	/**
	 * Normalize integer lists from string or array input.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int,int>
	 */
	protected function to_int_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$values = array_map( 'absint', $value );
		$values = array_values( array_filter( $values ) );

		return array_values( array_unique( $values ) );
	}
}
