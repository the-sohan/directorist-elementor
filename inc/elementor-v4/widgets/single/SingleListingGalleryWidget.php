<?php
/**
 * Single listing gallery widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionFieldTemplateWidget;

class SingleListingGalleryWidget extends AbstractSingleExtensionFieldTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_gallery';
	}

	public function get_title(): string {
		return __( 'Gallery', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'gallery', 'images' ] );
	}

	protected function get_extension_slug(): string {
		return 'gallery';
	}

	protected function get_field_widget_name(): string {
		return 'gallery';
	}

	protected function get_field_wrapper_selector(): string {
		return '.directorist-gallery-grid-two';
	}

	protected function get_field_body_selector(): string {
		return '.directorist-gallery-grid-two';
	}

	protected function get_field_icon_selector(): string {
		return '';
	}

	protected function build_field_data( int $listing_id, array $settings ): array {
		return [
			'widget_group' => 'other_widgets',
			'widget_name'  => 'gallery',
			'value'        => get_post_meta( $listing_id, '_gallery_img', true ),
		];
	}

	protected function register_additional_extension_field_style_controls(): void {
		$this->register_extension_box_style_section(
			'section_gallery_item_style',
			__( 'Gallery Item', 'directorist-elementor' ),
			'.directorist-gallery-grid-two__item, .directorist-gallery-grid-two a, .directorist-gallery-single',
			'gallery_item'
		);

		$this->register_extension_box_style_section(
			'section_gallery_image_style',
			__( 'Gallery Image', 'directorist-elementor' ),
			'.directorist-gallery-grid-two img, .directorist-gallery-single img',
			'gallery_image'
		);
	}
}
