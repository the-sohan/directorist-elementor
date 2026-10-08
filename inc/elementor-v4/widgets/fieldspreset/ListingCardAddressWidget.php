<?php
/**
 * Listing card address widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardAddressWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_address';
	}

	public function get_title(): string {
		return __( 'Listing Address', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-google-maps';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'address', 'map' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_address_content',
			__( 'Address', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-map-marker-alt',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Address:', 'directorist-elementor' ),
			]
		);

		$this->register_icon_text_style_controls(
			'section_address_style',
			__( 'Address', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-address'
		);
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing address.', 'directorist-elementor' )
			);
			return;
		}

		$value = DirectoristBridge::get_instance()->get_listing_address( $listing_id );

		if ( '' === $value ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-address',
			'directorist-elementor-listing-card-field__value',
			$value,
			(string) ( $settings['html_tag'] ?? 'div' ),
			'',
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : ''
		);
	}
}
