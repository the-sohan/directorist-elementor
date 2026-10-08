<?php
/**
 * Listing card popular badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractBadgeFieldWidget;

class ListingCardBadgePopularWidget extends AbstractBadgeFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_badge_popular';
	}

	public function get_title(): string {
		return __( 'Badge Popular', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-flash';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'badge', 'popular' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_badge_icon_controls(
			'section_badge_popular_content',
			__( 'Popular Badge', 'directorist-elementor' ),
			[
				'value'   => 'fas fa-fire',
				'library' => 'fa-solid',
			]
		);

		$this->register_badge_style_controls(
			'section_badge_popular_style',
			__( 'Popular Badge', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-popular__badge'
		);

		$this->register_badge_icon_style_controls(
			'section_badge_popular_icon_style',
			__( 'Popular Icon', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-popular__badge'
		);
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the popular badge.', 'directorist-elementor' )
			);
			return;
		}

		$labels = DirectoristBridge::get_instance()->get_listing_badge_labels( $listing_id );

		if ( empty( $labels['popular'] ) ) {
			return;
		}

		$default_icon = [
			'value'   => 'fas fa-fire',
			'library' => 'fa-solid',
		];
		$settings     = $this->get_settings_for_display();

		$this->render_badge_markup(
			(string) $labels['popular'],
			'directorist-elementor-listing-card-badge-popular',
			'directorist-elementor-listing-card-badge-popular__badge',
			$this->get_badge_icon_markup( (array) ( $settings['badge_icon'] ?? [] ), $default_icon )
		);
	}
}
