<?php
/**
 * Listing card pricing widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardPricingWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_pricing';
	}

	public function get_title(): string {
		return __( 'Listing Pricing', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'directorist-eicon directorist-eicon--pricing';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'pricing', 'price', 'cost' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_pricing_content',
			__( 'Pricing', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-tag',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Price:', 'directorist-elementor' ),
			]
		);

		$this->register_icon_text_style_controls(
			'section_pricing_style',
			__( 'Pricing', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-pricing'
		);
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing price.', 'directorist-elementor' )
			);
			return;
		}

		$value = DirectoristBridge::get_instance()->get_listing_price_html( $listing_id );

		if ( '' === trim( wp_strip_all_tags( $value ) ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-pricing',
			'directorist-elementor-listing-card-field__value',
			$value,
			(string) ( $settings['html_tag'] ?? 'div' ),
			'',
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : '',
			true
		);
	}
}
