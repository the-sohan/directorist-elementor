<?php
/**
 * Listing card custom checkbox widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomCheckboxWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_checkbox';
	}

	public function get_title(): string {
		return __( 'Custom Checkbox', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-checkbox';
	}

	protected function get_custom_field_widget_name(): string {
		return 'checkbox';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_checkbox_content',
			__( 'Custom Checkbox', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-check-square',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_checkbox_style',
			__( 'Custom Checkbox', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
