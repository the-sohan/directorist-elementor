<?php
/**
 * Base preset widget for listing badge fields.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

abstract class AbstractBadgeFieldWidget extends AbstractPresetFieldWidget {

	/**
	 * Register shared badge style controls.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param string $badge_selector Badge selector.
	 * @param bool   $extended Include spacing and shadow controls.
	 * @return void
	 */
	protected function register_badge_style_controls( string $section_id, string $label, string $badge_selector, bool $extended = false ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $badge_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'badge_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $badge_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		if ( $extended ) {
			$this->add_responsive_control(
				'badge_gap',
				[
					'label'      => __( 'Content Gap', 'directorist-elementor' ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ 'px', 'em', 'rem' ],
					'range'      => [
						'px' => [
							'min' => 0,
							'max' => 80,
						],
					],
					'selectors'  => [
						'{{WRAPPER}} ' . $badge_selector => 'gap: {{SIZE}}{{UNIT}};',
					],
				]
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_typography',
				'selector' => '{{WRAPPER}} ' . $badge_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'badge_border',
				'selector' => '{{WRAPPER}} ' . $badge_selector,
			]
		);

		$this->add_responsive_control(
			'badge_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $badge_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $badge_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		if ( $extended ) {
			$this->add_responsive_control(
				'badge_margin',
				[
					'label'      => __( 'Margin', 'directorist-elementor' ),
					'type'       => Controls_Manager::DIMENSIONS,
					'size_units' => [ 'px', 'em', 'rem', '%' ],
					'selectors'  => [
						'{{WRAPPER}} ' . $badge_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			);

			$this->add_group_control(
				Group_Control_Box_Shadow::get_type(),
				[
					'name'     => 'badge_box_shadow',
					'selector' => '{{WRAPPER}} ' . $badge_selector,
				]
			);
		}

		$this->end_controls_section();
	}

	/**
	 * Register icon-only custom badge tooltip styles.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param string $tooltip_selector Tooltip selector.
	 * @return void
	 */
	protected function register_badge_tooltip_style_controls( string $section_id, string $label, string $tooltip_selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_tooltip_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $tooltip_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'badge_tooltip_background',
				'selector' => '{{WRAPPER}} ' . $tooltip_selector,
				'types'    => [ 'classic', 'gradient' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_tooltip_typography',
				'selector' => '{{WRAPPER}} ' . $tooltip_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'badge_tooltip_border',
				'selector' => '{{WRAPPER}} ' . $tooltip_selector,
			]
		);

		$this->add_responsive_control(
			'badge_tooltip_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $tooltip_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_tooltip_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $tooltip_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'badge_tooltip_box_shadow',
				'selector' => '{{WRAPPER}} ' . $tooltip_selector,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register independent custom badge label styles.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param string $text_selector Badge label selector.
	 * @return void
	 */
	protected function register_badge_text_style_controls( string $section_id, string $label, string $text_selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_label_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $text_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_label_alignment',
			[
				'label'   => __( 'Alignment', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left'   => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right'  => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} ' . $text_selector => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_label_opacity',
			[
				'label'     => __( 'Opacity', 'directorist-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} ' . $text_selector => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'badge_label_background',
				'selector' => '{{WRAPPER}} ' . $text_selector,
				'types'    => [ 'classic', 'gradient' ],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'badge_label_typography',
				'selector' => '{{WRAPPER}} ' . $text_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'badge_label_border',
				'selector' => '{{WRAPPER}} ' . $text_selector,
			]
		);

		$this->add_responsive_control(
			'badge_label_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $text_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_label_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $text_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_label_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $text_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'badge_label_box_shadow',
				'selector' => '{{WRAPPER}} ' . $text_selector,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register shared badge icon controls.
	 *
	 * @param string              $section_id Elementor section id.
	 * @param string              $label Section label.
	 * @param array<string,mixed> $default_icon Default Elementor icon.
	 * @return void
	 */
	protected function register_badge_icon_controls( string $section_id, string $label, array $default_icon ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
			]
		);

		$this->add_control(
			'badge_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => $default_icon,
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register shared badge icon style controls.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param string $badge_selector Badge selector.
	 * @return void
	 */
	protected function register_badge_icon_style_controls( string $section_id, string $label, string $badge_selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'badge_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $badge_selector . ' .directorist-elementor-listing-card-badge__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $badge_selector . ' .directorist-elementor-listing-card-badge__icon svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'badge_icon_size',
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
					'{{WRAPPER}} ' . $badge_selector . ' .directorist-elementor-listing-card-badge__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render a simple badge wrapper.
	 *
	 * @param string $text Badge text.
	 * @param string $wrapper_class Wrapper class.
	 * @param string $badge_class Badge class.
	 * @param string $icon_markup Optional icon markup.
	 * @return void
	 */
	protected function render_badge_markup( string $text, string $wrapper_class, string $badge_class, string $icon_markup = '' ): void {
		if ( '' === trim( $text ) ) {
			return;
		}

		$icon = '' !== $icon_markup
			? sprintf(
				'<span class="directorist-elementor-listing-card-badge__icon" aria-hidden="true">%s</span>',
				$icon_markup
			)
			: '';

		printf(
			'<div class="%1$s"><span class="%2$s">%3$s<span class="directorist-elementor-listing-card-badge__text">%4$s</span></span></div>',
			esc_attr( 'directorist-elementor-listing-card-badge ' . $wrapper_class ),
			esc_attr( $badge_class ),
			$icon, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon markup from get_badge_icon_markup().
			esc_html( $text )
		);
	}

	/**
	 * Build Elementor icon markup for a badge.
	 *
	 * @param array<string,mixed> $icon_settings Icon control settings.
	 * @param array<string,mixed> $fallback_icon Fallback icon control settings.
	 * @return string
	 */
	protected function get_badge_icon_markup( array $icon_settings, array $fallback_icon ): string {
		if ( empty( $icon_settings['value'] ) ) {
			$icon_settings = $fallback_icon;
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
