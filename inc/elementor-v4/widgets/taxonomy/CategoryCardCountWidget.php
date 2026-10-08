<?php
/**
 * Category card count widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class CategoryCardCountWidget extends AbstractTaxonomyCountWidget {

	public function get_name(): string {
		return 'directorist_category_card_count';
	}

	public function get_title(): string {
		return __( 'Category Count', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function get_taxonomy_scope(): string {
		return 'category';
	}

	protected function get_scope_label(): string {
		return __( 'Category Count', 'directorist-elementor' );
	}
}
