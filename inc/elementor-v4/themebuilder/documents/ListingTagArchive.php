<?php
/**
 * Directorist listing tag archive Theme Builder document.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingTagArchivesCondition;

class ListingTagArchive extends ListingArchive {
	/**
	 * Resolve the Theme Builder root condition for this document type.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_type(): string {
		return DirectoristListingTagArchivesCondition::NAME;
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist-listing-tag-archive';
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return esc_html__( 'Listing Tag Archive', 'directorist-elementor' );
	}

	/**
	 * Get document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return esc_html__( 'Listing Tag Archives', 'directorist-elementor' );
	}

	/**
	 * Theme Builder icon.
	 *
	 * @return string
	 */
	protected static function get_site_editor_icon() {
		return 'eicon-tags';
	}

	/**
	 * Resolve archive taxonomy.
	 *
	 * @return string
	 */
	protected static function get_archive_taxonomy(): string {
		return DirectoristBridge::get_instance()->get_tag_taxonomy();
	}

	/**
	 * Resolve Theme Builder sub condition.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_sub_type(): string {
		return '';
	}
}
