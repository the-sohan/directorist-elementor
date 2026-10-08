<?php
/**
 * Structure defaults for Elementor composition widgets.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Composition;

use DirectoristElementor\Traits\Singleton;

class StructureDefaults {
	use Singleton;

	/**
	 * Get default nested child container structure.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_default_children_elements(): array {
		return [];
	}

	/**
	 * Get default listing-card field widgets for newly created card templates.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_default_card_field_elements(): array {
		return [
			$this->get_default_child_widget( 'directorist_listing_card_thumbnail', __( 'Listing Thumbnail', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_listing_card_title', __( 'Listing Title', 'directorist-elementor' ) ),
		];
	}

	/**
	 * Build a default child widget payload.
	 *
	 * @param string $widget_type Elementor widget type.
	 * @param string $title Widget title.
	 * @return array<string,mixed>
	 */
	protected function get_default_child_widget( string $widget_type, string $title ): array {
		return [
			'elType'          => 'widget',
			'widgetType'      => $widget_type,
			'settings'        => [
				'_title' => $title,
			],
			'elements'        => [],
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Get hard fallback card markup.
	 *
	 * @return string
	 */
	public function get_fallback_card_markup(): string {
		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%s</p><p>%s</p></div>',
			esc_html__( 'Listing card template', 'directorist-elementor' ),
			esc_html__( 'Add Directorist field widgets inside the listing card template to compose the current listing card.', 'directorist-elementor' )
		);
	}
}
