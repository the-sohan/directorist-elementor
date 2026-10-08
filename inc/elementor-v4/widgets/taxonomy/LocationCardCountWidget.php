<?php
/**
 * Location card count widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class LocationCardCountWidget extends AbstractTaxonomyCountWidget {

	public function get_name(): string {
		return 'directorist_location_card_count';
	}

	public function get_title(): string {
		return __( 'Location Count', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function get_taxonomy_scope(): string {
		return 'location';
	}

	protected function get_scope_label(): string {
		return __( 'Location Count', 'directorist-elementor' );
	}
}
