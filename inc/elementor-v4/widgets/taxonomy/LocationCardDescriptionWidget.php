<?php
/**
 * Location card description widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class LocationCardDescriptionWidget extends AbstractTaxonomyDescriptionWidget {

	public function get_name(): string {
		return 'directorist_location_card_description';
	}

	public function get_title(): string {
		return __( 'Location Description', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	protected function get_taxonomy_scope(): string {
		return 'location';
	}

	protected function get_scope_label(): string {
		return __( 'Location Description', 'directorist-elementor' );
	}
}
