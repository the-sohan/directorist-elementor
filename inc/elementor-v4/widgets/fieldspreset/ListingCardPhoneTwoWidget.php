<?php
/**
 * Listing card secondary phone widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardPhoneTwoWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_phone_two';
	}

	public function get_title(): string {
		return __( 'Listing Phone 2', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'directorist-eicon directorist-eicon--phone';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'phone', 'telephone', 'secondary' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_phone_two_content',
			__( 'Phone 2', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-phone-volume',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Phone 2:', 'directorist-elementor' ),
				'supports_link'      => true,
			]
		);

		$this->register_icon_text_style_controls(
			'section_phone_two_style',
			__( 'Phone 2', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-phone-two'
		);
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the secondary listing phone number.', 'directorist-elementor' )
			);
			return;
		}

		$value = DirectoristBridge::get_instance()->get_listing_phone_two( $listing_id );

		if ( '' === $value ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$link     = 'yes' === ( $settings['link_to_value'] ?? 'yes' ) ? 'tel:' . preg_replace( '/[^0-9\+]/', '', $value ) : '';

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-phone-two',
			'directorist-elementor-listing-card-field__value',
			$value,
			(string) ( $settings['html_tag'] ?? 'div' ),
			$link,
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : ''
		);
	}
}
