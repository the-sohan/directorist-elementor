<?php
/**
 * Single listing custom content widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleListingWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class SingleListingCustomContentWidget extends AbstractSingleListingWidget {

	public function get_name(): string {
		return 'directorist_single_listing_custom_content';
	}

	public function get_title(): string {
		return __( 'Custom Content', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-editor-paragraph';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'custom', 'content', 'html' ] );
	}

	protected function register_widget_controls(): void {

		$this->start_controls_section(
			'section_custom_content',
			[
				'label' => __( 'Custom Content', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'label',
			[
				'label'       => __( 'Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
			]
		);

		$this->add_control(
			'icon',
			[
				'label'       => __( 'Icon Class', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => 'las la-tag',
			]
		);

		$this->add_control(
			'content',
			[
				'label'   => __( 'Content', 'directorist-elementor' ),
				'type'    => Controls_Manager::WYSIWYG,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_custom_content_style',
			[
				'label' => __( 'Custom Content', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'label_color',
			[
				'label'     => __( 'Label Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-single-info-custom .directorist-single-info__label' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .directorist-single-info-custom .directorist-single-info__label',
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Content Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-single-info-custom .directorist-single-info__value' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'value_typography',
				'selector' => '{{WRAPPER}} .directorist-single-info-custom .directorist-single-info__value',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Custom Content', 'directorist-elementor' ),
					__( 'Place this widget inside a Directorist single listing template to render custom content.', 'directorist-elementor' )
				)
			);
			return;
		}

		$bridge   = DirectoristBridge::get_instance();
		$settings = $this->get_settings_for_display();
		$content  = trim( (string) ( $settings['content'] ?? '' ) );

		if ( '' === $content ) {
			if ( $this->is_editor_context() ) {
				$content = esc_html__( 'Add content from widget settings.', 'directorist-elementor' );
			} else {
				return;
			}
		}

		$output = $bridge->render_single_listing_field(
			$listing_id,
			[
				'widget_group' => 'other_widgets',
				'widget_name'  => 'custom_content',
				'content'      => wp_kses_post( $content ),
				'label'        => sanitize_text_field( (string) ( $settings['label'] ?? '' ) ),
				'icon'         => sanitize_text_field( (string) ( $settings['icon'] ?? '' ) ),
			]
		);

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Custom Content', 'directorist-elementor' ),
						__( 'Custom content is unavailable for the current preview listing.', 'directorist-elementor' )
					)
				);
			}
			return;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
