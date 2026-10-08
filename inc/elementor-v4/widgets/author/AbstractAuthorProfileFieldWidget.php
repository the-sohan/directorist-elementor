<?php
/**
 * Base author profile field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\AuthorProfileRenderService;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleListingWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

abstract class AbstractAuthorProfileFieldWidget extends AbstractSingleListingWidget {

	/**
	 * Author fields belong to the author profile child category.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_AUTHOR;
	}

	/**
	 * Add shared keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'author', 'profile', 'single' ] );
	}

	/**
	 * Render field by key.
	 *
	 * @param string $field Field key.
	 * @param string $empty_message Editor placeholder message.
	 * @return void
	 */
	protected function render_author_field( string $field, string $empty_message ): void {
		$context  = $this->resolve_author_profile_context();
		$author   = is_array( $context['author'] ?? null ) ? (array) $context['author'] : [];
		$settings = $this->get_settings_for_display();

		if ( empty( $author ) ) {
			$this->render_author_placeholder(
				__( 'Place this widget inside an Author Profile element.', 'directorist-elementor' )
			);
			return;
		}

		$html = AuthorProfileRenderService::get_instance()->render_field( $field, $author, $settings );

		if ( '' === trim( $html ) ) {
			$this->render_author_placeholder( $empty_message );
			return;
		}

		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Field output is escaped in AuthorProfileRenderService.
	}

	/**
	 * Resolve author context from the parent author profile composition.
	 *
	 * @return array<string,mixed>
	 */
	protected function resolve_author_profile_context(): array {
		$context = RenderContext::get_instance()->current_author_profile_context();

		if ( ! empty( $context['author'] ) && is_array( $context['author'] ) ) {
			return $context;
		}

		return [];
	}

	/**
	 * Render editor-only placeholder.
	 *
	 * @param string $description Description.
	 * @return void
	 */
	protected function render_author_placeholder( string $description ): void {
		if ( ! $this->is_editor_context() ) {
			return;
		}

		echo wp_kses_post( $this->render_placeholder( $this->get_title(), $description ) );
	}

	/**
	 * Register alignment control for a selector.
	 *
	 * @param string $control_id Control id.
	 * @param string $selector CSS selector.
	 * @param string $property CSS property.
	 * @return void
	 */
	protected function register_alignment_control( string $control_id, string $selector, string $property = 'text-align' ): void {
		$this->add_responsive_control(
			$control_id,
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
					'{{WRAPPER}} ' . $selector => $property . ': {{VALUE}};',
				],
			]
		);
	}

	/**
	 * Register common text style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @param string $link_selector Optional link selector.
	 * @return void
	 */
	protected function register_text_style_controls( string $section_id, string $label, string $selector, string $link_selector = '' ): void {
		$text_color_selectors = [
			'{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
		];
		$text_color_selectors[ '' !== $link_selector ? '{{WRAPPER}} ' . $link_selector : '{{WRAPPER}} ' . $selector . ' a' ] = 'color: {{VALUE}};';

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->register_alignment_control( $section_id . '_alignment', $selector );

		$this->add_control(
			$section_id . '_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => $text_color_selectors,
			]
		);

		if ( '' !== $link_selector ) {
			$this->add_control(
				$section_id . '_hover_color',
				[
					'label'     => __( 'Hover Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						'{{WRAPPER}} ' . $link_selector . ':hover, {{WRAPPER}} ' . $link_selector . ':focus' => 'color: {{VALUE}};',
					],
				]
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $section_id . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register icon text row style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @return void
	 */
	protected function register_icon_text_style_controls( string $section_id, string $label, string $selector ): void {
		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			$section_id . '_alignment',
			[
				'label'   => __( 'Alignment', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
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
					'{{WRAPPER}} ' . $selector => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			$section_id . '_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			$section_id . '_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $selector . ' i, {{WRAPPER}} ' . $selector . ' svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			$section_id . '_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector . ' .directorist-elementor-author-profile-contact__icon, {{WRAPPER}} ' . $selector . ' .directorist-elementor-author-profile-rating__icon, {{WRAPPER}} ' . $selector . ' .directorist-elementor-author-profile-listing-count__icon, {{WRAPPER}} ' . $selector . ' i, {{WRAPPER}} ' . $selector . ' svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			$section_id . '_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $selector => 'color: {{VALUE}};',
					'{{WRAPPER}} ' . $selector . ' a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			$section_id . '_link_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} ' . $selector . ' a:hover, {{WRAPPER}} ' . $selector . ' a:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => $section_id . '_typography',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register box controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $selector CSS selector.
	 * @return void
	 */
	protected function register_box_style_controls( string $section_id, string $label, string $selector ): void {
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
				'name'     => $section_id . '_background',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => $section_id . '_border',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => $section_id . '_shadow',
				'selector' => '{{WRAPPER}} ' . $selector,
			]
		);

		$this->add_responsive_control(
			$section_id . '_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			$section_id . '_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} ' . $selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
