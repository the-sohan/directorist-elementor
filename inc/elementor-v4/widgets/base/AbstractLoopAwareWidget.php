<?php
/**
 * Base loop-aware widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;

abstract class AbstractLoopAwareWidget extends AbstractDirectoristWidget {

	/**
	 * Get current loop context.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_loop_context(): array {
		return RenderContext::get_instance()->current_loop_context();
	}

	/**
	 * Get current listing context.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_listing_context(): array {
		return RenderContext::get_instance()->current_listing_context();
	}

	/**
	 * Get current listing id.
	 *
	 * @return int
	 */
	protected function get_current_listing_id(): int {
		$listing_id = absint( $this->get_listing_context()['listing_id'] ?? 0 );

		if ( $listing_id > 0 ) {
			return $listing_id;
		}

		$listing_id = $this->resolve_single_listing_fallback_id();

		if ( $listing_id > 0 ) {
			return $listing_id;
		}

		return $this->resolve_editor_loop_preview_listing_id();
	}

	/**
	 * Get current directory type id.
	 *
	 * @return int
	 */
	protected function get_current_directory_type_id(): int {
		$directory_type_id = absint( $this->get_listing_context()['directory_type_id'] ?? $this->get_loop_context()['active_directory'] ?? 0 );

		if ( $directory_type_id > 0 ) {
			return $directory_type_id;
		}

		$listing_id = $this->resolve_single_listing_fallback_id();

		if ( $listing_id <= 0 ) {
			$listing_id = $this->resolve_editor_loop_preview_listing_id();
		}

		if ( $listing_id <= 0 ) {
			return $this->resolve_editor_loop_preview_directory_type_id();
		}

		return DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );
	}

	/**
	 * Resolve directory type ID from the current Elementor document type.
	 *
	 * Directory-specific templates use document types like
	 * 'directorist-single-listing-directory-{term_id}'.
	 *
	 * @return int
	 */
	protected function resolve_document_directory_type_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_name' ) ) {
				$directory_type_id = $this->parse_directory_type_id_from_document_type( (string) $current_document->get_name() );
				if ( $directory_type_id > 0 ) {
					return $directory_type_id;
				}
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return 0;
		}

		return $this->parse_directory_type_id_from_document_type(
			(string) get_post_meta( $editor_post_id, '_elementor_template_type', true )
		);
	}

	/**
	 * Parse a Directorist directory id from an Elementor document type.
	 *
	 * @param string $document_type Elementor document type.
	 * @return int
	 */
	protected function parse_directory_type_id_from_document_type( string $document_type ): int {
		$prefix   = 'directorist-single-listing-directory-';

		if ( 0 !== strpos( $document_type, $prefix ) ) {
			return 0;
		}

		return absint( substr( $document_type, strlen( $prefix ) ) );
	}

	/**
	 * Check whether the edited document is a directory-specific single template.
	 *
	 * @return bool
	 */
	protected function is_single_listing_template_context(): bool {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();

			if ( $current_document && method_exists( $current_document, 'get_name' ) ) {
				return 0 === strpos( (string) $current_document->get_name(), 'directorist-single-listing-directory-' );
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return false;
		}

		$template_type = (string) get_post_meta( $editor_post_id, '_elementor_template_type', true );

		return 0 === strpos( $template_type, 'directorist-single-listing-directory-' );
	}

	/**
	 * Resolve the edited Elementor document post id from editor/AJAX requests.
	 *
	 * @return int
	 */
	protected function resolve_editor_document_post_id_from_request(): int {
		foreach ( [ 'editor_post_id', 'post_id', 'post' ] as $request_key ) {
			if ( empty( $_REQUEST[ $request_key ] ) ) {
				continue;
			}

			$post_id = absint( wp_unslash( $_REQUEST[ $request_key ] ) );
			if ( $post_id > 0 ) {
				return $post_id;
			}
		}

		return 0;
	}

	/**
	 * Check whether a listing belongs to the current directory-specific document.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	protected function is_listing_in_document_directory_type( int $listing_id ): bool {
		if ( $listing_id <= 0 ) {
			return false;
		}

		$directory_type_id = $this->resolve_document_directory_type_id();
		if ( $directory_type_id <= 0 ) {
			return true;
		}

		return DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id ) === $directory_type_id;
	}

	/**
	 * Resolve the current single-listing fallback id outside loop context.
	 *
	 * This lets listing field widgets work inside Theme Builder single listing
	 * documents, where there is no loop/listing stack but there is a queried or
	 * preview listing.
	 *
	 * @return int
	 */
	protected function resolve_single_listing_fallback_id(): int {
		$post_type = DirectoristBridge::get_instance()->get_listing_post_type();

		if ( is_singular( $post_type ) ) {
			return absint( get_queried_object_id() );
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return 0;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_settings' ) ) {
			return 0;
		}

		$preview_type = (string) $current_document->get_settings( 'preview_type' );

		if ( 'single/' . $post_type !== $preview_type ) {
			return 0;
		}

		$preview_id = absint( $current_document->get_settings( 'preview_id' ) );

		return $this->is_listing_in_document_directory_type( $preview_id ) ? $preview_id : 0;
	}

	/**
	 * Resolve a preview listing for isolated editor renders inside listing loops.
	 *
	 * Elementor can server-render nested card field widgets without the parent
	 * card/loop render stack. In that case use the nearest saved loop settings
	 * when available, then fall back to the current document directory or any
	 * recent listing. The canvas remains Elementor-owned; this only supplies data.
	 *
	 * @return int
	 */
	protected function resolve_editor_loop_preview_listing_id(): int {
		if ( ! $this->is_editor_context() ) {
			return 0;
		}

		$directory_type_id = 0;
		$loop_element      = $this->resolve_ancestor_loop_element();

		if ( is_array( $loop_element ) ) {
			$loop_settings = is_array( $loop_element['settings'] ?? null ) ? (array) $loop_element['settings'] : [];
			$instance_id   = InstanceState::get_instance()->normalize_instance_id(
				'direl-loop-' . sanitize_key( (string) ( $loop_element['id'] ?? 'preview' ) )
			);
			$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
				$loop_settings,
				$instance_id,
				true,
				1
			);
			$listing_ids        = array_values( array_map( 'absint', (array) ( $runtime_state['listing_ids'] ?? [] ) ) );
			$directory_type_id = absint( $runtime_state['active_directory'] ?? 0 );

			if ( ! empty( $listing_ids[0] ) ) {
				return absint( $listing_ids[0] );
			}
		}

		if ( $directory_type_id <= 0 ) {
			$directory_type_id = $this->resolve_document_directory_type_id();
		}

		return DirectoristBridge::get_instance()->get_default_preview_listing_id( $directory_type_id );
	}

	/**
	 * Resolve the active preview directory for isolated editor renders.
	 *
	 * @return int
	 */
	protected function resolve_editor_loop_preview_directory_type_id(): int {
		if ( ! $this->is_editor_context() ) {
			return 0;
		}

		$loop_element = $this->resolve_ancestor_loop_element();

		if ( is_array( $loop_element ) ) {
			$loop_settings = is_array( $loop_element['settings'] ?? null ) ? (array) $loop_element['settings'] : [];
			$instance_id   = InstanceState::get_instance()->normalize_instance_id(
				'direl-loop-' . sanitize_key( (string) ( $loop_element['id'] ?? 'preview' ) )
			);
			$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
				$loop_settings,
				$instance_id,
				true,
				1
			);
			$directory_type_id = absint( $runtime_state['active_directory'] ?? 0 );

			if ( $directory_type_id > 0 ) {
				return $directory_type_id;
			}
		}

		return $this->resolve_document_directory_type_id();
	}

	/**
	 * Execute a callback within the active loop context.
	 *
	 * In Elementor editor, child widgets can be server-rendered in isolation.
	 * When that happens, rebuild loop context from the current document tree so
	 * loop-aware widgets still render against their parent Listings Loop.
	 *
	 * @param callable $callback Render callback.
	 * @return bool
	 */
	protected function with_active_loop_context( callable $callback ): bool {
		if ( ! empty( $this->get_loop_context() ) ) {
			$callback();

			return true;
		}

		if ( ! $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}

		$loop_element = $this->resolve_ancestor_loop_element();

		if ( ! is_array( $loop_element ) ) {
			return false;
		}

		$loop_settings = is_array( $loop_element['settings'] ?? null ) ? (array) $loop_element['settings'] : [];
		if ( 'directorist_homepage_search_loop' === sanitize_key( (string) ( $loop_element['elType'] ?? '' ) ) ) {
			$loop_settings['query_mode'] = 'default';
			$loop_settings['query_type'] = 'regular';
			$loop_settings['directorist_elementor_source'] = 'homepage-search-loop';
			$loop_settings['editor_notice'] = __( 'Compose this result template with Directory Types, Homepage Search, header, filters, pagination, and card template.', 'directorist-elementor' );
		}
		$instance_id   = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_element['id'] ?? 'preview' ) )
		);
		$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
			$loop_settings,
			$instance_id,
			true,
			null
		);
		$runtime_state['utility_state'] = LoopRenderService::get_instance()->extract_utility_state_from_elements(
			is_array( $loop_element['elements'] ?? null ) ? (array) $loop_element['elements'] : []
		);

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			$callback();
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}

		return true;
	}

	/**
	 * Resolve the nearest ancestor Listings Loop element from the editor document.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function resolve_ancestor_loop_element(): ?array {
		if ( ! $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_elements_data' ) ) {
			return null;
		}

		return $this->find_ancestor_loop_element(
			(array) $current_document->get_elements_data(),
			(string) $this->get_id()
		);
	}

	/**
	 * Find the nearest ancestor Listings Loop element for a widget id.
	 *
	 * @param array<int,array<string,mixed>>   $elements Raw element tree.
	 * @param string                           $target_id Target widget id.
	 * @param array<string,mixed>|null         $current_loop Nearest loop ancestor.
	 * @return array<string,mixed>|null
	 */
	protected function find_ancestor_loop_element( array $elements, string $target_id, ?array $current_loop = null ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$next_loop = $current_loop;

			if ( in_array( sanitize_key( (string) ( $element['elType'] ?? '' ) ), [ 'directorist_listings_loop', 'directorist_homepage_search_loop' ], true ) ) {
				$next_loop = $element;
			}

			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $next_loop;
			}

			$children = (array) ( $element['elements'] ?? [] );

			if ( empty( $children ) ) {
				continue;
			}

			$found = $this->find_ancestor_loop_element( $children, $target_id, $next_loop );

			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}
}
