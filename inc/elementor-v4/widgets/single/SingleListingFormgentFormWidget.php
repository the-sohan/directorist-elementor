<?php
/**
 * Single listing FormGent form widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractExtensionWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SingleListingFormgentFormWidget extends AbstractExtensionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_formgent_form';
	}

	public function get_title(): string {
		return __( 'FormGent Form', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-form-horizontal';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'formgent', 'form', 'extension' ] );
	}

	protected function get_extension_slug(): string {
		return 'formgent';
	}

	protected function register_widget_controls(): void {
		$form_options = DirectoristBridge::get_instance()->get_formgent_form_options();

		$this->start_controls_section(
			'section_formgent_content',
			[
				'label' => __( 'FormGent Form', 'directorist-elementor' ),
			]
		);

		$this->register_editor_preview_listing_control();

		if ( empty( $form_options ) ) {
			$this->add_control(
				'formgent_forms_notice',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'No FormGent forms were found. Create a form first to use this widget.', 'directorist-elementor' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				]
			);
		} else {
			$this->add_control(
				'form_id',
				[
					'label'       => __( 'Form', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'default'     => (string) ( array_key_first( $form_options ) ?? '' ),
					'options'     => $form_options,
					'label_block' => true,
					'description' => __( 'Select the FormGent form to render inside the single listing template.', 'directorist-elementor' ),
				]
			);
		}

		$this->end_controls_section();

		$field_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-single-formgent-form input:not([type="submit"])',
				'{{WRAPPER}} .directorist-single-formgent-form textarea',
				'{{WRAPPER}} .directorist-single-formgent-form select',
			]
		);

		$field_focus_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-single-formgent-form input:not([type="submit"]):focus',
				'{{WRAPPER}} .directorist-single-formgent-form textarea:focus',
				'{{WRAPPER}} .directorist-single-formgent-form select:focus',
			]
		);

		$submit_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-single-formgent-form button',
				'{{WRAPPER}} .directorist-single-formgent-form input[type="submit"]',
				'{{WRAPPER}} .directorist-single-formgent-form .formgent-btn',
			]
		);

		$submit_hover_selector = implode(
			', ',
			[
				'{{WRAPPER}} .directorist-single-formgent-form button:hover',
				'{{WRAPPER}} .directorist-single-formgent-form button:focus',
				'{{WRAPPER}} .directorist-single-formgent-form input[type="submit"]:hover',
				'{{WRAPPER}} .directorist-single-formgent-form input[type="submit"]:focus',
				'{{WRAPPER}} .directorist-single-formgent-form .formgent-btn:hover',
				'{{WRAPPER}} .directorist-single-formgent-form .formgent-btn:focus',
			]
		);

		$this->start_controls_section(
			'section_formgent_style_container',
			[
				'label' => __( 'Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'formgent_container_background',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'formgent_container_border',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'formgent_container_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form',
			]
		);

		$this->add_responsive_control(
			'formgent_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-single-formgent-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'formgent_container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-single-formgent-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_formgent_style_wrap',
			[
				'label' => __( 'Form Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'formgent_wrap_background',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form .formgent-form',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'formgent_wrap_border',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form .formgent-form',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'formgent_wrap_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form .formgent-form',
			]
		);

		$this->add_control(
			'formgent_wrap_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-single-formgent-form .formgent-form' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'formgent_wrap_typography',
				'selector' => '{{WRAPPER}} .directorist-single-formgent-form .formgent-form',
			]
		);

		$this->add_responsive_control(
			'formgent_wrap_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-single-formgent-form .formgent-form' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'formgent_wrap_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-single-formgent-form .formgent-form' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_formgent_style_fields',
			[
				'label' => __( 'Form Fields', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'formgent_fields_typography',
				'selector' => $field_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_formgent_field_states' );

		$this->start_controls_tab(
			'tab_formgent_field_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'formgent_field_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-single-formgent-form input:not([type="submit"])::placeholder' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-single-formgent-form textarea::placeholder'                  => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_field_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_field_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_formgent_field_focus',
			[
				'label' => __( 'Focus', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'formgent_field_focus_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_focus_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_field_focus_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_focus_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_field_focus_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_focus_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'formgent_field_padding',
			[
				'label'      => __( 'Field Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$field_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'formgent_field_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$field_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_formgent_style_submit',
			[
				'label' => __( 'Submit Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'formgent_submit_typography',
				'selector' => $submit_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_formgent_submit_states' );

		$this->start_controls_tab(
			'tab_formgent_submit_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'formgent_submit_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_submit_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_submit_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_formgent_submit_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'formgent_submit_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_hover_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_submit_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'formgent_submit_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'formgent_submit_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$submit_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'formgent_submit_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$submit_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render_extension_widget( int $listing_id ): void {
		$form_id = trim( (string) ( $this->get_settings_for_display()['form_id'] ?? '' ) );

		if ( '' === $form_id ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'Set a Form ID to render the FormGent form.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$output = DirectoristBridge::get_instance()->render_single_listing_field(
			$listing_id,
			[
				'widget_group' => 'other_widgets',
				'widget_name'  => 'formgent-form',
				'value'        => sanitize_text_field( $form_id ),
			]
		);

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'FormGent output is unavailable for the current preview listing.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
