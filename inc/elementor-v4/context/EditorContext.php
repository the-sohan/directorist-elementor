<?php
/**
 * Elementor editor context helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Context;

use DirectoristElementor\Traits\Singleton;

class EditorContext {
	use Singleton;

	/**
	 * Forced editor-request override used by authenticated preview AJAX.
	 *
	 * @var bool
	 */
	protected bool $forced_editor_request = false;

	/**
	 * Check whether the current request is an Elementor editor request.
	 *
	 * @return bool
	 */
	public function is_editor_request(): bool {
		if ( $this->forced_editor_request ) {
			return true;
		}

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$plugin = \Elementor\Plugin::$instance;

			if ( isset( $plugin->editor ) && method_exists( $plugin->editor, 'is_edit_mode' ) && $plugin->editor->is_edit_mode() ) {
				return true;
			}

			if ( isset( $plugin->preview ) && method_exists( $plugin->preview, 'is_preview_mode' ) && $plugin->preview->is_preview_mode() ) {
				return true;
			}
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST && isset( $_REQUEST['context'] ) ) {
			return 'edit' === sanitize_text_field( wp_unslash( $_REQUEST['context'] ) );
		}

		return false;
	}

	/**
	 * Force the current request to behave like an Elementor editor render.
	 *
	 * @param bool $is_editor_request Forced editor state.
	 * @return void
	 */
	public function set_forced_editor_request( bool $is_editor_request ): void {
		$this->forced_editor_request = $is_editor_request;
	}

	/**
	 * Resolve the preview directory type id.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return int
	 */
	public function resolve_preview_directory_type_id( array $settings ): int {
		return absint( $settings['active_directory_type_id'] ?? 0 );
	}

	/**
	 * Resolve preview view.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	public function resolve_preview_view_type( array $settings ): string {
		$active_view_type = sanitize_key( (string) ( $settings['active_view_type'] ?? '' ) );
		$display_mode     = sanitize_key( (string) ( $settings['display_mode'] ?? 'default' ) );
		$default_view_type = 'map_list' === $display_mode
			? sanitize_key( (string) ( $settings['map_list_view_type'] ?? $settings['view_type'] ?? 'grid' ) )
			: sanitize_key( (string) ( $settings['view_type'] ?? 'grid' ) );
		$view_type        = in_array( $active_view_type, [ 'grid', 'list', 'map' ], true )
			? $active_view_type
			: $default_view_type;

		return in_array( $view_type, [ 'grid', 'list', 'map' ], true ) ? $view_type : 'grid';
	}
}
