<?php
/**
 * Listing card social info widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardSocialInfoWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_social_info';
	}

	public function get_title(): string {
		return __( 'Listing Social Info', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-share-arrow';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'social', 'social info', 'links' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_social_info_content',
			__( 'Social Info', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-share-alt',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Social Info:', 'directorist-elementor' ),
			]
		);

		$this->register_icon_text_style_controls(
			'section_social_info_style',
			__( 'Social Info', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-social-info'
		);
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render listing social info.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();

		if ( ! $bridge->is_preset_widget_allowed( $listing_id, 'social_info' ) ) {
			return;
		}

		$social_links = $bridge->get_listing_social_links( $listing_id );

		if ( empty( $social_links ) ) {
			return;
		}

		$links_markup = $this->build_social_links_markup( $social_links );

		if ( '' === $links_markup ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-social-info',
			'directorist-elementor-listing-card-field__value directorist-elementor-listing-card-social-links',
			$links_markup,
			(string) ( $settings['html_tag'] ?? 'div' ),
			'',
			'directorist-elementor-listing-card-social-info__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : '',
			true
		);
	}

	/**
	 * Build social links markup for card output.
	 *
	 * @param array<int,array{id:string,url:string}> $social_links Social links.
	 * @return string
	 */
	protected function build_social_links_markup( array $social_links ): string {
		$links_markup = '';

		foreach ( $social_links as $social_link ) {
			$social_id  = sanitize_key( (string) ( $social_link['id'] ?? '' ) );
			$social_url = esc_url( (string) ( $social_link['url'] ?? '' ) );

			if ( '' === $social_id || '' === $social_url ) {
				continue;
			}

			$icon_markup = function_exists( 'directorist_icon' )
				? (string) directorist_icon( 'lab la-' . $social_id, false )
				: strtoupper( substr( $social_id, 0, 1 ) );

			$links_markup .= sprintf(
				'<a class="%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s">%4$s</a>',
				esc_attr( 'directorist-elementor-listing-card-field__link directorist-elementor-listing-card-social-info__link directorist-elementor-listing-card-social-info__link--' . $social_id ),
				esc_url( $social_url ),
				esc_attr( ucfirst( $social_id ) ),
				wp_kses_post( $icon_markup )
			);
		}

		return $links_markup;
	}
}
