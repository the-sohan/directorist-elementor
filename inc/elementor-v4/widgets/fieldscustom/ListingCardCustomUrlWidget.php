<?php
/**
 * Listing card custom URL widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomUrlWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_url';
	}

	public function get_title(): string {
		return __( 'Custom URL', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-link';
	}

	protected function get_custom_field_widget_name(): string {
		return 'url';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_url_content',
			__( 'Custom URL', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-link',
					'library' => 'fa-solid',
				],
				'supports_link' => true,
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_url_style',
			__( 'Custom URL', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}

	protected function get_custom_field_link_url( int $listing_id, array $field_definition, array $settings, string $display_value ): string {
		if ( 'yes' !== ( $settings['link_to_value'] ?? 'yes' ) ) {
			return '';
		}

		$value = DirectoristBridge::get_instance()->get_listing_custom_field_display_value( $listing_id, $field_definition );

		return filter_var( $value, FILTER_VALIDATE_URL ) ? $value : '';
	}
}
