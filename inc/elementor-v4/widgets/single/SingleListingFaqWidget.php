<?php
/**
 * Single listing FAQ widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingFaqWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_faq';
	}

	public function get_title(): string {
		return __( 'FAQ', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-help-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'faq', 'accordion' ] );
	}

	protected function get_extension_slug(): string {
		return 'faq';
	}

	protected function get_field_widget_name(): string {
		return 'faqs';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-faq-accordion';
	}

	protected function get_field_title_selector(): string {
		return '.directorist-faq-accordion__title, .directorist-faq-accordion__title a';
	}

	protected function get_field_body_selector(): string {
		return '.directorist-faq-accordion__content';
	}

	protected function get_field_icon_selector(): string {
		return '.directorist-faq-accordion__title i, .directorist-faq-accordion__title .directorist-icon-mask';
	}

	protected function build_field_data( int $listing_id, array $settings ): array {
		return [
			'widget_group' => 'other_widgets',
			'widget_name'  => 'faqs',
			'value'        => get_post_meta( $listing_id, '_faqs', true ),
		];
	}

	protected function register_additional_extension_field_style_controls(): void {
		$this->register_extension_box_style_section(
			'section_faq_item_style',
			__( 'FAQ Item', 'directorist-elementor' ),
			'.directorist-faq-accordion__single',
			'faq_item'
		);

		$this->register_extension_box_style_section(
			'section_faq_question_style',
			__( 'Question Row', 'directorist-elementor' ),
			'.directorist-faq-accordion__title',
			'faq_question'
		);

		$this->register_extension_icon_style_section(
			'section_faq_toggle_icon_style',
			__( 'Toggle Icon', 'directorist-elementor' ),
			'.directorist-faq-accordion__title i, .directorist-faq-accordion__title .directorist-icon-mask',
			'faq_toggle_icon'
		);

		$this->register_extension_box_style_section(
			'section_faq_answer_style',
			__( 'Answer Panel', 'directorist-elementor' ),
			'.directorist-faq-accordion__content',
			'faq_answer'
		);
	}
}
