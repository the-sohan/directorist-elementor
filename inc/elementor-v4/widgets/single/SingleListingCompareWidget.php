<?php
/**
 * Single listing compare widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingCompareWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_compare';
	}

	public function get_title(): string {
		return __( 'Compare', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-handle';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'compare' ] );
	}

	protected function get_extension_slug(): string {
		return 'compare';
	}

	protected function get_field_widget_name(): string {
		return 'compare_badge';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-compare-btn';
	}

	protected function get_field_body_selector(): string {
		return '.directorist-compare-btn';
	}

	protected function get_field_icon_selector(): string {
		return '.directorist-compare-btn .atdlc-compare-icon, .directorist-compare-btn .directorist-icon-mask';
	}

	protected function register_additional_extension_field_style_controls(): void {
		$this->register_extension_button_style_section(
			'section_compare_button_style',
			__( 'Compare Button', 'directorist-elementor' ),
			'.directorist-compare-btn',
			'compare_button'
		);
	}
}
