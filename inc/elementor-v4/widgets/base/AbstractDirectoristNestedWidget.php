<?php
/**
 * Base Directorist nested Elementor widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use Elementor\Modules\NestedElements\Base\Widget_Nested_Base;
use Elementor\Plugin;

abstract class AbstractDirectoristNestedWidget extends Widget_Nested_Base {

	/**
	 * Keep nested widgets aligned with Elementor's optimized markup behavior.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return ! Plugin::$instance->experiments->is_feature_active( 'e_optimized_markup' );
	}

	/**
	 * Show nested widgets only when Elementor nested elements are available.
	 *
	 * @return bool
	 */
	public function show_in_panel(): bool {
		return Plugin::$instance->experiments->is_feature_active( 'nested-elements', true );
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
	protected function register_controls() {
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

	/**
	 * Determine whether a nested element tree has meaningful child content.
	 *
	 * Empty locked containers should not count as real composition content, otherwise
	 * the editor appears blank even though the nested slot/branch exists structurally.
	 *
	 * @param mixed $element Nested Elementor element instance.
	 * @return bool
	 */
	protected function has_composition_content( $element ): bool {
		if ( ! is_object( $element ) ) {
			return false;
		}

		if ( ! $this->is_container_element( $element ) ) {
			return true;
		}

		if ( ! method_exists( $element, 'get_children' ) ) {
			return false;
		}

		foreach ( (array) $element->get_children() as $child ) {
			if ( $this->has_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether a child collection includes meaningful composition.
	 *
	 * @param array<int,mixed> $children Child elements.
	 * @return bool
	 */
	protected function has_composition_children( array $children ): bool {
		foreach ( $children as $child ) {
			if ( $this->has_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether the supplied element is a container node.
	 *
	 * @param mixed $element Elementor element instance.
	 * @return bool
	 */
	protected function is_container_element( $element ): bool {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_data' ) ) {
			return false;
		}

		$data = (array) $element->get_data();

		return 'container' === (string) ( $data['elType'] ?? '' );
	}
}
