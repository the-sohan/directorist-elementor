<?php
/**
 * Listings header widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class ListingsHeaderWidget extends AbstractLoopUtilityWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listings_header';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listings Header', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-posts-ticker';
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_header_content',
			[
				'label' => __( 'Header', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_listings_count',
			[
				'label'        => __( 'Show Listings Count', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'listings_count_text',
			[
				'label'       => __( 'Listings Count Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Listings Found', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_filter_button',
			[
				'label'        => __( 'Show Filter Button', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_view_switcher',
			[
				'label'        => __( 'Show View Switcher', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'view_type',
			[
				'label'       => __( 'Available Views', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [ 'grid', 'list', 'map' ],
				'options'     => [
					'grid' => __( 'Grid', 'directorist-elementor' ),
					'list' => __( 'List', 'directorist-elementor' ),
					'map'  => __( 'Map', 'directorist-elementor' ),
				],
				'condition'   => [
					'show_view_switcher' => 'yes',
				],
			]
		);

		$this->add_control(
			'enable_sorting',
			[
				'label'        => __( 'Enable Sorting', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'sort_by_label',
			[
				'label'       => __( 'Sort By Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => __( 'Sort By', 'directorist-elementor' ),
				'condition'   => [
					'enable_sorting' => 'yes',
				],
			]
		);

		$this->add_control(
			'sort_by',
			[
				'label'       => __( 'Sort Options', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => [ 'a_z', 'z_a', 'latest', 'oldest', 'popular', 'price_low_high', 'price_high_low', 'random' ],
				'options'     => [
					'a_z'             => __( 'Title A-Z', 'directorist-elementor' ),
					'z_a'             => __( 'Title Z-A', 'directorist-elementor' ),
					'latest'          => __( 'Latest', 'directorist-elementor' ),
					'oldest'          => __( 'Oldest', 'directorist-elementor' ),
					'popular'         => __( 'Popular', 'directorist-elementor' ),
					'price_low_high'  => __( 'Price Low-High', 'directorist-elementor' ),
					'price_high_low'  => __( 'Price High-Low', 'directorist-elementor' ),
					'random'          => __( 'Random', 'directorist-elementor' ),
				],
				'condition'   => [
					'enable_sorting' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_container',
			[
				'label' => __( 'Header Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_container_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header',
			]
		);

		$this->add_responsive_control(
			'header_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_shell',
			[
				'label' => __( 'Header Bar', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_shell_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-bar, {{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_shell_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-bar, {{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_shell_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-bar, {{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header',
			]
		);

		$this->add_responsive_control(
			'header_shell_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-bar'      => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_shell_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-bar'      => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_left_area',
			[
				'label' => __( 'Left Area', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_left_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__left',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_left_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__left',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_left_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__left',
			]
		);

		$this->add_responsive_control(
			'header_left_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__left' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_left_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__left' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_right_area',
			[
				'label' => __( 'Right Area', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_right_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__right',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_right_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__right',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_right_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__right',
			]
		);

		$this->add_responsive_control(
			'header_right_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__right' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_right_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-listings-header__right' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_count',
			[
				'label' => __( 'Listings Count', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_count_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-found-title',
			]
		);

		$this->add_control(
			'header_count_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-header-found-title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_toolbar',
			[
				'label' => __( 'View Toggle', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_toolbar_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item, {{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item a',
			]
		);

		$this->start_controls_tabs( 'tabs_header_toolbar_states' );

		$this->start_controls_tab(
			'tab_header_toolbar_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_toolbar_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item'                       => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item a'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_header_toolbar_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_toolbar_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:hover'                       => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:focus'                       => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:hover a'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:focus a'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:hover .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:focus .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_header_toolbar_active',
			[
				'label' => __( 'Active', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_toolbar_active_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item.active'                       => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item.active a'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item.active .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_active_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item.active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_toolbar_active_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item.active' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_toolbar_border_group',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item',
				'exclude'  => [ 'color' ],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_toolbar_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item',
			]
		);

		$this->add_responsive_control(
			'header_toolbar_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item .directorist-icon-mask:after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item i:not(.directorist-icon-mask)' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_toolbar_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_toolbar_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-viewas__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_filter_button',
			[
				'label' => __( 'Filter Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_filter_button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn, {{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle',
			]
		);

		$this->start_controls_tabs( 'tabs_header_filter_button_states' );

		$this->start_controls_tab(
			'tab_header_filter_button_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_filter_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn'                                 => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn .directorist-icon-mask:after'    => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_filter_button_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn'             => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'header_filter_button_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn'             => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_header_filter_button_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_filter_button_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:hover'                                 => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:focus'                                 => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:hover'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:focus'                     => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:hover .directorist-icon-mask:after'    => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:focus .directorist-icon-mask:after'    => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:hover .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:focus .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'header_filter_button_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:hover'             => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:focus'             => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:hover' => 'background-color: {{VALUE}} !important;',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:focus' => 'background-color: {{VALUE}} !important;',
				],
			]
		);

		$this->add_control(
			'header_filter_button_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:hover'             => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn:focus'             => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$filter_button_selector = '{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn, {{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle';

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_filter_button_border_group',
				'selector' => $filter_button_selector,
				'exclude'  => [ 'color' ],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_filter_button_shadow',
				'selector' => $filter_button_selector,
			]
		);

		$this->add_responsive_control(
			'header_filter_button_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn .directorist-icon-mask:after, {{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle .directorist-icon-mask:after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn svg, {{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn i:not(.directorist-icon-mask), {{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle i:not(.directorist-icon-mask)' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_filter_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn'             => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_filter_button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-filter-btn'             => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-archive-sidebar-toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_sort_toggle',
			[
				'label' => __( 'Sort Dropdown', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_sort_toggle_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_sort_toggle_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_sort_toggle_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_sort_toggle_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle, {{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__label',
			]
		);

		$this->start_controls_tabs( 'tabs_header_sort_toggle_states' );

		$this->start_controls_tab(
			'tab_header_sort_toggle_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_sort_toggle_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__label'  => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_header_sort_toggle_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_sort_toggle_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle:hover .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle:focus .directorist-icon-mask:after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'header_sort_toggle_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'header_sort_toggle_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_header_style_dropdown',
			[
				'label' => __( 'Sort Dropdown Menu', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'header_dropdown_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_dropdown_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_dropdown_link_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links__single',
			]
		);

		$this->start_controls_tabs( 'tabs_header_dropdown_links' );

		$this->start_controls_tab(
			'tab_header_dropdown_link_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_dropdown_link_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links__single' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_header_dropdown_link_hover',
			[
				'label' => __( 'Hover / Active', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header_dropdown_link_hover_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links__single:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links__single.active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'header_dropdown_padding',
			[
				'label'      => __( 'Item Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-header .directorist-sortby-dropdown .directorist-dropdown__links__single' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the header widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$rendered   = false;

		$this->with_active_loop_context(
			function() use ( $settings, &$rendered ) {
				$controller              = $this->get_cloned_loop_controller();
				$loop_context            = $this->get_loop_context();
				$display_mode            = LoopRenderService::get_instance()->normalize_display_mode( $loop_context['display_mode'] ?? 'default' );
				$utility_state           = $this->resolve_header_utility_state();
				$has_filters_widget      = ! empty( $utility_state['has_filters_widget'] );
				$filters_mobile_floating = ! empty( $utility_state['filters_mobile_floating'] );
				$show_filter_button      = $has_filters_widget && $filters_mobile_floating && 'yes' === (string) ( $settings['show_filter_button'] ?? 'yes' );

				if ( ! $controller || ! method_exists( $controller, 'header_bar_template' ) ) {
					return;
				}

				if ( 'yes' !== (string) ( $settings['show_listings_count'] ?? 'yes' ) ) {
					$controller->header_title = '';
				} elseif ( '' !== trim( (string) ( $settings['listings_count_text'] ?? '' ) ) ) {
					$controller->header_title = (string) $settings['listings_count_text'];
				}

				$controller->display_viewas_dropdown = 'yes' === (string) ( $settings['show_view_switcher'] ?? 'yes' );
				$controller->display_sortby_dropdown = 'yes' === (string) ( $settings['enable_sorting'] ?? 'yes' );
				$controller->has_filters_button      = false;
				$controller->sidebar                 = 'no_sidebar';
				$controller->advanced_filter         = false;
				$controller->listing_filters_button  = '';
				$controller->options['listing_filters_button'] = '';

				if ( 'map_list' === $display_mode && 'map' === sanitize_key( (string) ( $controller->view ?? '' ) ) ) {
					$controller->view = 'grid';
					if ( is_array( $controller->atts ?? null ) ) {
						$controller->atts['view'] = 'grid';
					}
					if ( is_array( $controller->params ?? null ) ) {
						$controller->params['view'] = 'grid';
					}
				}

				if ( $show_filter_button ) {
					$controller->advanced_filter         = true;
					$controller->has_filters_button      = true;
					$controller->listing_filters_button  = 'yes';
					$controller->options['listing_filters_button'] = 'yes';

					if ( $filters_mobile_floating && $controller->display_viewas_dropdown ) {
						$controller->sidebar = 'left_sidebar';
					}
				}

				if ( $controller->display_viewas_dropdown ) {
					$available_views = 'map_list' === $display_mode ? [ 'grid', 'list' ] : [ 'grid', 'list', 'map' ];
					$view_types = array_values(
						array_filter(
							array_map(
								static fn( $view_type ): string => sanitize_key( (string) $view_type ),
								(array) ( $settings['view_type'] ?? [ 'grid', 'list', 'map' ] )
							),
							static fn( string $view_type ): bool => in_array( $view_type, $available_views, true )
						)
					);

					if ( empty( $view_types ) ) {
						$view_types = $available_views;
					}

					$view_labels = [
						'grid' => __( 'Grid', 'directorist-elementor' ),
						'list' => __( 'List', 'directorist-elementor' ),
						'map'  => __( 'Map', 'directorist-elementor' ),
					];

					$controller->views = [];

					foreach ( $view_types as $view_type ) {
						$controller->views[ $view_type ] = $view_labels[ $view_type ] ?? ucfirst( $view_type );
					}
				} else {
					$controller->views = [];
				}

				if ( $controller->display_sortby_dropdown ) {
					$sort_items = array_values(
						array_filter(
							array_map(
								static fn( $sort_key ): string => sanitize_key( (string) $sort_key ),
								(array) ( $settings['sort_by'] ?? [] )
							)
						)
					);

					if ( ! empty( $sort_items ) ) {
						$controller->sort_by_items = $sort_items;
					}

					if ( '' !== trim( (string) ( $settings['sort_by_label'] ?? '' ) ) ) {
						$controller->sort_by_text = (string) $settings['sort_by_label'];
					}
				}

				$header_classes = [
					'directorist-elementor-listings-header',
					'directorist-elementor-listings-archive-header',
				];

				if ( $show_filter_button ) {
					$header_classes[] = 'directorist-elementor-listings-header--floating-filters';
				}

				$attributes = $this->build_loop_utility_attributes( $header_classes );

				echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
				$controller->header_bar_template();
				echo '</div>';

				$rendered = true;
			}
		);

		if ( ! $rendered ) {
			$this->render_loop_context_placeholder(
				__( 'Listings Header', 'directorist-elementor' ),
				__( 'Place this widget inside Listings Loop to render listing count, view switcher, and sorting.', 'directorist-elementor' )
			);
		}
	}

	/**
	 * Resolve loop utility state for header rendering.
	 *
	 * Elementor can render the selected header widget with a loop context that
	 * lacks the sibling utility summary. In editor renders, recover that summary
	 * from the nearest ancestor Listings Loop so the filter button follows the
	 * actual composed filters widget.
	 *
	 * @return array<string,bool>
	 */
	protected function resolve_header_utility_state(): array {
		$loop_context  = $this->get_loop_context();
		$utility_state = is_array( $loop_context['utility_state'] ?? null ) ? (array) $loop_context['utility_state'] : [];

		if ( ! empty( $utility_state['has_filters_widget'] ) || ! $this->is_editor_context() ) {
			return $utility_state;
		}

		$loop_element = $this->resolve_ancestor_loop_element();
		if ( ! is_array( $loop_element ) ) {
			return $utility_state;
		}

		$resolved_state = LoopRenderService::get_instance()->extract_utility_state_from_elements(
			is_array( $loop_element['elements'] ?? null ) ? (array) $loop_element['elements'] : []
		);

		return array_merge( $utility_state, $resolved_state );
	}
}
