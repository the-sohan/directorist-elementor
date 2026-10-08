<?php
/**
 * Listing query service.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Query;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\Traits\Singleton;

class ListingsQueryService {
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
	 * Build preview listing ids from widget settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int                 $limit Max results.
	 * @param string              $instance_id Runtime instance id.
	 * @return array<int,int>
	 */
	public function resolve_preview_listing_ids( array $settings = [], int $limit = 3, string $instance_id = '' ): array {
		$result = $this->query_listings( $settings, max( 1, $limit ), $instance_id );

		return $result['ids'];
	}

	/**
	 * Query listing ids with Directorist_Listings as the primary runtime.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int|null            $limit Optional hard limit.
	 * @param string              $instance_id Runtime instance id.
	 * @return array<string,mixed>
	 */
	public function query_listings( array $settings = [], ?int $limit = null, string $instance_id = '' ): array {
		$query_args       = QueryArgsBuilder::get_instance()->build( $settings );
		$query_args       = $this->apply_default_template_context( $query_args );
		$query_args       = $this->apply_home_search_request_context( $query_args );
		$directorist_atts = $this->build_directorist_atts( $query_args, $instance_id, $limit );
		$controller       = $this->create_directorist_controller( $directorist_atts, $query_args );

		if ( $controller ) {
			return $this->normalize_directorist_result( $controller, $query_args, $directorist_atts, $instance_id );
		}

		return $this->query_listings_fallback( $query_args, $settings, $limit, $instance_id, $directorist_atts );
	}

	/**
	 * Apply archive/theme-builder context to default queries.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @return array<string,mixed>
	 */
	protected function apply_default_template_context( array $query_args ): array {
		if (
			'default' !== (string) ( $query_args['query_mode'] ?? 'default' ) ||
			EditorContext::get_instance()->is_editor_request()
		) {
			return $query_args;
		}

		$bridge         = DirectoristBridge::get_instance();
		$queried_object = $bridge->get_current_archive_term();

		$author_id = $bridge->get_current_author_profile_user_id();
		if ( $author_id > 0 && ( $bridge->is_author_profile_request() || wp_doing_ajax() ) ) {
			$query_args['author'] = $author_id;

			$directory_type_id = $bridge->get_current_author_profile_directory_type_id();
			if ( $directory_type_id > 0 ) {
				$query_args['directory_type_ids']     = [ $directory_type_id ];
				$query_args['default_directory_type'] = $directory_type_id;
			}

			return $query_args;
		}

		if ( ! $queried_object instanceof \WP_Term ) {
			return $query_args;
		}

		switch ( $queried_object->taxonomy ) {
			case $bridge->get_directory_taxonomy():
				$query_args['directory_type_ids']     = [ (int) $queried_object->term_id ];
				$query_args['default_directory_type'] = (int) $queried_object->term_id;
				break;

			case $bridge->get_category_taxonomy():
				$query_args['category'] = [ (int) $queried_object->term_id ];
				break;

			case $bridge->get_location_taxonomy():
				$query_args['location'] = [ (int) $queried_object->term_id ];
				break;

			case $bridge->get_tag_taxonomy():
				$query_args['tag'] = [ (int) $queried_object->term_id ];
				break;
		}

		return $query_args;
	}

	/**
	 * Apply submitted Homepage Search directory context to the result loop.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @return array<string,mixed>
	 */
	protected function apply_home_search_request_context( array $query_args ): array {
		if (
			'homepage-search-loop' !== (string) ( $query_args['directorist_elementor_source'] ?? '' ) ||
			EditorContext::get_instance()->is_editor_request()
		) {
			return $query_args;
		}

		$directory_ids         = array_values( array_filter( array_map( 'absint', (array) ( $query_args['directory_type_ids'] ?? [] ) ) ) );
		$requested_default_id  = absint( $_REQUEST['default_directory_type_id'] ?? $_REQUEST['default_directory_type'] ?? 0 );
		$requested_directory_id = $this->resolve_request_directory_type_id( $_REQUEST['directory_type_id'] ?? $_REQUEST['directory_type'] ?? '' );
		$default_id            = $requested_directory_id > 0 ? $requested_directory_id : $requested_default_id;

		if ( $default_id > 0 && ! empty( $directory_ids ) && ! in_array( $default_id, $directory_ids, true ) ) {
			$default_id = (int) $directory_ids[0];
		}

		if ( $default_id > 0 && empty( $directory_ids ) ) {
			$directory_ids = [ $default_id ];
		}

		if ( $default_id <= 0 && ! empty( $directory_ids ) ) {
			$default_id = (int) $directory_ids[0];
		}

		$query_args['directory_type_ids']    = $directory_ids;
		$query_args['default_directory_type'] = $default_id;
		$query_args['query_mode']            = 'default';
		$query_args['query_type']            = 'regular';

		return $query_args;
	}

	/**
	 * Build the Directorist listings atts array from normalized widget settings.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @param string              $instance_id Runtime instance id.
	 * @param int|null            $limit Optional hard limit.
	 * @return array<string,mixed>
	 */
	protected function build_directorist_atts( array $query_args, string $instance_id = '', ?int $limit = null ): array {
		$posts_per_page       = null !== $limit ? max( 1, $limit ) : (int) $query_args['listings_per_page'];
		$directory_type_ids   = array_values( array_map( 'absint', (array) ( $query_args['directory_type_ids'] ?? [] ) ) );
		$directory_type_slugs = $this->resolve_term_slugs( $directory_type_ids, DirectoristBridge::get_instance()->get_directory_taxonomy() );
		$category_slugs       = $this->resolve_term_slugs( (array) ( $query_args['category'] ?? [] ), DirectoristBridge::get_instance()->get_category_taxonomy() );
		$location_slugs       = $this->resolve_term_slugs( (array) ( $query_args['location'] ?? [] ), DirectoristBridge::get_instance()->get_location_taxonomy() );
		$tag_slugs            = $this->resolve_term_slugs( (array) ( $query_args['tag'] ?? [] ), DirectoristBridge::get_instance()->get_tag_taxonomy() );
		$atts                 = [
			'_current_page' => 'search_result',
			'view'          => (string) ( $query_args['view'] ?? 'grid' ),
			'columns'       => $this->resolve_column_width( (int) ( $query_args['columns'] ?? 3 ) ),
			'listings_per_page'   => $posts_per_page,
			'orderby'       => (string) ( $query_args['orderby'] ?? 'date' ),
			'order'         => (string) ( $query_args['order'] ?? 'DESC' ),
			'pagination_type'     => (string) ( $query_args['pagination_type'] ?? 'numbered' ),
			'featured_only' => ! empty( $query_args['featured_only'] ) ? 'yes' : '',
			'popular_only'  => ! empty( $query_args['popular_only'] ) ? 'yes' : '',
			'logged_in_user_only' => ! empty( $query_args['logged_in_user_only'] ) ? 'yes' : '',
			'directorist_elementor_source' => (string) ( $query_args['directorist_elementor_source'] ?? 'listings-loop' ),
		];

		if ( '' !== $instance_id ) {
			$atts['instance_id'] = $instance_id;
		}

		if ( ! empty( $directory_type_slugs ) ) {
			$atts['directory_type'] = implode( ',', $directory_type_slugs );
		}

		if ( ! empty( $query_args['default_directory_type'] ) ) {
			$atts['default_directory_type'] = absint( $query_args['default_directory_type'] );
		}

		if ( ! empty( $query_args['author'] ) ) {
			$atts['author'] = absint( $query_args['author'] );
		}

		if (
			'selective' === (string) ( $query_args['query_type'] ?? 'regular' ) &&
			! empty( $query_args['ids'] )
		) {
			$atts['ids'] = implode( ',', array_map( 'absint', (array) $query_args['ids'] ) );
		} else {
			if ( ! empty( $category_slugs ) ) {
				$atts['category'] = implode( ',', $category_slugs );
			}

			if ( ! empty( $location_slugs ) ) {
				$atts['location'] = implode( ',', $location_slugs );
			}

			if ( ! empty( $tag_slugs ) ) {
				$atts['tag'] = implode( ',', $tag_slugs );
			}
		}

		return $atts;
	}

	/**
	 * Instantiate the Directorist listings controller when available.
	 *
	 * @param array<string,mixed> $directorist_atts Controller atts.
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @return object|null
	 */
	protected function create_directorist_controller( array $directorist_atts, array $query_args = [] ) {
		$class_name = '\\Directorist\\Directorist_Listings';

		if ( ! class_exists( $class_name ) ) {
			return null;
		}

		$view_snapshot = null;
		$author_id     = absint( $query_args['author'] ?? 0 );
		$author_filter = null;

		if ( ! empty( $query_args['view'] ) ) {
			$view_snapshot = [
				'request' => $_REQUEST['view'] ?? null,
				'get'     => $_GET['view'] ?? null,
			];
			$resolved_view = (string) $query_args['view'];

			$_REQUEST['view'] = $resolved_view;
			$_GET['view']     = $resolved_view;
		}

		if ( $author_id > 0 ) {
			$listing_post_type = DirectoristBridge::get_instance()->get_listing_post_type();
			$author_filter     = static function( \WP_Query $query ) use ( $author_id, $listing_post_type ): void {
				$post_type = $query->get( 'post_type' );
				$post_types = is_array( $post_type ) ? $post_type : [ $post_type ];

				if ( ! in_array( $listing_post_type, array_map( 'strval', $post_types ), true ) ) {
					return;
				}

				$query->set( 'author', $author_id );
			};

			add_action( 'pre_get_posts', $author_filter, 20 );
		}

		try {
			$controller_type = in_array( (string) ( $query_args['directorist_elementor_source'] ?? '' ), [ 'homepage-search', 'homepage-search-loop' ], true )
				? 'search_result'
				: 'listing';

			return new $class_name( $directorist_atts, $controller_type );
		} finally {
			if ( null !== $author_filter ) {
				remove_action( 'pre_get_posts', $author_filter, 20 );
			}

			if ( null !== $view_snapshot ) {
				if ( null === $view_snapshot['request'] ) {
					unset( $_REQUEST['view'] );
				} else {
					$_REQUEST['view'] = $view_snapshot['request'];
				}

				if ( null === $view_snapshot['get'] ) {
					unset( $_GET['view'] );
				} else {
					$_GET['view'] = $view_snapshot['get'];
				}
			}
		}
	}

	/**
	 * Normalize the controller result into the plugin runtime shape.
	 *
	 * @param object              $controller Directorist listings controller.
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @param array<string,mixed> $directorist_atts Controller atts.
	 * @param string              $instance_id Runtime instance id.
	 * @return array<string,mixed>
	 */
	protected function normalize_directorist_result( $controller, array $query_args, array $directorist_atts, string $instance_id = '' ): array {
		$active_directory     = $this->resolve_active_directory_type_id( $query_args, $controller );
		$effective_query_args = $this->normalize_effective_query_args( $query_args, $active_directory );
		$directory_type_ids   = array_values( array_map( 'absint', (array) ( $effective_query_args['directory_type_ids'] ?? [] ) ) );
		$directory_taxonomy  = DirectoristBridge::get_instance()->get_directory_taxonomy();
		$directory_type_slugs = $this->resolve_term_slugs( $directory_type_ids, $directory_taxonomy );
		$category_slugs      = $this->resolve_term_slugs( (array) ( $effective_query_args['category'] ?? [] ), DirectoristBridge::get_instance()->get_category_taxonomy() );
		$location_slugs      = $this->resolve_term_slugs( (array) ( $effective_query_args['location'] ?? [] ), DirectoristBridge::get_instance()->get_location_taxonomy() );
		$tag_slugs           = $this->resolve_term_slugs( (array) ( $effective_query_args['tag'] ?? [] ), DirectoristBridge::get_instance()->get_tag_taxonomy() );

		if ( $active_directory > 0 && empty( $directory_type_ids ) ) {
			$directory_type_ids   = [ $active_directory ];
			$directory_type_slugs = $this->resolve_term_slugs( $directory_type_ids, $directory_taxonomy );
			$effective_query_args['directory_type_ids'] = $directory_type_ids;
		}

		$controller->options['pagination_type'] = (string) ( $effective_query_args['pagination_type'] ?? 'numbered' );

		if ( $active_directory > 0 ) {
			$controller->directory_type_id    = $active_directory;
			$controller->current_listing_type = method_exists( $controller, 'get_current_listing_type' )
				? (int) $controller->get_current_listing_type()
				: $active_directory;
			$controller->atts['directory_type_id']    = $active_directory;
			$controller->atts['default_directory_type'] = $active_directory;
		}

		$controller->view               = (string) ( $effective_query_args['view'] ?? 'grid' );
		$controller->columns            = (int) ( $effective_query_args['columns'] ?? 3 );
		$controller->listings_per_page  = (int) ( $effective_query_args['listings_per_page'] ?? 6 );
		$controller->orderby            = (string) ( $effective_query_args['orderby'] ?? 'date' );
		$controller->order              = (string) ( $effective_query_args['order'] ?? 'DESC' );
		$controller->params['view']     = $controller->view;
		$controller->params['columns']  = $this->resolve_column_width( $controller->columns );
		$controller->params['listings_per_page'] = $controller->listings_per_page;
		$controller->params['orderby']  = $controller->orderby;
		$controller->params['order']    = $controller->order;

		if ( ! empty( $directory_type_slugs ) ) {
			$controller->atts['directory_type']     = implode( ',', $directory_type_slugs );
			$controller->atts['directory_type_ids'] = implode( ',', $directory_type_ids );
		}

		if ( ! empty( $category_slugs ) ) {
			$controller->atts['category'] = implode( ',', $category_slugs );
		}

		if ( ! empty( $location_slugs ) ) {
			$controller->atts['location'] = implode( ',', $location_slugs );
		}

		if ( ! empty( $tag_slugs ) ) {
			$controller->atts['tag'] = implode( ',', $tag_slugs );
		}

		if ( ! empty( $effective_query_args['author'] ) ) {
			$controller->atts['author'] = absint( $effective_query_args['author'] );
		}

		if ( ! empty( $effective_query_args['ids'] ) ) {
			$controller->atts['ids'] = implode( ',', array_map( 'absint', (array) $effective_query_args['ids'] ) );
		}

		if ( '' !== $instance_id ) {
			$controller->atts['instance_id'] = $instance_id;
		}

		$controller->atts['_current_page']       = 'search_result';
		$controller->atts['view']                = (string) ( $effective_query_args['view'] ?? 'grid' );
		$controller->atts['columns']             = $this->resolve_column_width( (int) ( $effective_query_args['columns'] ?? 3 ) );
		$controller->atts['listings_columns']    = (int) ( $effective_query_args['columns'] ?? 3 );
		$controller->atts['listings_per_page']   = (int) ( $effective_query_args['listings_per_page'] ?? 6 );
		$controller->atts['orderby']             = (string) ( $effective_query_args['orderby'] ?? 'date' );
		$controller->atts['order']               = (string) ( $effective_query_args['order'] ?? 'DESC' );
		$controller->atts['pagination_type']     = (string) ( $effective_query_args['pagination_type'] ?? 'numbered' );
		$controller->atts['featured_only']       = ! empty( $effective_query_args['featured_only'] ) ? 'yes' : '';
		$controller->atts['popular_only']        = ! empty( $effective_query_args['popular_only'] ) ? 'yes' : '';
		$controller->atts['logged_in_user_only'] = ! empty( $effective_query_args['logged_in_user_only'] ) ? 'yes' : '';
		$controller->atts['query_mode']          = (string) ( $effective_query_args['query_mode'] ?? 'default' );
		$controller->atts['query_type']          = (string) ( $effective_query_args['query_type'] ?? 'regular' );
		$controller->atts['directorist_elementor_source'] = (string) ( $effective_query_args['directorist_elementor_source'] ?? 'listings-loop' );

		$query_results = isset( $controller->query_results ) && is_object( $controller->query_results )
			? $controller->query_results
			: null;
		$listing_ids   = method_exists( $controller, 'post_ids' ) ? (array) $controller->post_ids() : [];

		return [
			'ids'                      => array_values( array_map( 'absint', $listing_ids ) ),
			'total'                    => (int) ( $query_results?->total ?? 0 ),
			'max_pages'                => (int) ( $query_results?->total_pages ?? 1 ),
			'current_page'             => (int) ( $query_results?->current_page ?? 1 ),
			'query_args'               => $effective_query_args,
			'directorist_atts'         => $directorist_atts,
			'data_atts'                => is_array( $controller->atts ) ? $controller->atts : [],
			'query_state'              => $this->build_query_state( $effective_query_args, $active_directory ),
			'active_directory_type_id' => $active_directory,
			'active_view'              => (string) ( $effective_query_args['view'] ?? 'grid' ),
			'controller'               => $controller,
		];
	}

	/**
	 * Query listing ids with a lightweight WP_Query fallback.
	 *
	 * @param array<string,mixed> $directorist_args Normalized Directorist args.
	 * @param array<string,mixed> $settings Raw widget settings.
	 * @param int|null            $limit Optional hard limit.
	 * @param string              $instance_id Runtime instance id.
	 * @param array<string,mixed> $directorist_atts Controller atts.
	 * @return array<string,mixed>
	 */
	protected function query_listings_fallback(
		array $directorist_args,
		array $settings = [],
		?int $limit = null,
		string $instance_id = '',
		array $directorist_atts = []
	): array {
		$effective_args = $this->normalize_effective_query_args( $directorist_args );
		$post_type      = DirectoristBridge::get_instance()->get_listing_post_type();
		$posts_per_page = null !== $limit ? max( 1, $limit ) : (int) ( $effective_args['listings_per_page'] ?? 6 );
		$query_args     = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $posts_per_page,
			'orderby'                => 'rand' === ( $effective_args['orderby'] ?? 'date' ) ? 'rand' : ( $effective_args['orderby'] ?? 'date' ),
			'order'                  => $effective_args['order'] ?? 'DESC',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => true,
		];

		if ( 'selective' === ( $effective_args['query_type'] ?? 'regular' ) && ! empty( $effective_args['ids'] ) ) {
			$query_args['post__in'] = $effective_args['ids'];
			$query_args['orderby']  = 'post__in';
		}

		if ( ! empty( $effective_args['author'] ) ) {
			$query_args['author'] = absint( $effective_args['author'] );
		}

		$tax_query = [];

		if ( ! empty( $effective_args['directory_type_ids'] ) ) {
			$tax_query[] = [
				'taxonomy' => DirectoristBridge::get_instance()->get_directory_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $effective_args['directory_type_ids'],
			];
		}

		if ( ! empty( $effective_args['category'] ) ) {
			$tax_query[] = [
				'taxonomy' => DirectoristBridge::get_instance()->get_category_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $effective_args['category'],
			];
		}

		if ( ! empty( $effective_args['location'] ) ) {
			$tax_query[] = [
				'taxonomy' => DirectoristBridge::get_instance()->get_location_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $effective_args['location'],
			];
		}

		if ( ! empty( $effective_args['tag'] ) ) {
			$tax_query[] = [
				'taxonomy' => DirectoristBridge::get_instance()->get_tag_taxonomy(),
				'field'    => 'term_id',
				'terms'    => $effective_args['tag'],
			];
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = $tax_query;
		}

		/**
		 * Filters the fallback WP_Query args used by the Elementor scaffold.
		 *
		 * @param array<string,mixed> $query_args       WP_Query args.
		 * @param array<string,mixed> $directorist_args Normalized Directorist args.
		 * @param array<string,mixed> $settings         Raw widget settings.
		 */
		$query_args = (array) apply_filters( 'directorist_elementor/listings_query_args', $query_args, $effective_args, $settings );

		$query = new \WP_Query( $query_args );
		$active_directory = absint( $effective_args['default_directory_type'] ?? 0 );

		$data_atts = array_merge(
			$directorist_atts,
			[
				'view'                         => (string) ( $effective_args['view'] ?? 'grid' ),
				'columns'                      => $this->resolve_column_width( (int) ( $effective_args['columns'] ?? 3 ) ),
				'listings_columns'             => (int) ( $effective_args['columns'] ?? 3 ),
				'listings_per_page'            => (int) ( $effective_args['listings_per_page'] ?? 6 ),
				'orderby'                      => (string) ( $effective_args['orderby'] ?? 'date' ),
				'order'                        => (string) ( $effective_args['order'] ?? 'DESC' ),
				'pagination_type'              => (string) ( $effective_args['pagination_type'] ?? 'numbered' ),
				'featured_only'                => ! empty( $effective_args['featured_only'] ) ? 'yes' : '',
				'popular_only'                 => ! empty( $effective_args['popular_only'] ) ? 'yes' : '',
				'logged_in_user_only'          => ! empty( $effective_args['logged_in_user_only'] ) ? 'yes' : '',
				'directorist_elementor_source' => (string) ( $effective_args['directorist_elementor_source'] ?? 'listings-loop' ),
			]
		);

		if ( '' !== $instance_id ) {
			$data_atts['instance_id'] = $instance_id;
		}

		return [
			'ids'                      => array_values( array_map( 'absint', $query->posts ) ),
			'total'                    => (int) $query->found_posts,
			'max_pages'                => (int) $query->max_num_pages,
			'current_page'             => (int) max( 1, $query->get( 'paged', 1 ) ),
			'query_args'               => $effective_args,
			'directorist_atts'         => $directorist_atts,
			'data_atts'                => $data_atts,
			'query_state'              => $this->build_query_state( $effective_args, $active_directory ),
			'active_directory_type_id' => $active_directory,
			'active_view'              => (string) ( $effective_args['view'] ?? 'grid' ),
			'controller'               => null,
			'wp_query'                 => $query,
		];
	}

	/**
	 * Normalize the effective loop state after Directorist resolves the request.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @param int                 $active_directory Resolved active directory id.
	 * @return array<string,mixed>
	 */
	protected function normalize_effective_query_args( array $query_args, int $active_directory = 0 ): array {
		$effective_args = $query_args;

		if ( $active_directory > 0 ) {
			$effective_args['default_directory_type'] = $active_directory;
		}

		$effective_args['view']                = $this->normalize_view( (string) ( $query_args['view'] ?? 'grid' ) );
		$effective_args['columns']             = max( 1, absint( $query_args['columns'] ?? 3 ) );
		$effective_args['listings_per_page']   = max( 1, absint( $query_args['listings_per_page'] ?? 6 ) );
		$effective_args['orderby']             = $this->normalize_orderby( (string) ( $query_args['orderby'] ?? 'date' ) );
		$effective_args['order']               = $this->normalize_order( (string) ( $query_args['order'] ?? 'DESC' ) );
		$effective_args['pagination_type']     = $this->normalize_pagination_type( (string) ( $query_args['pagination_type'] ?? 'numbered' ) );
		$effective_args['featured_only']       = $this->to_bool( $query_args['featured_only'] ?? false );
		$effective_args['popular_only']        = $this->to_bool( $query_args['popular_only'] ?? false );
		$effective_args['logged_in_user_only'] = $this->to_bool( $query_args['logged_in_user_only'] ?? false );
		$effective_args['author']              = absint( $query_args['author'] ?? 0 );

		if ( empty( $effective_args['default_directory_type'] ) && function_exists( 'directorist_get_default_directory' ) ) {
			$effective_args['default_directory_type'] = absint( directorist_get_default_directory() );
		}

		return $effective_args;
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
	 * Normalize a view value.
	 *
	 * @param string $view Raw view value.
	 * @return string
	 */
	protected function normalize_view( string $view ): string {
		$view = sanitize_key( $view );

		return in_array( $view, [ 'grid', 'list', 'map' ], true ) ? $view : 'grid';
	}

	/**
	 * Normalize an order by value.
	 *
	 * @param string $orderby Raw order by value.
	 * @return string
	 */
	protected function normalize_orderby( string $orderby ): string {
		$orderby = sanitize_key( $orderby );

		return in_array( $orderby, $this->allowed_orderby_values, true ) ? $orderby : 'date';
	}

	/**
	 * Normalize an order value.
	 *
	 * @param string $order Raw order value.
	 * @return string
	 */
	protected function normalize_order( string $order ): string {
		$order = strtoupper( sanitize_key( $order ) );

		return in_array( $order, [ 'ASC', 'DESC' ], true ) ? $order : 'DESC';
	}

	/**
	 * Normalize a pagination type value.
	 *
	 * @param string $pagination_type Raw pagination type.
	 * @return string
	 */
	protected function normalize_pagination_type( string $pagination_type ): string {
		$pagination_type = sanitize_key( $pagination_type );

		return in_array( $pagination_type, $this->allowed_pagination_types, true ) ? $pagination_type : 'numbered';
	}

	/**
	 * Build runtime query state for JS and child widgets.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @param int                 $active_directory Active directory type id.
	 * @return array<string,mixed>
	 */
	protected function build_query_state( array $query_args, int $active_directory ): array {
		return [
			'queryMode'              => (string) ( $query_args['query_mode'] ?? 'default' ),
			'queryType'              => (string) ( $query_args['query_type'] ?? 'regular' ),
			'directoryTypeIds'       => array_values( array_map( 'absint', (array) ( $query_args['directory_type_ids'] ?? [] ) ) ),
			'defaultDirectoryTypeId' => $active_directory,
			'view'                   => (string) ( $query_args['view'] ?? 'grid' ),
			'columns'                => (int) ( $query_args['columns'] ?? 3 ),
			'listingsPerPage'        => (int) ( $query_args['listings_per_page'] ?? 6 ),
			'paginationType'         => (string) ( $query_args['pagination_type'] ?? 'numbered' ),
			'featuredOnly'           => ! empty( $query_args['featured_only'] ),
			'popularOnly'            => ! empty( $query_args['popular_only'] ),
			'loggedInUserOnly'       => ! empty( $query_args['logged_in_user_only'] ),
			'authorId'               => absint( $query_args['author'] ?? 0 ),
			'listingIds'             => array_values( array_map( 'absint', (array) ( $query_args['ids'] ?? [] ) ) ),
			'categoryIds'            => array_values( array_map( 'absint', (array) ( $query_args['category'] ?? [] ) ) ),
			'locationIds'            => array_values( array_map( 'absint', (array) ( $query_args['location'] ?? [] ) ) ),
			'tagIds'                 => array_values( array_map( 'absint', (array) ( $query_args['tag'] ?? [] ) ) ),
		];
	}

	/**
	 * Resolve the active directory type for the current controller state.
	 *
	 * @param array<string,mixed> $query_args Normalized query args.
	 * @param object              $controller Directorist listings controller.
	 * @return int
	 */
	protected function resolve_active_directory_type_id( array $query_args, $controller ): int {
		$active_directory = absint( $query_args['default_directory_type'] ?? 0 );

		if ( $active_directory <= 0 && ! empty( $controller->current_listing_type ) ) {
			$active_directory = absint( $controller->current_listing_type );
		}

		if ( $active_directory <= 0 && ! empty( $query_args['directory_type_ids'][0] ) ) {
			$active_directory = absint( $query_args['directory_type_ids'][0] );
		}

		if ( $active_directory <= 0 && function_exists( 'directorist_get_default_directory' ) ) {
			$active_directory = absint( directorist_get_default_directory() );
		}

		return $active_directory;
	}

	/**
	 * Resolve a requested directory type id from id or slug.
	 *
	 * @param mixed $value Raw request value.
	 * @return int
	 */
	protected function resolve_request_directory_type_id( $value ): int {
		if ( is_array( $value ) ) {
			$value = reset( $value );
		}

		if ( ! is_scalar( $value ) ) {
			return 0;
		}

		$value = trim( (string) $value );

		if ( '' === $value || 'all' === strtolower( $value ) ) {
			return 0;
		}

		$directory_id = absint( $value );

		if ( $directory_id > 0 ) {
			return $directory_id;
		}

		$term = get_term_by( 'slug', sanitize_title( urldecode( $value ) ), DirectoristBridge::get_instance()->get_directory_taxonomy() );

		return $term instanceof \WP_Term ? absint( $term->term_id ) : 0;
	}

	/**
	 * Resolve term slugs from a taxonomy/id list pair.
	 *
	 * @param array<int,mixed> $term_ids Term ids.
	 * @param string           $taxonomy Taxonomy name.
	 * @return array<int,string>
	 */
	protected function resolve_term_slugs( array $term_ids, string $taxonomy ): array {
		$resolved_slugs = [];

		foreach ( array_values( array_map( 'absint', $term_ids ) ) as $term_id ) {
			if ( $term_id <= 0 ) {
				continue;
			}

			$term = get_term( $term_id, $taxonomy );

			if ( $term instanceof \WP_Term && '' !== (string) $term->slug ) {
				$resolved_slugs[] = (string) $term->slug;
			}
		}

		return array_values( array_unique( $resolved_slugs ) );
	}

	/**
	 * Convert requested grid columns into Directorist column-width values.
	 *
	 * @param int $columns Requested columns count.
	 * @return int
	 */
	protected function resolve_column_width( int $columns ): int {
		$columns = max( 1, min( 6, $columns ) );

		return max( 1, (int) round( 12 / $columns ) );
	}
}
