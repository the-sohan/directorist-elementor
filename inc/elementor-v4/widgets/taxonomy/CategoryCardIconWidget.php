<?php
/**
 * Category card icon widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class CategoryCardIconWidget extends AbstractTaxonomyIconWidget {

	public function get_name(): string {
		return 'directorist_category_card_icon';
	}

	public function get_title(): string {
		return __( 'Category Icon', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-favorite';
	}

	protected function get_taxonomy_scope(): string {
		return 'category';
	}

	protected function get_scope_label(): string {
		return __( 'Category Icon', 'directorist-elementor' );
	}
}
