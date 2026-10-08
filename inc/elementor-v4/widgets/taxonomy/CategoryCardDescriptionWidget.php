<?php
/**
 * Category card description widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Taxonomy;

class CategoryCardDescriptionWidget extends AbstractTaxonomyDescriptionWidget {

	public function get_name(): string {
		return 'directorist_category_card_description';
	}

	public function get_title(): string {
		return __( 'Category Description', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text';
	}

	protected function get_taxonomy_scope(): string {
		return 'category';
	}

	protected function get_scope_label(): string {
		return __( 'Category Description', 'directorist-elementor' );
	}
}
