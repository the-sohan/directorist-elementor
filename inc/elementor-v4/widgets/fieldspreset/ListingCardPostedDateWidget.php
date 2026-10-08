<?php
/**
 * Listing card posted date widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;
use Elementor\Controls_Manager;

class ListingCardPostedDateWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_posted_date';
	}

	public function get_title(): string {
		return __( 'Listing Posted Date', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'date', 'posted', 'time' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_posted_date_content',
			__( 'Posted Date', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-calendar-alt',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Posted:', 'directorist-elementor' ),
			]
		);

		$this->start_injection( [ 'at' => 'after', 'of' => 'html_tag' ] );

		$this->add_control(
			'date_format',
			[
				'label'       => __( 'Date Format', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => get_option( 'date_format' ),
			]
		);

		$this->end_injection();

		$this->register_icon_text_style_controls(
			'section_posted_date_style',
			__( 'Posted Date', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-posted-date'
		);
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing posted date.', 'directorist-elementor' )
			);
			return;
		}

		$settings = $this->get_settings_for_display();
		$value    = DirectoristBridge::get_instance()->get_listing_posted_date( $listing_id, (string) ( $settings['date_format'] ?? '' ) );

		if ( '' === $value ) {
			return;
		}

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-posted-date',
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
