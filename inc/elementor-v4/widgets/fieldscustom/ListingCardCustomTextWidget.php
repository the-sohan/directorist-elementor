<?php
/**
 * Listing card custom text widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomTextWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_text';
	}

	public function get_title(): string {
		return __( 'Custom Text', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-editor-list-ul';
	}

	protected function get_custom_field_widget_name(): string {
		return 'text';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_text_content',
			__( 'Custom Text', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-font',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_text_style',
			__( 'Custom Text', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
