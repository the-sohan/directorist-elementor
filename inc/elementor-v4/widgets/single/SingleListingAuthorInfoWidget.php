<?php
/**
 * Single listing author info widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleSectionWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SingleListingAuthorInfoWidget extends AbstractSingleSectionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_author_info';
	}

	public function get_title(): string {
		return __( 'Author Info', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'author', 'profile' ] );
	}

	protected function register_widget_controls(): void {

		$this->start_controls_section(
			'section_author_info_content',
			[
				'label' => __( 'Author Info', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Author Info', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'display_email',
			[
				'label'   => __( 'Display Email', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'public',
				'options' => [
					'public'    => __( 'Public', 'directorist-elementor' ),
					'logged_in' => __( 'Logged In Users', 'directorist-elementor' ),
					'hidden'    => __( 'Hidden', 'directorist-elementor' ),
				],
			]
		);

		$this->end_controls_section();

		$this->register_single_section_style_controls(
			'section_author_info_style',
			__( 'Author Info', 'directorist-elementor' ),
			'.directorist-card-author-info',
			'.directorist-card-author-info .directorist-card__header__title',
			'.directorist-card-author-info .directorist-card__body, .directorist-card-author-info .directorist-single-author-info'
		);

		$meta_text_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-name',
				'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-name h4',
				'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-membership',
				'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-contact-info-text',
				'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-contact-info a',
			]
		);

		$social_link_selector = '{{WRAPPER}} .directorist-card-author-info .directorist-author-social__item a';
		$social_hover_selector = '{{WRAPPER}} .directorist-card-author-info .directorist-author-social__item a:hover, {{WRAPPER}} .directorist-card-author-info .directorist-author-social__item a:focus';
		$social_hover_icon_selector = '{{WRAPPER}} .directorist-card-author-info .directorist-author-social__item a:hover svg, {{WRAPPER}} .directorist-card-author-info .directorist-author-social__item a:focus svg';
		$button_selector = '{{WRAPPER}} .directorist-card-author-info .diretorist-view-profile-btn';
		$button_hover_selector = '{{WRAPPER}} .directorist-card-author-info .diretorist-view-profile-btn:hover, {{WRAPPER}} .directorist-card-author-info .diretorist-view-profile-btn:focus';

		$this->start_controls_section(
			'section_author_avatar_style',
			[
				'label' => __( 'Avatar', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'author_avatar_width',
			[
				'label'      => __( 'Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-avatar-inner img' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'author_avatar_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-avatar-inner img' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'author_avatar_border',
				'selector' => '{{WRAPPER}} .directorist-card-author-info .directorist-single-author-avatar-inner img',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'author_avatar_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-card-author-info .directorist-single-author-avatar-inner img',
			]
		);

		$this->add_responsive_control(
			'author_avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-card-author-info .directorist-single-author-avatar-inner img' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_author_meta_style',
			[
				'label' => __( 'Meta Text', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'author_meta_text_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$meta_text_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'author_meta_text_typography',
				'selector' => $meta_text_selector,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_author_social_style',
			[
				'label' => __( 'Social Link', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_author_social_states' );

		$this->start_controls_tab(
			'tab_author_social_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'author_social_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_link_selector           => 'color: {{VALUE}};',
					$social_link_selector . ' svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_social_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_link_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_social_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_link_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_author_social_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'author_social_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_hover_selector      => 'color: {{VALUE}};',
					$social_hover_icon_selector => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_social_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_social_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$social_hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'author_social_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$social_link_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'author_social_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$social_link_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_author_button_style',
			[
				'label' => __( 'Profile Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'author_button_typography',
				'selector' => $button_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_author_button_states' );

		$this->start_controls_tab(
			'tab_author_button_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'author_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_button_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_button_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_author_button_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'author_button_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_hover_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_button_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'author_button_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$button_hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'author_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$button_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'author_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$button_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_section_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render author information.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/section-author_info' );

		$settings     = $this->get_settings_for_display();
		$existing     = $bridge->get_single_listing_section_data( $listing_id, 'author_info' );
		$section_data = wp_parse_args(
			$existing,
			[
				'type'          => 'other_widgets',
				'widget_name'   => 'author_info',
				'label'         => __( 'Author Info', 'directorist-elementor' ),
				'display_email' => 'public',
			]
		);

		$section_data['label'] = '' !== trim( (string) ( $settings['section_title'] ?? '' ) )
			? sanitize_text_field( (string) $settings['section_title'] )
			: (string) $section_data['label'];

		$display_email = sanitize_key( (string) ( $settings['display_email'] ?? $section_data['display_email'] ?? 'public' ) );
		if ( in_array( $display_email, [ 'public', 'logged_in', 'hidden' ], true ) ) {
			$section_data['display_email'] = $display_email;
		}

		$output = $bridge->render_single_listing_section( $listing_id, $section_data );

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_section_placeholder(
					__( 'Author information is unavailable for the current preview listing.', 'directorist-elementor' )
				);
			}
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Directorist section template output.
		echo $output;
	}
}
