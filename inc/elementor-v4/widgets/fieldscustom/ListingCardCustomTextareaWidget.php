<?php
/**
 * Listing card custom textarea widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomTextareaWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_textarea';
	}

	public function get_title(): string {
		return __( 'Custom Textarea', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text-area';
	}

	protected function get_custom_field_widget_name(): string {
		return 'textarea';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_textarea_content',
			__( 'Custom Textarea', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-align-left',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_textarea_style',
			__( 'Custom Textarea', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}

	protected function format_custom_field_display_value( int $listing_id, array $field_definition ): string {
		$value = DirectoristBridge::get_instance()->get_listing_custom_field_display_value( $listing_id, $field_definition );

		return '' !== $value ? nl2br( esc_html( $value ) ) : '';
	}

	protected function allow_custom_field_html_value(): bool {
		return true;
	}
}
