<?php
/**
 * Base Directorist Elementor widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use Elementor\Widget_Base;

abstract class AbstractDirectoristWidget extends Widget_Base {

	/**
	 * Directorist field widgets must render inline inside loop/listing context.
	 *
	 * Elementor's deferred shortcode render path breaks that context because the
	 * second-pass shortcode expansion runs after the loop listing stack is gone.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return false;
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<int,string>
	 */
	public function get_categories(): array {
		return [ $this->get_directorist_category_slug() ];
	}

	/**
	 * Get the primary Elementor category slug for the widget.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return [ 'directorist', 'directory', 'listing' ];
	}

	/**
	 * Register shared controls.
	 *
	 * @return void
	 */
	protected function register_controls(): void {
		$this->register_widget_controls();
	}

	/**
	 * Register widget-specific controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
	}

	/**
	 * Check whether the current render runs inside the editor.
	 *
	 * @return bool
	 */
	protected function is_editor_context(): bool {
		return EditorContext::get_instance()->is_editor_request();
	}

	/**
	 * Format HTML attributes.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	protected function format_html_attributes( array $attributes ): string {
		$pairs = [];

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ' ', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf(
				'%1$s="%2$s"',
				esc_attr( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		return implode( ' ', $pairs );
	}

	/**
	 * Render a standard placeholder block.
	 *
	 * @param string $title Placeholder title.
	 * @param string $description Placeholder description.
	 * @param string $meta Optional meta text.
	 * @return string
	 */
	protected function render_placeholder( string $title, string $description, string $meta = '' ): string {
		$meta_markup = '' !== $meta
			? sprintf( '<p class="directorist-elementor-placeholder__meta">%s</p>', esc_html( $meta ) )
			: '';

		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%1$s</p><p>%2$s</p>%3$s</div>',
			esc_html( $title ),
			esc_html( $description ),
			$meta_markup
		);
	}
}
