<?php
/**
 * Author profile message button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class AuthorProfileMessageButtonWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_message_button';
	}

	public function get_title(): string {
		return __( 'Author Message Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-comments';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_message_content',
			[
				'label' => __( 'Message Button', 'directorist-elementor' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_message_button_style',
			[
				'label' => __( 'Message Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_alignment_control( 'message_alignment', '.directorist-elementor-author-profile-message-button' );

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'message_button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button',
			]
		);

		$this->start_controls_tabs( 'tabs_message_button_states' );

		$this->start_controls_tab(
			'tab_message_button_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'message_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'message_button_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_message_button_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'message_button_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control:hover, {{WRAPPER}} .directorist-elementor-author-profile-message-button__control:focus, {{WRAPPER}} .directorist-elementor-author-profile-message-button button:hover, {{WRAPPER}} .directorist-elementor-author-profile-message-button button:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'message_button_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control:hover, {{WRAPPER}} .directorist-elementor-author-profile-message-button__control:focus, {{WRAPPER}} .directorist-elementor-author-profile-message-button button:hover, {{WRAPPER}} .directorist-elementor-author-profile-message-button button:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'message_button_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button',
			]
		);

		$this->add_responsive_control(
			'message_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'message_button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-message-button__control, {{WRAPPER}} .directorist-elementor-author-profile-message-button button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$this->render_author_field(
			'message-button',
			__( 'No message button is available for the current preview author.', 'directorist-elementor' )
		);
	}
}
