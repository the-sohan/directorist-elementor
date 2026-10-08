<?php
/**
 * Load FormGent assets early for Elementor documents.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Frontend;

use DirectoristElementor\Services\ExtensionStatusService;
use DirectoristElementor\Traits\Singleton;

class FormgentAssetCompatibility {
	use Singleton;

	/**
	 * FormGent frontend asset handle.
	 */
	private const FRONTEND_ASSET_HANDLE = 'formgent/blocks-frontend';

	/**
	 * Elementor locations that can render outside the queried document.
	 *
	 * @var array<int,string>
	 */
	private const THEME_BUILDER_LOCATIONS = [ 'header', 'footer', 'single', 'archive', 'popup' ];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 20 );
	}

	/**
	 * Enqueue FormGent's module before a block theme prints its import map.
	 *
	 * Elementor expands cached dynamic elements after wp_head. Waiting for the
	 * FormGent shortcode callback would therefore enqueue the Interactivity API
	 * dependency too late on block themes.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if (
			is_admin()
			|| ! function_exists( 'wp_enqueue_script_module' )
			|| ! shortcode_exists( 'formgent' )
			|| ! ExtensionStatusService::get_instance()->is_extension_active( 'formgent' )
			|| ! $this->request_uses_formgent()
		) {
			return;
		}

		wp_enqueue_script_module( self::FRONTEND_ASSET_HANDLE );

		if ( wp_style_is( self::FRONTEND_ASSET_HANDLE, 'registered' ) ) {
			wp_enqueue_style( self::FRONTEND_ASSET_HANDLE );
		}
	}

	/**
	 * Determine whether the current Elementor request can render FormGent.
	 *
	 * @return bool
	 */
	private function request_uses_formgent(): bool {
		$checked = [];

		if ( is_singular() && $this->document_uses_formgent( get_queried_object_id(), $checked ) ) {
			return true;
		}

		foreach ( $this->get_active_theme_builder_document_ids() as $document_id ) {
			if ( $this->document_uses_formgent( $document_id, $checked ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check a document and any Elementor templates it references.
	 *
	 * @param int             $post_id Document post ID.
	 * @param array<int,bool> $checked Previously inspected post IDs.
	 * @return bool
	 */
	private function document_uses_formgent( int $post_id, array &$checked ): bool {
		$post_id = absint( $post_id );

		if ( $post_id <= 0 || isset( $checked[ $post_id ] ) ) {
			return false;
		}

		$checked[ $post_id ] = true;
		$post                 = get_post( $post_id );

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( has_shortcode( (string) $post->post_content, 'formgent' ) ) {
			return true;
		}

		$elementor_data = (string) get_post_meta( $post_id, '_elementor_data', true );

		if ( '' === $elementor_data ) {
			return false;
		}

		if (
			false !== stripos( $elementor_data, '[formgent' )
			|| false !== strpos( $elementor_data, 'directorist_single_listing_formgent_form' )
		) {
			return true;
		}

		foreach ( $this->get_referenced_template_ids( $elementor_data ) as $template_id ) {
			if ( $this->document_uses_formgent( $template_id, $checked ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve Elementor templates referenced by the document.
	 *
	 * @param string $elementor_data Raw Elementor JSON.
	 * @return array<int>
	 */
	private function get_referenced_template_ids( string $elementor_data ): array {
		$matches = [];

		preg_match_all(
			'/"(?:template_id|loop_template_id)"\s*:\s*(?:"(\d+)"|(\d+))/',
			$elementor_data,
			$matches,
			PREG_SET_ORDER
		);

		$template_ids = [];

		foreach ( $matches as $match ) {
			$template_id = absint( $match[1] ?? $match[2] ?? 0 );

			if ( $template_id > 0 ) {
				$template_ids[] = $template_id;
			}
		}

		return array_values( array_unique( $template_ids ) );
	}

	/**
	 * Get Theme Builder documents selected for the current request.
	 *
	 * @return array<int>
	 */
	private function get_active_theme_builder_document_ids(): array {
		$module_class = '\ElementorPro\Modules\ThemeBuilder\Module';

		if ( ! class_exists( $module_class ) || ! method_exists( $module_class, 'instance' ) ) {
			return [];
		}

		$module = $module_class::instance();

		if ( ! is_object( $module ) || ! method_exists( $module, 'get_conditions_manager' ) ) {
			return [];
		}

		$conditions_manager = $module->get_conditions_manager();

		if ( ! is_object( $conditions_manager ) || ! method_exists( $conditions_manager, 'get_documents_for_location' ) ) {
			return [];
		}

		$document_ids = [];

		foreach ( self::THEME_BUILDER_LOCATIONS as $location ) {
			try {
				$documents = $conditions_manager->get_documents_for_location( $location );
			} catch ( \Throwable $exception ) {
				continue;
			}

			$this->collect_document_ids( $documents, $document_ids );
		}

		return array_values( array_unique( $document_ids ) );
	}

	/**
	 * Collect post IDs from Elementor's location response.
	 *
	 * @param mixed      $documents Elementor documents, IDs, or nested arrays.
	 * @param array<int> $document_ids Collected document IDs.
	 * @return void
	 */
	private function collect_document_ids( $documents, array &$document_ids ): void {
		if ( is_numeric( $documents ) ) {
			$document_id = absint( $documents );

			if ( $document_id > 0 ) {
				$document_ids[] = $document_id;
			}

			return;
		}

		if ( is_object( $documents ) ) {
			$document_id = method_exists( $documents, 'get_main_id' )
				? absint( $documents->get_main_id() )
				: 0;

			if ( $document_id <= 0 && method_exists( $documents, 'get_post' ) ) {
				$post        = $documents->get_post();
				$document_id = $post instanceof \WP_Post ? absint( $post->ID ) : 0;
			}

			if ( $document_id > 0 ) {
				$document_ids[] = $document_id;
			}

			return;
		}

		if ( ! is_array( $documents ) ) {
			return;
		}

		foreach ( $documents as $document ) {
			$this->collect_document_ids( $document, $document_ids );
		}
	}
}
