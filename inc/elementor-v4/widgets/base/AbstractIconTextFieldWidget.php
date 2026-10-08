<?php
/**
 * Base preset widget for icon/text listing fields.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

abstract class AbstractIconTextFieldWidget extends AbstractPresetFieldWidget {

	/**
	 * Allowed HTML tags for icon/text fields.
	 *
	 * @return array<int,string>
	 */
	protected function get_allowed_text_tags(): array {
		return [ 'div', 'span', 'p', 'h3', 'h4', 'h5', 'h6' ];
	}

	/**
	 * Sanitize a requested text wrapper tag.
	 *
	 * @param string $tag Requested tag.
	 * @return string
	 */
	protected function sanitize_text_tag( string $tag ): string {
		$tag = strtolower( $tag );

		return in_array( $tag, $this->get_allowed_text_tags(), true ) ? $tag : 'div';
	}

	/**
	 * Register shared content controls for icon/text preset widgets.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param array  $args Optional control defaults.
	 * @return void
	 */
	protected function register_icon_text_content_controls( string $section_id, string $label, array $args = [] ): void {
		$defaults = [
			'default_icon' => [
				'value'   => 'fas fa-circle',
				'library' => 'fa-solid',
			],
			'default_tag'       => 'div',
			'default_show_icon' => 'yes',
			'default_show_label' => '',
			'default_label_text' => '',
			'supports_link'      => false,
		];

		$args = array_merge( $defaults, $args );

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => $args['default_show_icon'],
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'            => __( 'Icon', 'directorist-elementor' ),
				'type'             => Controls_Manager::ICONS,
				'default'          => $args['default_icon'],
				'skin'             => 'inline',
				'label_block'      => false,
				'condition'        => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => $args['default_show_label'],
			]
		);

		$this->add_control(
			'label_text',
			[
				'label'     => __( 'Label Text', 'directorist-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => $args['default_label_text'],
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => $args['default_tag'],
				'options' => [
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
				],
			]
		);

		if ( ! empty( $args['supports_link'] ) ) {
			$this->add_control(
				'link_to_value',
				[
					'label'        => __( 'Link Value', 'directorist-elementor' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Yes', 'directorist-elementor' ),
					'label_off'    => __( 'No', 'directorist-elementor' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Register shared style controls for icon/text preset widgets.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param string $base_selector Widget base selector.
	 * @return void
	 */
	protected function register_icon_text_style_controls( string $section_id, string $label, string $base_selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'field_align',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end' => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} ' . $base_selector => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $base_selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__icon svg' => 'fill: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__label' => 'color: {{VALUE}};',
				],
				'condition' => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Value Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__value' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'value_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__link:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_typography',
				'selector' => '{{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__label, {{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__value, {{WRAPPER}} ' . $base_selector . ' .directorist-elementor-listing-card-field__link',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render an icon/text wrapper with optional link.
	 *
	 * @param string               $wrapper_class Wrapper class name.
	 * @param string               $value_class Value class name.
	 * @param string               $value Value text.
	 * @param string               $tag HTML tag.
	 * @param string               $link Optional URL.
	 * @param string               $link_class Optional link class.
	 * @param string               $icon_markup Optional icon markup.
	 * @param string               $label Optional label text.
	 * @param bool                 $allow_html Whether to allow HTML in value output.
	 * @return void
	 */
	protected function render_icon_text_markup(
		string $wrapper_class,
		string $value_class,
		string $value,
		string $tag = 'div',
		string $link = '',
		string $link_class = '',
		string $icon_markup = '',
		string $label = '',
		bool $allow_html = false
	): void {
		if ( '' === trim( $value ) ) {
			return;
		}

		$tag = $this->sanitize_text_tag( $tag );

		echo '<div class="' . esc_attr( 'directorist-elementor-listing-card-field ' . $wrapper_class ) . '">';

		if ( '' !== $icon_markup ) {
			echo '<span class="directorist-elementor-listing-card-field__icon">' . $icon_markup . '</span>';
		}

		if ( '' !== $label ) {
			printf(
				'<span class="directorist-elementor-listing-card-field__label">%s</span>',
				esc_html( $label )
			);
		}

		printf( '<%1$s class="%2$s">', esc_attr( $tag ), esc_attr( $value_class ) );

		if ( '' !== $link ) {
			printf(
				'<a class="%1$s" href="%2$s">%3$s</a>',
				esc_attr( '' !== $link_class ? $link_class : $value_class . '__link' ),
				esc_url( $link ),
				$allow_html ? wp_kses_post( $value ) : esc_html( $value )
			);
		} else {
			echo $allow_html ? wp_kses_post( $value ) : esc_html( $value );
		}

		printf( '</%s>', esc_attr( $tag ) );
		echo '</div>';
	}

	/**
	 * Build icon markup from Elementor icon settings.
	 *
	 * @param array|string $icon_settings Icon control settings.
	 * @return string
	 */
	protected function get_icon_markup( $icon_settings ): string {
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
