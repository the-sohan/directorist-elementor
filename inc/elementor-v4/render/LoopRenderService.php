<?php
/**
 * Shared loop runtime builder.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Query\ListingsQueryService;
use DirectoristElementor\Traits\Singleton;

class LoopRenderService {
	use Singleton;

	/**
	 * Build the normalized runtime state for a loop render.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param string              $instance_id Runtime instance id.
	 * @param bool                $is_editor Whether this render is for editor preview.
	 * @param int|null            $preview_limit Optional editor preview limit.
	 * @return array<string,mixed>
	 */
	public function build_runtime_state(
		array $settings,
		string $instance_id,
		bool $is_editor = false,
		?int $preview_limit = null
	): array {
		$editor_scope_tabs = $is_editor ? $this->resolve_editor_scope_tabs( $settings ) : [];
		$active_directory  = $is_editor
			? $this->resolve_editor_active_directory( $settings, $editor_scope_tabs )
			: 0;
		$active_view       = $is_editor
			? EditorContext::get_instance()->resolve_preview_view_type( $settings )
			: '';

		if ( $is_editor && null === $preview_limit ) {
			$preview_limit = $this->resolve_editor_preview_limit( $settings );
		}

		$query_settings = $is_editor
			? $this->build_editor_query_settings( $settings, $active_directory, $active_view )
			: $settings;

		$query_result      = ListingsQueryService::get_instance()->query_listings(
			$query_settings,
			$is_editor ? max( 1, (int) ( $preview_limit ?? 1 ) ) : $preview_limit,
			$instance_id
		);
		$query_args        = is_array( $query_result['query_args'] ?? null ) ? (array) $query_result['query_args'] : [];
		$data_atts         = is_array( $query_result['data_atts'] ?? null ) ? (array) $query_result['data_atts'] : [];
		$active_directory  = $is_editor
			? $active_directory
			: absint( $query_result['active_directory_type_id'] ?? $query_args['default_directory_type'] ?? 0 );
		$active_view       = $is_editor
			? $active_view
			: (string) ( $query_result['active_view'] ?? $query_args['view'] ?? 'grid' );
		$active_view       = in_array( $active_view, [ 'grid', 'list', 'map' ], true ) ? $active_view : 'grid';
		$display_mode      = $this->resolve_display_mode( $settings, $active_view );
		$listing_ids       = array_values( array_map( 'absint', (array) ( $query_result['ids'] ?? [] ) ) );
		$active_author     = absint( $query_args['author'] ?? 0 );
		$empty_message     = trim( (string) ( $settings['empty_state_message'] ?? '' ) );
		$empty_message     = '' !== $empty_message ? $empty_message : __( 'No listings matched the current settings.', 'directorist-elementor' );

		return [
			'instance_id'          => $instance_id,
			'settings'             => $settings,
			'query_result'         => $query_result,
			'query_args'           => $query_args,
			'data_atts'            => $data_atts,
			'editor_scope_tabs'    => $editor_scope_tabs,
			'active_directory'     => $active_directory,
			'active_author'        => $active_author,
			'active_view'          => $active_view,
			'display_mode'         => $display_mode,
			'slider_settings'      => $this->resolve_slider_settings( $settings ),
			'map_list_settings'    => $this->resolve_map_list_settings( $settings ),
			'gap_values'           => $this->resolve_loop_gap_values( $settings ),
			'listing_ids'          => $listing_ids,
			'total'                => (int) ( $query_result['total'] ?? 0 ),
			'max_pages'            => (int) ( $query_result['max_pages'] ?? 1 ),
			'current_page'         => (int) ( $query_result['current_page'] ?? 1 ),
			'empty_message'        => $empty_message,
			'editor_preview_mode'  => $is_editor ? $this->resolve_editor_preview_mode( $settings ) : '',
		];
	}

	/**
	 * Build the query settings used for editor preview renders.
	 *
	 * Editor should always query/render from the currently selected loop scope,
	 * not from the saved frontend default directory/view.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param int                 $active_directory Active editor directory.
	 * @param string              $active_view Active editor view.
	 * @return array<string,mixed>
	 */
	protected function build_editor_query_settings( array $settings, int $active_directory, string $active_view ): array {
		$query_settings = $settings;

		if ( $active_directory > 0 ) {
			$query_settings['default_directory_type_id'] = $active_directory;
		}

		if ( '' !== $active_view ) {
			$query_settings['view_type'] = $active_view;

			if (
				'map_list' === $this->normalize_display_mode( $query_settings['display_mode'] ?? 'default' ) &&
				in_array( $active_view, [ 'grid', 'list' ], true )
			) {
				$query_settings['map_list_view_type'] = $active_view;
			}
		}

		if (
			'default' === sanitize_key( (string) ( $query_settings['query_mode'] ?? 'default' ) ) &&
			empty( $query_settings['author_id'] ) &&
			$this->is_author_archive_editor_document()
		) {
			$preview_author_id = $this->resolve_author_archive_preview_author_id( $active_directory );

			if ( $preview_author_id > 0 ) {
				$query_settings['author_id'] = $preview_author_id;
			}
		}

		return $query_settings;
	}

	/**
	 * Check whether the current editor render belongs to an author archive document.
	 *
	 * @return bool
	 */
	protected function is_author_archive_editor_document(): bool {
		$document_type = '';

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_name' ) ) {
				$document_type = (string) $current_document->get_name();
			}
		}

		if ( 'directorist-listing-author-archive' === $document_type ) {
			return true;
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return false;
		}

		return 'directorist-listing-author-archive' === (string) get_post_meta( $editor_post_id, '_elementor_template_type', true );
	}

	/**
	 * Resolve the preview author for sibling loop widgets in author archive editor.
	 *
	 * @param int $active_directory Active editor directory.
	 * @return int
	 */
	protected function resolve_author_archive_preview_author_id( int $active_directory = 0 ): int {
		$elements = $this->get_current_document_elements();
		$author_id = $this->find_author_profile_preview_author_id( $elements );

		if ( $author_id > 0 ) {
			return $author_id;
		}

		return DirectoristBridge::get_instance()->get_default_preview_author_id( $active_directory );
	}

	/**
	 * Get the current Elementor document element payload.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_current_document_elements(): array {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_elements_data' ) ) {
				$elements = $current_document->get_elements_data();

				if ( is_array( $elements ) ) {
					return $elements;
				}
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return [];
		}

		$raw_data = get_post_meta( $editor_post_id, '_elementor_data', true );
		$decoded  = is_string( $raw_data ) ? json_decode( $raw_data, true ) : [];

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Find the preview author configured on the first author profile element.
	 *
	 * @param array<int,array<string,mixed>> $elements Elementor element tree.
	 * @return int
	 */
	protected function find_author_profile_preview_author_id( array $elements ): int {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$element_type = sanitize_key( (string) ( $element['elType'] ?? '' ) );
			$widget_type  = sanitize_key( (string) ( $element['widgetType'] ?? '' ) );

			if ( 'directorist_single_listing_author_profile' === $element_type || 'directorist_single_listing_author_profile' === $widget_type ) {
				$settings  = is_array( $element['settings'] ?? null ) ? (array) $element['settings'] : [];
				$author_id = absint( $settings['preview_author_id'] ?? 0 );

				if ( $author_id > 0 ) {
					return $author_id;
				}
			}

			$children = is_array( $element['elements'] ?? null ) ? (array) $element['elements'] : [];
			if ( empty( $children ) ) {
				continue;
			}

			$author_id = $this->find_author_profile_preview_author_id( $children );
			if ( $author_id > 0 ) {
				return $author_id;
			}
		}

		return 0;
	}

	/**
	 * Resolve editor document id from common Elementor request values.
	 *
	 * @return int
	 */
	protected function resolve_editor_document_post_id_from_request(): int {
		foreach ( [ 'editor_post_id', 'post_id', 'post', 'elementor-preview' ] as $request_key ) {
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
	 * Push the normalized runtime state onto the shared loop render context.
	 *
	 * @param array<string,mixed> $runtime_state Loop runtime state.
	 * @return array<string,mixed>
	 */
	public function push_loop_context( array $runtime_state ): array {
		$query_args = is_array( $runtime_state['query_args'] ?? null ) ? (array) $runtime_state['query_args'] : [];
		$display_mode = $this->normalize_display_mode( $runtime_state['display_mode'] ?? 'default' );
		$pagination_type = in_array( $display_mode, [ 'slider', 'map_list' ], true )
			? 'numbered'
			: (string) ( $query_args['pagination_type'] ?? 'numbered' );

		return RenderContext::get_instance()->push_loop_context(
			(string) ( $runtime_state['instance_id'] ?? '' ),
			$query_args,
			(string) ( $runtime_state['active_view'] ?? 'grid' ),
			(int) ( $runtime_state['active_directory'] ?? 0 ),
			[
				'controller'                 => $runtime_state['query_result']['controller'] ?? null,
				'data_atts'                  => is_array( $runtime_state['data_atts'] ?? null ) ? (array) $runtime_state['data_atts'] : [],
				'query_state'                => is_array( $runtime_state['query_result']['query_state'] ?? null ) ? (array) $runtime_state['query_result']['query_state'] : [],
				'listing_ids'                => array_values( array_map( 'absint', (array) ( $runtime_state['listing_ids'] ?? [] ) ) ),
				'total'                      => (int) ( $runtime_state['total'] ?? 0 ),
				'max_pages'                  => (int) ( $runtime_state['max_pages'] ?? 1 ),
				'current_page'               => (int) ( $runtime_state['current_page'] ?? 1 ),
				'empty_message'              => (string) ( $runtime_state['empty_message'] ?? '' ),
				'display_mode'               => $display_mode,
				'slider_settings'            => is_array( $runtime_state['slider_settings'] ?? null ) ? (array) $runtime_state['slider_settings'] : $this->resolve_slider_settings( [] ),
				'map_list_settings'          => is_array( $runtime_state['map_list_settings'] ?? null ) ? (array) $runtime_state['map_list_settings'] : $this->resolve_map_list_settings( [] ),
				'gap_values'                 => is_array( $runtime_state['gap_values'] ?? null ) ? (array) $runtime_state['gap_values'] : $this->resolve_loop_gap_values( [] ),
				'pagination_type'            => $pagination_type,
				'listings_columns'           => (int) ( $query_args['columns'] ?? 3 ),
				'directory_type_ids'         => array_values( array_map( 'absint', (array) ( $query_args['directory_type_ids'] ?? [] ) ) ),
				'default_directory_type_id'  => (int) ( $runtime_state['active_directory'] ?? 0 ),
				'author_id'                  => (int) ( $runtime_state['active_author'] ?? 0 ),
				'editor_preview_mode'        => (string) ( $runtime_state['editor_preview_mode'] ?? '' ),
				'utility_state'              => is_array( $runtime_state['utility_state'] ?? null ) ? (array) $runtime_state['utility_state'] : [],
			]
		);
	}

	/**
	 * Resolve the active loop display mode.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param string              $active_view Active view.
	 * @return string
	 */
	public function resolve_display_mode( array $settings, string $active_view = 'grid' ): string {
		$display_mode = $this->normalize_display_mode( $settings['display_mode'] ?? 'default' );

		if ( 'map' === sanitize_key( $active_view ) && 'map_list' !== $display_mode ) {
			return 'default';
		}

		return $display_mode;
	}

	/**
	 * Normalize a display mode value.
	 *
	 * @param mixed $value Raw display mode.
	 * @return string
	 */
	public function normalize_display_mode( $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, [ 'slider', 'map_list' ], true ) ? $value : 'default';
	}

	/**
	 * Resolve listings-with-map settings used by render and frontend runtime.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @return array<string,mixed>
	 */
	public function resolve_map_list_settings( array $settings ): array {
		$position = sanitize_key( (string) ( $settings['map_list_map_position'] ?? 'right' ) );
		$position = in_array( $position, [ 'right', 'left', 'top', 'bottom' ], true ) ? $position : 'right';

		return [
			'map_position'  => $position,
			'map_height'    => max( 220, absint( $settings['map_list_map_height'] ?? 520 ) ),
			'map_width'     => max( 25, min( 70, absint( $settings['map_list_map_width'] ?? 42 ) ) ),
			'sticky_map'    => $this->normalize_switcher_setting( $settings, 'map_list_sticky_map', false ),
			'fit_on_load'   => $this->normalize_switcher_setting( $settings, 'map_list_fit_on_load', true ),
			'hover_focus'   => $this->normalize_switcher_setting( $settings, 'map_list_hover_focus', true ),
			'hover_zoom'    => max( 1, min( 22, absint( $settings['map_list_hover_zoom'] ?? 14 ) ) ),
			'show_map_card' => $this->normalize_switcher_setting( $settings, 'map_list_show_map_card', true ),
		];
	}

	/**
	 * Normalize Elementor switcher settings while preserving an explicit off state.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $key Setting key.
	 * @param bool                $default Default when the setting has never been saved.
	 * @return bool
	 */
	protected function normalize_switcher_setting( array $settings, string $key, bool $default ): bool {
		if ( ! array_key_exists( $key, $settings ) ) {
			return $default;
		}

		$value = $settings[ $key ];

		if ( '' === $value || null === $value || false === $value ) {
			return false;
		}

		return $this->normalize_slider_bool( $value, $default );
	}

	/**
	 * Resolve slider settings used by card-template rendering and frontend JS.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @return array<string,mixed>
	 */
	public function resolve_slider_settings( array $settings ): array {
		$desktop = $this->normalize_slider_count( $settings['slides_per_view'] ?? 3 );
		$tablet  = $this->normalize_slider_count( $settings['slides_per_view_tablet'] ?? $desktop );
		$mobile  = $this->normalize_slider_count( $settings['slides_per_view_mobile'] ?? $tablet );
		$effect  = sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) );

		if ( ! in_array( $effect, [ 'slide', 'fade', 'grid', 'thumb', 'creative', 'cards', 'cube', 'flip', 'coverflow' ], true ) ) {
			$effect = 'slide';
		}

		return [
			'slides_per_view'        => $desktop,
			'slides_per_view_tablet' => $tablet,
			'slides_per_view_mobile' => $mobile,
			'show_arrow_navigation'  => $this->normalize_slider_bool( $settings['show_arrow_navigation'] ?? 'yes', true ),
			'show_dot_navigation'    => $this->normalize_slider_bool( $settings['show_dot_navigation'] ?? 'yes', true ),
			'autoplay'               => $this->normalize_slider_bool( $settings['slider_autoplay'] ?? 'yes', true ),
			'pause_on_hover'         => $this->normalize_slider_bool( $settings['pause_on_hover'] ?? 'yes', true ),
			'effect'                 => $effect,
			'grid_rows'              => max( 1, min( 6, absint( $settings['slider_grid_rows'] ?? 2 ) ) ),
			'autoplay_delay'         => max( 100, absint( $settings['autoplay_delay'] ?? 3000 ) ),
			'transition_speed'       => max( 100, absint( $settings['transition_speed'] ?? 500 ) ),
		];
	}

	/**
	 * Normalize a slider count control value.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected function normalize_slider_count( $value ): int {
		return max( 1, min( 6, absint( $value ) ) );
	}

	/**
	 * Normalize a slider bool-like control value.
	 *
	 * @param mixed $value Raw value.
	 * @param bool  $default Default value.
	 * @return bool
	 */
	protected function normalize_slider_bool( $value, bool $default ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_numeric( $value ) ) {
			return (int) $value > 0;
		}

		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			if ( in_array( $value, [ '1', 'true', 'yes', 'on' ], true ) ) {
				return true;
			}

			if ( in_array( $value, [ '0', 'false', 'no', 'off' ], true ) ) {
				return false;
			}
		}

		return $default;
	}

	/**
	 * Extract loop utility state from a raw element tree.
	 *
	 * @param array<int,array<string,mixed>> $elements Raw element tree.
	 * @return array<string,bool>
	 */
	public function extract_utility_state_from_elements( array $elements ): array {
		$state = [
			'has_filters_widget'      => false,
			'filters_mobile_floating' => false,
			'has_pagination_widget'   => false,
		];

		$walk = static function( array $branch ) use ( &$walk, &$state ): void {
			foreach ( $branch as $element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}

				$widget_type = sanitize_key( (string) ( $element['widgetType'] ?? '' ) );

				if ( 'directorist_listings_pagination' === $widget_type ) {
					$state['has_pagination_widget'] = true;
				}

				if ( 'directorist_listings_filters' === $widget_type ) {
					$state['has_filters_widget'] = true;
					$settings = is_array( $element['settings'] ?? null ) ? (array) $element['settings'] : [];

					if ( 'yes' === (string) ( $settings['mobile_floating'] ?? '' ) ) {
						$state['filters_mobile_floating'] = true;
					}
				}

				if ( ! empty( $state['has_filters_widget'] ) && ! empty( $state['has_pagination_widget'] ) ) {
					return;
				}

				$children = is_array( $element['elements'] ?? null ) ? (array) $element['elements'] : [];

				if ( ! empty( $children ) ) {
					$walk( $children );
				}

				if ( ! empty( $state['has_filters_widget'] ) && ! empty( $state['has_pagination_widget'] ) ) {
					return;
				}
			}
		};

		$walk( $elements );

		return $state;
	}

	/**
	 * Resolve the active editor preview mode.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @return string
	 */
	public function resolve_editor_preview_mode( array $settings ): string {
		$preview_mode = sanitize_key( (string) ( $settings['editor_preview_mode'] ?? 'results' ) );

		return in_array( $preview_mode, [ 'structure', 'results' ], true ) ? $preview_mode : 'results';
	}

	/**
	 * Resolve how many listings the editor preview should load.
	 *
	 * Grid and list follow the loop pagination size. Map uses a single card preview.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @return int
	 */
	public function resolve_editor_preview_limit( array $settings ): int {
		$active_view = EditorContext::get_instance()->resolve_preview_view_type( $settings );
		$display_mode = $this->normalize_display_mode( $settings['display_mode'] ?? 'default' );

		if ( 'map_list' === $display_mode ) {
			return max( 1, absint( $settings['listings_per_page'] ?? 6 ) );
		}

		if ( 'map' === $active_view ) {
			return 1;
		}

		return max( 1, absint( $settings['listings_per_page'] ?? 6 ) );
	}

	/**
	 * Resolve the visible editor scope tabs.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param int                 $active_directory Optional active directory.
	 * @return array<int,array<string,mixed>>
	 */
	public function resolve_editor_scope_tabs( array $settings, int $active_directory = 0 ): array {
		$directory_options = DirectoristBridge::get_instance()->get_directory_options();
		$selected_ids      = array_values(
			array_filter(
				array_unique( array_map( 'absint', (array) ( $settings['directory_type_ids'] ?? [] ) ) )
			)
		);
		$tab_ids           = ! empty( $selected_ids )
			? $selected_ids
			: array_map( 'absint', array_keys( $directory_options ) );

		$tabs = [];

		foreach ( array_values( array_unique( $tab_ids ) ) as $directory_id ) {
			if ( $directory_id <= 0 || ! isset( $directory_options[ $directory_id ] ) ) {
				continue;
			}

			$tabs[] = [
				'id'    => (int) $directory_id,
				'label' => (string) $directory_options[ $directory_id ],
			];
		}

		return $tabs;
	}

	/**
	 * Resolve the active editor directory to a visible tab.
	 *
	 * @param array<string,mixed>            $settings Loop settings.
	 * @param array<int,array<string,mixed>> $directory_tabs Tabs.
	 * @return int
	 */
	public function resolve_editor_active_directory( array $settings, array $directory_tabs = [] ): int {
		$directory_tabs = ! empty( $directory_tabs ) ? $directory_tabs : $this->resolve_editor_scope_tabs( $settings );
		$tab_ids        = array_values(
			array_filter(
				array_map(
					static fn( array $tab ): int => absint( $tab['id'] ?? 0 ),
					$directory_tabs
				)
			)
		);

		$active_directory_id = absint( $settings['active_directory_type_id'] ?? 0 );

		if ( $active_directory_id > 0 && in_array( $active_directory_id, $tab_ids, true ) ) {
			return $active_directory_id;
		}

		return ! empty( $tab_ids ) ? (int) $tab_ids[0] : 0;
	}

	/**
	 * Render the editor loop state chrome.
	 *
	 * @param array<string,mixed> $runtime_state Loop runtime state.
	 * @return string
	 */
	public function render_editor_state_markup( array $runtime_state ): string {
		$directory_tabs   = is_array( $runtime_state['editor_scope_tabs'] ?? null ) ? (array) $runtime_state['editor_scope_tabs'] : [];
		$active_directory = absint( $runtime_state['active_directory'] ?? 0 );
		$active_view      = (string) ( $runtime_state['active_view'] ?? 'grid' );
		$total            = (int) ( $runtime_state['total'] ?? 0 );
		$settings         = is_array( $runtime_state['settings'] ?? null ) ? (array) $runtime_state['settings'] : [];
		$notice           = trim( (string) ( $settings['editor_notice'] ?? '' ) );

		if ( '' === $notice ) {
			$notice = __( 'Compose your loop with child widgets such as search, header, filters, pagination, and card template.', 'directorist-elementor' );
		}

		ob_start();
		?>
		<div class="directorist-elementor-loop__state">
			<p class="directorist-elementor-loop__notice"><?php echo esc_html( $notice ); ?></p>
			<?php if ( $total > 0 ) : ?>
				<p class="directorist-elementor-loop__results-meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: %d: listing count. */
							_n( '%d listing matches the current loop scope.', '%d listings match the current loop scope.', $total, 'directorist-elementor' ),
							$total
						)
					);
					?>
				</p>
			<?php endif; ?>
			<?php echo $this->render_editor_scope_switcher( $directory_tabs, $active_directory, $active_view ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render the inline editor scope switcher.
	 *
	 * @param array<int,array<string,mixed>> $directory_tabs Directory tabs.
	 * @param int                            $active_directory Active directory.
	 * @param string                         $active_view Active view.
	 * @return string
	 */
	public function render_editor_scope_switcher( array $directory_tabs, int $active_directory, string $active_view ): string {
		$view_labels = [
			'grid' => __( 'Grid', 'directorist-elementor' ),
			'list' => __( 'List', 'directorist-elementor' ),
			'map'  => __( 'Map', 'directorist-elementor' ),
		];
		$view_icons  = [
			'grid' => 'eicon-gallery-grid',
			'list' => 'eicon-post-list',
			'map'  => 'eicon-google-maps',
		];

		ob_start();
		?>
		<div class="directorist-elementor-loop__scope" role="group" aria-label="<?php echo esc_attr__( 'Listing branch scope', 'directorist-elementor' ); ?>">
			<?php if ( ! empty( $directory_tabs ) ) : ?>
				<div class="directorist-elementor-loop__directory-tabs">
					<?php foreach ( $directory_tabs as $tab ) : ?>
						<button
							type="button"
							class="directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--directory <?php echo (int) $tab['id'] === $active_directory ? 'is-active' : ''; ?>"
							data-direl-loop-directory="<?php echo esc_attr( (string) $tab['id'] ); ?>"
						><?php echo esc_html( (string) $tab['label'] ); ?></button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="directorist-elementor-loop__view-tabs">
				<?php foreach ( $view_labels as $view_key => $view_label ) : ?>
					<button
						type="button"
						class="directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--view <?php echo $view_key === $active_view ? 'is-active' : ''; ?>"
						data-direl-loop-view="<?php echo esc_attr( $view_key ); ?>"
						aria-label="<?php echo esc_attr( $view_label ); ?>"
						title="<?php echo esc_attr( $view_label ); ?>"
					><i class="<?php echo esc_attr( $view_icons[ $view_key ] ?? '' ); ?>" aria-hidden="true"></i></button>
				<?php endforeach; ?>
			</div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build loop CSS variables.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param string              $active_view Active view.
	 * @param int                 $columns_override Optional resolved columns count.
	 * @return string
	 */
	public function build_loop_style( array $settings, string $active_view, int $columns_override = 0 ): string {
		$columns_desktop = $columns_override > 0
			? $this->normalize_loop_columns( $columns_override )
			: $this->normalize_loop_columns( $settings['columns'] ?? 3 );
		$columns_tablet  = $this->resolve_responsive_loop_columns( $settings, 'columns_tablet', 2 );
		$columns_mobile  = $this->resolve_responsive_loop_columns( $settings, 'columns_mobile', 1 );

		if ( 'grid' !== $active_view ) {
			$columns_desktop = 1;
			$columns_tablet  = 1;
			$columns_mobile  = 1;
		}

		$display_mode = $this->normalize_display_mode( $settings['display_mode'] ?? 'default' );
		$gaps         = $this->resolve_loop_gap_values( $settings );
		$active_gap   = $this->resolve_active_loop_gap( $gaps, $active_view, $display_mode );

		$style = sprintf(
			'--direl-loop-columns:%1$d;--direl-loop-columns-tablet:%2$d;--direl-loop-columns-mobile:%3$d;--direl-loop-gap:%4$s;',
			$columns_desktop,
			$columns_tablet,
			$columns_mobile,
			$active_gap['desktop']
		);

		$style .= $this->build_loop_gap_style_variables( $gaps );

		return $style;
	}

	/**
	 * Resolve all loop gap values with old card_gap fallback support.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @return array<string,array{desktop:string,tablet:string,mobile:string}>
	 */
	public function resolve_loop_gap_values( array $settings ): array {
		$legacy_card_gap = $this->normalize_loop_gap_css_value( $settings['card_gap'] ?? null, '20px' );

		return [
			'grid_card_gap'     => $this->resolve_responsive_loop_gap( $settings, 'grid_card_gap', $legacy_card_gap ),
			'list_card_gap'     => $this->resolve_responsive_loop_gap( $settings, 'list_card_gap', $legacy_card_gap ),
			'slider_card_gap'   => $this->resolve_responsive_loop_gap( $settings, 'slider_card_gap', '30px', '20px', '10px' ),
			'map_list_card_gap' => $this->resolve_responsive_loop_gap( $settings, 'map_list_card_gap', $legacy_card_gap ),
			'map_list_pane_gap' => $this->resolve_responsive_loop_gap( $settings, 'map_list_pane_gap', '24px' ),
		];
	}

	/**
	 * Build CSS variables for all loop gap controls.
	 *
	 * @param array<string,array{desktop:string,tablet:string,mobile:string}> $gaps Gap values.
	 * @return string
	 */
	public function build_loop_gap_style_variables( array $gaps ): string {
		$map = [
			'grid_card_gap'     => '--direl-loop-grid-card-gap',
			'list_card_gap'     => '--direl-loop-list-card-gap',
			'slider_card_gap'   => '--direl-loop-slider-card-gap',
			'map_list_card_gap' => '--direl-map-list-card-gap',
			'map_list_pane_gap' => '--direl-map-list-pane-gap',
		];
		$style = '';

		foreach ( $map as $key => $css_variable ) {
			$values = is_array( $gaps[ $key ] ?? null ) ? $gaps[ $key ] : [];

			foreach ( [ 'desktop' => '', 'tablet' => '-tablet', 'mobile' => '-mobile' ] as $device => $suffix ) {
				$value = (string) ( $values[ $device ] ?? '' );

				if ( '' === $value ) {
					continue;
				}

				$style .= $css_variable . $suffix . ':' . esc_attr( $value ) . ';';
			}
		}

		return $style;
	}

	/**
	 * Resolve the active display gap used by legacy --direl-loop-gap consumers.
	 *
	 * @param array<string,array{desktop:string,tablet:string,mobile:string}> $gaps Gap values.
	 * @param string                                                          $active_view Active view.
	 * @param string                                                          $display_mode Display mode.
	 * @return array{desktop:string,tablet:string,mobile:string}
	 */
	protected function resolve_active_loop_gap( array $gaps, string $active_view, string $display_mode ): array {
		if ( 'slider' === $display_mode ) {
			return $gaps['slider_card_gap'] ?? [ 'desktop' => '30px', 'tablet' => '20px', 'mobile' => '10px' ];
		}

		if ( 'map_list' === $display_mode ) {
			return $gaps['map_list_card_gap'] ?? [ 'desktop' => '20px', 'tablet' => '20px', 'mobile' => '20px' ];
		}

		if ( 'list' === sanitize_key( $active_view ) || 'map' === sanitize_key( $active_view ) ) {
			return $gaps['list_card_gap'] ?? [ 'desktop' => '20px', 'tablet' => '20px', 'mobile' => '20px' ];
		}

		return $gaps['grid_card_gap'] ?? [ 'desktop' => '20px', 'tablet' => '20px', 'mobile' => '20px' ];
	}

	/**
	 * Resolve responsive gap values for one control.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param string              $key Setting key.
	 * @param string              $fallback_desktop Desktop fallback.
	 * @param string|null         $fallback_tablet Tablet fallback.
	 * @param string|null         $fallback_mobile Mobile fallback.
	 * @return array{desktop:string,tablet:string,mobile:string}
	 */
	protected function resolve_responsive_loop_gap(
		array $settings,
		string $key,
		string $fallback_desktop,
		?string $fallback_tablet = null,
		?string $fallback_mobile = null
	): array {
		$desktop = $this->normalize_loop_gap_css_value( $settings[ $key ] ?? null, $fallback_desktop );
		$tablet  = $this->normalize_loop_gap_css_value( $settings[ $key . '_tablet' ] ?? null, $fallback_tablet ?? $desktop );
		$mobile  = $this->normalize_loop_gap_css_value( $settings[ $key . '_mobile' ] ?? null, $fallback_mobile ?? $tablet );

		return [
			'desktop' => $desktop,
			'tablet'  => $tablet,
			'mobile'  => $mobile,
		];
	}

	/**
	 * Normalize an Elementor slider value to a CSS length.
	 *
	 * @param mixed  $value Raw value.
	 * @param string $fallback Fallback length.
	 * @return string
	 */
	public function normalize_loop_gap_css_value( $value, string $fallback = '20px' ): string {
		if ( is_array( $value ) ) {
			$size = $value['size'] ?? null;
			$unit = sanitize_key( (string) ( $value['unit'] ?? 'px' ) );

			if ( is_numeric( $size ) && (float) $size >= 0 ) {
				$unit = in_array( $unit, [ 'px', 'em', 'rem', '%' ], true ) ? $unit : 'px';

				return $this->format_loop_gap_number( (float) $size ) . $unit;
			}
		}

		if ( is_numeric( $value ) && (float) $value >= 0 ) {
			return $this->format_loop_gap_number( (float) $value ) . 'px';
		}

		if ( is_string( $value ) ) {
			$value = trim( $value );

			if ( preg_match( '/^\d+(?:\.\d+)?(?:px|em|rem|%)$/', $value ) ) {
				return $value;
			}
		}

		return preg_match( '/^\d+(?:\.\d+)?(?:px|em|rem|%)$/', $fallback ) ? $fallback : '20px';
	}

	/**
	 * Format numeric gap values without stripping significant integer zeros.
	 *
	 * @param float $value Numeric value.
	 * @return string
	 */
	protected function format_loop_gap_number( float $value ): string {
		if ( 0.0 === $value ) {
			return '0';
		}

		return rtrim( rtrim( number_format( $value, 4, '.', '' ), '0' ), '.' );
	}

	/**
	 * Convert a CSS length into a pixel number for Swiper's spaceBetween API.
	 *
	 * @param string $value CSS length.
	 * @param int    $fallback Fallback pixels.
	 * @return int
	 */
	public function loop_gap_css_value_to_swiper_px( string $value, int $fallback = 20 ): int {
		if ( preg_match( '/^(\d+(?:\.\d+)?)(px)?$/', trim( $value ), $matches ) ) {
			return max( 0, (int) round( (float) $matches[1] ) );
		}

		return max( 0, $fallback );
	}

	/**
	 * Normalize requested loop columns to a safe range.
	 *
	 * @param mixed $value Raw columns value.
	 * @return int
	 */
	protected function normalize_loop_columns( $value ): int {
		return max( 1, min( 6, absint( $value ) ) );
	}

	/**
	 * Resolve a responsive loop columns value with its device default.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param string              $setting_key Responsive setting key.
	 * @param int                 $fallback Fallback column count.
	 * @return int
	 */
	protected function resolve_responsive_loop_columns( array $settings, string $setting_key, int $fallback ): int {
		if ( ! array_key_exists( $setting_key, $settings ) ) {
			return $fallback;
		}

		$value = $settings[ $setting_key ];

		if ( is_string( $value ) && '' === trim( $value ) ) {
			return $fallback;
		}

		$normalized = $this->normalize_loop_columns( $value );

		return $normalized > 0 ? $normalized : $fallback;
	}
}
