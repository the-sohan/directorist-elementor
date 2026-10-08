<?php
/**
 * Search directory types widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Search;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Widgets\Loop\AbstractLoopUtilityWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SearchDirectoryTypesWidget extends AbstractLoopUtilityWidget {

	public function get_name(): string {
		return 'directorist_search_directory_types';
	}

	public function get_title(): string {
		return __( 'Directory Types', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-site-search';
	}

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_directory_types_wrapper_style',
			[
				'label' => __( 'Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'directory_types_alignment',
			[
				'label'   => __( 'Alignment', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav, {{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav ul' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_types_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav, {{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav ul' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'directory_types_wrapper_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'directory_types_wrapper_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'directory_types_wrapper_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types',
			]
		);

		$this->add_responsive_control(
			'directory_types_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_types_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_types_wrapper_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directory_types_item_style',
			[
				'label' => __( 'Directory Type', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'directory_type_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link',
			]
		);

		$this->start_controls_tabs( 'tabs_directory_type_states' );

		$this->start_controls_tab(
			'tab_directory_type_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'directory_type_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_directory_type_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'directory_type_hover_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link:hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_hover_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link:hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link:hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_directory_type_active',
			[
				'label' => __( 'Active', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'directory_type_active_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__list__current .directorist-type-nav__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_active_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__list__current .directorist-type-nav__link' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_active_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__list__current .directorist-type-nav__link' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'directory_type_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'directory_type_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link',
			]
		);

		$this->add_responsive_control(
			'directory_type_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_type_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_directory_types_icon_style',
			[
				'label' => __( 'Icon', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'directory_type_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_icon_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link:hover .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link:hover svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'directory_type_icon_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__list__current .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__list__current .directorist-type-nav__link svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_type_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 80,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link .directorist-icon-mask' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'directory_type_icon_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-directory-types .directorist-type-nav__link' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$rendered = false;

		$this->with_active_loop_context(
			function() use ( &$rendered ) {
				$controller = $this->get_cloned_loop_controller();

				if ( ! is_object( $controller ) || ! method_exists( $controller, 'directory_type_nav_template' ) ) {
					return;
				}

				$editor_attributes = $this->is_editor_context()
					? [ 'data-direl-editor-inert' => '1' ]
					: [];

				$attributes = $this->build_loop_utility_attributes(
					[
						'directorist-elementor-search-directory-types',
						'directorist-elementor-listings-search__nav',
						'directorist-elementor-listings-search-nav',
						'directorist-elementor-listings-archive-search-nav',
					],
					$editor_attributes
				);

				echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
				$controller->directory_type_nav_template();
				echo '</div>';

				$rendered = true;
			}
		);

		if ( ! $rendered ) {
			$this->render_loop_context_placeholder(
				__( 'Directory Types', 'directorist-elementor' ),
				__( 'Place this widget directly inside a Directorist listings loop to render directory type tabs.', 'directorist-elementor' )
			);
		}
	}
}
