<?php
/**
 * Listing card custom button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class ListingCardCustomButtonWidget extends AbstractCustomFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_button';
	}

	public function get_title(): string {
		return __( 'Custom Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	protected function get_custom_field_widget_name(): string {
		return 'button';
	}

	protected function register_widget_controls(): void {
		$this->register_button_content_controls();
		$this->register_button_layout_style_controls();
		$this->register_button_icon_style_controls();
		$this->register_button_link_style_controls();
	}

	protected function register_button_content_controls(): void {
		$options = $this->get_custom_field_control_options();
		$notice  = $this->get_custom_field_unavailable_notice(
			__( 'No matching button custom fields were found across your directory types yet.', 'directorist-elementor' )
		);

		$this->start_controls_section(
			'section_custom_button_content',
			[
				'label' => __( 'Custom Button', 'directorist-elementor' ),
			]
		);

		$this->register_custom_field_preview_listing_control();

		if ( empty( $options ) ) {
			$this->add_control(
				'custom_field_notice',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => $notice,
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				]
			);
		} else {
			$this->add_control(
				'custom_field',
				[
					'label'       => __( 'Custom Field', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'default'     => '',
					'options'     => $options,
					'label_block' => true,
					'description' => __( 'Choose which button field this widget should render.', 'directorist-elementor' ),
				]
			);
		}

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-link',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_button_layout_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-custom-button';

		$this->start_controls_section(
			'section_custom_button_layout_style',
			[
				'label' => __( 'Layout', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'custom_button_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
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
					$selector => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_direction',
			[
				'label'     => __( 'Direction', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'row',
				'options'   => [
					'row'         => [
						'title' => __( 'Row', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'row-reverse' => [
						'title' => __( 'Reverse', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-left',
					],
					'column'      => [
						'title' => __( 'Column', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'selectors' => [
					$selector => 'flex-direction: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_button_icon_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-custom-button__icon';

		$this->start_controls_section(
			'section_custom_button_icon_style',
			[
				'label'     => __( 'Icon', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'custom_button_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
					$selector . ' svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors'  => [
					$selector => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'custom_button_icon_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'custom_button_icon_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_button_icon_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_icon_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_icon_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'custom_button_icon_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function register_button_link_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-custom-button__link';

		$this->start_controls_section(
			'section_custom_button_link_style',
			[
				'label' => __( 'Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'custom_button_typography',
				'selector' => $selector,
			]
		);

		$this->start_controls_tabs( 'tabs_custom_button_style' );

		$this->start_controls_tab(
			'tab_custom_button_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'custom_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'custom_button_background',
				'selector' => $selector,
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_custom_button_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'custom_button_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector . ':hover, ' . $selector . ':focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'custom_button_hover_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector . ':hover, ' . $selector . ':focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'custom_button_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_button_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'custom_button_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_custom_field_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template and choose a matching button custom field to render its button.', 'directorist-elementor' )
			);
			return;
		}

		$field_definition = $this->get_selected_custom_field_definition();

		if ( empty( $field_definition ) ) {
			$this->render_custom_field_context_placeholder(
				__( 'Choose a button custom field in the widget settings to render it inside the active listing card branch.', 'directorist-elementor' )
			);
			return;
		}

		$raw_value = DirectoristBridge::get_instance()->get_listing_custom_field_raw_value(
			$listing_id,
			(string) ( $field_definition['field_key'] ?? '' )
		);
		$value     = is_array( $raw_value ) ? $raw_value : maybe_unserialize( $raw_value );

		if ( ! is_array( $value ) ) {
			return;
		}

		$button_text = isset( $value['button_text'] ) && is_scalar( $value['button_text'] )
			? trim( (string) $value['button_text'] )
			: '';
		$button_url  = isset( $value['button_url_label'] ) && is_scalar( $value['button_url_label'] )
			? trim( (string) $value['button_url_label'] )
			: '';

		if ( '' === $button_text || '' === $button_url ) {
			return;
		}

		$button_style = ! empty( $field_definition['button_style'] )
			? sanitize_key( (string) $field_definition['button_style'] )
			: 'default';

		if ( ! in_array( $button_style, [ 'default', 'primary', 'secondary' ], true ) ) {
			$button_style = 'default';
		}

		$button_style_class = 'directorist-btn-default';

		if ( 'primary' === $button_style ) {
			$button_style_class = 'directorist-btn-primary';
		} elseif ( 'secondary' === $button_style ) {
			$button_style_class = 'directorist-btn-secondary';
		}

		$open_in_new_tab = false;

		if ( array_key_exists( 'open_in_new_tab', $field_definition ) ) {
			$open_in_new_tab = filter_var( $field_definition['open_in_new_tab'], FILTER_VALIDATE_BOOLEAN );
		}

		$settings    = $this->get_settings_for_display();
		$icon_markup = 'yes' === ( $settings['show_icon'] ?? 'yes' )
			? $this->get_custom_button_icon_markup( $settings['field_icon'] ?? [] )
			: '';

		echo '<div class="directorist-elementor-listing-card-custom-button">';

		if ( '' !== $icon_markup ) {
			echo '<span class="directorist-elementor-listing-card-custom-button__icon">' . $icon_markup . '</span>';
		}

		printf(
			'<a class="%1$s" href="%2$s"%3$s><span class="directorist-btn-text">%4$s</span></a>',
			esc_attr( 'directorist-elementor-listing-card-custom-button__link directorist-btn directorist-btn-xs ' . $button_style_class ),
			esc_url( $button_url ),
			$open_in_new_tab ? ' target="_blank" rel="noopener noreferrer"' : '',
			esc_html( $button_text )
		);

		echo '</div>';
	}

	protected function get_custom_button_icon_markup( $icon_settings ): string {
		if ( empty( $icon_settings ) || ! is_array( $icon_settings ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon(
			$icon_settings,
			[
				'aria-hidden' => 'true',
			]
		);

		return (string) ob_get_clean();
	}
}
