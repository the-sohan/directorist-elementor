<?php
/**
 * Elementor frontend asset manager.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Assets;

use DirectoristElementor\Core\Plugin;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\Traits\Singleton;

class FrontendAssetManager {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_frontend_styles' ] );
	}

	/**
	 * Enqueue frontend loop interaction scripts.
	 *
	 * @return void
	 */
	public function enqueue_frontend_scripts(): void {
		if ( EditorContext::get_instance()->is_editor_request() ) {
			DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/fields/map' );
		}

		$asset_relative_path = 'assets/js/directorist-loop-frontend.js';

		wp_enqueue_script(
			'directorist-elementor-v4-loop-frontend',
			Plugin::$plugin_url . $asset_relative_path,
			[ 'jquery' ],
			$this->resolve_asset_version( $asset_relative_path ),
			true
		);

		wp_add_inline_script(
			'directorist-elementor-v4-loop-frontend',
			'window.directoristElementorV4Frontend = ' . wp_json_encode(
				[
					'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
					'action'             => 'directorist_elementor_v4_render_loop',
					'homeSearchFormAction' => 'directorist_elementor_v4_render_homepage_search_form',
						'nonce'              => wp_create_nonce( 'directorist_elementor_v4_frontend' ),
						'directoristNonce'   => wp_create_nonce( function_exists( 'directorist_get_nonce_key' ) ? directorist_get_nonce_key() : 'directorist_nonce' ),
						'currentDocumentId'  => $this->resolve_frontend_document_id(),
						'currentRequestPostId' => $this->resolve_frontend_request_post_id(),
						'currentPostId'      => $this->resolve_frontend_request_post_id(),
						'homeSearchResultUrl'=> DirectoristBridge::get_instance()->get_home_search_result_url(),
						'i18n'               => [
							'addedFavourite' => __( 'Added to favorites', 'directorist-elementor' ),
							'pleaseLogin'    => __( 'Please login first', 'directorist-elementor' ),
						],
					]
				) . ';',
			'before'
		);

		$slider_relative_path = 'assets/js/taxonomy-slider.js';

		wp_enqueue_script(
			'directorist-elementor-v4-taxonomy-slider',
			Plugin::$plugin_url . $slider_relative_path,
			[ 'jquery' ],
			$this->resolve_asset_version( $slider_relative_path ),
			true
		);

		$related_relative_path = 'assets/js/related-listings-frontend.js';

		wp_enqueue_script(
			'directorist-elementor-v4-related-frontend',
			Plugin::$plugin_url . $related_relative_path,
			[ 'jquery' ],
			$this->resolve_asset_version( $related_relative_path ),
			true
		);
	}

	/**
	 * Add shared inline styles to the frontend renderer.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles(): void {
		AssetManager::add_inline_styles_to_handle( 'elementor-frontend' );
	}

	/**
	 * Resolve a stable asset version with local cache busting in development.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	protected function resolve_asset_version( string $relative_path ): string {
		$asset_path = trailingslashit( Plugin::$plugin_path ) . ltrim( $relative_path, '/\\' );

		if ( file_exists( $asset_path ) ) {
			return (string) filemtime( $asset_path );
		}

		return Plugin::$version;
	}

	/**
	 * Resolve the current Elementor document id when available.
	 *
	 * @return int
	 */
	protected function resolve_frontend_document_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();

			if ( $current_document && method_exists( $current_document, 'get_main_id' ) ) {
				$current_document_id = absint( $current_document->get_main_id() );

				if ( $current_document_id > 0 ) {
					return $current_document_id;
				}
			}
		}

		return $this->resolve_frontend_request_post_id();
	}

	/**
	 * Resolve the current frontend request post id.
	 *
	 * @return int
	 */
	protected function resolve_frontend_request_post_id(): int {
		$current_post = $GLOBALS['post'] ?? null;

		if ( $current_post instanceof \WP_Post ) {
			return absint( $current_post->ID );
		}

		return absint( get_the_ID() );
	}
}
