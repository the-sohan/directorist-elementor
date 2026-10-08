<?php
/**
 * Single listing digital downloads widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionSectionTemplateWidget;

class SingleListingDigitalDownloadsWidget extends AbstractSingleExtensionSectionTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_digital_downloads';
	}

	public function get_title(): string {
		return __( 'Digital Downloads', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-download-button';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'digital', 'download', 'marketplace' ] );
	}

	protected function get_extension_slug(): string {
		return 'marketplace';
	}

	protected function get_section_widget_name(): string {
		return 'sell_digital_download';
	}

	protected function get_default_section_icon(): string {
		return 'las la-shopping-cart';
	}

	protected function get_section_wrapper_selector(): string {
		return '.directorist-card-digital-download, .ddm_widget';
	}

	protected function get_section_title_selector(): string {
		return '.directorist-card-digital-download .directorist-card__header__title, .directorist-card-digital-download .directorist-card__header--title, .ddm_widget-title .atbd_widget_title';
	}

	protected function get_section_body_selector(): string {
		return '.directorist-card-digital-download .directorist-card__body, .ddm_widget-body, #directorist-digital-product';
	}

	protected function get_section_header_selector(): string {
		return '.directorist-card-digital-download .directorist-card__header, .ddm_widget-title';
	}

	protected function register_additional_extension_section_style_controls( string $section_id ): void {
		$this->register_extension_box_style_section(
			$section_id . '_marketplace_form',
			__( 'Purchase Form', 'directorist-elementor' ),
			'#directorist-digital-product',
			'marketplace_form'
		);

		$this->register_extension_box_style_section(
			$section_id . '_marketplace_price_summary',
			__( 'Price Summary', 'directorist-elementor' ),
			'.ddm_listing-pricing-total',
			'marketplace_price_summary'
		);

		$this->register_extension_text_style_section(
			$section_id . '_marketplace_price',
			__( 'Price Text', 'directorist-elementor' ),
			'.ddm_listing-pricing-total .ddm_price, .ddm_listing-pricing-total strong',
			'marketplace_price'
		);

		$this->register_extension_box_style_section(
			$section_id . '_marketplace_option_group',
			__( 'Option Group', 'directorist-elementor' ),
			'.ddm-template',
			'marketplace_option_group'
		);

		$this->register_extension_text_style_section(
			$section_id . '_marketplace_option_title',
			__( 'Option Title', 'directorist-elementor' ),
			'.ddm-template__title',
			'marketplace_option_title'
		);

		$this->register_extension_box_style_section(
			$section_id . '_marketplace_option_row',
			__( 'Option Row', 'directorist-elementor' ),
			'.ddm-template-input',
			'marketplace_option_row'
		);

		$this->register_extension_icon_style_section(
			$section_id . '_marketplace_choice_input',
			__( 'Choice Input', 'directorist-elementor' ),
			'.ddm-template-input input[type="radio"], .ddm-template-input input[type="checkbox"]',
			'marketplace_choice_input'
		);

		$this->register_extension_text_style_section(
			$section_id . '_marketplace_option_label',
			__( 'Option Label', 'directorist-elementor' ),
			'.ddm-template-input label, .ddm-template-input .ddm-option-label',
			'marketplace_option_label'
		);

		$this->register_extension_text_style_section(
			$section_id . '_marketplace_option_description',
			__( 'Option Description', 'directorist-elementor' ),
			'.ddm-template-input .ddm-description, .ddm-template-input p',
			'marketplace_option_description'
		);

		$this->register_extension_text_style_section(
			$section_id . '_marketplace_option_price',
			__( 'Option Price', 'directorist-elementor' ),
			'.ddm-template-input .ddm_price, .ddm-template-input .ddm-option-price',
			'marketplace_option_price'
		);

		$this->register_extension_box_style_section(
			$section_id . '_marketplace_quantity',
			__( 'Quantity Input', 'directorist-elementor' ),
			'.single-quantity-template input, .ddm-template-input input[type="number"]',
			'marketplace_quantity'
		);

		$this->register_extension_box_style_section(
			$section_id . '_marketplace_submit_area',
			__( 'Submit Area', 'directorist-elementor' ),
			'.ddm_submit-btn',
			'marketplace_submit_area'
		);

		$this->register_extension_button_style_section(
			$section_id . '_marketplace_button',
			__( 'Buy Button', 'directorist-elementor' ),
			'.ddm_submit-btn .buy-now-btn, .buy-now-btn',
			'marketplace_button'
		);
	}
}
