<?php
/**
 * Single listing review widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleSectionWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SingleListingReviewWidget extends AbstractSingleSectionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_review';
	}

	public function get_title(): string {
		return __( 'Review', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-star';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'review', 'comments' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_single_section_style_controls(
			'section_review_style',
			__( 'Review', 'directorist-elementor' ),
			'.directorist-review-container, .comments-area',
			'.directorist-review-content__header__title, .comments-title, #reply-title',
			'.directorist-review-container, .comments-area'
		);

		$review_field_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-review-submit__form input',
				'{{WRAPPER}} .directorist-review-submit__form textarea',
				'{{WRAPPER}} .directorist-review-submit__form select',
			]
		);

		$review_field_focus_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-review-submit__form input:focus',
				'{{WRAPPER}} .directorist-review-submit__form textarea:focus',
				'{{WRAPPER}} .directorist-review-submit__form select:focus',
			]
		);

		$review_submit_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-review-submit__form .directorist-btn',
				'{{WRAPPER}} .directorist-review-submit__form input[type="submit"]',
				'{{WRAPPER}} .directorist-review-submit__form button[type="submit"]',
			]
		);

		$review_submit_hover_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-review-submit__form .directorist-btn:hover',
				'{{WRAPPER}} .directorist-review-submit__form .directorist-btn:focus',
				'{{WRAPPER}} .directorist-review-submit__form input[type="submit"]:hover',
				'{{WRAPPER}} .directorist-review-submit__form input[type="submit"]:focus',
				'{{WRAPPER}} .directorist-review-submit__form button[type="submit"]:hover',
				'{{WRAPPER}} .directorist-review-submit__form button[type="submit"]:focus',
			]
		);

		$this->start_controls_section(
			'section_review_style_overview',
			[
				'label' => __( 'Review Overview', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'review_overview_background',
				'selector' => '{{WRAPPER}} .directorist-review-content__overview',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'review_overview_border',
				'selector' => '{{WRAPPER}} .directorist-review-content__overview',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'review_overview_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-review-content__overview',
			]
		);

		$this->add_control(
			'review_overview_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-review-content__overview' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'review_overview_typography',
				'selector' => '{{WRAPPER}} .directorist-review-content__overview',
			]
		);

		$this->add_responsive_control(
			'review_overview_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-review-content__overview' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_overview_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-review-content__overview' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_review_style_list',
			[
				'label' => __( 'Review List', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'review_list_background',
				'selector' => '{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'review_list_border',
				'selector' => '{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'review_list_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist',
			]
		);

		$this->add_control(
			'review_list_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'review_list_typography',
				'selector' => '{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist',
			]
		);

		$this->add_responsive_control(
			'review_list_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_list_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-review-content__reviews, {{WRAPPER}} .commentlist' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_review_style_fields',
			[
				'label' => __( 'Review Form Field', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'review_field_typography',
				'selector' => $review_field_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_review_field_states' );

		$this->start_controls_tab(
			'tab_review_field_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'review_field_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-review-submit__form input::placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-review-submit__form textarea::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_field_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_field_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_review_field_focus',
			[
				'label' => __( 'Focus', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'review_field_focus_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_focus_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_field_focus_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_focus_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_field_focus_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_field_focus_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'review_field_padding',
			[
				'label'      => __( 'Field Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$review_field_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_field_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$review_field_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_review_style_submit',
			[
				'label' => __( 'Review Submit Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'review_submit_typography',
				'selector' => $review_submit_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_review_submit_states' );

		$this->start_controls_tab(
			'tab_review_submit_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'review_submit_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_submit_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_submit_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_review_submit_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'review_submit_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_hover_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_submit_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'review_submit_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$review_submit_hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'review_submit_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$review_submit_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'review_submit_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$review_submit_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_section_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render listing reviews.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/section-review' );

		$section_data = wp_parse_args(
			$bridge->get_single_listing_section_data( $listing_id, 'review' ),
			[
				'type'        => 'other_widgets',
				'widget_name' => 'review',
				'label'       => __( 'Reviews', 'directorist-elementor' ),
			]
		);

		$output = $bridge->render_single_listing_section( $listing_id, $section_data );

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_section_placeholder(
					__( 'Review output is unavailable for the current preview listing.', 'directorist-elementor' )
				);
			}
			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Directorist section template output.
		echo $output;
	}
}
