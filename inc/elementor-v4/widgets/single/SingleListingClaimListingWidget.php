<?php
/**
 * Single listing claim listing widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionSectionTemplateWidget;

class SingleListingClaimListingWidget extends AbstractSingleExtensionSectionTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_claim_listing';
	}

	public function get_title(): string {
		return __( 'Claim Listing', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-check-circle-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'claim', 'listing' ] );
	}

	protected function get_extension_slug(): string {
		return 'claim';
	}

	protected function get_section_widget_name(): string {
		return 'claim_listing';
	}

	protected function get_default_section_icon(): string {
		return 'las la-id-card';
	}

	protected function get_section_wrapper_selector(): string {
		return '.directorist-claim-listing';
	}

	protected function get_section_title_selector(): string {
		return '.directorist-claim-listing__title, .directorist-claim-listing .directorist-card__header__title, .directorist-claim-listing .directorist-card__header--title';
	}

	protected function get_section_body_selector(): string {
		return '.directorist-claim-listing .directorist-card__body, .directorist-claim-listing__description';
	}

	protected function get_section_header_selector(): string {
		return '.directorist-claim-listing .directorist-card__header, .directorist-claim-listing__header';
	}

	protected function get_section_icon_selector(): string {
		return '.directorist-claim-listing .directorist-card__header i, .directorist-claim-listing .directorist-card__header .directorist-icon-mask';
	}

	protected function register_additional_extension_section_style_controls( string $section_id ): void {
		$this->register_extension_text_style_section(
			$section_id . '_claim_description',
			__( 'Description', 'directorist-elementor' ),
			'.directorist-claim-listing__description',
			'claim_description'
		);

		$this->register_extension_box_style_section(
			$section_id . '_claim_notice',
			__( 'Notice', 'directorist-elementor' ),
			'.directorist-claim-listing__notice, .directorist-claim-listing__login-notice',
			'claim_notice'
		);

		$this->register_extension_button_style_section(
			$section_id . '_claim_button',
			__( 'Claim Button', 'directorist-elementor' ),
			'.directorist-claim-listing .directorist-btn, .directorist-claim-listing-widget .directorist-btn',
			'claim_button'
		);
	}
}
