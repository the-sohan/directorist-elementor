<?php
/**
 * Protect Elementor menu labels on Directorist taxonomy pages.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Frontend;

use DirectoristElementor\Traits\Singleton;

class TaxonomyMenuTitleGuard {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_filter( 'nav_menu_item_title', [ $this, 'restore_directorist_taxonomy_menu_title' ], 999, 4 );
	}

	/**
	 * Restore the configured nav-menu label for Directorist virtual taxonomy pages.
	 *
	 * Directorist SEO intentionally replaces taxonomy page titles with the current
	 * term name. Elementor nav menus still need the menu item's configured label.
	 *
	 * @param string   $title Menu item title.
	 * @param \WP_Post $menu_item Menu item object.
	 * @param object   $args Menu args.
	 * @param int      $depth Menu depth.
	 * @return string
	 */
	public function restore_directorist_taxonomy_menu_title( $title, $menu_item, $args, $depth ): string {
		unset( $args, $depth );

		if ( ! $this->is_directorist_taxonomy_request() || ! $menu_item instanceof \WP_Post ) {
			return (string) $title;
		}

		if ( 'post_type' !== $menu_item->type || 'page' !== $menu_item->object ) {
			return (string) $title;
		}

		$taxonomy_page_ids = $this->get_directorist_taxonomy_page_ids();
		if ( ! in_array( absint( $menu_item->object_id ), $taxonomy_page_ids, true ) ) {
			return (string) $title;
		}

		$menu_title = get_post_field( 'post_title', absint( $menu_item->ID ), 'raw' );
		if ( '' === trim( (string) $menu_title ) ) {
			$menu_title = get_post_field( 'post_title', absint( $menu_item->object_id ), 'raw' );
		}

		return '' !== trim( (string) $menu_title ) ? (string) $menu_title : (string) $title;
	}

	/**
	 * Determine whether the current request is a Directorist virtual taxonomy page.
	 *
	 * @return bool
	 */
	protected function is_directorist_taxonomy_request(): bool {
		return '' !== (string) get_query_var( 'atbdp_category' )
			|| '' !== (string) get_query_var( 'atbdp_location' )
			|| '' !== (string) get_query_var( 'atbdp_tag' );
	}

	/**
	 * Get Directorist taxonomy page IDs.
	 *
	 * @return array<int>
	 */
	protected function get_directorist_taxonomy_page_ids(): array {
		if ( ! function_exists( 'directorist_get_page_id' ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map(
					'absint',
					[
						directorist_get_page_id( 'category' ),
						directorist_get_page_id( 'location' ),
						directorist_get_page_id( 'tag' ),
					]
				)
			)
		);
	}
}
