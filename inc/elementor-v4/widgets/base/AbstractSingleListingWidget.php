<?php
/**
 * Base single listing widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use Elementor\Controls_Manager;

abstract class AbstractSingleListingWidget extends AbstractLoopAwareWidget {

	/**
	 * Single listing widgets belong to the "Others Fields" category by default.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_OTHERS;
	}

	/**
	 * Resolve listing id from runtime context.
	 *
	 * @return int
	 */
	protected function resolve_listing_id(): int {
		$context_listing_id = $this->get_current_listing_id();

		if ( $context_listing_id > 0 ) {
			return $context_listing_id;
		}

		$document_preview_listing_id = $this->resolve_single_listing_fallback_id();

		if ( $document_preview_listing_id > 0 ) {
			return $document_preview_listing_id;
		}

		// Editor fallback: query the most recent published listing,
		// scoped to the directory type if editing a directory-specific template.
		if ( $this->is_editor_context() ) {
			return $this->resolve_editor_fallback_listing_id();
		}

		return 0;
	}

	/**
	 * Register the editor-only preview listing selector used by single-template widgets.
	 *
	 * @param string $control_id Elementor control id.
	 * @return void
	 */
	protected function register_editor_preview_listing_control( string $control_id = 'preview_listing_id' ): void {
		$directory_type_id = $this->resolve_document_directory_type_id();

		$this->add_control(
			$control_id,
			[
				'label'       => __( 'Preview Listing', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_listing_options( 100, $directory_type_id ),
				'description' => __( 'Editor-only preview listing for single listing templates.', 'directorist-elementor' ),
			]
		);
	}

	/**
	 * Resolve a manually selected editor preview listing.
	 *
	 * @param string $control_id Elementor control id.
	 * @return int
	 */
	protected function resolve_editor_selected_listing_id( string $control_id = 'preview_listing_id' ): int {
		if ( ! $this->is_editor_context() || ! empty( $this->get_loop_context() ) ) {
			return 0;
		}

		$settings = $this->get_settings_for_display();
		$listing_id = absint( $settings[ $control_id ] ?? 0 );

		if ( $listing_id <= 0 ) {
			return 0;
		}

		if ( ! $this->is_listing_in_document_directory_type( $listing_id ) ) {
			return 0;
		}

		return $listing_id;
	}

	/**
	 * Resolve directory type id from runtime context.
	 *
	 * @return int
	 */
	protected function resolve_directory_type_id(): int {
		$context_directory_type_id = $this->get_current_directory_type_id();

		if ( $context_directory_type_id > 0 ) {
			return $context_directory_type_id;
		}

		$listing_id = $this->resolve_listing_id();

		return DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );
	}

	/**
	 * Resolve a fallback listing ID for the editor by querying the most recent
	 * published listing, optionally scoped to the directory type of the current
	 * Theme Builder template.
	 *
	 * @return int
	 */
	protected function resolve_editor_fallback_listing_id(): int {
		$post_type = DirectoristBridge::get_instance()->get_listing_post_type();

		$query_args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];

		$directory_type_id = $this->resolve_document_directory_type_id();

		if ( $directory_type_id > 0 ) {
			$query_args['meta_query'] = [
				[
					'key'     => '_directory_type',
					'value'   => (string) $directory_type_id,
					'compare' => '=',
				],
			];
		}

		$fallback_query = new \WP_Query( $query_args );
		$fallback_id    = ! empty( $fallback_query->posts ) ? (int) $fallback_query->posts[0] : 0;
		wp_reset_postdata();

		// If directory-scoped query found nothing, retry without filter.
		if ( $fallback_id <= 0 && $directory_type_id > 0 ) {
			unset( $query_args['meta_query'] );
			$retry_query = new \WP_Query( $query_args );
			$fallback_id = ! empty( $retry_query->posts ) ? (int) $retry_query->posts[0] : 0;
			wp_reset_postdata();
		}

		return $fallback_id;
	}

}
