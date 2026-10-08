<?php
/**
 * Listing card view count widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;

class ListingCardViewCountWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_view_count';
	}

	public function get_title(): string {
		return __( 'Listing View Count', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-preview-medium';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'views', 'count', 'stats' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_view_count_content',
			__( 'View Count', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-eye',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Views:', 'directorist-elementor' ),
			]
		);

		$this->register_icon_text_style_controls(
			'section_view_count_style',
			__( 'View Count', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-view-count'
		);
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing view count.', 'directorist-elementor' )
			);
			return;
		}

		$value = DirectoristBridge::get_instance()->get_listing_views_count( $listing_id );

		if ( $value <= 0 ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-view-count',
			'directorist-elementor-listing-card-field__value',
			(string) number_format_i18n( $value ),
			(string) ( $settings['html_tag'] ?? 'div' ),
			'',
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : ''
		);
	}
}
