<?php
/**
 * Single social info widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Extensions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleListingWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SocialFieldsWidget extends AbstractSingleListingWidget {

	/**
	 * Social info follows the preset-field grouping, even on single templates.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRESET;
	}

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_social_fields';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Social Info', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-share-arrow';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'social', 'social info' ] );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_social_content',
			[
				'label' => __( 'Content', 'directorist-elementor' ),
			]
		);

		$this->register_editor_preview_listing_control();

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Social Info', 'directorist-elementor' ),
				'placeholder' => __( 'Social Info', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'section_icon',
			[
				'label'       => __( 'Section Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'las la-share-alt',
				'placeholder' => 'las la-share-alt',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_container',
			[
				'label' => __( 'Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'social_container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'social_container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'social_container_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social',
			]
		);

		$this->add_responsive_control(
			'social_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_header',
			[
				'label' => __( 'Header', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'social_header_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__header',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'social_header_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__header',
			]
		);

		$this->add_responsive_control(
			'social_header_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_header_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_title',
			[
				'label' => __( 'Title', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'social_title_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__title' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__title *' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'social_title_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__title',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_header_icon',
			[
				'label' => __( 'Header Icon', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$header_icon_selector = '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__icon';

		$this->add_control(
			'social_header_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$header_icon_selector                                      => 'color: {{VALUE}};',
					$header_icon_selector . ' svg'                             => 'fill: {{VALUE}};',
					$header_icon_selector . ' .directorist-icon-mask::after'   => 'background-color: {{VALUE}};',
					$header_icon_selector . '.directorist-icon-mask::after'    => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_header_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 96,
					],
				],
				'selectors'  => [
					$header_icon_selector                                      => 'font-size: {{SIZE}}{{UNIT}};',
					$header_icon_selector . ' svg'                             => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					$header_icon_selector . ' .directorist-icon-mask::after'   => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					$header_icon_selector . '.directorist-icon-mask::after'    => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_content',
			[
				'label' => __( 'Content', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'social_content_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'social_content_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'social_content_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content',
			]
		);

		$this->add_control(
			'social_content_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'social_content_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content',
			]
		);

		$this->add_responsive_control(
			'social_content_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_content_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-elementor-extension__content' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_links_wrapper',
			[
				'label' => __( 'Social Links Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'social_links_wrapper_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'social_links_wrapper_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links',
			]
		);

		$this->add_responsive_control(
			'social_links_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_links_wrapper_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$action_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a',
			]
		);

		$action_hover_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:hover',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:focus',
			]
		);

		$action_icon_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a svg',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a i',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a .directorist-icon-mask::after',
			]
		);

		$action_hover_icon_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:hover svg',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:focus svg',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:hover i',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:focus i',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:hover .directorist-icon-mask::after',
				'{{WRAPPER}} .directorist-elementor-extension--social .directorist-social-links a:focus .directorist-icon-mask::after',
			]
		);

		$this->start_controls_section(
			'section_social_style_action',
			[
				'label' => __( 'Action / Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'social_action_typography',
				'selector' => $action_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_social_action_states' );

		$this->start_controls_tab(
			'tab_social_action_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'social_action_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_selector      => 'color: {{VALUE}};',
					$action_icon_selector => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_action_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_action_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_social_action_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'social_action_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_hover_selector      => 'color: {{VALUE}};',
					$action_hover_icon_selector => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_action_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_action_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'social_action_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$action_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_action_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$action_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style_icon',
			[
				'label' => __( 'Social Icon', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'social_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$action_icon_selector => 'color: {{VALUE}}; fill: {{VALUE}}; background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 96,
					],
				],
				'selectors'  => [
					$action_icon_selector => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render social fields only for the active single listing context.
	 *
	 * @return void
	 */
	protected function render(): void {
		if ( ! $this->is_supported_single_listing_context() ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'Social Fields is available only in single listing templates and cannot be rendered inside listing card or related listing compositions.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$editor_preview_listing_id = $this->resolve_editor_selected_listing_id();
		$listing_id                 = $editor_preview_listing_id > 0 ? $editor_preview_listing_id : $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'A preview listing is required to render social info.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$this->render_social_widget( $listing_id );
	}

	/**
	 * Check whether the widget is rendering the active single listing document.
	 *
	 * @return bool
	 */
	protected function is_supported_single_listing_context(): bool {
		$fallback_listing_id = $this->resolve_single_listing_fallback_id();
		$context_listing_id  = absint( $this->get_listing_context()['listing_id'] ?? 0 );

		if ( $fallback_listing_id > 0 ) {
			return $context_listing_id <= 0 || $context_listing_id === $fallback_listing_id;
		}

		return empty( $this->get_loop_context() ) && $context_listing_id <= 0;
	}

	/**
	 * Render social info output.
	 *
	 * @param int $listing_id Listing id.
	 * @return void
	 */
	protected function render_social_widget( int $listing_id ): void {
		$bridge              = DirectoristBridge::get_instance();
		$social_info_allowed = $bridge->is_preset_widget_allowed( $listing_id, 'social_info' );

		if ( ! $social_info_allowed ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'The current directory schema does not expose social info for this listing.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$bridge->ensure_single_listing_assets( 'single/fields/social_info' );

		$content = $bridge->render_single_listing_field(
			$listing_id,
			[
				'widget_group' => 'preset_widgets',
				'widget_name'  => 'social_info',
			]
		);

		if ( '' === $content ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'No social info was found for the current preview listing.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$settings      = $this->get_settings_for_display();
		$section_title = sanitize_text_field( (string) ( $settings['section_title'] ?? '' ) );
		$section_icon  = sanitize_text_field( (string) ( $settings['section_icon'] ?? '' ) );

		echo '<div class="directorist-elementor-extension directorist-elementor-extension--social">';

		if ( '' !== $section_title || '' !== $section_icon ) {
			echo '<header class="directorist-elementor-extension__header"><h3 class="directorist-elementor-extension__title">';

			if ( '' !== $section_icon ) {
				printf(
					'<span class="directorist-elementor-extension__icon"><i class="%s" aria-hidden="true"></i></span>',
					esc_attr( $section_icon )
				);
			}

			if ( '' !== $section_title ) {
				printf( '<span class="directorist-elementor-extension__title-text">%s</span>', esc_html( $section_title ) );
			}

			echo '</h3></header>';
		}

		echo '<div class="directorist-elementor-extension__content">';
		echo wp_kses_post( $content );
		echo '</div>';
		echo '</div>';
	}
}
