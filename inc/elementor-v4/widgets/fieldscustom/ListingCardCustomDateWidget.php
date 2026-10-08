<?php
/**
 * Listing card custom date widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomDateWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_date';
	}

	public function get_title(): string {
		return __( 'Custom Date', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	protected function get_custom_field_widget_name(): string {
		return 'date';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_date_content',
			__( 'Custom Date', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-calendar',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_date_style',
			__( 'Custom Date', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
