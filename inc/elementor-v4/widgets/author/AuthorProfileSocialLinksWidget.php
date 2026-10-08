<?php
/**
 * Author profile social links widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;

class AuthorProfileSocialLinksWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_social_links';
	}

	public function get_title(): string {
		return __( 'Author Social Links', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-social-icons';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_social_content',
			[
				'label' => __( 'Social Links', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'open_in_new_tab',
			[
				'label'        => __( 'Open in New Tab', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_social_style',
			[
				'label' => __( 'Social Links', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'social_alignment',
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
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->start_controls_tabs( 'tabs_social_states' );

		$this->start_controls_tab(
			'tab_social_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'social_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'social_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-social-links__link',
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_social_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'social_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link:hover, {{WRAPPER}} .directorist-elementor-author-profile-social-links__link:focus' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link:hover svg, {{WRAPPER}} .directorist-elementor-author-profile-social-links__link:focus svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'social_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link:hover, {{WRAPPER}} .directorist-elementor-author-profile-social-links__link:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'social_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 16,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__icon' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'social_item_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'social_item_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-social-links__link',
			]
		);

		$this->add_responsive_control(
			'social_item_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-social-links__link' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$this->render_author_field(
			'social-links',
			__( 'No social links are available for the current preview author.', 'directorist-elementor' )
		);
	}
}
