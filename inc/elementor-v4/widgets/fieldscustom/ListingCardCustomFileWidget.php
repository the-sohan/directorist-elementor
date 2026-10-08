<?php
/**
 * Listing card custom file widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsCustom;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractCustomIconTextFieldWidget;

class ListingCardCustomFileWidget extends AbstractCustomIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_custom_file';
	}

	public function get_title(): string {
		return __( 'File Upload', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-file-download';
	}

	protected function get_custom_field_widget_name(): string {
		return 'file';
	}

	protected function register_widget_controls(): void {
		$this->register_custom_field_content_controls(
			'section_custom_file_content',
			__( 'File Upload', 'directorist-elementor' ),
			[
				'default_icon' => [
					'value'   => 'fas fa-file-upload',
					'library' => 'fa-solid',
				],
			]
		);

		$this->register_icon_text_style_controls(
			'section_custom_file_style',
			__( 'File Upload', 'directorist-elementor' ),
			$this->get_custom_field_base_selector()
		);
	}

	protected function format_custom_field_display_value( int $listing_id, array $field_definition ): string {
		$file_items = DirectoristBridge::get_instance()->get_listing_custom_field_file_items( $listing_id, $field_definition );

		if ( ! empty( $file_items ) ) {
			$links = array_map(
				static function ( array $item ): string {
					return sprintf(
						'<a class="directorist-elementor-custom-file-link" href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
						esc_url( $item['url'] ?? '' ),
						esc_html( (string) ( $item['label'] ?? '' ) )
					);
				},
				$file_items
			);

			return implode( ', ', $links );
		}

		$fallback = DirectoristBridge::get_instance()->get_listing_custom_field_display_value( $listing_id, $field_definition );

		return '' !== $fallback ? esc_html( $fallback ) : '';
	}

	protected function allow_custom_field_html_value(): bool {
		return true;
	}
}
