<?php
/**
 * Location card icon widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class LocationCardIconWidget extends AbstractTaxonomyIconWidget {

	public function get_name(): string {
		return 'directorist_location_card_icon';
	}

	public function get_title(): string {
		return __( 'Location Icon', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-map-pin';
	}

	protected function get_taxonomy_scope(): string {
		return 'location';
	}

	protected function get_scope_label(): string {
		return __( 'Location Icon', 'directorist-elementor' );
	}
}
