<?php
/**
 * Single listing related listings widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractDirectoristNestedWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SingleListingRelatedListingsWidget extends AbstractDirectoristNestedWidget {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	public function get_name(): string {
		return 'directorist_single_listing_related_listings';
	}

	public function get_title(): string {
		return __( 'Related Listings', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'related', 'similar' ] );
	}

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_OTHERS;
	}

	protected function get_default_children_elements() {
		return [
			[
				'elType'   => 'container',
				'isInner'  => true,
				'settings' => [
					'_title' => __( 'Related Card Template', 'directorist-elementor' ),
				],
				'elements' => [],
			],
		];
	}

	protected function get_default_repeater_title_setting_key() {
		return '_title';
	}

	protected function get_default_children_title() {
		return __( 'Related Card Template', 'directorist-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-related-listings__template-storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-related-listings__template-canvas';
	}

	protected function get_initial_config(): array {
		return array_merge(
			parent::get_initial_config(),
			[
				'target_container' => [ '.directorist-elementor-related-listings__template-storage' ],
				'node'             => 'div',
				'is_interlaced'    => true,
			]
		);
	}

	// =========================================================================
	// Controls
	// =========================================================================

	protected function register_widget_controls(): void {
		// --- Content: Related Listings ---
		$this->start_controls_section(
			'section_related_listings_content',
			[
				'label' => __( 'Related Listings', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'template_status',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Compose one related listing card template here. Every related listing item reuses the same Elementor structure.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Related Listings', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'display_mode',
			[
				'label'     => __( 'Display Mode', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'grid',
				'options'   => [
					'grid'       => __( 'Grid', 'directorist-elementor' ),
					'slider'     => __( 'Slider', 'directorist-elementor' ),
					'pagination' => __( 'Pagination', 'directorist-elementor' ),
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'similar_listings_number_of_listings_to_show',
			[
				'label'     => __( 'Listings To Show', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 24,
				'condition' => [
					'display_mode!' => 'slider',
				],
			]
		);

		$this->add_responsive_control(
			'similar_listings_number_of_columns',
			[
				'label'          => __( 'Columns', 'directorist-elementor' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
				],
				'condition' => [
					'display_mode!' => 'slider',
				],
			]
		);

		// --- Slider controls ---
		$this->add_responsive_control(
			'slides_per_view',
			[
				'label'          => __( 'Slides Per View', 'directorist-elementor' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'show_arrow_navigation',
			[
				'label'        => __( 'Arrow Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'show_dot_navigation',
			[
				'label'        => __( 'Dot Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'slider_autoplay',
			[
				'label'        => __( 'Autoplay', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'slider_effect',
			[
				'label'   => __( 'Effect', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => [
					'slide'     => __( 'Slide', 'directorist-elementor' ),
					'fade'      => __( 'Fade', 'directorist-elementor' ),
					'grid'      => __( 'Grid', 'directorist-elementor' ),
					'thumb'     => __( 'Thumb', 'directorist-elementor' ),
					'creative'  => __( 'Creative', 'directorist-elementor' ),
					'cards'     => __( 'Cards', 'directorist-elementor' ),
					'cube'      => __( 'Cube', 'directorist-elementor' ),
					'flip'      => __( 'Flip', 'directorist-elementor' ),
					'coverflow' => __( 'Coverflow', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'slider_grid_rows',
			[
				'label'   => __( 'Grid Rows', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2,
				'min'     => 1,
				'max'     => 6,
				'condition' => [
					'display_mode'  => 'slider',
					'slider_effect' => 'grid',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => __( 'Pause on Hover', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode'    => 'slider',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'autoplay_delay',
			[
				'label'   => __( 'Autoplay Delay (ms)', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3000,
				'min'     => 100,
				'step'    => 50,
				'condition' => [
					'display_mode'    => 'slider',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => __( 'Transition Speed (ms)', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 500,
				'min'     => 100,
				'step'    => 50,
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);

		// --- Query controls ---
		$this->add_control(
			'listing_from_same_author',
			[
				'label'        => __( 'Same Author Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => '1',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'similar_listings_logics',
			[
				'label'   => __( 'Match Logic', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'OR',
				'options' => [
					'OR'  => __( 'Any Matching Category or Tag', 'directorist-elementor' ),
					'AND' => __( 'Match Category and Tag', 'directorist-elementor' ),
				],
			]
		);

		$this->end_controls_section();

		// --- Style: Container ---
		$this->start_controls_section(
			'section_related_listings_style',
			[
				'label' => __( 'Related Listings', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'section_background',
				'selector' => '{{WRAPPER}} .directorist-related-listing',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'section_border',
				'selector' => '{{WRAPPER}} .directorist-related-listing',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'section_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-related-listing',
			]
		);

		$this->add_responsive_control(
			'section_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-related-listing' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'section_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-related-listing' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'section_title_color',
			[
				'label'     => __( 'Title Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-related-listing .directorist-related-listing-header__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'section_title_typography',
				'selector' => '{{WRAPPER}} .directorist-related-listing .directorist-related-listing-header__title',
			]
		);

		$this->end_controls_section();

		// --- Style: Related Card ---
		$this->start_controls_section(
			'section_style_related_card',
			[
				'label' => __( 'Related Card', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_background',
				'selector' => '{{WRAPPER}} .directorist-gbi-related-card, {{WRAPPER}} .directorist-elementor-related-listings__preview-item',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .directorist-gbi-related-card, {{WRAPPER}} .directorist-elementor-related-listings__preview-item',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-gbi-related-card, {{WRAPPER}} .directorist-elementor-related-listings__preview-item',
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-related-card, {{WRAPPER}} .directorist-elementor-related-listings__preview-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-related-card, {{WRAPPER}} .directorist-elementor-related-listings__preview-item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Style: Slider Arrows ---
		$this->start_controls_section(
			'section_style_arrow_nav',
			[
				'label'     => __( 'Slider Arrows', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_mode'          => 'slider',
					'show_arrow_navigation' => 'yes',
				],
			]
		);

		$this->add_control(
			'arrow_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-swiper__nav--prev-related, {{WRAPPER}} .directorist-swiper__nav--next-related' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrow_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-swiper__nav--prev-related, {{WRAPPER}} .directorist-swiper__nav--next-related' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 12, 'max' => 60 ] ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-swiper__nav--prev-related, {{WRAPPER}} .directorist-swiper__nav--next-related' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Style: Slider Dots ---
		$this->start_controls_section(
			'section_style_dot_nav',
			[
				'label'     => __( 'Slider Dots', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_mode'        => 'slider',
					'show_dot_navigation' => 'yes',
				],
			]
		);

		$this->add_control(
			'dot_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-swiper__pagination--related .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dot_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-swiper__pagination--related .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'dot_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 4, 'max' => 24 ] ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-swiper__pagination--related .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Style: Pagination Button ---
		$this->start_controls_section(
			'section_style_pagination_button',
			[
				'label'     => __( 'Pagination Button', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_mode' => 'pagination',
				],
			]
		);

		$this->add_control(
			'pagination_button_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-related-pagination__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_button_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-related-pagination__button.is-active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_button_typography',
				'selector' => '{{WRAPPER}} .directorist-gbi-related-pagination__button',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'pagination_button_border',
				'selector' => '{{WRAPPER}} .directorist-gbi-related-pagination__button',
			]
		);

		$this->add_control(
			'pagination_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-related-pagination__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-related-pagination__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	// =========================================================================
	// Render
	// =========================================================================

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Related Listings', 'directorist-elementor' ),
					__( 'Place this widget inside a Directorist single listing template to render related listings.', 'directorist-elementor' )
				)
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/section-related_listings' );

		$settings     = $this->get_settings_for_display();
		$children     = array_values( $this->get_children() );
		$display_mode = sanitize_key( (string) ( $settings['display_mode'] ?? 'grid' ) );
		$section_data = $this->get_related_section_data( $listing_id, $settings );

		// In slider mode, fetch all related IDs.
		if ( 'slider' === $display_mode ) {
			$section_data['similar_listings_number_of_listings_to_show'] = 999;
		}

		$related_ids = $bridge->get_related_listing_ids( $listing_id, $section_data );

		if ( empty( $related_ids ) && ! $this->is_editor_context() ) {
			return;
		}

		$columns     = max( 1, min( 4, absint( $section_data['similar_listings_number_of_columns'] ?? 3 ) ) );
		$title       = sanitize_text_field( (string) ( $section_data['label'] ?? __( 'Related Listings', 'directorist-elementor' ) ) );
		$instance_id = InstanceState::get_instance()->normalize_instance_id( 'direl-related-listings-' . $this->get_id() );

		$section_classes = [
			'directorist-related-listing',
			'directorist-elementor-related-listings',
			'directorist-gbi-related-display-' . $display_mode,
			$this->is_editor_context() ? 'directorist-elementor-related-listings--editor-preview' : 'directorist-elementor-related-listings--frontend',
		];

		$attributes = InstanceState::get_instance()->build_widget_root_attributes(
			$instance_id,
			'related-listings',
			[
				'class'                   => $section_classes,
				'style'                    => '--direl-related-columns:' . $columns . ';',
				'data-direl-related-count' => (string) count( $related_ids ),
			]
		);

		echo '<section ' . $this->format_html_attributes( $attributes ) . '>';

		if ( '' !== $title ) {
			echo '<div class="directorist-related-listing-header">';
			printf( '<h3 class="directorist-related-listing-header__title">%s</h3>', esc_html( $title ) );
			echo '</div>';
		}

		if ( $this->is_editor_context() ) {
			printf(
				'<div class="directorist-elementor-related-listings__state"><p class="directorist-elementor-card-template__notice">%s</p></div>',
				esc_html__( 'Compose one related listing card template. Every visible related item reuses the same Elementor structure.', 'directorist-elementor' )
			);
		}

		if ( empty( $related_ids ) ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Related Listings', 'directorist-elementor' ),
					__( 'No related listings were found for the current preview listing.', 'directorist-elementor' )
				)
			);
			echo '</section>';
			return;
		}

		if ( 'slider' === $display_mode ) {
			$this->render_slider_mode( $children, $related_ids, $settings );
		} elseif ( 'pagination' === $display_mode ) {
			$this->render_pagination_mode( $children, $related_ids, $settings );
		} else {
			$this->render_grid_mode( $children, $related_ids );
		}

		echo '</section>';
	}

	// =========================================================================
	// Render modes
	// =========================================================================

	protected function render_grid_mode( array $children, array $related_ids ): void {
		echo '<div class="directorist-elementor-related-listings__preview-items">';
		$this->render_card_loop( $children, $related_ids );
		echo '</div>';
	}

	protected function render_slider_mode( array $children, array $related_ids, array $settings ): void {
		$spv_desktop  = max( 1, absint( $settings['slides_per_view'] ?? 3 ) );
		$spv_tablet   = max( 1, absint( $settings['slides_per_view_tablet'] ?? 2 ) );
		$spv_mobile   = max( 1, absint( $settings['slides_per_view_mobile'] ?? 1 ) );
		$show_arrows  = 'yes' === ( $settings['show_arrow_navigation'] ?? 'yes' );
		$show_dots    = 'yes' === ( $settings['show_dot_navigation'] ?? 'yes' );
		$autoplay     = 'yes' === ( $settings['slider_autoplay'] ?? '' );
		$effect       = sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) );
		$grid_rows    = max( 1, min( 6, absint( $settings['slider_grid_rows'] ?? 2 ) ) );
		$pause        = 'yes' === ( $settings['pause_on_hover'] ?? 'yes' );
		$delay        = max( 100, absint( $settings['autoplay_delay'] ?? 3000 ) );
		$speed        = max( 100, absint( $settings['transition_speed'] ?? 500 ) );
		$should_loop  = count( $related_ids ) > $spv_desktop;

		$breakpoints = wp_json_encode( [
			0    => [ 'slidesPerView' => $spv_mobile, 'spaceBetween' => 10 ],
			768  => [ 'slidesPerView' => $spv_tablet, 'spaceBetween' => 20 ],
			1200 => [ 'slidesPerView' => $spv_desktop, 'spaceBetween' => 30 ],
		] );

		$slider_attrs = [
			'class'                 => 'directorist-swiper directorist-gbi-related-slider',
			'data-sw-items'         => (string) $spv_desktop,
			'data-sw-margin'        => '30',
			'data-sw-loop'          => $should_loop ? 'true' : 'false',
			'data-sw-perslide'      => '1',
			'data-sw-speed'         => (string) $speed,
			'data-sw-delay'         => (string) $delay,
			'data-sw-autoplay'      => $autoplay ? 'true' : 'false',
			'data-sw-effect'        => $effect,
			'data-sw-grid-rows'     => (string) $grid_rows,
			'data-sw-pause-on-hover' => $pause ? 'true' : 'false',
			'data-sw-responsive'    => $breakpoints,
		];

		echo '<div ' . $this->format_html_attributes( $slider_attrs ) . '>';
		echo '<div class="swiper-wrapper">';

		foreach ( $related_ids as $index => $related_listing_id ) {
			$related_listing_id = absint( $related_listing_id );
			if ( $related_listing_id <= 0 ) {
				continue;
			}

			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $related_listing_id );
			RenderContext::get_instance()->push_listing_context( $related_listing_id, $index, $directory_type_id );

			try {
				echo '<div class="swiper-slide"><div class="directorist-gbi-related-card">';
				echo '<article class="directorist-elementor-related-listings__preview-item" data-direl-related-listing-id="' . esc_attr( (string) $related_listing_id ) . '">';
				$this->render_related_listing_card( $children, $related_listing_id );
				echo '</article>';
				echo '</div></div>';
			} finally {
				RenderContext::get_instance()->pop_listing_context();
			}
		}

		echo '</div>'; // .swiper-wrapper

		// Arrow navigation.
		$nav_hidden = $show_arrows ? '' : ' directorist-gbi-related-navigation--hidden';
		echo '<div class="directorist-swiper__navigation' . esc_attr( $nav_hidden ) . '">';
		echo '<div class="directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-related"><span aria-hidden="true">&#8249;</span></div>';
		echo '<div class="directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-related"><span aria-hidden="true">&#8250;</span></div>';
		echo '</div>';

		// Dot pagination.
		$dot_hidden = $show_dots ? '' : ' directorist-gbi-related-pagination--hidden';
		echo '<div class="directorist-swiper__pagination directorist-swiper__pagination--related' . esc_attr( $dot_hidden ) . '"></div>';

		echo '</div>'; // .directorist-swiper
	}

	protected function render_pagination_mode( array $children, array $related_ids, array $settings ): void {
		$per_page    = max( 1, absint( $settings['similar_listings_number_of_listings_to_show'] ?? 3 ) );
		$total_pages = max( 1, (int) ceil( count( $related_ids ) / $per_page ) );
		$columns     = max( 1, min( 4, absint( $settings['similar_listings_number_of_columns'] ?? 3 ) ) );
		$col_span    = max( 1, (int) floor( 12 / $columns ) );

		echo '<div class="directorist-row directorist-gbi-related-grid directorist-gbi-related-grid--cols-' . esc_attr( (string) $columns ) . '">';

		foreach ( $related_ids as $index => $related_listing_id ) {
			$related_listing_id = absint( $related_listing_id );
			if ( $related_listing_id <= 0 ) {
				continue;
			}

			$page    = (int) floor( $index / $per_page ) + 1;
			$hidden  = $page > 1 ? ' style="display:none"' : '';
			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $related_listing_id );
			RenderContext::get_instance()->push_listing_context( $related_listing_id, $index, $directory_type_id );

			try {
				printf(
					'<div class="directorist-col-%1$d directorist-col-sm-6 directorist-col-12" data-direl-related-page="%2$d"%3$s>',
					$col_span,
					$page,
					$hidden
				);
				echo '<div class="directorist-gbi-related-card">';
				echo '<article class="directorist-elementor-related-listings__preview-item" data-direl-related-listing-id="' . esc_attr( (string) $related_listing_id ) . '">';
				$this->render_related_listing_card( $children, $related_listing_id );
				echo '</article>';
				echo '</div></div>';
			} finally {
				RenderContext::get_instance()->pop_listing_context();
			}
		}

		echo '</div>'; // .directorist-gbi-related-grid

		// Pagination buttons.
		if ( $total_pages > 1 ) {
			echo '<nav class="directorist-gbi-related-pagination">';
			echo '<ul class="directorist-gbi-related-pagination__list">';

			for ( $p = 1; $p <= $total_pages; $p++ ) {
				$active = 1 === $p ? ' is-active' : '';
				printf(
					'<li class="directorist-gbi-related-pagination__item%1$s"><button class="directorist-gbi-related-pagination__button%1$s" data-gbi-related-page="%2$d"%3$s>%2$d</button></li>',
					$active,
					$p,
					1 === $p ? ' aria-current="page"' : ''
				);
			}

			echo '</ul></nav>';
		}
	}

	// =========================================================================
	// Card rendering helpers
	// =========================================================================

	protected function render_card_loop( array $children, array $related_ids ): void {
		foreach ( $related_ids as $index => $related_listing_id ) {
			$related_listing_id = absint( $related_listing_id );
			if ( $related_listing_id <= 0 ) {
				continue;
			}

			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $related_listing_id );
			RenderContext::get_instance()->push_listing_context( $related_listing_id, $index, $directory_type_id );

			try {
				printf(
					'<article class="directorist-elementor-related-listings__preview-item" data-direl-related-listing-id="%d">',
					$related_listing_id
				);
				$this->render_related_listing_card( $children, $related_listing_id );
				echo '</article>';
			} finally {
				RenderContext::get_instance()->pop_listing_context();
			}
		}
	}

	protected function render_related_listing_card( array $children, int $related_listing_id ): void {
		$template_raw   = $this->get_template_raw_data();
		$has_template   = false;

		if ( is_array( $template_raw ) && $this->has_raw_composition_content( $template_raw ) ) {
			$has_template = true;
		} elseif ( ! $this->is_editor_context() ) {
			$template_child = $children[0] ?? null;
			if ( is_object( $template_child ) && $this->has_composition_content( $template_child ) ) {
				$has_template = true;
			}
		}

		echo '<div class="directorist-elementor-related-listings__content">';

		if ( $has_template ) {
			if ( is_array( $template_raw ) && $this->has_raw_composition_content( $template_raw ) ) {
				$this->render_template_raw_data( $template_raw );
			} else {
				$this->render_template_live_element( $children[0] );
			}
		} else {
			$this->render_default_related_listing_card( $related_listing_id );
		}

		echo '</div>';
	}

	protected function get_template_raw_data(): ?array {
		$raw_children = $this->get_render_child_raw_elements();
		if ( empty( $raw_children[0] ) || ! is_array( $raw_children[0] ) ) {
			return null;
		}
		return $raw_children[0];
	}

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

	protected function find_raw_element_by_id( array $elements, string $target_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $element;
			}
			$children = (array) ( $element['elements'] ?? [] );
			if ( ! empty( $children ) ) {
				$found = $this->find_raw_element_by_id( $children, $target_id );
				if ( is_array( $found ) ) {
					return $found;
				}
			}
		}
		return null;
	}

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

	protected function render_template_raw_data( array $template_raw ): void {
		$fresh_element = ElementTreeRenderService::get_instance()->create_element_instance( $template_raw );
		if ( $fresh_element && method_exists( $fresh_element, 'print_element' ) ) {
			$fresh_element->print_element();
		}
	}

	protected function render_template_live_element( $template_child ): void {
		if ( method_exists( $template_child, 'print_element' ) ) {
			$template_child->print_element();
		}
	}

	protected function render_default_related_listing_card( int $listing_id ): void {
		$bridge    = DirectoristBridge::get_instance();
		$title     = $bridge->get_listing_title( $listing_id );
		$permalink = $bridge->get_listing_permalink( $listing_id );
		$image_url = $bridge->get_listing_image_url( $listing_id, 'large' );

		echo '<div class="directorist-elementor-related-listings__fallback-card">';

		if ( '' !== $image_url ) {
			printf(
				'<a class="directorist-elementor-listing-card-thumbnail__link" href="%1$s"><img class="directorist-elementor-listing-card-thumbnail__image" src="%2$s" alt="%3$s" /></a>',
				esc_url( $permalink ),
				esc_url( $image_url ),
				esc_attr( $title )
			);
		}

		if ( '' !== $title ) {
			printf(
				'<h3 class="directorist-elementor-listing-card-title directorist-elementor-listing-card-title__text"><a class="directorist-elementor-listing-card-title__link" href="%1$s">%2$s</a></h3>',
				esc_url( $permalink ),
				esc_html( $title )
			);
		}

		echo '</div>';
	}

	// =========================================================================
	// Helpers
	// =========================================================================

	protected function get_related_section_data( int $listing_id, array $settings ): array {
		$existing     = DirectoristBridge::get_instance()->get_single_listing_section_data( $listing_id, 'related_listings' );
		$section_data = wp_parse_args(
			$existing,
			[
				'type'                                        => 'other_widgets',
				'widget_name'                                 => 'related_listings',
				'label'                                       => __( 'Related Listings', 'directorist-elementor' ),
				'similar_listings_number_of_listings_to_show' => 3,
				'similar_listings_number_of_columns'          => 3,
				'listing_from_same_author'                    => false,
				'similar_listings_logics'                     => 'OR',
			]
		);

		$section_data['label'] = '' !== trim( (string) ( $settings['section_title'] ?? '' ) )
			? sanitize_text_field( (string) $settings['section_title'] )
			: (string) $section_data['label'];
		$section_data['similar_listings_number_of_listings_to_show'] = max( 1, absint( $settings['similar_listings_number_of_listings_to_show'] ?? $section_data['similar_listings_number_of_listings_to_show'] ) );
		$section_data['similar_listings_number_of_columns']          = max( 1, min( 4, absint( $settings['similar_listings_number_of_columns'] ?? $section_data['similar_listings_number_of_columns'] ) ) );
		$section_data['listing_from_same_author']                    = ! empty( $settings['listing_from_same_author'] );

		$logic = strtoupper( sanitize_key( (string) ( $settings['similar_listings_logics'] ?? $section_data['similar_listings_logics'] ?? 'OR' ) ) );
		$section_data['similar_listings_logics'] = in_array( $logic, [ 'AND', 'OR' ], true ) ? $logic : 'OR';

		return $section_data;
	}

	protected function resolve_listing_id(): int {
		$post_type = DirectoristBridge::get_instance()->get_listing_post_type();

		$normalize = static function ( $candidate_id ) use ( $post_type ): int {
			$id = absint( $candidate_id );
			return ( $id > 0 && get_post_type( $id ) === $post_type ) ? $id : 0;
		};

		// Frontend: current single listing post.
		if ( is_singular( $post_type ) ) {
			return absint( get_queried_object_id() );
		}

		// Queried object (Theme Builder frontend).
		$queried = get_queried_object();
		if ( $queried instanceof \WP_Post ) {
			$listing_id = $normalize( $queried->ID );
			if ( $listing_id > 0 ) {
				return $listing_id;
			}
		}

		// Global post (set by begin_editor_render_session or setup_postdata).
		$global_id = $normalize( get_the_ID() );
		if ( $global_id > 0 ) {
			return $global_id;
		}

		// Editor / Theme Builder: use the document's preview post context.
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_settings' ) ) {
				$preview_type = (string) $current_document->get_settings( 'preview_type' );
				if ( 'single/' . $post_type === $preview_type ) {
					$preview_id = $normalize( $current_document->get_settings( 'preview_id' ) );
					if ( $preview_id > 0 ) {
						return $preview_id;
					}
				}
			}
		}

		// Editor fallback: use the most recent published listing,
		// scoped to the directory type if editing a directory-specific template.
		if ( $this->is_editor_context() ) {
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

			// Try to detect directory type from the current document type.
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

		return 0;
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
		<div class="directorist-related-listing directorist-elementor-related-listings directorist-elementor-related-listings--editor">
			<div class="directorist-elementor-related-listings__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-related-listings__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the related listings preview for the current single listing context.', 'directorist-elementor' ); ?></p>
				</div>
			</div>
			<div class="directorist-elementor-related-listings__template-storage" aria-hidden="true">
				<div class="directorist-elementor-related-listings__template">
					<div class="directorist-elementor-related-listings__template-canvas"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
