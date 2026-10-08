<?php
/**
 * Listing card custom HTML widget.
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

class ListingCardCustomHtmlWidget extends AbstractCustomFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_html';
	}

	public function get_title(): string {
		return __( 'Custom HTML', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-code';
	}

	protected function get_custom_field_widget_name(): string {
		return 'html';
	}

	protected function get_custom_field_widget_names(): array {
		return [ 'html', 'wp_editor' ];
	}

	protected function register_widget_controls(): void {
		$this->register_html_content_controls();
		$this->register_html_container_style_controls();
		$this->register_html_text_style_controls( 'body', __( 'Body Text', 'directorist-elementor' ), '.directorist-elementor-listing-card-custom-html__content' );
		$this->register_html_text_style_controls( 'paragraph', __( 'Paragraph', 'directorist-elementor' ), '.directorist-elementor-listing-card-custom-html__content p' );
		$this->register_html_text_style_controls( 'link', __( 'Link', 'directorist-elementor' ), '.directorist-elementor-listing-card-custom-html__content a', true );
		$this->register_html_text_style_controls( 'unordered_list', __( 'Unordered List', 'directorist-elementor' ), '.directorist-elementor-listing-card-custom-html__content ul' );
		$this->register_html_text_style_controls( 'ordered_list', __( 'Ordered List', 'directorist-elementor' ), '.directorist-elementor-listing-card-custom-html__content ol' );
		$this->register_html_blockquote_style_controls();

		foreach ( [ 1, 2, 3, 4, 5, 6 ] as $level ) {
			$this->register_html_text_style_controls(
				'heading_' . $level,
				sprintf(
					/* translators: %d: Heading level. */
					__( 'Heading %d', 'directorist-elementor' ),
					$level
				),
				'.directorist-elementor-listing-card-custom-html__content h' . $level
			);
		}
	}

	protected function register_html_content_controls(): void {
		$options = $this->get_custom_field_control_options();
		$notice  = $this->get_custom_field_unavailable_notice(
			__( 'No matching HTML custom fields were found across your directory types yet.', 'directorist-elementor' )
		);

		$this->start_controls_section(
			'section_custom_html_content',
			[
				'label' => __( 'Custom HTML', 'directorist-elementor' ),
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
					'description' => __( 'Choose which HTML field this widget should render.', 'directorist-elementor' ),
				]
			);
		}

		$this->end_controls_section();
	}

	protected function register_html_container_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-custom-html__content';

		$this->start_controls_section(
			'section_custom_html_container_style',
			[
				'label' => __( 'Content Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'custom_html_container_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'custom_html_container_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_html_container_radius',
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
			'custom_html_container_padding',
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
			'custom_html_container_margin',
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
				'name'     => 'custom_html_container_shadow',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_html_container_opacity',
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
					$selector => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_html_container_translate_x',
			[
				'label'      => __( 'Translate X', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					$selector => '--direl-custom-html-translate-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'custom_html_container_translate_y',
			[
				'label'      => __( 'Translate Y', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					$selector => '--direl-custom-html-translate-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_html_text_style_controls( string $prefix, string $label, string $target_selector, bool $include_hover = false ): void {
		$selector = '{{WRAPPER}} ' . $target_selector;

		$this->start_controls_section(
			'section_custom_html_' . $prefix . '_style',
			[
				'label' => $label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'custom_html_' . $prefix . '_align',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
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
					$selector => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'custom_html_' . $prefix . '_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		if ( $include_hover ) {
			$this->add_control(
				'custom_html_' . $prefix . '_hover_color',
				[
					'label'     => __( 'Hover Color', 'directorist-elementor' ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						$selector . ':hover, ' . $selector . ':focus' => 'color: {{VALUE}};',
					],
				]
			);
		}

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'custom_html_' . $prefix . '_typography',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_html_' . $prefix . '_margin',
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

	protected function register_html_blockquote_style_controls(): void {
		$selector = '{{WRAPPER}} .directorist-elementor-listing-card-custom-html__content blockquote';

		$this->start_controls_section(
			'section_custom_html_blockquote_style',
			[
				'label' => __( 'Blockquote', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'custom_html_blockquote_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'custom_html_blockquote_typography',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'custom_html_blockquote_background',
				'selector' => $selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'custom_html_blockquote_border',
				'selector' => $selector,
			]
		);

		$this->add_responsive_control(
			'custom_html_blockquote_radius',
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
			'custom_html_blockquote_padding',
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
			'custom_html_blockquote_margin',
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
				'name'     => 'custom_html_blockquote_shadow',
				'selector' => $selector,
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_custom_field_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template and choose a matching HTML custom field to render its content.', 'directorist-elementor' )
			);
			return;
		}

		$field_definition = $this->get_selected_custom_field_definition();

		if ( empty( $field_definition ) ) {
			$this->render_custom_field_context_placeholder(
				__( 'Choose an HTML custom field in the widget settings to render it inside the active listing card branch.', 'directorist-elementor' )
			);
			return;
		}

		$raw_content = DirectoristBridge::get_instance()->get_listing_custom_field_raw_value(
			$listing_id,
			(string) ( $field_definition['field_key'] ?? '' )
		);

		if ( ! is_scalar( $raw_content ) ) {
			return;
		}

		$content = trim( (string) $raw_content );

		if ( '' === $content ) {
			return;
		}

		$allowed_html = wp_kses_allowed_html( 'post' );

		if ( class_exists( '\Directorist\Fields\HTML_Field' ) ) {
			$allowed_html = \Directorist\Fields\HTML_Field::allowed_html();
		}

		$content = shortcode_unautop( wpautop( $content ) );
		$content = wp_kses( $content, $allowed_html );

		if ( '' === trim( $content ) ) {
			return;
		}

		echo '<div class="directorist-elementor-listing-card-custom-html">';
		echo '<div class="directorist-elementor-listing-card-custom-html__content">';
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized with Directorist HTML field allowlist above.
		echo '</div>';
		echo '</div>';
	}
}
