<?php
/**
 * Category card button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class CategoryCardButtonWidget extends AbstractTaxonomyButtonWidget {

	public function get_name(): string {
		return 'directorist_category_card_button';
	}

	public function get_title(): string {
		return __( 'Category Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	protected function get_taxonomy_scope(): string {
		return 'category';
	}

	protected function get_scope_label(): string {
		return __( 'Category Button', 'directorist-elementor' );
	}
}
