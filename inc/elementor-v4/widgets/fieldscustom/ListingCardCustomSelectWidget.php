<?php
/**
 * Listing card custom select widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomSelectWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_select';
	}

	public function get_title(): string {
		return __( 'Custom Select', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-select';
	}

	protected function get_custom_field_widget_name(): string {
		return 'select';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_select_content',
			__( 'Custom Select', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-chevron-circle-down',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_select_style',
			__( 'Custom Select', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}
}
