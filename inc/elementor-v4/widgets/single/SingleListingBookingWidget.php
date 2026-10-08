<?php
/**
 * Single listing booking widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingBookingWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_booking';
	}

	public function get_title(): string {
		return __( 'Booking', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'booking', 'reservation' ] );
	}

	protected function get_extension_slug(): string {
		return 'booking';
	}

	protected function get_field_widget_name(): string {
		return 'booking';
	}

	protected function get_field_wrapper_selector(): string {
		return '.booking-wrapper, .directorist-booking-wrapper';
	}

	protected function get_field_title_selector(): string {
		return '.booking-wrapper .atbd_area_title h4, .directorist-booking-wrapper .atbd_area_title h4, .directorist-booking-wrapper__title';
	}

	protected function get_field_body_selector(): string {
		return '.booking-wrapper .booking-content, .directorist-booking-wrapper__content, .directorist-booking-wrapper__content-grid';
	}

	protected function get_field_icon_selector(): string {
		return '';
	}

	protected function register_additional_extension_field_style_controls(): void {
		$this->register_extension_box_style_section(
			'section_booking_calendar_style',
			__( 'Calendar / Date Area', 'directorist-elementor' ),
			'.booking-wrapper .bdb-select-hours, .booking-wrapper .booking-content .directorist-booking-fields, .directorist-booking-wrapper .directorist-booking-fields, .directorist-booking-panel-dropdown',
			'booking_calendar'
		);

		$this->register_extension_box_style_section(
			'section_booking_slot_style',
			__( 'Time Slot', 'directorist-elementor' ),
			'.booking-wrapper .time-slot, .directorist-booking-wrapper .time-slot, .directorist-booking-panel-dropdown-content .time-slot',
			'booking_slot'
		);

		$this->register_extension_text_style_section(
			'section_booking_slot_text_style',
			__( 'Time Slot Text', 'directorist-elementor' ),
			'.booking-wrapper .time-slot label, .directorist-booking-wrapper .time-slot label, .directorist-booking-panel-dropdown-content .time-slot label',
			'booking_slot_text'
		);

		$this->register_extension_box_style_section(
			'section_booking_input_style',
			__( 'Form Fields', 'directorist-elementor' ),
			'.booking-wrapper input:not([type="submit"]):not([type="radio"]), .booking-wrapper select, .booking-wrapper textarea, .directorist-booking-wrapper input:not([type="submit"]):not([type="radio"]), .directorist-booking-wrapper select, .directorist-booking-wrapper textarea',
			'booking_field'
		);

		$this->register_extension_button_style_section(
			'section_booking_button_style',
			__( 'Book Button', 'directorist-elementor' ),
			'.booking-wrapper .directorist-book-now, .booking-wrapper .directorist-btn, .directorist-booking-wrapper .directorist-book-now, .directorist-booking-wrapper .directorist-btn, .directorist-booking-wrapper button[type="submit"]',
			'booking_button'
		);
	}
}
