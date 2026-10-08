<?php
/**
 * Listing card custom time widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomTimeWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_time';
	}

	public function get_title(): string {
		return __( 'Custom Time', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-clock';
	}

	protected function get_custom_field_widget_name(): string {
		return 'time';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_time_content',
			__( 'Custom Time', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-clock',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_time_style',
			__( 'Custom Time', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
