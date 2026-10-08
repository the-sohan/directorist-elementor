<?php
/**
 * Listing card website widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardWebsiteWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_website';
	}

	public function get_title(): string {
		return __( 'Listing Website', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-editor-link';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'website', 'url', 'link' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_website_content',
			__( 'Website', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-globe',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Website:', 'directorist-elementor' ),
				'supports_link'      => true,
			]
		);

		$this->register_icon_text_style_controls(
			'section_website_style',
			__( 'Website', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-website'
		);
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing website.', 'directorist-elementor' )
			);
			return;
		}

		$website = DirectoristBridge::get_instance()->get_listing_website( $listing_id );

		if ( '' === $website ) {
			return;
		}

		$display = wp_parse_url( $website, PHP_URL_HOST );
		$display = is_string( $display ) && '' !== $display ? preg_replace( '/^www\./', '', $display ) : $website;

		$settings = $this->get_settings_for_display();
		$link     = 'yes' === ( $settings['link_to_value'] ?? 'yes' ) ? $website : '';

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-website',
			'directorist-elementor-listing-card-field__value',
			(string) $display,
			(string) ( $settings['html_tag'] ?? 'div' ),
			$link,
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : ''
		);
	}
}
