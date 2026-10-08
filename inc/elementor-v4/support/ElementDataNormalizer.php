<?php
/**
 * Normalize legacy Elementor payloads for Directorist container elements.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Support;

use DirectoristElementor\Traits\Singleton;

class ElementDataNormalizer {
	use Singleton;

	/**
	 * Directorist elements that are registered with Elementor's element manager.
	 *
	 * Older saved documents may still store these as `elType: widget` with a
	 * `widgetType` value. Elementor's widget manager cannot render those because
	 * these classes extend Container/Element, not Widget_Base.
	 *
	 * @var array<string,bool>
	 */
	protected const CONTAINER_ELEMENT_TYPES = [
		'directorist_listings_loop'                => true,
		'directorist_homepage_search_loop'         => true,
		'directorist_listings_search'              => true,
		'directorist_homepage_search'              => true,
		'directorist_listing_card_template'        => true,
		'directorist_pricing_plans'                => true,
		'directorist_single_listing_author_profile' => true,
	];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_filter( 'elementor/frontend/builder_content_data', [ $this, 'normalize_builder_content_data' ], 20, 2 );
	}

	/**
	 * Normalize Elementor frontend builder content.
	 *
	 * @param mixed $elements_data Elementor data.
	 * @param int   $post_id Document post ID.
	 * @return mixed
	 */
	public function normalize_builder_content_data( $elements_data, int $post_id ) {
		unset( $post_id );

		if ( ! is_array( $elements_data ) ) {
			return $elements_data;
		}

		return $this->normalize_elements( $elements_data );
	}

	/**
	 * Normalize a list of Elementor elements.
	 *
	 * @param array<int,mixed> $elements Elementor element tree.
	 * @return array<int,mixed>
	 */
	public function normalize_elements( array $elements ): array {
		foreach ( $elements as $index => $element ) {
			if ( is_array( $element ) ) {
				$elements[ $index ] = $this->normalize_element( $element );
			}
		}

		return $elements;
	}

	/**
	 * Normalize one Elementor element and its children.
	 *
	 * @param array<string,mixed> $element Elementor element payload.
	 * @return array<string,mixed>
	 */
	public function normalize_element( array $element ): array {
		$element_type = sanitize_key( (string) ( $element['elType'] ?? '' ) );
		$widget_type  = sanitize_key( (string) ( $element['widgetType'] ?? '' ) );

		if ( 'widget' === $element_type && isset( self::CONTAINER_ELEMENT_TYPES[ $widget_type ] ) ) {
			$element['elType'] = $widget_type;
			unset( $element['widgetType'] );
		}

		if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$element['elements'] = $this->normalize_elements( $element['elements'] );
		}

		return $element;
	}
}
