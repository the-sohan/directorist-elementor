<?php
/**
 * Directorist listing author archive Theme Builder document.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingAuthorArchiveCondition;

class ListingAuthorArchive extends ListingArchive {
	/**
	 * Resolve the Theme Builder root condition for this document type.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_type(): string {
		return DirectoristListingAuthorArchiveCondition::NAME;
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist-listing-author-archive';
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return esc_html__( 'Author Profile', 'directorist-elementor' );
	}

	/**
	 * Get document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return esc_html__( 'Author Profiles', 'directorist-elementor' );
	}

	/**
	 * Site Editor icon.
	 *
	 * @return string
	 */
	protected static function get_site_editor_icon() {
		return 'eicon-person';
	}

	/**
	 * Site Editor tooltip content.
	 *
	 * @return array<string,string>
	 */
	protected static function get_site_editor_tooltip_data() {
		return [
			'title'     => esc_html__( 'What is an Author Profile Template?', 'directorist-elementor' ),
			'content'   => esc_html__( 'An author profile template lets you design Directorist listing author profile pages.', 'directorist-elementor' ),
			'tip'       => esc_html__( 'Use the Author Profile element for profile fields and Listings Loop in Default mode for the current author listings.', 'directorist-elementor' ),
			'docs'      => '',
			'video_url' => '',
		];
	}

	/**
	 * Get default preview target.
	 *
	 * @return string
	 */
	public static function get_preview_as_default() {
		$page_id = DirectoristBridge::get_instance()->get_author_profile_page_id();

		return $page_id > 0 ? 'page/' . $page_id : parent::get_preview_as_default();
	}

	/**
	 * Limit preview options to the Directorist author profile page.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_preview_as_options() {
		$page_id = DirectoristBridge::get_instance()->get_author_profile_page_id();

		if ( $page_id <= 0 ) {
			return parent::get_preview_as_options();
		}

		return [
			'singular' => [
				'label'   => esc_html__( 'Author Profile', 'directorist-elementor' ),
				'options' => [
					'page/' . $page_id => get_the_title( $page_id ),
				],
			],
		];
	}

	/**
	 * Remote library category.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'author profile';

		return $config;
	}
}
