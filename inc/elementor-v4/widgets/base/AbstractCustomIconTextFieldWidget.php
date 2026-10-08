<?php
/**
 * Base custom field widget for icon/text style output.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;

abstract class AbstractCustomIconTextFieldWidget extends AbstractCustomFieldWidget {

	/**
	 * Format the selected custom field value for output.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $field_definition Field definition.
	 * @return string
	 */
	protected function format_custom_field_display_value( int $listing_id, array $field_definition ): string {
		return DirectoristBridge::get_instance()->get_listing_custom_field_display_value( $listing_id, $field_definition );
	}

	/**
	 * Resolve an optional link URL for the rendered value.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $field_definition Field definition.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string              $display_value Formatted value.
	 * @return string
	 */
	protected function get_custom_field_link_url( int $listing_id, array $field_definition, array $settings, string $display_value ): string {
		return '';
	}

	/**
	 * Whether the formatted value contains safe inline HTML markup.
	 *
	 * @return bool
	 */
	protected function allow_custom_field_html_value(): bool {
		return false;
	}

	/**
	 * Render the current custom field widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_custom_field_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template and choose a matching custom field to render its value.', 'directorist-elementor' )
			);
			return;
		}

		$field_definition = $this->get_selected_custom_field_definition();

		if ( empty( $field_definition ) ) {
			$this->render_custom_field_context_placeholder(
				__( 'Choose a custom field in the widget settings to render it inside the active listing card branch.', 'directorist-elementor' )
			);
			return;
		}

		$settings      = $this->get_settings_for_display();
		$display_value = $this->format_custom_field_display_value( $listing_id, $field_definition );

		if ( '' === trim( wp_strip_all_tags( $display_value ) ) ) {
			return;
		}

		$this->render_icon_text_markup(
			$this->get_custom_field_base_class(),
			'directorist-elementor-listing-card-field__value',
			$display_value,
			(string) ( $settings['html_tag'] ?? 'div' ),
			$this->get_custom_field_link_url( $listing_id, $field_definition, $settings, $display_value ),
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			$this->get_custom_field_label_text( $field_definition, $settings ),
			$this->allow_custom_field_html_value()
		);
	}
}
