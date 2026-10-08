<?php
/**
 * Base extension-aware single listing widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\Services\ExtensionStatusService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class AbstractExtensionWidget extends AbstractSingleListingWidget {

	/**
	 * Get the required extension slug.
	 *
	 * @return string
	 */
	abstract protected function get_extension_slug(): string;

	/**
	 * Render the active widget output.
	 *
	 * @param int $listing_id Listing id.
	 * @return void
	 */
	abstract protected function render_extension_widget( int $listing_id ): void;

	/**
	 * Resolve listing id, honoring an editor-only preview listing when present.
	 *
	 * @return int
	 */
	protected function resolve_listing_id(): int {
		$editor_preview_listing_id = $this->resolve_editor_selected_listing_id();

		if ( $editor_preview_listing_id > 0 ) {
			return $editor_preview_listing_id;
		}

		return parent::resolve_listing_id();
	}

	/**
	 * Render extension widget with availability gating.
	 *
	 * @return void
	 */
	protected function render(): void {
		if ( ! $this->is_extension_active() ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						sprintf(
							/* translators: %s: Extension slug. */
							__( 'The required Directorist extension is inactive: %s.', 'directorist-elementor' ),
							$this->get_extension_slug()
						)
					)
				);
			}

			return;
		}

		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'A preview listing is required to render this extension widget.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$this->render_extension_widget( $listing_id );
	}

	/**
	 * Check whether the extension is active.
	 *
	 * @return bool
	 */
	protected function is_extension_active(): bool {
		return ExtensionStatusService::get_instance()->is_extension_active( $this->get_extension_slug() );
	}

	/**
	 * Normalize an Elementor selector for this widget wrapper.
	 *
	 * @param string $selector CSS selector.
	 * @return string
	 */
	protected function extension_style_selector( string $selector ): string {
		$selectors = array_filter( array_map( 'trim', explode( ',', $selector ) ) );

		$selectors = array_map(
			static function ( string $single_selector ): string {
				return false !== strpos( $single_selector, '{{WRAPPER}}' ) ? $single_selector : '{{WRAPPER}} ' . $single_selector;
			},
			$selectors
		);

		return implode( ', ', $selectors );
	}

	/**
	 * Append a selector suffix to every selector in a comma-separated list.
	 *
	 * @param string $selector CSS selector.
	 * @param string $suffix Selector suffix.
	 * @return string
	 */
	protected function extension_style_selector_append( string $selector, string $suffix ): string {
		$selector_parts = array_filter( array_map( 'trim', explode( ',', $this->extension_style_selector( $selector ) ) ) );

		return implode(
			', ',
			array_map(
				static function ( string $single_selector ) use ( $suffix ): string {
					return $single_selector . $suffix;
				},
				$selector_parts
			)
		);
	}

	/**
	 * Register box-level controls for a rendered extension element.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @param string $prefix Control prefix.
	 * @return void
	 */
	protected function register_extension_box_style_section( string $section_id, string $label, string $selector, string $prefix ): void {
		if ( '' === $selector ) {
			return;
		}

		$selector      = $this->extension_style_selector( $selector );
		$svg_selector  = $this->extension_style_selector_append( $selector, ' svg' );
		$mask_selector = implode(
			', ',
			[
				$this->extension_style_selector_append( $selector, ' .directorist-icon-mask::after' ),
				$this->extension_style_selector_append( $selector, '.directorist-icon-mask::after' ),
			]
		);

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => $prefix . '_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => $prefix . '_border',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'section' === $prefix ? 'section_box_shadow' : $prefix . '_shadow',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			$prefix . '_padding',
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
			$prefix . '_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register text controls for a rendered extension element.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @param string $prefix Control prefix.
	 * @param string $color_control_id Optional legacy color control id.
	 * @param string $typography_control_id Optional legacy typography control id.
	 * @return void
	 */
	protected function register_extension_text_style_section(
		string $section_id,
		string $label,
		string $selector,
		string $prefix,
		string $color_control_id = '',
		string $typography_control_id = ''
	): void {
		if ( '' === $selector ) {
			return;
		}

		$selector = $this->extension_style_selector( $selector );
		$svg_selector  = $this->extension_style_selector_append( $selector, ' svg' );
		$mask_selector = implode(
			', ',
			[
				$this->extension_style_selector_append( $selector, ' .directorist-icon-mask::after' ),
				$this->extension_style_selector_append( $selector, '.directorist-icon-mask::after' ),
			]
		);

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'' !== $color_control_id ? $color_control_id : $prefix . '_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => '' !== $typography_control_id ? $typography_control_id : $prefix . '_typography',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register icon controls for a rendered extension element.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @param string $prefix Control prefix.
	 * @param string $color_control_id Optional legacy color control id.
	 * @return void
	 */
	protected function register_extension_icon_style_section( string $section_id, string $label, string $selector, string $prefix, string $color_control_id = '' ): void {
		if ( '' === $selector ) {
			return;
		}

		$selector      = $this->extension_style_selector( $selector );
		$svg_selector  = $this->extension_style_selector_append( $selector, ' svg' );
		$mask_selector = implode(
			', ',
			[
				$this->extension_style_selector_append( $selector, ' .directorist-icon-mask::after' ),
				$this->extension_style_selector_append( $selector, '.directorist-icon-mask::after' ),
			]
		);

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'' !== $color_control_id ? $color_control_id : $prefix . '_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector      => 'color: {{VALUE}};',
					$svg_selector  => 'fill: {{VALUE}};',
					$mask_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			$prefix . '_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 96,
					],
				],
				'selectors'  => [
					$selector      => 'font-size: {{SIZE}}{{UNIT}};',
					$svg_selector  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					$mask_selector => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register button/action controls for a rendered extension element.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @param string $prefix Control prefix.
	 * @param string $hover_selector Optional hover selector.
	 * @return void
	 */
	protected function register_extension_button_style_section( string $section_id, string $label, string $selector, string $prefix, string $hover_selector = '' ): void {
		if ( '' === $selector ) {
			return;
		}

		$selector            = $this->extension_style_selector( $selector );
		$hover_selector      = '' !== $hover_selector
			? $this->extension_style_selector( $hover_selector )
			: $this->extension_style_selector_append( $selector, ':hover' ) . ', ' . $this->extension_style_selector_append( $selector, ':focus' );
		$svg_selector        = $this->extension_style_selector_append( $selector, ' svg' );
		$mask_selector       = $this->extension_style_selector_append( $selector, ' .directorist-icon-mask::after' );
		$hover_svg_selector  = $this->extension_style_selector_append( $hover_selector, ' svg' );
		$hover_mask_selector = $this->extension_style_selector_append( $hover_selector, ' .directorist-icon-mask::after' );

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $prefix . '_typography',
				'selector' => $selector,
			]
		);

		$this->start_controls_tabs( $prefix . '_state_tabs' );

		$this->start_controls_tab(
			$prefix . '_normal_tab',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			$prefix . '_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector      => 'color: {{VALUE}};',
					$svg_selector  => 'fill: {{VALUE}};',
					$mask_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix . '_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix . '_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			$prefix . '_hover_tab',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			$prefix . '_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$hover_selector      => 'color: {{VALUE}};',
					$hover_svg_selector  => 'fill: {{VALUE}};',
					$hover_mask_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix . '_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$hover_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$prefix . '_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$hover_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			$prefix . '_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					$selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$prefix . '_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
