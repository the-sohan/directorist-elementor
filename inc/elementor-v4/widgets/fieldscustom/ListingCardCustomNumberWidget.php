<?php
/**
 * Listing card custom number widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomNumberWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_number';
	}

	public function get_title(): string {
		return __( 'Custom Number', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function get_custom_field_widget_name(): string {
		return 'number';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_number_content',
			__( 'Custom Number', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-hashtag',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_number_style',
			__( 'Custom Number', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
