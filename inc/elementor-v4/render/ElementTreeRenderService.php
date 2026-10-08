<?php
/**
 * Raw Elementor element tree renderer.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\ElementorV4\Support\ElementDataNormalizer;
use DirectoristElementor\Traits\Singleton;
use Elementor\Element_Base;
use Elementor\Plugin as ElementorPlugin;

class ElementTreeRenderService {
	use Singleton;

	/**
	 * Create an Elementor element instance from raw editor payload.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return Element_Base|null
	 */
	public function create_element_instance( array $element_data ): ?Element_Base {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		if ( $this->is_element_hidden( $element_data ) ) {
			return null;
		}

		$normalized_data = $this->normalize_element_data( $element_data );

		return ElementorPlugin::$instance->elements_manager->create_element_instance( $normalized_data );
	}

	/**
	 * Render a widget payload without the outer editor wrapper element.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return string
	 */
	public function render_widget_content( array $element_data ): string {
		$element = $this->create_element_instance( $element_data );

		if ( ! $element ) {
			return '';
		}

		if ( method_exists( $element, 'render_directorist_preview_content' ) ) {
			return trim( (string) $element->render_directorist_preview_content() );
		}

		if ( 'widget' !== sanitize_key( (string) ( $element_data['elType'] ?? 'widget' ) ) && method_exists( $element, 'print_element' ) ) {
			ob_start();
			$element->print_element();

			return trim( (string) ob_get_clean() );
		}

		if ( method_exists( $element, 'render_content' ) ) {
			ob_start();
			$element->render_content();

			return trim( (string) ob_get_clean() );
		}

		if ( method_exists( $element, 'print_element' ) ) {
			ob_start();
			$element->print_element();

			return trim( (string) ob_get_clean() );
		}

		return '';
	}

	/**
	 * Render a full Elementor element including its outer wrapper.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return string
	 */
	public function render_element( array $element_data ): string {
		$element = $this->create_element_instance( $element_data );

		if ( ! $element || ! method_exists( $element, 'print_element' ) ) {
			return '';
		}

		ob_start();
		$element->print_element();

		return trim( (string) ob_get_clean() );
	}

	/**
	 * Normalize a raw editor payload into an Elementor element structure.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return array<string,mixed>
	 */
	public function normalize_element_data( array $element_data ): array {
		$element_data = ElementDataNormalizer::get_instance()->normalize_element( $element_data );

		$normalized = [
			'id'       => $this->normalize_element_id( $element_data['id'] ?? wp_generate_uuid4() ),
			'elType'   => sanitize_key( (string) ( $element_data['elType'] ?? 'widget' ) ),
			'settings' => $this->normalize_value( $element_data['settings'] ?? [] ),
			'elements' => [],
			'isInner'  => ! empty( $element_data['isInner'] ),
		];

		if ( isset( $element_data['widgetType'] ) ) {
			$normalized['widgetType'] = sanitize_key( (string) $element_data['widgetType'] );
		}

		if ( $this->is_element_hidden( $element_data ) ) {
			$normalized['hidden'] = true;
		}

		foreach ( (array) ( $element_data['elements'] ?? [] ) as $child_data ) {
			if ( ! is_array( $child_data ) ) {
				continue;
			}

			if ( $this->is_element_hidden( $child_data ) ) {
				continue;
			}

			$normalized['elements'][] = $this->normalize_element_data( $child_data );
		}

		return $normalized;
	}

	/**
	 * Determine whether an Elementor payload has been hidden from Navigator.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return bool
	 */
	protected function is_element_hidden( array $element_data ): bool {
		return true === ( $element_data['hidden'] ?? false ) || 'true' === (string) ( $element_data['hidden'] ?? '' );
	}

	/**
	 * Normalize an Elementor element id without changing its case.
	 *
	 * @param mixed $id Raw id.
	 * @return string
	 */
	protected function normalize_element_id( $id ): string {
		$normalized = preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $id );

		return '' !== (string) $normalized ? (string) $normalized : wp_generate_uuid4();
	}

	/**
	 * Normalize arbitrary settings payload values.
	 *
	 * @param mixed $value Raw value.
	 * @return mixed
	 */
	protected function normalize_value( $value ) {
		if ( is_array( $value ) ) {
			$normalized = [];

			foreach ( $value as $key => $item ) {
				$normalized[ $key ] = $this->normalize_value( $item );
			}

			return $normalized;
		}

		if ( is_scalar( $value ) || null === $value ) {
			return $value;
		}

		return null;
	}
}
