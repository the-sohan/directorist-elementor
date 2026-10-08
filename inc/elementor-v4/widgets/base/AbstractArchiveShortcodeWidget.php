<?php
/**
 * Base widget for Directorist archive shortcodes.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

abstract class AbstractArchiveShortcodeWidget extends AbstractDirectoristWidget {

	/**
	 * Archive widgets belong to the archive category.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	/**
	 * Get the taxonomy type identifier for slider class suffixes.
	 *
	 * @return string 'categories' or 'locations'.
	 */
	protected function get_taxonomy_slider_type(): string {
		return '';
	}

	/**
	 * Register slider controls inline (without wrapping section).
	 *
	 * @return void
	 */
	protected function register_slider_controls_inline(): void {
		$this->add_control(
			'display_mode',
			[
				'label'   => __( 'Display Mode', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pagination',
				'options' => [
					'pagination' => __( 'Pagination', 'directorist-elementor' ),
					'slider'     => __( 'Slider', 'directorist-elementor' ),
				],
				'separator' => 'before',
			]
		);

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
				'step'    => 1,
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
	}

	/**
	 * Register taxonomy style tab sections.
	 *
	 * @param string $item_label   Label for item section, e.g. "Category Item".
	 * @param string $title_label  Label for title section, e.g. "Category Title".
	 * @param string $image_label  Label for image section, e.g. "Category Image".
	 * @param string $icon_label   Label for icon section, e.g. "Category Icon".
	 * @return void
	 */
	protected function register_taxonomy_style_sections(
		string $item_label,
		string $title_label,
		string $image_label,
		string $icon_label
	): void {
		// --- Item ---
		$this->start_controls_section(
			'section_style_item',
			[
				'label' => $item_label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'item_border',
				'selector' => '{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single, {{WRAPPER}} .directorist-taxonomy-list__card',
			]
		);

		$this->add_control(
			'item_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single, {{WRAPPER}} .directorist-taxonomy-list__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-categories__single__content, {{WRAPPER}} .directorist-location__content, {{WRAPPER}} .directorist-taxonomy-list__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single, {{WRAPPER}} .directorist-taxonomy-list__card' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'item_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single, {{WRAPPER}} .directorist-taxonomy-list__card',
			]
		);

		$this->end_controls_section();

		// --- Title ---
		$this->start_controls_section(
			'section_style_title',
			[
				'label' => $title_label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single__name, {{WRAPPER}} .directorist-categories__single__name a, {{WRAPPER}} .directorist-location__content h3, {{WRAPPER}} .directorist-location__content h3 a, {{WRAPPER}} .directorist-taxonomy-list__name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single__name a:hover, {{WRAPPER}} .directorist-location__content h3 a:hover, {{WRAPPER}} .directorist-taxonomy-list__card:hover .directorist-taxonomy-list__name' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .directorist-categories__single__name, {{WRAPPER}} .directorist-categories__single__name a, {{WRAPPER}} .directorist-location__content h3, {{WRAPPER}} .directorist-location__content h3 a, {{WRAPPER}} .directorist-taxonomy-list__name',
			]
		);

		$this->end_controls_section();

		// --- Image ---
		$this->start_controls_section(
			'section_style_image',
			[
				'label' => $image_label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'image_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 50, 'max' => 600, 'step' => 5 ],
					'vh' => [ 'min' => 5, 'max' => 100 ],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single__img img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'image_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single__img img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Icon ---
		$this->start_controls_section(
			'section_style_icon',
			[
				'label' => $icon_label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single__icon i, {{WRAPPER}} .directorist-categories__single__icon svg, {{WRAPPER}} .directorist-taxonomy-list__icon i, {{WRAPPER}} .directorist-taxonomy-list__icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
					'{{WRAPPER}} .directorist-categories__single__icon .directorist-icon-mask::after, {{WRAPPER}} .directorist-taxonomy-list__icon .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 80 ],
					'em' => [ 'min' => 0.5, 'max' => 5, 'step' => 0.1 ],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single__icon i, {{WRAPPER}} .directorist-categories__single__icon svg, {{WRAPPER}} .directorist-taxonomy-list__icon i, {{WRAPPER}} .directorist-taxonomy-list__icon svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-categories__single__icon .directorist-icon-mask::after, {{WRAPPER}} .directorist-taxonomy-list__icon .directorist-icon-mask::after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Listing Count ---
		$this->start_controls_section(
			'section_style_count',
			[
				'label' => __( 'Listing Count', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'count_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-categories__single__total, {{WRAPPER}} .directorist-category-term, {{WRAPPER}} .directorist-location__count, {{WRAPPER}} .directorist-taxonomy-list__count' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'count_typography',
				'selector' => '{{WRAPPER}} .directorist-categories__single__total, {{WRAPPER}} .directorist-category-term, {{WRAPPER}} .directorist-location__count, {{WRAPPER}} .directorist-taxonomy-list__count',
			]
		);

		$this->end_controls_section();

		// --- Directory Tabs ---
		$this->start_controls_section(
			'section_style_directory_tabs',
			[
				'label' => __( 'Directory Tabs', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tab_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__link:hover .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__list__current .directorist-type-nav__link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__list__current .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_typography',
				'selector' => '{{WRAPPER}} .directorist-type-nav__link',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'tab_border',
				'selector' => '{{WRAPPER}} .directorist-type-nav__link',
			]
		);

		$this->end_controls_section();

		// --- Slider Arrows ---
		$this->start_controls_section(
			'section_style_slider_arrows',
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
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrow_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// --- Slider Dots ---
		$this->start_controls_section(
			'section_style_slider_dots',
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
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dot_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
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
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render a shortcode using normalized string attributes.
	 *
	 * @param string               $shortcode Shortcode tag.
	 * @param array<string,mixed>  $attributes Shortcode attributes.
	 * @param array<string,mixed>  $slider_settings Optional slider settings.
	 * @param array<string,mixed>  $visibility_settings Optional visibility toggles.
	 * @param array<string,mixed>  $tab_settings Optional directory tab settings.
	 * @return void
	 */
	protected function render_archive_shortcode( string $shortcode, array $attributes, array $slider_settings = [], array $visibility_settings = [], array $tab_settings = [] ): void {
		$is_slider = ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' );

		// Slider mode always renders the taxonomy card layout, matching the
		// Gutenberg integration and keeping dependent controls consistent.
		if ( $is_slider ) {
			$attributes['view'] = 'grid';
		}

		// In slider mode, load all items (suppress pagination).
		if ( $is_slider ) {
			foreach ( [ 'cat_per_page', 'loc_per_page' ] as $per_page_key ) {
				if ( array_key_exists( $per_page_key, $attributes ) ) {
					$attributes[ $per_page_key ] = 9999;
				}
			}
		}

		$pairs = [];

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || '' === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_bool( $attribute_value ) ) {
				$attribute_value = $attribute_value ? 'yes' : 'no';
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ',', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf(
				'%s="%s"',
				sanitize_key( (string) $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		$shortcode_markup = sprintf(
			'[%1$s%2$s]',
			sanitize_key( $shortcode ),
			empty( $pairs ) ? '' : ' ' . implode( ' ', $pairs )
		);

		$output = do_shortcode( $shortcode_markup );

		// Post-process directory tab navigation.
			if ( ! empty( $tab_settings ) ) {
				$output = $this->process_directory_tabs( $output, $tab_settings );
			}

			$output = $this->remove_taxonomy_wrapper_row_class( $output );

			if ( $is_slider ) {
				$output = $this->convert_to_slider( $output, $slider_settings );
			}

		$wrapper_classes    = [ 'directorist-elementor-taxonomy-widget' ];
		$wrapper_style      = $this->build_taxonomy_responsive_style( $attributes );
		$wrapper_data_attrs = [
			'data-shortcode' => sanitize_key( $shortcode ),
			'data-atts'      => wp_json_encode( $attributes ),
		];

		if ( ! empty( $tab_settings ) ) {
			$wrapper_data_attrs['data-show-all-tab'] = ! empty( $tab_settings['show_all_directory_tab'] ) ? 'true' : 'false';

			$all_tab_icon = $tab_settings['all_tab_icon'] ?? [];
			if ( ! empty( $all_tab_icon ) && is_array( $all_tab_icon ) && ! empty( $all_tab_icon['value'] ) ) {
				$wrapper_data_attrs['data-all-tab-icon'] = wp_json_encode( $all_tab_icon );
			}
		}

		if ( $is_slider ) {
			$wrapper_data_attrs['data-slider'] = wp_json_encode( $slider_settings );
		}

		if ( ! empty( $visibility_settings ) ) {
			if ( empty( $visibility_settings['show_image'] ) ) {
				$wrapper_classes[] = 'directorist-gbi-taxonomy-hide-image';
			}
			if ( empty( $visibility_settings['show_icon'] ) ) {
				$wrapper_classes[] = 'directorist-gbi-taxonomy-hide-icon';
			}
			if ( ! empty( $visibility_settings['show_description'] ) ) {
				$wrapper_classes[] = 'directorist-gbi-taxonomy-show-description';
			}
		}

		$data_string = '';
		foreach ( $wrapper_data_attrs as $data_key => $data_value ) {
			$data_string .= sprintf( ' %s="%s"', $data_key, esc_attr( (string) $data_value ) );
		}

		$output = sprintf(
			'<div class="%s"%s%s>%s</div>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			'' !== $wrapper_style ? sprintf( ' style="%s"', esc_attr( $wrapper_style ) ) : '',
			$data_string,
			$output
		);

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode output is the widget payload.
	}

	/**
	 * Build responsive CSS variables for taxonomy grid/list layouts.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	protected function build_taxonomy_responsive_style( array $attributes ): string {
		$columns_desktop = $this->normalize_taxonomy_columns( $attributes['columns'] ?? 3 );
		$columns_tablet  = $this->resolve_responsive_taxonomy_columns( $attributes, 'columns_tablet', $columns_desktop );
		$columns_mobile  = $this->resolve_responsive_taxonomy_columns( $attributes, 'columns_mobile', $columns_tablet );

		return sprintf(
			'--direl-taxonomy-columns:%1$d;--direl-taxonomy-columns-tablet:%2$d;--direl-taxonomy-columns-mobile:%3$d;',
			$columns_desktop,
			$columns_tablet,
			$columns_mobile
		);
	}

	/**
	 * Normalize a taxonomy column count into the supported range.
	 *
	 * @param mixed $value Raw value.
	 * @return int
	 */
	protected function normalize_taxonomy_columns( $value ): int {
		return max( 1, min( 6, absint( $value ) ) );
	}

	/**
	 * Resolve a responsive taxonomy column value with fallback chaining.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @param string              $attribute_key Responsive attribute key.
	 * @param int                 $fallback Fallback value.
	 * @return int
	 */
	protected function resolve_responsive_taxonomy_columns( array $attributes, string $attribute_key, int $fallback ): int {
		if ( ! array_key_exists( $attribute_key, $attributes ) ) {
			return $fallback;
		}

		$value = $attributes[ $attribute_key ];

		if ( is_string( $value ) && '' === trim( $value ) ) {
			return $fallback;
		}

		$normalized = $this->normalize_taxonomy_columns( $value );

		return $normalized > 0 ? $normalized : $fallback;
	}

	/**
	 * Post-process directory tab navigation in shortcode output.
	 *
	 * Handles the "Enable All Tab" toggle and replaces the All tab icon
	 * with the user-selected icon from Elementor's icon picker.
	 *
	 * @param string              $content      Rendered shortcode HTML.
	 * @param array<string,mixed> $tab_settings Tab settings.
	 * @return string
	 */
	protected function process_directory_tabs( string $content, array $tab_settings ): string {
		$show_all_tab = ! empty( $tab_settings['show_all_directory_tab'] );
		$all_tab_icon = $tab_settings['all_tab_icon'] ?? [];

		// Remove the All tab when disabled.
		if ( ! $show_all_tab ) {
			// The core template wraps the All tab in a <li class="list-inline-item"> containing
			// an <a> whose href includes "directory_type=all". Remove that <li> entirely.
			$content = preg_replace(
				'/<li[^>]*class="[^"]*list-inline-item[^"]*"[^>]*>\s*<a[^>]*href="[^"]*directory_type=all[^"]*"[^>]*>.*?<\/a>\s*<\/li>/si',
				'',
				$content
			);
			return $content;
		}

		// Replace the default All tab icon with the selected Elementor icon.
		$new_icon_html = $this->render_icon_html( $all_tab_icon );
		if ( '' !== $new_icon_html ) {
			// The core uses directorist_icon() which outputs:
			// <i class="directorist-icon-mask" aria-hidden="true" style="--directorist-icon: url(...)"></i>
			// Match any <i> element (including self-closing-style) inside the All tab <a> link.
			$content = preg_replace_callback(
				'/(<a[^>]*href="[^"]*directory_type=all[^"]*"[^>]*>)\s*(<i[^>]*>(?:<\/i>)?)\s*/si',
				static function ( array $matches ) use ( $new_icon_html ): string {
					return $matches[1] . $new_icon_html . ' ';
				},
				$content
			);
		}

		// Neutralize tab link hrefs and ensure all tabs have data-listing_type.
		// The core All tab lacks data-listing_type — add it so frontend JS can detect it.
		$content = preg_replace_callback(
			'/(<a[^>]*class="[^"]*directorist-type-nav__link[^"]*"[^>]*)href="([^"]*)"/si',
			static function ( array $matches ): string {
				$attrs = $matches[1];
				$href  = $matches[2];

				// Add data-listing_type="all" to the All tab if missing.
				if ( false !== strpos( $href, 'directory_type=all' ) && false === strpos( $attrs, 'data-listing_type' ) ) {
					$attrs .= ' data-listing_type="all" ';
				}

				return $attrs . 'href="#"';
			},
			$content
		);

		// Neutralize pagination links.
		$content = preg_replace_callback(
			'/(<a[^>]*)href="([^"]*)"([^>]*class="[^"]*page-numbers[^"]*")/si',
			static function ( array $matches ): string {
				$href = $matches[2];
				$page = 1;
				if ( preg_match( '/[?&](?:paged|page)=(\d+)/', $href, $pm ) ) {
					$page = (int) $pm[1];
				} elseif ( preg_match( '/\/page\/(\d+)/', $href, $pm ) ) {
					$page = (int) $pm[1];
				}
				return $matches[1] . 'href="#" data-page="' . esc_attr( (string) $page ) . '"' . $matches[3];
			},
			$content
		);

		return $content;
	}

	/**
	 * Resolve tab settings from widget display settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function resolve_tab_settings( array $settings ): array {
		return [
			'show_all_directory_tab' => 'yes' === ( $settings['show_all_directory_tab'] ?? 'yes' ),
			'all_tab_icon'           => $settings['all_tab_icon'] ?? [],
		];
	}

	/**
	 * Render an Elementor icon array to HTML string.
	 *
	 * Uses Elementor's Icons_Manager::render_icon() which outputs inline SVG,
	 * avoiding external font CSS dependency issues.
	 *
	 * @param array<string,mixed> $icon Elementor icon data (value + library).
	 * @return string
	 */
	protected function render_icon_html( array $icon ): string {
		if ( empty( $icon ) || ! is_array( $icon ) || empty( $icon['value'] ) ) {
			return '';
		}

		ob_start();
		\Elementor\Icons_Manager::render_icon(
			$icon,
			[ 'aria-hidden' => 'true', 'style' => 'width:1em;height:1em;vertical-align:middle;fill:currentColor;' ]
		);
		$output = ob_get_clean();

		return is_string( $output ) && '' !== trim( $output ) ? $output : '';
	}

	/**
	 * Resolve slider settings from widget display settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function resolve_slider_settings( array $settings ): array {
		$display_mode = sanitize_key( (string) ( $settings['display_mode'] ?? 'pagination' ) );

		if ( 'slider' !== $display_mode ) {
			return [];
		}

		return [
			'display_mode'           => 'slider',
			'slides_per_view'        => max( 1, absint( $settings['slides_per_view'] ?? 3 ) ),
			'slides_per_view_tablet' => max( 1, absint( $settings['slides_per_view_tablet'] ?? 2 ) ),
			'slides_per_view_mobile' => max( 1, absint( $settings['slides_per_view_mobile'] ?? 1 ) ),
			'slider_effect'          => sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) ),
			'slider_grid_rows'       => max( 1, min( 6, absint( $settings['slider_grid_rows'] ?? 2 ) ) ),
			'show_arrow_navigation'  => 'yes' === ( $settings['show_arrow_navigation'] ?? 'yes' ),
			'show_dot_navigation'    => 'yes' === ( $settings['show_dot_navigation'] ?? 'yes' ),
			'slider_autoplay'        => 'yes' === ( $settings['slider_autoplay'] ?? '' ),
			'autoplay_delay'         => max( 100, absint( $settings['autoplay_delay'] ?? 3000 ) ),
			'pause_on_hover'         => 'yes' === ( $settings['pause_on_hover'] ?? 'yes' ),
			'transition_speed'       => max( 100, absint( $settings['transition_speed'] ?? 500 ) ),
		];
	}

	/**
	 * Resolve visibility settings from widget display settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function resolve_visibility_settings( array $settings ): array {
		return [
			'show_image'       => 'yes' === ( $settings['show_image'] ?? 'yes' ),
			'show_icon'        => 'yes' === ( $settings['show_icon'] ?? 'yes' ),
			'show_description' => 'yes' === ( $settings['show_description'] ?? '' ),
		];
	}

	/**
	 * Convert shortcode grid/list output into Swiper slider markup.
	 *
	 * @param string              $content  Rendered shortcode HTML.
	 * @param array<string,mixed> $settings Slider settings.
	 * @return string
	 */
	protected function convert_to_slider( string $content, array $settings ): string {
		if ( '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}

		$slides_per_view_desktop = max( 1, min( 12, (int) ( $settings['slides_per_view'] ?? 3 ) ) );
		$slides_per_view_tablet  = max( 1, min( 12, (int) ( $settings['slides_per_view_tablet'] ?? 2 ) ) );
		$slides_per_view_mobile  = max( 1, min( 12, (int) ( $settings['slides_per_view_mobile'] ?? 1 ) ) );
		$show_arrows             = (bool) ( $settings['show_arrow_navigation'] ?? true );
		$show_dots               = (bool) ( $settings['show_dot_navigation'] ?? true );
		$autoplay                = (bool) ( $settings['slider_autoplay'] ?? false );
		$transition_speed        = max( 100, (int) ( $settings['transition_speed'] ?? 500 ) );
		$slider_effect           = sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) );
		$grid_rows               = max( 1, min( 6, (int) ( $settings['slider_grid_rows'] ?? 2 ) ) );
		$pause_on_hover          = (bool) ( $settings['pause_on_hover'] ?? true );
		$autoplay_delay          = max( 100, (int) ( $settings['autoplay_delay'] ?? 3000 ) );

		$valid_effects = [ 'slide', 'fade', 'grid', 'thumb', 'creative', 'cards', 'cube', 'flip', 'coverflow' ];
		if ( ! in_array( $slider_effect, $valid_effects, true ) ) {
			$slider_effect = 'slide';
		}

		$space_between    = 16;
		$breakpoints      = [
			0    => [ 'slidesPerView' => $slides_per_view_mobile, 'spaceBetween' => 10 ],
			768  => [ 'slidesPerView' => $slides_per_view_tablet, 'spaceBetween' => 14 ],
			1200 => [ 'slidesPerView' => $slides_per_view_desktop, 'spaceBetween' => 16 ],
		];
		$breakpoints_json = wp_json_encode( $breakpoints );

		$previous_libxml_state = libxml_use_internal_errors( true );

		$document     = new \DOMDocument( '1.0', 'UTF-8' );
		$wrapper_id   = 'directorist-taxonomy-slider-root-' . wp_rand( 1000, 999999 );
		$load_options = ( defined( 'LIBXML_HTML_NOIMPLIED' ) ? LIBXML_HTML_NOIMPLIED : 0 )
			| ( defined( 'LIBXML_HTML_NODEFDTD' ) ? LIBXML_HTML_NODEFDTD : 0 );

		$loaded = $document->loadHTML(
			sprintf( '<?xml encoding="utf-8" ?><div id="%1$s">%2$s</div>', esc_attr( $wrapper_id ), $content ),
			$load_options
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_state );

		if ( ! $loaded ) {
			return $content;
		}

		$xpath  = new \DOMXPath( $document );
		$tracks = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " taxonomy-category-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " taxonomy-location-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " atbdp-no-margin ")]'
		);

		if ( ! ( $tracks instanceof \DOMNodeList ) || 0 === $tracks->length ) {
			return $content;
		}

		$taxonomy_type = $this->get_taxonomy_slider_type();
		$slider_suffix = $taxonomy_type ? ( '--' . $taxonomy_type ) : '';

		$track_elements = [];
		foreach ( $tracks as $track ) {
			if ( $track instanceof \DOMElement ) {
				$track_elements[] = $track;
			}
		}

			foreach ( $track_elements as $track ) {
				$this->remove_dom_element_css_class( $track, 'directorist-row' );

				$track_class_name = trim( (string) $track->getAttribute( 'class' ) );

				$child_nodes = [];
			foreach ( $track->childNodes as $child_node ) {
				$child_nodes[] = $child_node;
			}

			$slide_nodes = [];
			$fixed_nodes = [];

			foreach ( $child_nodes as $child_node ) {
				if ( ! ( $child_node instanceof \DOMElement ) ) {
					continue;
				}

				$child_class = trim( (string) $child_node->getAttribute( 'class' ) );
				$is_column   = 1 === preg_match( '/(^|\s)(directorist-col-[^\s]+|col-[^\s]+)/', $child_class );
				$is_fixed    = $this->has_css_class( $track_class_name, 'atbdp-no-margin' )
					&& $this->has_css_class( $child_class, 'directorist-col-12' );

				if ( $is_column && ! $is_fixed ) {
					$slide_nodes[] = $child_node;
					continue;
				}

				$fixed_nodes[] = $child_node;
			}

			if ( empty( $slide_nodes ) ) {
				continue;
			}

			$parent_node = $track->parentNode;
			if ( ! $parent_node ) {
				continue;
			}

			$slider_class = 'directorist-swiper directorist-swiper-listing directorist-gbi-taxonomy-slider' . ( $slider_suffix ? ' directorist-gbi-taxonomy-slider' . $slider_suffix : '' );
			$slider_node  = $document->createElement( 'div' );
			$slider_node->setAttribute( 'class', $slider_class );
			$slider_node->setAttribute( 'data-gbi-slides-per-view', (string) $slides_per_view_desktop );
			$slider_node->setAttribute( 'data-gbi-space-between', (string) $space_between );
			$slider_node->setAttribute( 'data-gbi-show-arrows', $show_arrows ? 'true' : 'false' );
			$slider_node->setAttribute( 'data-gbi-show-dots', $show_dots ? 'true' : 'false' );
			$slider_node->setAttribute( 'data-gbi-transition-speed', (string) $transition_speed );
			$slider_node->setAttribute( 'data-gbi-autoplay', $autoplay ? 'true' : 'false' );
			$slider_node->setAttribute( 'data-gbi-effect', $slider_effect );
			$slider_node->setAttribute( 'data-gbi-grid-rows', (string) $grid_rows );
			$slider_node->setAttribute( 'data-gbi-pause-on-hover', $pause_on_hover ? 'true' : 'false' );
			$slider_node->setAttribute( 'data-gbi-delay', (string) $autoplay_delay );

			$slider_node->setAttribute( 'data-sw-items', (string) $slides_per_view_desktop );
			$slider_node->setAttribute( 'data-sw-margin', (string) $space_between );
			$slider_node->setAttribute( 'data-sw-loop', 'true' );
			$slider_node->setAttribute( 'data-sw-perslide', '1' );
			$slider_node->setAttribute( 'data-sw-speed', (string) $transition_speed );
			$slider_node->setAttribute( 'data-sw-delay', (string) $autoplay_delay );
			$slider_node->setAttribute( 'data-sw-autoplay', $autoplay ? 'true' : 'false' );
			$slider_node->setAttribute( 'data-sw-effect', $slider_effect );
			$slider_node->setAttribute( 'data-sw-grid-rows', (string) $grid_rows );
			$slider_node->setAttribute( 'data-sw-pause-on-hover', $pause_on_hover ? 'true' : 'false' );

			if ( is_string( $breakpoints_json ) && '' !== $breakpoints_json ) {
				$slider_node->setAttribute( 'data-gbi-breakpoints', $breakpoints_json );
				$slider_node->setAttribute( 'data-sw-responsive', $breakpoints_json );
			}

			$swiper_wrapper = $document->createElement( 'div' );
			$swiper_wrapper->setAttribute( 'class', 'swiper-wrapper directorist-gbi-taxonomy-slider__wrapper' );

			foreach ( $slide_nodes as $slide_node ) {
				$existing_class = trim( (string) $slide_node->getAttribute( 'class' ) );
				$slide_node->setAttribute( 'class', $this->append_css_class( $existing_class, 'swiper-slide' ) );
				$swiper_wrapper->appendChild( $slide_node );
			}

			$slider_node->appendChild( $swiper_wrapper );

			if ( $show_arrows ) {
				$navigation_node = $document->createElement( 'div' );
				$navigation_node->setAttribute( 'class', 'directorist-swiper__navigation directorist-gbi-taxonomy-slider__navigation' );

				$prev_node = $document->createElement( 'div' );
				$prev_node->setAttribute(
					'class',
					'directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--prev'
				);
				$prev_symbol = $document->createElement( 'span' );
				$prev_symbol->setAttribute( 'aria-hidden', 'true' );
				$prev_symbol->appendChild( $document->createTextNode( "\xE2\x80\xB9" ) );
				$prev_node->appendChild( $prev_symbol );

				$next_node = $document->createElement( 'div' );
				$next_node->setAttribute(
					'class',
					'directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--next'
				);
				$next_symbol = $document->createElement( 'span' );
				$next_symbol->setAttribute( 'aria-hidden', 'true' );
				$next_symbol->appendChild( $document->createTextNode( "\xE2\x80\xBA" ) );
				$next_node->appendChild( $next_symbol );

				$navigation_node->appendChild( $prev_node );
				$navigation_node->appendChild( $next_node );
				$slider_node->appendChild( $navigation_node );
			}

			if ( $show_dots ) {
				$pagination_node = $document->createElement( 'div' );
				$pagination_node->setAttribute(
					'class',
					'directorist-swiper__pagination directorist-swiper__pagination--listing directorist-gbi-taxonomy-slider__pagination'
				);
				$slider_node->appendChild( $pagination_node );
			}

			foreach ( $fixed_nodes as $fixed_node ) {
				$parent_node->insertBefore( $fixed_node, $track );
			}

			$parent_node->replaceChild( $slider_node, $track );
		}

		$root_wrapper = $document->getElementById( $wrapper_id );
		if ( ! ( $root_wrapper instanceof \DOMElement ) ) {
			return $content;
		}

		$updated_content = '';
		foreach ( $root_wrapper->childNodes as $child_node ) {
			$updated_content .= $document->saveHTML( $child_node );
		}

		return '' !== trim( $updated_content ) ? $updated_content : $content;
	}

	private function remove_taxonomy_wrapper_row_class( string $content ): string {
		if ( '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}

		$previous_libxml_state = libxml_use_internal_errors( true );
		$document             = new \DOMDocument( '1.0', 'UTF-8' );
		$wrapper_id           = 'directorist-taxonomy-row-cleanup-' . wp_rand( 1000, 999999 );
		$load_options         = ( defined( 'LIBXML_HTML_NOIMPLIED' ) ? LIBXML_HTML_NOIMPLIED : 0 )
			| ( defined( 'LIBXML_HTML_NODEFDTD' ) ? LIBXML_HTML_NODEFDTD : 0 );

		$loaded = $document->loadHTML(
			sprintf( '<?xml encoding="utf-8" ?><div id="%1$s">%2$s</div>', esc_attr( $wrapper_id ), $content ),
			$load_options
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_state );

		if ( ! $loaded ) {
			return $content;
		}

		$xpath  = new \DOMXPath( $document );
		$tracks = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " taxonomy-category-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " taxonomy-location-wrapper ")]'
		);

		if ( ! ( $tracks instanceof \DOMNodeList ) || 0 === $tracks->length ) {
			return $content;
		}

		foreach ( $tracks as $track ) {
			if ( $track instanceof \DOMElement ) {
				$this->remove_dom_element_css_class( $track, 'directorist-row' );
			}
		}

		$root_wrapper = $document->getElementById( $wrapper_id );
		if ( ! ( $root_wrapper instanceof \DOMElement ) ) {
			return $content;
		}

		$updated_content = '';
		foreach ( $root_wrapper->childNodes as $child_node ) {
			$updated_content .= $document->saveHTML( $child_node );
		}

		return '' !== trim( $updated_content ) ? $updated_content : $content;
	}

	private function remove_dom_element_css_class( \DOMElement $element, string $class_name ): void {
		$classes = preg_split( '/\s+/', trim( (string) $element->getAttribute( 'class' ) ) );
		$classes = is_array( $classes ) ? array_filter( $classes, static fn( $class ): bool => $class_name !== $class ) : [];

		$element->setAttribute( 'class', implode( ' ', $classes ) );
	}

	/**
	 * Check whether a class string contains a specific class.
	 *
	 * @param string $class_names Space-separated class names.
	 * @param string $expected    Class to search for.
	 * @return bool
	 */
	private function has_css_class( string $class_names, string $expected ): bool {
		$normalized = ' ' . preg_replace( '/\s+/', ' ', trim( $class_names ) ) . ' ';
		return false !== strpos( $normalized, ' ' . trim( $expected ) . ' ' );
	}

	/**
	 * Append a CSS class if not already present.
	 *
	 * @param string $class_names Existing class string.
	 * @param string $new_class   Class to append.
	 * @return string
	 */
	private function append_css_class( string $class_names, string $new_class ): string {
		$normalized = trim( preg_replace( '/\s+/', ' ', $class_names ) );
		if ( '' === $new_class || $this->has_css_class( $normalized, $new_class ) ) {
			return $normalized;
		}
		return '' === $normalized ? $new_class : $normalized . ' ' . $new_class;
	}
}
