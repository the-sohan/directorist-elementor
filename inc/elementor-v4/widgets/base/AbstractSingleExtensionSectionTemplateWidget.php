<?php
/**
 * Base widget for extension-driven single listing section templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use Elementor\Controls_Manager;

abstract class AbstractSingleExtensionSectionTemplateWidget extends AbstractExtensionWidget {

	/**
	 * Get the Directorist section widget name.
	 *
	 * @return string
	 */
	abstract protected function get_section_widget_name(): string;

	/**
	 * Get the optional asset/template key.
	 *
	 * @return string
	 */
	protected function get_section_template_key(): string {
		return '';
	}

	/**
	 * Get the default section label.
	 *
	 * @return string
	 */
	protected function get_default_section_label(): string {
		return $this->get_title();
	}

	/**
	 * Get the default section icon class.
	 *
	 * @return string
	 */
	protected function get_default_section_icon(): string {
		return '';
	}

	/**
	 * Get section wrapper selector.
	 *
	 * @return string
	 */
	protected function get_section_wrapper_selector(): string {
		return '.directorist-card';
	}

	/**
	 * Get section title selector.
	 *
	 * @return string
	 */
	protected function get_section_title_selector(): string {
		return '.directorist-card__header__title, .directorist-card__header--title';
	}

	/**
	 * Get section body selector.
	 *
	 * @return string
	 */
	protected function get_section_body_selector(): string {
		return '.directorist-card__body';
	}

	/**
	 * Get section header selector.
	 *
	 * @return string
	 */
	protected function get_section_header_selector(): string {
		return '.directorist-card__header, .directorist-card-header, .directorist-elementor-extension__header';
	}

	/**
	 * Get section icon selector.
	 *
	 * @return string
	 */
	protected function get_section_icon_selector(): string {
		return '.directorist-card__header i, .directorist-card__header .directorist-icon-mask, .directorist-elementor-extension__icon, .directorist-elementor-extension__icon i';
	}

	/**
	 * Register additional content controls.
	 *
	 * @return void
	 */
	protected function register_extension_section_controls(): void {
	}

	/**
	 * Allow subclasses to customize the final section data.
	 *
	 * @param array<string,mixed> $section_data Section data.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int                 $listing_id Listing id.
	 * @return array<string,mixed>
	 */
	protected function filter_section_data( array $section_data, array $settings, int $listing_id ): array {
		return $section_data;
	}

	/**
	 * Get section placeholder message.
	 *
	 * @return string
	 */
	protected function get_section_placeholder_message(): string {
		return sprintf(
			/* translators: %s: Widget title. */
			__( '%s is unavailable for the current preview listing.', 'directorist-elementor' ),
			$this->get_title()
		);
	}

	/**
	 * Render a section-context placeholder.
	 *
	 * @param string $message Placeholder message.
	 * @return void
	 */
	protected function render_single_section_placeholder( string $message ): void {
		echo wp_kses_post(
			$this->render_placeholder(
				__( 'Single Listing Section', 'directorist-elementor' ),
				$message
			)
		);
	}

	/**
	 * Register shared section style controls.
	 *
	 * @param string $section_id Section id.
	 * @param string $label Section label.
	 * @param string $wrapper_selector Section wrapper selector.
	 * @param string $title_selector Title selector.
	 * @param string $body_selector Body selector.
	 * @return void
	 */
	protected function register_single_section_style_controls(
		string $section_id,
		string $label,
		string $wrapper_selector,
		string $title_selector,
		string $body_selector = ''
	): void {
		$this->register_extension_box_style_section(
			$section_id . '_container',
			__( 'Block Container', 'directorist-elementor' ),
			$wrapper_selector,
			'section'
		);

		$this->register_extension_box_style_section(
			$section_id . '_header',
			__( 'Block Header', 'directorist-elementor' ),
			$this->get_section_header_selector(),
			'section_header'
		);

		$this->register_extension_text_style_section(
			$section_id . '_title',
			__( 'Header Title', 'directorist-elementor' ),
			$title_selector,
			'section_title',
			'section_title_color',
			'section_title_typography'
		);

		$this->register_extension_icon_style_section(
			$section_id . '_icon',
			__( 'Header Icon', 'directorist-elementor' ),
			$this->get_section_icon_selector(),
			'section_icon',
			'section_icon_color'
		);

		$this->register_extension_box_style_section(
			$section_id . '_body_box',
			__( 'Block Content', 'directorist-elementor' ),
			$body_selector,
			'section_body'
		);

		$this->register_extension_text_style_section(
			$section_id . '_body_text',
			__( 'Content Text', 'directorist-elementor' ),
			$body_selector,
			'section_body_text',
			'section_body_color',
			'section_body_typography'
		);

		$this->register_additional_extension_section_style_controls( $section_id );
	}

	/**
	 * Register extension-specific section style controls.
	 *
	 * @param string $section_id Base section id.
	 * @return void
	 */
	protected function register_additional_extension_section_style_controls( string $section_id ): void {
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_extension_section_content',
			[
				'label' => $this->get_title(),
			]
		);

		$this->register_editor_preview_listing_control();

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $this->get_default_section_label(),
				'label_block' => true,
			]
		);

		$this->add_control(
			'section_icon',
			[
				'label'       => __( 'Section Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $this->get_default_section_icon(),
				'placeholder' => $this->get_default_section_icon(),
			]
		);

		$this->register_extension_section_controls();

		$this->end_controls_section();

		$this->register_single_section_style_controls(
			'section_extension_section_style',
			__( 'Style', 'directorist-elementor' ),
			$this->get_section_wrapper_selector(),
			$this->get_section_title_selector(),
			$this->get_section_body_selector()
		);
	}

	/**
	 * Render the section template.
	 *
	 * @param int $listing_id Listing id.
	 * @return void
	 */
	protected function render_extension_widget( int $listing_id ): void {
		$bridge       = DirectoristBridge::get_instance();
		$template_key = $this->get_section_template_key();

		if ( '' !== $template_key ) {
			$bridge->ensure_single_listing_assets( $template_key );
		}

		$settings     = $this->get_settings_for_display();
		$existing     = $bridge->get_single_listing_section_data( $listing_id, $this->get_section_widget_name() );
		$section_data = wp_parse_args(
			$existing,
			[
				'type'        => 'other_widgets',
				'widget_name' => $this->get_section_widget_name(),
				'label'       => $this->get_default_section_label(),
				'icon'        => $this->get_default_section_icon(),
			]
		);

		$section_title = trim( (string) ( $settings['section_title'] ?? '' ) );
		$section_icon  = trim( (string) ( $settings['section_icon'] ?? '' ) );

		if ( '' !== $section_title ) {
			$section_data['label'] = sanitize_text_field( $section_title );
		}

		if ( '' !== $section_icon ) {
			$section_data['icon'] = sanitize_text_field( $section_icon );
		}

		$section_data = $this->filter_section_data( $section_data, $settings, $listing_id );
		$output       = $bridge->render_single_listing_section( $listing_id, $section_data );

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_section_placeholder( $this->get_section_placeholder_message() );
			}

			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Directorist template output.
		echo $output;
	}
}
