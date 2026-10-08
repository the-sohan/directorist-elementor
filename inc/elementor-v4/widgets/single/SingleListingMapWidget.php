<?php
/**
 * Single listing map widget with popup-card composition support.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractDirectoristNestedWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class SingleListingMapWidget extends AbstractDirectoristNestedWidget {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	public function get_name(): string {
		return 'directorist_single_listing_map';
	}

	public function get_title(): string {
		return __( 'Map Card Template', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-google-maps';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'map', 'location', 'popup' ] );
	}

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	protected function get_default_children_elements() {
		return [
			[
				'elType'   => 'container',
				'isInner'  => true,
				'settings' => [
					'_title' => __( 'Map Card Template', 'directorist-elementor' ),
				],
				'elements' => [],
			],
		];
	}

	protected function get_default_repeater_title_setting_key() {
		return '_title';
	}

	protected function get_default_children_title() {
		return __( 'Map Card Template', 'directorist-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-single-map__template-storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-single-map__template-canvas';
	}

	protected function get_initial_config(): array {
		return array_merge(
			parent::get_initial_config(),
			[
				'target_container' => [ '.directorist-elementor-single-map__template-storage' ],
				'node'             => 'div',
				'is_interlaced'    => true,
			]
		);
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_single_map_content',
			[
				'label' => __( 'Map Card Template', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'map_popup_status',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Compose one map card template here. The single listing marker reuses this Elementor structure for its active map card, and falls back to Directorist core markup when the composition is empty.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_single_map_style_map',
			[
				'label' => __( 'Map', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'map_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 240,
						'max' => 960,
					],
					'vh' => [
						'min' => 20,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-map__canvas' => 'height: {{SIZE}}{{UNIT}}; min-height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'map_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-map__canvas',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'map_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-map__canvas',
			]
		);

		$this->add_responsive_control(
			'map_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-map__canvas' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_single_map_style_card',
			[
				'label' => __( 'Map Card', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'map_card_width',
			[
				'label'      => __( 'Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range'      => [
					'px' => [
						'min' => 180,
						'max' => 420,
					],
					'%' => [
						'min' => 40,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-map__popup-card' => 'width: min(100%, {{SIZE}}{{UNIT}});',
					'{{WRAPPER}} .directorist-elementor-single-map .leaflet-popup-content' => 'width: min(100%, {{SIZE}}{{UNIT}});',
					'{{WRAPPER}} .directorist-elementor-single-map .gm-style .gm-style-iw-d' => 'width: min(100%, {{SIZE}}{{UNIT}}); max-width: none;',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'map_card_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-map__popup-card',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'map_card_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-map__popup-card',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'map_card_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-map__popup-card',
			]
		);

		$this->add_responsive_control(
			'map_card_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-map__popup-card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'map_card_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-map__popup-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-single-map .leaflet-popup-content-wrapper, {{WRAPPER}} .directorist-elementor-single-map .gm-style .gm-style-iw-c' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Map Card Template', 'directorist-elementor' ),
					__( 'Place this widget inside a Directorist single listing template to render the listing map and active map card.', 'directorist-elementor' )
				)
			);
			return;
		}

		$manual_lat = trim( (string) get_post_meta( $listing_id, '_manual_lat', true ) );
		$manual_lng = trim( (string) get_post_meta( $listing_id, '_manual_lng', true ) );
		$hide_map   = ! empty( get_post_meta( $listing_id, '_hide_map', true ) );

		if ( '' === $manual_lat || '' === $manual_lng || $hide_map ) {
			if ( ! $this->is_editor_context() ) {
				return;
			}

			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Map Card Template', 'directorist-elementor' ),
					__( 'The current preview listing does not have map coordinates or the map is hidden.', 'directorist-elementor' )
				)
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/fields/map' );

		$map_payload = $this->build_map_payload( $listing_id );

		if ( empty( $map_payload ) ) {
			if ( ! $this->is_editor_context() ) {
				return;
			}

			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Map Card Template', 'directorist-elementor' ),
					__( 'The current listing map could not be prepared for preview.', 'directorist-elementor' )
				)
			);
			return;
		}

		$children              = array_values( $this->get_children() );
		$composed_popup_markup = $this->render_map_popup_markup( $children, $listing_id );

		if ( '' !== $composed_popup_markup ) {
			$map_payload['info_content'] = $composed_popup_markup;
		}

		$map_data = wp_json_encode(
			$map_payload,
			JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_AMP
		);

		if ( ! is_string( $map_data ) || '' === $map_data ) {
			if ( ! $this->is_editor_context() ) {
				return;
			}

			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Map Card Template', 'directorist-elementor' ),
					__( 'The current listing map payload could not be encoded for preview.', 'directorist-elementor' )
				)
			);
			return;
		}

		$root_classes = [
			'directorist-elementor-single-map',
			$this->is_editor_context() ? 'directorist-elementor-single-map--editor-preview' : 'directorist-elementor-single-map--frontend',
		];

		echo '<section class="' . esc_attr( implode( ' ', $root_classes ) ) . '">';

		if ( $this->is_editor_context() ) {
			printf(
				'<div class="directorist-elementor-single-map__state"><p class="directorist-elementor-card-template__notice">%s</p></div>',
				esc_html__( 'Compose one map card template. The marker reuses this Elementor structure for the current listing map card.', 'directorist-elementor' )
			);
		}

		$this->render_map_markup(
			$listing_id,
			$map_data
		);

		echo '</section>';
	}

	/**
	 * Render the single listing map markup.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $map_data Encoded map payload.
	 * @return void
	 */
	protected function render_map_markup( int $listing_id, string $map_data ): void {
		$display_address_map   = (bool) get_directorist_option( 'display_address_map', 1 );
		$display_direction_map = (bool) get_directorist_option( 'display_direction_map', 1 );
		$address               = trim( (string) get_post_meta( $listing_id, '_address', true ) );
		$manual_lat            = trim( (string) get_post_meta( $listing_id, '_manual_lat', true ) );
		$manual_lng            = trim( (string) get_post_meta( $listing_id, '_manual_lng', true ) );

		$surface_classes = [
			'directorist-elementor-single-map__surface',
		];

		if ( $this->is_editor_context() ) {
			$surface_classes[] = 'directorist-elementor-single-map__surface--editor';
		}

		echo '<div class="' . esc_attr( implode( ' ', $surface_classes ) ) . '">';
		$canvas_attributes = [
			'class'    => 'directorist-single-map directorist-elementor-single-map__canvas',
			'data-map' => $map_data,
		];

		if ( $this->is_editor_context() ) {
			$canvas_attributes['data-direl-editor-open-popup'] = 'yes';
		}

		echo '<div ' . $this->format_html_attributes( $canvas_attributes ) . '></div>';

		if ( ( $display_address_map && '' !== $address ) || ( $display_direction_map && '' !== $manual_lat && '' !== $manual_lng ) ) {
			echo '<div class="directorist-single-map__location">';

			if ( $display_address_map && '' !== $address ) {
				echo '<div class="directorist-single-map__address">';
				echo wp_kses_post( (string) directorist_icon( 'fas fa-map-marker-alt', false ) );
				echo ' ' . esc_html( $address );
				echo '</div>';
			}

			if ( $display_direction_map && '' !== $manual_lat && '' !== $manual_lng ) {
				printf(
					'<div class="directorist-single-map__direction"><a href="%1$s" target="_blank">%2$s %3$s</a></div>',
					esc_url( 'http://www.google.com/maps?daddr=' . rawurlencode( $manual_lat ) . ',' . rawurlencode( $manual_lng ) ),
					wp_kses_post( (string) directorist_icon( 'fas fa-paper-plane', false ) ),
					esc_html__( 'Get Directions', 'directorist' )
				);
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/**
	 * Build the core Directorist single map payload for a listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<string,mixed>
	 */
	protected function build_map_payload( int $listing_id ): array {
		$bridge = DirectoristBridge::get_instance();

		$payload_json = (string) $bridge->with_listing_post_context(
			$listing_id,
			static function () use ( $bridge, $listing_id ): string {
				$listing = $bridge->get_single_listing_instance( $listing_id );

				if ( ! $listing || ! method_exists( $listing, 'map_data' ) ) {
					return '';
				}

				return (string) $listing->map_data();
			}
		);

		$payload = json_decode( $payload_json, true );

		return is_array( $payload ) ? $payload : [];
	}

	/**
	 * Render popup card markup for the active listing context.
	 *
	 * @param array<int,mixed> $children Child elements.
	 * @param int              $listing_id Listing id.
	 * @return string
	 */
	protected function render_map_popup_markup( array $children, int $listing_id ): string {
		$bridge            = DirectoristBridge::get_instance();
		$directory_type_id = $bridge->get_listing_directory_type_id( $listing_id );

		return (string) $bridge->with_listing_post_context(
			$listing_id,
			function () use ( $children, $listing_id, $directory_type_id ): string {
				RenderContext::get_instance()->push_listing_context( $listing_id, 0, $directory_type_id );

				ob_start();

				try {
					$template_raw = $this->get_template_raw_data();

					if ( is_array( $template_raw ) && $this->has_raw_composition_content( $template_raw ) ) {
						$this->render_template_raw_data( $template_raw );
					} else {
						$template_child = $children[0] ?? null;

						if ( is_object( $template_child ) && $this->has_composition_content( $template_child ) ) {
							$this->render_template_live_element( $template_child );
						}
					}
				} finally {
					RenderContext::get_instance()->pop_listing_context();
				}

				$popup_markup = trim( (string) ob_get_clean() );

				if ( '' === $popup_markup ) {
					return '';
				}

				return sprintf(
					'<div class="directorist-elementor-single-map__popup-card">%s</div>',
					$popup_markup
				);
			}
		);
	}

	/**
	 * Resolve the first child raw payload as the popup-card template.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function get_template_raw_data(): ?array {
		$raw_children = $this->get_render_child_raw_elements();

		if ( empty( $raw_children[0] ) || ! is_array( $raw_children[0] ) ) {
			return null;
		}

		return $raw_children[0];
	}

	/**
	 * Get cached raw child elements from the widget payload.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements(): array {
		if ( is_array( $this->render_child_raw_elements ) ) {
			return $this->render_child_raw_elements;
		}

		$raw_data = $this->get_current_widget_raw_data();

		if ( null === $raw_data ) {
			$raw_data = $this->get_raw_data();
		}

		$normalized = [];

		foreach ( array_values( (array) ( $raw_data['elements'] ?? [] ) ) as $raw_child ) {
			if ( is_array( $raw_child ) ) {
				$normalized[] = $raw_child;
			}
		}

		$this->render_child_raw_elements = $normalized;

		return $this->render_child_raw_elements;
	}

	/**
	 * Resolve the current widget raw data from the active Elementor document.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function get_current_widget_raw_data(): ?array {
		if ( $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_elements_data' ) ) {
			return null;
		}

		$element_data = $this->find_raw_element_by_id(
			(array) $current_document->get_elements_data(),
			(string) $this->get_id()
		);

		return is_array( $element_data ) ? $element_data : null;
	}

	/**
	 * Recursively find a raw Elementor element by id.
	 *
	 * @param array<int,array<string,mixed>> $elements Raw element collection.
	 * @param string                         $target_id Target widget id.
	 * @return array<string,mixed>|null
	 */
	protected function find_raw_element_by_id( array $elements, string $target_id ): ?array {
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

			$found = $this->find_raw_element_by_id( $children, $target_id );

			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Determine whether raw branch data contains meaningful composition.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return bool
	 */
	protected function has_raw_composition_content( array $element_data ): bool {
		$el_type  = (string) ( $element_data['elType'] ?? '' );
		$children = (array) ( $element_data['elements'] ?? [] );

		if ( 'container' !== $el_type ) {
			return true;
		}

		foreach ( $children as $child ) {
			if ( is_array( $child ) && $this->has_raw_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render a fresh Elementor element instance for the saved popup-card template.
	 *
	 * @param array<string,mixed> $template_raw Template raw data.
	 * @return void
	 */
	protected function render_template_raw_data( array $template_raw ): void {
		$fresh_element = ElementTreeRenderService::get_instance()->create_element_instance( $template_raw );

		if ( $fresh_element && method_exists( $fresh_element, 'print_element' ) ) {
			$fresh_element->print_element();
		}
	}

	/**
	 * Render the live popup-card child element when raw data is unavailable.
	 *
	 * @param mixed $template_child Template child element.
	 * @return void
	 */
	protected function render_template_live_element( $template_child ): void {
		if ( method_exists( $template_child, 'print_element' ) ) {
			$template_child->print_element();
		}
	}

	/**
	 * Resolve listing id from runtime context.
	 *
	 * @return int
	 */
	protected function resolve_listing_id(): int {
		$listing_context = RenderContext::get_instance()->current_listing_context();
		$listing_id      = absint( $listing_context['listing_id'] ?? 0 );

		if ( $listing_id > 0 ) {
			return $listing_id;
		}

		if ( is_singular( DirectoristBridge::get_instance()->get_listing_post_type() ) ) {
			return absint( get_queried_object_id() );
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return 0;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( $current_document && method_exists( $current_document, 'get_settings' ) ) {
			$preview_type = (string) $current_document->get_settings( 'preview_type' );

			if ( 'single/' . DirectoristBridge::get_instance()->get_listing_post_type() === $preview_type ) {
				$preview_id = absint( $current_document->get_settings( 'preview_id' ) );

				if ( $preview_id > 0 ) {
					return $preview_id;
				}
			}
		}

		if ( $this->is_editor_context() ) {
			return $this->resolve_editor_fallback_listing_id();
		}

		return 0;
	}

	/**
	 * Resolve a fallback listing ID for editor preview.
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

		if ( $fallback_id <= 0 && $directory_type_id > 0 ) {
			unset( $query_args['meta_query'] );
			$retry_query = new \WP_Query( $query_args );
			$fallback_id = ! empty( $retry_query->posts ) ? (int) $retry_query->posts[0] : 0;
			wp_reset_postdata();
		}

		return $fallback_id;
	}

	/**
	 * Resolve directory type ID from the current Elementor document type.
	 *
	 * @return int
	 */
	protected function resolve_document_directory_type_id(): int {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return 0;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_name' ) ) {
			return 0;
		}

		$doc_type = $current_document->get_name();
		$prefix   = 'directorist-single-listing-directory-';

		if ( 0 !== strpos( $doc_type, $prefix ) ) {
			return 0;
		}

		return absint( substr( $doc_type, strlen( $prefix ) ) );
	}

	protected function content_template() {
		?>
		<div class="directorist-elementor-single-map directorist-elementor-single-map--editor-preview">
			<div class="directorist-elementor-single-map__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-single-map__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the single listing map preview for the current listing context.', 'directorist-elementor' ); ?></p>
				</div>
			</div>
			<div class="directorist-elementor-single-map__template-storage" aria-hidden="true">
				<div class="directorist-elementor-single-map__template">
					<div class="directorist-elementor-single-map__template-canvas"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
