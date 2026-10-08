<?php
/**
 * Location card title widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class LocationCardTitleWidget extends AbstractTaxonomyTitleWidget {

	public function get_name(): string {
		return 'directorist_location_card_title';
	}

	public function get_title(): string {
		return __( 'Location Title', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-heading';
	}

	protected function get_taxonomy_scope(): string {
		return 'location';
	}

	protected function get_scope_label(): string {
		return __( 'Location Title', 'directorist-elementor' );
	}
}
