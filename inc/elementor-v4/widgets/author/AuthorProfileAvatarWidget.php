<?php
/**
 * Author profile avatar widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class AuthorProfileAvatarWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_avatar';
	}

	public function get_title(): string {
		return __( 'Author Avatar', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_avatar_content',
			[
				'label' => __( 'Avatar', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'link',
			[
				'label'        => __( 'Link to Profile', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_avatar_style',
			[
				'label' => __( 'Avatar', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_alignment_control( 'avatar_alignment', '.directorist-elementor-author-profile-avatar', 'text-align' );

		$this->add_responsive_control(
			'avatar_width',
			[
				'label'      => __( 'Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar' => 'width: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar__image' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avatar_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar' => 'height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar__image' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'avatar_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-avatar__image',
			]
		);

		$this->add_responsive_control(
			'avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar, {{WRAPPER}} .directorist-elementor-author-profile-avatar__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'avatar_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile-avatar__image',
			]
		);

		$this->add_responsive_control(
			'avatar_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile-avatar' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$this->render_author_field(
			'avatar',
			__( 'The current preview author has no avatar.', 'directorist-elementor' )
		);
	}
}
