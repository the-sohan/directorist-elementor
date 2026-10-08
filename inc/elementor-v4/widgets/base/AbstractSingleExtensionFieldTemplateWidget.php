<?php
/**
 * Base widget for extension-driven single listing field templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;

abstract class AbstractSingleExtensionFieldTemplateWidget extends AbstractExtensionWidget {

	/**
	 * Field-based extension widgets follow Gutenberg's preset-field grouping.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRESET;
	}

	/**
	 * Get the Directorist field widget name.
	 *
	 * @return string
	 */
	abstract protected function get_field_widget_name(): string;

	/**
	 * Get the optional asset/template key.
	 *
	 * @return string
	 */
	protected function get_field_template_key(): string {
		return '';
	}

	/**
	 * Get a selector for the field wrapper.
	 *
	 * @return string
	 */
	protected function get_field_wrapper_selector(): string {
		return '.directorist-info-item';
	}

	/**
	 * Get a selector for the field title.
	 *
	 * @return string
	 */
	protected function get_field_title_selector(): string {
		return '.directorist-info-item__label, .directorist-info-item__label span';
	}

	/**
	 * Get a selector for the field body.
	 *
	 * @return string
	 */
	protected function get_field_body_selector(): string {
		return '.directorist-info-item__value';
	}

	/**
	 * Get a selector for the field icon.
	 *
	 * @return string
	 */
	protected function get_field_icon_selector(): string {
		return '.directorist-info-item__label-icon';
	}

	/**
	 * Register any widget-specific content controls.
	 *
	 * @return void
	 */
	protected function register_extension_field_controls(): void {
	}

	/**
	 * Build the field data passed into Directorist field templates.
	 *
	 * @param int                  $listing_id Listing id.
	 * @param array<string,mixed>  $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function build_field_data( int $listing_id, array $settings ): array {
		return [
			'widget_group' => 'other_widgets',
			'widget_name'  => $this->get_field_widget_name(),
		];
	}

	/**
	 * Get the placeholder message shown in editor context.
	 *
	 * @return string
	 */
	protected function get_field_placeholder_message(): string {
		return sprintf(
			/* translators: %s: Widget title. */
			__( '%s is unavailable for the current preview listing.', 'directorist-elementor' ),
			$this->get_title()
		);
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_extension_field_content',
			[
				'label' => $this->get_title(),
			]
		);

		$this->register_editor_preview_listing_control();
		$this->register_extension_field_controls();

		$this->end_controls_section();

		$this->register_extension_field_style_controls();
	}

	/**
	 * Register shared field style controls.
	 *
	 * @return void
	 */
	protected function register_extension_field_style_controls(): void {
		$wrapper_selector = $this->get_field_wrapper_selector();
		$title_selector   = $this->get_field_title_selector();
		$body_selector    = $this->get_field_body_selector();
		$icon_selector    = $this->get_field_icon_selector();

		$this->register_extension_box_style_section(
			'section_extension_field_wrapper_style',
			__( 'Field Wrapper', 'directorist-elementor' ),
			$wrapper_selector,
			'field'
		);

		$this->register_extension_text_style_section(
			'section_extension_field_title_style',
			__( 'Field Title', 'directorist-elementor' ),
			$title_selector,
			'field_title',
			'field_title_color',
			'field_title_typography'
		);

		$this->register_extension_text_style_section(
			'section_extension_field_value_style',
			__( 'Field Value', 'directorist-elementor' ),
			$body_selector,
			'field_text',
			'field_text_color',
			'field_text_typography'
		);

		$this->register_extension_icon_style_section(
			'section_extension_field_icon_style',
			__( 'Field Icon', 'directorist-elementor' ),
			$icon_selector,
			'field_icon',
			'field_icon_color'
		);

		$this->register_additional_extension_field_style_controls();
	}

	/**
	 * Register extension-specific field style controls.
	 *
	 * @return void
	 */
	protected function register_additional_extension_field_style_controls(): void {
	}

	/**
	 * Render the field template.
	 *
	 * @param int $listing_id Listing id.
	 * @return void
	 */
	protected function render_extension_widget( int $listing_id ): void {
		$bridge       = DirectoristBridge::get_instance();
		$template_key = $this->get_field_template_key();

		if ( '' !== $template_key ) {
			$bridge->ensure_single_listing_assets( $template_key );
		}

		$settings  = $this->get_settings_for_display();
		$field_data = $this->build_field_data( $listing_id, $settings );
		$output    = $bridge->render_single_listing_field( $listing_id, $field_data );

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						$this->get_field_placeholder_message()
					)
				);
			}

			return;
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Directorist template output.
		echo $output;
	}
}
