<?php
/**
 * Single listing contact owner form widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleSectionWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class SingleListingContactOwnerFormWidget extends AbstractSingleSectionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_contact_owner_form';
	}

	public function get_title(): string {
		return __( 'Contact Owner Form', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-mail';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'contact', 'owner', 'form' ] );
	}

	protected function register_widget_controls(): void {

		$this->start_controls_section(
			'section_contact_owner_content',
			[
				'label' => __( 'Contact Owner Form', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Contact Listings Owner Form', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'section_icon',
			[
				'label'       => __( 'Section Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'las la-phone',
				'placeholder' => 'las la-phone',
			]
		);

		$this->add_control(
			'contact_name_enable',
			[
				'label'        => __( 'Enable Name Field', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => '1',
				'default'      => '1',
			]
		);

		$this->add_control(
			'contact_name_placeholder',
			[
				'label'       => __( 'Name Placeholder', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Name', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'contact_name_enable' => '1',
				],
			]
		);

		$this->add_control(
			'contact_email_placeholder',
			[
				'label'       => __( 'Email Placeholder', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Email', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'contact_message_placeholder',
			[
				'label'       => __( 'Message Placeholder', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Message...', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->end_controls_section();

		$this->register_single_section_style_controls(
			'section_contact_owner_style',
			__( 'Contact Owner Form', 'directorist-elementor' ),
			'.directorist-card-contact-owner',
			'.directorist-card-contact-owner .directorist-card__header__title',
			'.directorist-card-contact-owner .directorist-card__body'
		);

		$field_selector = '{{WRAPPER}} .directorist-card-contact-owner .directorist-form-element';
		$field_focus_selector = '{{WRAPPER}} .directorist-card-contact-owner .directorist-form-element:focus';
		$submit_selector = '{{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form button[type="submit"], {{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form .directorist-btn';
		$submit_hover_selector = '{{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form button[type="submit"]:hover, {{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form button[type="submit"]:focus, {{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form .directorist-btn:hover, {{WRAPPER}} .directorist-card-contact-owner .directorist-contact-owner-form .directorist-btn:focus';

		$this->start_controls_section(
			'section_contact_owner_style_fields',
			[
				'label' => __( 'Form Field', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'contact_owner_field_typography',
				'selector' => $field_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_contact_owner_field_states' );

		$this->start_controls_tab(
			'tab_contact_owner_field_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'contact_owner_field_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_field_placeholder_color',
			[
				'label'     => __( 'Placeholder Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-card-contact-owner input.directorist-form-element::placeholder'    => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-card-contact-owner textarea.directorist-form-element::placeholder' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_field_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_field_border_color',
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
			'tab_contact_owner_field_focus',
			[
				'label' => __( 'Focus', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'contact_owner_field_focus_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_focus_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_field_focus_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$field_focus_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_field_focus_border_color',
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
			'contact_owner_field_padding',
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
			'contact_owner_field_border_radius',
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
			'section_contact_owner_style_submit',
			[
				'label' => __( 'Submit Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'contact_owner_submit_typography',
				'selector' => $submit_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_contact_owner_submit_states' );

		$this->start_controls_tab(
			'tab_contact_owner_submit_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'contact_owner_submit_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_submit_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_submit_border_color',
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
			'tab_contact_owner_submit_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'contact_owner_submit_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_hover_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_submit_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$submit_hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'contact_owner_submit_hover_border_color',
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
			'contact_owner_submit_padding',
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
			'contact_owner_submit_border_radius',
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

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_section_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the contact owner form.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/section-contact_listings_owner' );

		$settings     = $this->get_settings_for_display();
		$existing     = $bridge->get_single_listing_section_data( $listing_id, 'contact_listings_owner' );
		$section_data = wp_parse_args(
			$existing,
			[
				'type'        => 'other_widgets',
				'widget_name' => 'contact_listings_owner',
				'label'       => __( 'Contact Listings Owner Form', 'directorist-elementor' ),
				'icon'        => 'las la-phone',
				'fields'      => [
					'contact_name' => [
						'widget_child_name' => 'contact_name',
						'enable'            => true,
						'placeholder'       => __( 'Name', 'directorist-elementor' ),
					],
					'contact_email' => [
						'widget_child_name' => 'contact_email',
						'placeholder'       => __( 'Email', 'directorist-elementor' ),
					],
					'contact_message' => [
						'widget_child_name' => 'contact_message',
						'placeholder'       => __( 'Message...', 'directorist-elementor' ),
					],
				],
			]
		);

		$section_data['label'] = '' !== trim( (string) ( $settings['section_title'] ?? '' ) )
			? sanitize_text_field( (string) $settings['section_title'] )
			: (string) $section_data['label'];
		$section_data['icon'] = '' !== trim( (string) ( $settings['section_icon'] ?? '' ) )
			? sanitize_text_field( (string) $settings['section_icon'] )
			: (string) $section_data['icon'];

		if ( empty( $section_data['fields'] ) || ! is_array( $section_data['fields'] ) ) {
			$section_data['fields'] = [];
		}

		$this->upsert_contact_field(
			$section_data['fields'],
			'contact_name',
			[
				'enable'      => ! empty( $settings['contact_name_enable'] ),
				'placeholder' => sanitize_text_field( (string) ( $settings['contact_name_placeholder'] ?? __( 'Name', 'directorist-elementor' ) ) ),
			]
		);

		$this->upsert_contact_field(
			$section_data['fields'],
			'contact_email',
			[
				'placeholder' => sanitize_text_field( (string) ( $settings['contact_email_placeholder'] ?? __( 'Email', 'directorist-elementor' ) ) ),
			]
		);

		$this->upsert_contact_field(
			$section_data['fields'],
			'contact_message',
			[
				'placeholder' => sanitize_text_field( (string) ( $settings['contact_message_placeholder'] ?? __( 'Message...', 'directorist-elementor' ) ) ),
			]
		);

		$output = $bridge->render_single_listing_section( $listing_id, $section_data );

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_section_placeholder(
					__( 'Contact form is unavailable for the current preview listing.', 'directorist-elementor' )
				);
			}
			return;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Upsert a contact owner field config.
	 *
	 * @param array<int|string,array<string,mixed>> $fields Contact field collection.
	 * @param string                                $widget_child_name Field key.
	 * @param array<string,mixed>                   $overrides Overrides.
	 * @return void
	 */
	protected function upsert_contact_field( array &$fields, string $widget_child_name, array $overrides ): void {
		$field_index = null;

		foreach ( $fields as $index => $field_data ) {
			if ( ! is_array( $field_data ) ) {
				continue;
			}

			if ( $widget_child_name === (string) ( $field_data['widget_child_name'] ?? '' ) ) {
				$field_index = $index;
				break;
			}
		}

		if ( null === $field_index ) {
			$field_index = $widget_child_name;
			$fields[ $field_index ] = [
				'widget_child_name' => $widget_child_name,
			];
		}

		$fields[ $field_index ] = array_merge( $fields[ $field_index ], $overrides );
	}
}
