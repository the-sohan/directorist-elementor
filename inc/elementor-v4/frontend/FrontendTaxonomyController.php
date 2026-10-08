<?php
/**
 * Frontend taxonomy AJAX controller.
 *
 * Handles AJAX directory-type tab switching and pagination
 * for All Categories / All Locations widgets.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Frontend;

use DirectoristElementor\ElementorV4\Render\TaxonomyCompositionRenderService;
use DirectoristElementor\Traits\Singleton;

class FrontendTaxonomyController {
	use Singleton;

	protected function __construct() {
		add_action( 'wp_ajax_directorist_elementor_v4_taxonomy_switch', [ $this, 'handle_taxonomy_switch' ] );
		add_action( 'wp_ajax_nopriv_directorist_elementor_v4_taxonomy_switch', [ $this, 'handle_taxonomy_switch' ] );
	}

	/**
	 * Re-render a taxonomy shortcode for the requested directory type / page.
	 *
	 * @return void
	 */
	public function handle_taxonomy_switch(): void {
		check_ajax_referer( 'directorist_elementor_v4_frontend', 'nonce' );

		$raw_payload = wp_unslash( (string) ( $_POST['payload'] ?? '' ) );
		$payload     = '' !== $raw_payload ? json_decode( $raw_payload, true ) : null;

		if ( ! is_array( $payload ) ) {
			wp_send_json_error( [ 'message' => 'Invalid payload.' ], 400 );
		}

		$shortcode = sanitize_key( (string) ( $payload['shortcode'] ?? '' ) );
		$allowed   = [ 'directorist_all_categories', 'directorist_all_locations' ];

		if ( ! in_array( $shortcode, $allowed, true ) ) {
			wp_send_json_error( [ 'message' => 'Invalid shortcode.' ], 400 );
		}

		$atts            = is_array( $payload['atts'] ?? null ) ? (array) $payload['atts'] : [];
		$active_type     = sanitize_text_field( (string) ( $payload['activeDirectoryType'] ?? '' ) );
		$show_all_tab    = ! empty( $payload['showAllTab'] );
		$all_tab_icon    = is_array( $payload['allTabIcon'] ?? null ) ? (array) $payload['allTabIcon'] : [];
		$slider_settings = is_array( $payload['slider'] ?? null ) ? (array) $payload['slider'] : [];
		$scope           = sanitize_key( (string) ( $payload['compositionScope'] ?? '' ) );
		$template_raw    = is_array( $payload['template'] ?? null ) ? (array) $payload['template'] : [];

		// Set default_directory_type to filter items for the active tab.
		if ( '' !== $active_type && 'all' !== $active_type ) {
			$atts['default_directory_type'] = $active_type;
		} elseif ( 'all' === $active_type ) {
			unset( $atts['default_directory_type'] );
		}

		// Handle pagination.
		$paged = absint( $atts['paged'] ?? 1 );
		unset( $atts['paged'] );

		// Sanitize attributes.
		$sanitized_atts = [];
		foreach ( $atts as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_array( $value ) ) {
				$value = implode( ',', array_filter( array_map( 'sanitize_text_field', $value ) ) );
			} else {
				$value = sanitize_text_field( (string) $value );
			}
			if ( '' !== $value ) {
				$sanitized_atts[ $key ] = $value;
			}
		}

		// Set up globals for the shortcode render.
		$previous_get   = $_GET;
		$previous_paged = get_query_var( 'paged', 0 );

		if ( 'all' === $active_type ) {
			$_GET['directory_type'] = 'all';
		} elseif ( '' !== $active_type ) {
			$_GET['directory_type'] = $active_type;
		}

		if ( $paged > 1 ) {
			$_GET['paged'] = $paged;
			set_query_var( 'paged', $paged );
		}

		$tab_settings = [
			'show_all_directory_tab' => $show_all_tab,
			'all_tab_icon'           => $all_tab_icon,
		];
		$service      = TaxonomyCompositionRenderService::get_instance();

		try {
			if ( ! empty( $template_raw ) && in_array( $scope, [ 'category', 'location' ], true ) ) {
				$html = $service->render_composed_content(
					$shortcode,
					$sanitized_atts,
					$slider_settings,
					$tab_settings,
					$template_raw,
					$scope
				);
			} else {
				$html = $service->render_legacy_content( $shortcode, $sanitized_atts, $slider_settings, $tab_settings );
			}
		} finally {
			$_GET = $previous_get;
			set_query_var( 'paged', $previous_paged );
		}

		wp_send_json_success( [ 'html' => $html ] );
	}

}
