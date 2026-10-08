<?php
/**
 * Listing card custom color widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomColorWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_color';
	}

	public function get_title(): string {
		return __( 'Color Picker', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-eyedropper';
	}

	protected function get_custom_field_widget_name(): string {
		return 'color_picker';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_color_content',
			__( 'Color Picker', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-palette',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_color_style',
			__( 'Color Picker', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}

	protected function format_custom_field_display_value( int $listing_id, array $field_definition ): string {
		$display_color = DirectoristBridge::get_instance()->get_listing_custom_field_display_value( $listing_id, $field_definition );
		$raw_color     = trim( $display_color );

		if ( '' === $raw_color ) {
			return '';
		}

		$safe_color = sanitize_hex_color( $raw_color );

		if ( ! $safe_color && preg_match( '/^rgba?\([^)]+\)$/i', $raw_color ) ) {
			$safe_color = sanitize_text_field( $raw_color );
		}

		if ( ! $safe_color && preg_match( '/^hsla?\([^)]+\)$/i', $raw_color ) ) {
			$safe_color = sanitize_text_field( $raw_color );
		}

		$swatch_markup = '';

		if ( $safe_color ) {
			$swatch_markup = '<span class="directorist-elementor-custom-color-swatch" style="background-color:' . esc_attr( $safe_color ) . ';"></span>';
		}

		return $swatch_markup . '<span class="directorist-elementor-custom-color-value">' . esc_html( $raw_color ) . '</span>';
	}

	protected function allow_custom_field_html_value(): bool {
		return true;
	}
}
