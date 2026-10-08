<?php
/**
 * Frontend loop AJAX controller.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Frontend;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\Traits\Singleton;

class FrontendLoopController {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'wp_ajax_directorist_elementor_v4_render_loop', [ $this, 'handle_loop_render' ] );
		add_action( 'wp_ajax_nopriv_directorist_elementor_v4_render_loop', [ $this, 'handle_loop_render' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_render_homepage_search_form', [ $this, 'handle_homepage_search_form_render' ] );
		add_action( 'wp_ajax_nopriv_directorist_elementor_v4_render_homepage_search_form', [ $this, 'handle_homepage_search_form_render' ] );
	}

	/**
	 * Render a saved Elementor listings loop with submitted frontend state.
	 *
	 * @return void
	 */
	public function handle_loop_render(): void {
		check_ajax_referer( 'directorist_elementor_v4_frontend', 'nonce' );

		$payload   = $this->get_payload();
		$post_id   = absint( $payload['postId'] ?? 0 );
		$request_post_id = absint( $payload['requestPostId'] ?? 0 );
		$loop_id   = sanitize_key( (string) ( $payload['loopId'] ?? '' ) );
		$state     = is_array( $payload['state'] ?? null ) ? (array) $payload['state'] : [];
		$request   = is_array( $state['requestVars'] ?? null ) ? (array) $state['requestVars'] : [];
		$document  = $post_id > 0 && class_exists( '\\Elementor\\Plugin' )
			? \Elementor\Plugin::$instance->documents->get( $post_id )
			: null;
		$elements  = $document && method_exists( $document, 'get_elements_data' )
			? (array) $document->get_elements_data()
			: [];
		$loop_data = '' !== $loop_id ? $this->find_element_by_id( $elements, $loop_id ) : null;

		if ( ! is_array( $loop_data ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Unable to locate the requested listings loop.', 'directorist-elementor' ),
				],
				404
			);
		}

		$overridden_loop_data = $this->apply_loop_state_overrides( $loop_data, $state, $request );
		$request_snapshot     = $this->snapshot_request_globals();
		$previous_post        = $GLOBALS['post'] ?? null;
		$switched_document    = false;

		$this->apply_request_globals( $request );

		if ( $request_post_id > 0 ) {
			$post = get_post( $request_post_id );

			if ( $post instanceof \WP_Post ) {
				$GLOBALS['post'] = $post;
				setup_postdata( $post );
			}
		}

		if ( $document && class_exists( '\\Elementor\\Plugin' ) ) {
			\Elementor\Plugin::$instance->documents->switch_to_document( $document );
			$switched_document = true;
		}

		try {
			$html = ElementTreeRenderService::get_instance()->render_element( $overridden_loop_data );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the requested listings loop.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			if ( $switched_document && class_exists( '\\Elementor\\Plugin' ) ) {
				\Elementor\Plugin::$instance->documents->restore_document();
			}

			$this->restore_request_globals( $request_snapshot );
			$GLOBALS['post'] = $previous_post;
			wp_reset_postdata();
		}
	}

	/**
	 * Render Homepage Search nav and basic form for standalone directory switching.
	 *
	 * @return void
	 */
	public function handle_homepage_search_form_render(): void {
		check_ajax_referer( 'directorist_elementor_v4_frontend', 'nonce' );

		if ( ! class_exists( '\\Directorist\\Directorist_Listings' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'Directorist listings are unavailable.', 'directorist-elementor' ),
				],
				500
			);
		}

		$post_id   = absint( $_POST['post_id'] ?? 0 );
		$widget_id = sanitize_key( (string) ( $_POST['widget_id'] ?? '' ) );
		$document  = null;
		$standalone_search_element = [];

		if ( $post_id > 0 && '' !== $widget_id && class_exists( '\\Elementor\\Plugin' ) ) {
			$post = get_post( $post_id );
			if ( $post instanceof \WP_Post && ( 'publish' === $post->post_status || current_user_can( 'edit_post', $post_id ) ) ) {
				$document = \Elementor\Plugin::$instance->documents->get( $post_id );
				$elements = $document && method_exists( $document, 'get_elements_data' )
					? (array) $document->get_elements_data()
					: [];
				$matched = $this->find_element_by_id( $elements, $widget_id );

				if (
					is_array( $matched ) &&
					'directorist_homepage_search' === sanitize_key( (string) ( $matched['elType'] ?? $matched['widgetType'] ?? '' ) )
				) {
					$standalone_search_element = $matched;
				}
			}
		}

		$bridge = DirectoristBridge::get_instance();
		$contract = $bridge->resolve_home_search_contract();
		$directory_ids = $this->normalize_directory_ids( $contract['directory_type_ids'] ?? [] );
		if ( empty( $directory_ids ) ) {
			$directory_ids = $this->normalize_directory_ids( $_POST['directory_type_ids'] ?? [] );
		}

		$selected_directory_id = $this->resolve_homepage_search_form_directory_id( $directory_ids );
		if ( $selected_directory_id <= 0 ) {
			$selected_directory_id = absint( $contract['default_directory_type_id'] ?? $contract['directory_type_id'] ?? 0 );
		}
		if ( $selected_directory_id <= 0 && ! empty( $directory_ids ) ) {
			$selected_directory_id = (int) $directory_ids[0];
		}
		if (
			$selected_directory_id > 0 &&
			! empty( $directory_ids ) &&
			! in_array( $selected_directory_id, $directory_ids, true )
		) {
			$selected_directory_id = (int) $directory_ids[0];
		}

		$directory_slugs = $this->resolve_directory_slugs( $directory_ids );
		$listings_args = [
			'_current_page' => 'search_result',
		];

		if ( ! empty( $directory_slugs ) ) {
			$listings_args['directory_type'] = implode( ',', $directory_slugs );
		}
		if ( $selected_directory_id > 0 ) {
			$listings_args['default_directory_type'] = $selected_directory_id;
			$listings_args['default_directory_type_id'] = $selected_directory_id;
		}

		$listings = new \Directorist\Directorist_Listings( $listings_args, 'listing' );

		if ( $selected_directory_id > 0 ) {
			$listings->directory_type_id = $selected_directory_id;
			$listings->current_listing_type = $listings->get_current_listing_type();
			$listings->atts['directory_type_id'] = $selected_directory_id;
			$listings->atts['default_directory_type'] = $selected_directory_id;
			$listings->atts['default_directory_type_id'] = $selected_directory_id;
		}

		if ( ! empty( $directory_slugs ) ) {
			$listings->atts['directory_type'] = implode( ',', $directory_slugs );
			$listings->atts['directory_type_ids'] = implode( ',', $directory_ids );
		}

		$listings->atts['directorist_elementor_source'] = 'homepage-search';
		$listings->atts['_current_page'] = 'search_result';

		if ( isset( $listings->options ) && is_array( $listings->options ) ) {
			$listings->options['all_listing_layout'] = 'no_sidebar';
			$listings->options['listing_instant_search'] = '';
		}

		$switched_document = false;
		if ( $document && class_exists( '\\Elementor\\Plugin' ) ) {
			\Elementor\Plugin::$instance->documents->switch_to_document( $document );
			$switched_document = true;
		}

		try {
			$form_markup = $this->render_homepage_search_contract_form(
				$contract,
				$directory_ids,
				$selected_directory_id,
				$standalone_search_element
			);
		} finally {
			if ( $switched_document ) {
				\Elementor\Plugin::$instance->documents->restore_document();
			}
		}

		if ( '' === $form_markup ) {
			ob_start();
			$listings->basic_search_form_template();
			$form_markup = ob_get_clean();
		}

		wp_send_json_success(
			[
				'nav'                       => '',
				'form'                      => is_string( $form_markup ) ? $form_markup : '',
				'directory_type_id'         => $selected_directory_id,
				'default_directory_type_id' => $selected_directory_id,
			]
		);
	}

	/**
	 * Render the saved Homepage Search widget form under the requested directory.
	 *
	 * @param array<string,mixed> $contract Homepage search contract.
	 * @param array<int,int>      $directory_ids Selected directory ids.
	 * @param int                 $selected_directory_id Selected directory id.
	 * @param array<string,mixed> $standalone_search_element Saved standalone widget.
	 * @return string
	 */
	protected function render_homepage_search_contract_form( array $contract, array $directory_ids, int $selected_directory_id, array $standalone_search_element = [] ): string {
		$search_element = ! empty( $standalone_search_element )
			? $standalone_search_element
			: ( is_array( $contract['search_element'] ?? null ) ? (array) $contract['search_element'] : [] );

		if ( empty( $search_element ) ) {
			return '';
		}

		$settings = [
			'query_mode'                   => 'default',
			'query_type'                   => 'regular',
			'directory_type_ids'           => $directory_ids,
			'default_directory_type_id'    => $selected_directory_id,
			'active_directory_type_id'     => $selected_directory_id,
			'view_type'                    => 'grid',
			'columns'                      => 3,
			'listings_per_page'            => 1,
			'order_by'                     => 'date',
			'order'                        => 'DESC',
			'pagination_type'              => 'numbered',
			'directorist_elementor_source' => 'homepage-search',
		];
		$instance_id = InstanceState::get_instance()->normalize_instance_id( 'direl-home-search-ajax' );

		$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
			$settings,
			$instance_id,
			false,
			1
		);
		$runtime_state['active_directory'] = $selected_directory_id;

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			$markup = ElementTreeRenderService::get_instance()->render_widget_content( $search_element );
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}

		return $this->extract_first_markup_by_class( $markup, 'directorist-elementor-listings-search__form' );
	}

	/**
	 * Extract the first element matching a class from rendered markup.
	 *
	 * @param string $markup Rendered markup.
	 * @param string $class_name Target class.
	 * @return string
	 */
	protected function extract_first_markup_by_class( string $markup, string $class_name ): string {
		if ( '' === trim( $markup ) || ! class_exists( '\\DOMDocument' ) || ! class_exists( '\\DOMXPath' ) ) {
			return trim( $markup );
		}

		$previous_errors = libxml_use_internal_errors( true );
		$document        = new \DOMDocument();
		$loaded          = $document->loadHTML(
			'<?xml encoding="utf-8" ?><div id="direl-home-search-fragment">' . $markup . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( ! $loaded ) {
			return trim( $markup );
		}

		$xpath = new \DOMXPath( $document );
		$query = sprintf(
			"//*[contains(concat(' ', normalize-space(@class), ' '), ' %s ')]",
			$class_name
		);
		$nodes = $xpath->query( $query );

		if ( ! $nodes || $nodes->length <= 0 ) {
			return trim( $markup );
		}

		return trim( (string) $document->saveHTML( $nodes->item( 0 ) ) );
	}

	/**
	 * Parse the JSON payload from the AJAX request.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_payload(): array {
		$payload = wp_unslash( (string) ( $_POST['payload'] ?? '' ) );

		if ( '' === $payload ) {
			return [];
		}

		$decoded = json_decode( $payload, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Recursively find a raw Elementor element by id.
	 *
	 * @param array<int,array<string,mixed>> $elements Raw element data.
	 * @param string                         $target_id Target element id.
	 * @return array<string,mixed>|null
	 */
	protected function find_element_by_id( array $elements, string $target_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $element;
			}

			$children = (array) ( $element['elements'] ?? [] );

			if ( empty( $children ) ) {
				continue;
			}

			$found = $this->find_element_by_id( $children, $target_id );

			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Apply current frontend scope overrides to raw loop settings.
	 *
	 * @param array<string,mixed> $loop_data Raw loop element data.
	 * @param array<string,mixed> $state Requested state.
	 * @param array<string,mixed> $request Requested form/query vars.
	 * @return array<string,mixed>
	 */
	protected function apply_loop_state_overrides( array $loop_data, array $state, array $request ): array {
		$settings = is_array( $loop_data['settings'] ?? null ) ? (array) $loop_data['settings'] : [];
		$element_type = sanitize_key( (string) ( $loop_data['elType'] ?? $loop_data['widgetType'] ?? '' ) );

		if ( 'directorist_homepage_search_loop' === $element_type ) {
			$settings['query_mode'] = 'default';
			$settings['query_type'] = 'regular';
			$settings['directorist_elementor_source'] = 'homepage-search-loop';
		}

		$active_view = sanitize_key( (string) ( $state['activeView'] ?? '' ) );
		if ( in_array( $active_view, [ 'grid', 'list', 'map' ], true ) ) {
			$settings['active_view_type'] = $active_view;
			$settings['view_type']        = $active_view;

			if (
				'map_list' === sanitize_key( (string) ( $settings['display_mode'] ?? 'default' ) ) &&
				in_array( $active_view, [ 'grid', 'list' ], true )
			) {
				$settings['map_list_view_type'] = $active_view;
			}
		}

		$requested_directory_id = $this->resolve_requested_directory_type_id( $request, $settings );
		$active_directory_id    = $requested_directory_id > 0
			? $requested_directory_id
			: absint( $state['activeDirectoryId'] ?? 0 );

		if ( $active_directory_id > 0 ) {
			$settings['active_directory_type_id']  = $active_directory_id;
			$settings['default_directory_type_id'] = $active_directory_id;
		}

		$loop_data['settings'] = $settings;

		return $loop_data;
	}

	/**
	 * Resolve a requested directory type id from submitted request vars.
	 *
	 * @param array<string,mixed> $request Submitted request vars.
	 * @param array<string,mixed> $settings Saved loop settings.
	 * @return int
	 */
	protected function resolve_requested_directory_type_id( array $request, array $settings ): int {
		$raw_directory = $request['directory_type'] ?? '';

		if ( is_array( $raw_directory ) ) {
			$raw_directory = reset( $raw_directory );
		}

		if ( ! is_scalar( $raw_directory ) ) {
			return 0;
		}

		$raw_directory = trim( (string) $raw_directory );

		if ( '' === $raw_directory || 'all' === strtolower( $raw_directory ) ) {
			return 0;
		}

		$directory_id = absint( $raw_directory );

		if ( $directory_id <= 0 ) {
			$term = get_term_by( 'slug', sanitize_title( $raw_directory ), DirectoristBridge::get_instance()->get_directory_taxonomy() );

			if ( $term instanceof \WP_Term ) {
				$directory_id = (int) $term->term_id;
			}
		}

		if ( $directory_id <= 0 ) {
			return 0;
		}

		$allowed_directory_ids = array_values(
			array_filter(
				array_map( 'absint', (array) ( $settings['directory_type_ids'] ?? [] ) )
			)
		);

		if ( ! empty( $allowed_directory_ids ) && ! in_array( $directory_id, $allowed_directory_ids, true ) ) {
			return (int) $allowed_directory_ids[0];
		}

		return $directory_id;
	}

	/**
	 * Resolve selected directory id for Homepage Search form refresh AJAX.
	 *
	 * @param array<int,int> $allowed_directory_ids Allowed directory IDs.
	 * @return int
	 */
	protected function resolve_homepage_search_form_directory_id( array $allowed_directory_ids ): int {
		foreach ( [ 'directory_type_id', 'directory_type', 'default_directory_type_id', 'default_directory_type' ] as $request_key ) {
			if ( ! isset( $_POST[ $request_key ] ) ) {
				continue;
			}

			$value = wp_unslash( $_POST[ $request_key ] );
			$directory_id = 0;

			if ( is_scalar( $value ) && is_numeric( $value ) ) {
				$directory_id = absint( $value );
			} elseif ( is_scalar( $value ) ) {
				$slug = sanitize_title( (string) $value );
				if ( '' !== $slug ) {
					$term = get_term_by( 'slug', $slug, DirectoristBridge::get_instance()->get_directory_taxonomy() );
					$directory_id = $term instanceof \WP_Term ? absint( $term->term_id ) : 0;
				}
			}

			if (
				$directory_id > 0 &&
				( empty( $allowed_directory_ids ) || in_array( $directory_id, $allowed_directory_ids, true ) )
			) {
				return $directory_id;
			}
		}

		return 0;
	}

	/**
	 * Normalize directory ID values.
	 *
	 * @param mixed $value Raw directory IDs.
	 * @return array<int,int>
	 */
	protected function normalize_directory_ids( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_map( 'trim', explode( ',', $value ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		$ids = array_map( 'absint', $value );
		$ids = array_values( array_filter( $ids ) );

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Resolve ordered directory slugs from IDs.
	 *
	 * @param array<int,int> $directory_ids Directory IDs.
	 * @return array<int,string>
	 */
	protected function resolve_directory_slugs( array $directory_ids ): array {
		$directory_ids = $this->normalize_directory_ids( $directory_ids );
		$taxonomy = DirectoristBridge::get_instance()->get_directory_taxonomy();

		if ( empty( $directory_ids ) || ! taxonomy_exists( $taxonomy ) ) {
			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'include'    => $directory_ids,
			]
		);

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		$slugs_by_id = [];
		foreach ( $terms as $term ) {
			if ( $term instanceof \WP_Term ) {
				$slugs_by_id[ (int) $term->term_id ] = (string) $term->slug;
			}
		}

		$slugs = [];
		foreach ( $directory_ids as $directory_id ) {
			if ( isset( $slugs_by_id[ $directory_id ] ) ) {
				$slugs[] = $slugs_by_id[ $directory_id ];
			}
		}

		return array_values( array_unique( array_filter( $slugs ) ) );
	}

	/**
	 * Snapshot request globals before temporarily overriding them.
	 *
	 * @return array<string,mixed>
	 */
	protected function snapshot_request_globals(): array {
		return [
			'request' => $_REQUEST,
			'post'    => $_POST,
			'get'     => $_GET,
		];
	}

	/**
	 * Apply submitted request vars to PHP superglobals.
	 *
	 * Directorist listings/search controllers read from request state directly.
	 *
	 * @param array<string,mixed> $request Submitted request vars.
	 * @return void
	 */
	protected function apply_request_globals( array $request ): void {
		$sanitized_request = $this->sanitize_request_value( $request );

		if ( ! is_array( $sanitized_request ) ) {
			return;
		}

		$normalized_request = $this->normalize_request_payload( $sanitized_request );

		$_REQUEST = array_replace_recursive( $_REQUEST, $normalized_request );
		$_POST    = array_replace_recursive( $_POST, $normalized_request );
		$_GET     = array_replace_recursive( $_GET, $normalized_request );
	}

	/**
	 * Restore previous request globals.
	 *
	 * @param array<string,mixed> $snapshot Previous global snapshot.
	 * @return void
	 */
	protected function restore_request_globals( array $snapshot ): void {
		$_REQUEST = is_array( $snapshot['request'] ?? null ) ? $snapshot['request'] : [];
		$_POST    = is_array( $snapshot['post'] ?? null ) ? $snapshot['post'] : [];
		$_GET     = is_array( $snapshot['get'] ?? null ) ? $snapshot['get'] : [];
	}

	/**
	 * Sanitize arbitrary request payload values.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function sanitize_request_value( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = [];

			foreach ( $value as $key => $item ) {
				$sanitized[ $key ] = $this->sanitize_request_value( $item );
			}

			return $sanitized;
		}

		if ( is_bool( $value ) || is_numeric( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return null;
	}

	/**
	 * Normalize request payload keys so names like custom_field[foo] behave like
	 * a native PHP form submission after AJAX transport.
	 *
	 * @param array<string,mixed> $request Sanitized request payload.
	 * @return array<string,mixed>
	 */
	protected function normalize_request_payload( array $request ): array {
		$query_string = http_build_query( $request, '', '&' );

		if ( '' === $query_string ) {
			return [];
		}

		$normalized_request = [];
		wp_parse_str( $query_string, $normalized_request );

		return is_array( $normalized_request ) ? $normalized_request : [];
	}
}
