<?php
/**
 * Directorist Theme Builder cache guard.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder;

use DirectoristElementor\Traits\Singleton;
use Elementor\Core\Base\Document;

class TemplateCacheController {
	use Singleton;

	/**
	 * Elementor rendered element cache meta key.
	 */
	private const ELEMENT_CACHE_META_KEY = '_elementor_element_cache';

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/document/after_save', [ $this, 'clear_document_cache_after_save' ], 20 );
		add_action( 'added_post_meta', [ $this, 'clear_document_cache_after_meta_change' ], 20, 4 );
		add_action( 'updated_post_meta', [ $this, 'clear_document_cache_after_meta_change' ], 20, 4 );
		add_action( 'elementor/frontend/before_get_builder_content', [ $this, 'maybe_disable_element_cache' ], 1 );
		add_action( 'elementor/frontend/get_builder_content', [ $this, 'restore_element_cache' ], 999 );
		add_action( 'shutdown', [ $this, 'restore_element_cache_option' ] );
	}

	/**
	 * Clear stale rendered HTML after Elementor saves a Directorist template.
	 *
	 * @param Document            $document Elementor document.
	 * @param array<string,mixed> $data Save payload.
	 * @return void
	 */
	public function clear_document_cache_after_save( Document $document, array $data = [] ): void {
		$post_id = method_exists( $document, 'get_main_id' ) ? absint( $document->get_main_id() ) : 0;

		if ( $post_id <= 0 && method_exists( $document, 'get_post' ) ) {
			$post = $document->get_post();
			$post_id = $post instanceof \WP_Post ? absint( $post->ID ) : 0;
		}

		$this->clear_template_cache( $post_id );
	}

	/**
	 * Clear cache when Directorist template data changes outside Elementor's save flow.
	 *
	 * @param int    $meta_id Meta row id.
	 * @param int    $post_id Post id.
	 * @param string $meta_key Meta key.
	 * @param mixed  $meta_value Meta value.
	 * @return void
	 */
	public function clear_document_cache_after_meta_change( int $meta_id, int $post_id, string $meta_key, $meta_value ): void {
		if ( ! in_array( $meta_key, [ '_elementor_data', '_elementor_template_type', '_elementor_conditions' ], true ) ) {
			return;
		}

		if ( '_elementor_template_type' === $meta_key && 'elementor_library' === get_post_type( $post_id ) ) {
			delete_post_meta( $post_id, self::ELEMENT_CACHE_META_KEY );
			return;
		}

		$this->clear_template_cache( $post_id );
	}

	/**
	 * Elementor's document element cache is page-level, while Directorist single
	 * templates render from the active listing context. Disable it only while a
	 * Directorist Theme Builder document is being printed so listing-specific
	 * widgets are rendered fresh for each listing.
	 *
	 * @param Document $document Elementor document.
	 * @return void
	 */
	public function maybe_disable_element_cache( Document $document ): void {
		if ( ! $this->is_directorist_document( $document ) ) {
			return;
		}

		add_filter( 'pre_option_elementor_element_cache_ttl', [ $this, 'disable_element_cache_option' ], 99 );
	}

	/**
	 * Restore Elementor element cache handling after this document finishes.
	 *
	 * @param Document $document Elementor document.
	 * @return void
	 */
	public function restore_element_cache( Document $document ): void {
		if ( ! $this->is_directorist_document( $document ) ) {
			return;
		}

		$this->restore_element_cache_option();
	}

	/**
	 * Force Elementor's document renderer down the uncached branch.
	 *
	 * @param mixed $pre_option Pre-option short-circuit value.
	 * @return string
	 */
	public function disable_element_cache_option( $pre_option = false ): string {
		return 'disable';
	}

	/**
	 * Remove the temporary Elementor option override.
	 *
	 * @return void
	 */
	public function restore_element_cache_option(): void {
		remove_filter( 'pre_option_elementor_element_cache_ttl', [ $this, 'disable_element_cache_option' ], 99 );
	}

	/**
	 * Delete stale rendered document cache for a Directorist template.
	 *
	 * @param int $post_id Template post id.
	 * @return void
	 */
	private function clear_template_cache( int $post_id ): void {
		if ( $post_id <= 0 || ! $this->is_directorist_template_post( $post_id ) ) {
			return;
		}

		delete_post_meta( $post_id, self::ELEMENT_CACHE_META_KEY );
	}

	/**
	 * Check whether an Elementor document belongs to Directorist Theme Builder.
	 *
	 * @param Document $document Elementor document.
	 * @return bool
	 */
	private function is_directorist_document( Document $document ): bool {
		if ( method_exists( $document, 'get_name' ) && $this->is_directorist_template_type( (string) $document->get_name() ) ) {
			return true;
		}

		$post_id = method_exists( $document, 'get_main_id' ) ? absint( $document->get_main_id() ) : 0;

		return $this->is_directorist_template_post( $post_id );
	}

	/**
	 * Check whether a template post stores a Directorist Elementor template type.
	 *
	 * @param int $post_id Template post id.
	 * @return bool
	 */
	private function is_directorist_template_post( int $post_id ): bool {
		if ( $post_id <= 0 || 'elementor_library' !== get_post_type( $post_id ) ) {
			return false;
		}

		return $this->is_directorist_template_type(
			(string) get_post_meta( $post_id, '_elementor_template_type', true )
		);
	}

	/**
	 * Check Directorist Theme Builder template type names.
	 *
	 * @param string $template_type Elementor template type.
	 * @return bool
	 */
	private function is_directorist_template_type( string $template_type ): bool {
		return 0 === strpos( $template_type, 'directorist-single-listing-directory-' )
			|| 0 === strpos( $template_type, 'directorist-listing-' );
	}
}
