<?php
/**
 * Location card image widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class LocationCardImageWidget extends AbstractTaxonomyImageWidget {

	public function get_name(): string {
		return 'directorist_location_card_image';
	}

	public function get_title(): string {
		return __( 'Location Image', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-image';
	}

	protected function get_taxonomy_scope(): string {
		return 'location';
	}

	protected function get_scope_label(): string {
		return __( 'Location Image', 'directorist-elementor' );
	}
}
