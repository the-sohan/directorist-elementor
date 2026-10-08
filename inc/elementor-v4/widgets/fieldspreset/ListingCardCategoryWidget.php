<?php
/**
 * Listing card category widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;
use Elementor\Controls_Manager;

class ListingCardCategoryWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_category';
	}

	public function get_title(): string {
		return __( 'Listing Category', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-folder-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'category', 'taxonomy' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_icon_text_content_controls(
			'section_category_content',
			__( 'Category', 'directorist-elementor' ),
			[
				'default_icon'       => [
					'value'   => 'fas fa-folder-open',
					'library' => 'fa-solid',
				],
				'default_label_text' => __( 'Category:', 'directorist-elementor' ),
			]
		);

		$this->start_injection( [ 'at' => 'after', 'of' => 'html_tag' ] );

		$this->add_control(
			'separator',
			[
				'label'   => __( 'Separator', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => ', ',
			]
		);

		$this->end_injection();

		$this->register_icon_text_style_controls(
			'section_category_style',
			__( 'Category', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-category'
		);
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render listing categories.', 'directorist-elementor' )
			);
			return;
		}

		$settings  = $this->get_settings_for_display();
		$separator = isset( $settings['separator'] ) ? sanitize_text_field( (string) $settings['separator'] ) : ', ';
		$value     = $this->get_linked_category_markup( $listing_id, '' !== $separator ? $separator : ', ' );

		if ( '' === $value ) {
			return;
		}

		$this->render_icon_text_markup(
			'directorist-elementor-listing-card-category',
			'directorist-elementor-listing-card-field__value',
			$value,
			(string) ( $settings['html_tag'] ?? 'div' ),
			'',
			'directorist-elementor-listing-card-field__link',
			'yes' === ( $settings['show_icon'] ?? 'yes' ) ? $this->get_icon_markup( $settings['field_icon'] ?? [] ) : '',
			'yes' === ( $settings['show_label'] ?? '' ) ? (string) ( $settings['label_text'] ?? '' ) : '',
			true
		);
	}

	/**
	 * Build frontend category links for the current listing.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $separator Term separator.
	 * @return string
	 */
	protected function get_linked_category_markup( int $listing_id, string $separator ): string {
		$bridge = DirectoristBridge::get_instance();
		$terms  = $bridge->get_listing_terms( $listing_id, $bridge->get_category_taxonomy() );

		if ( empty( $terms ) ) {
			return '';
		}

		$items = [];

		foreach ( $terms as $term ) {
			$url = get_term_link( $term );

			if ( is_wp_error( $url ) ) {
				$items[] = esc_html( (string) $term->name );
				continue;
			}

			$items[] = sprintf(
				'<a class="directorist-elementor-listing-card-field__link" href="%1$s">%2$s</a>',
				esc_url( (string) $url ),
				esc_html( (string) $term->name )
			);
		}

		return implode( esc_html( $separator ), array_filter( $items ) );
	}
}
