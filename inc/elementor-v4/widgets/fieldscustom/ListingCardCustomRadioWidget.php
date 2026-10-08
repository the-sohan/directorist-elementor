<?php
/**
 * Listing card custom radio widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomRadioWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_radio';
	}

	public function get_title(): string {
		return __( 'Custom Radio', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-radio';
	}

	protected function get_custom_field_widget_name(): string {
		return 'radio';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_radio_content',
			__( 'Custom Radio', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-dot-circle',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_radio_style',
			__( 'Custom Radio', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
