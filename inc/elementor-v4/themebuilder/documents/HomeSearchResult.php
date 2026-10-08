<?php
/**
 * Directorist Homepage Search Result Theme Builder document.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristHomeSearchResultCondition;

class HomeSearchResult extends ListingArchive {
	/**
	 * Resolve the Theme Builder root condition for this document type.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_type(): string {
		return DirectoristHomeSearchResultCondition::NAME;
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist-home-search-result';
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return esc_html__( 'Homepage Search Result', 'directorist-elementor' );
	}

	/**
	 * Get document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return esc_html__( 'Homepage Search Results', 'directorist-elementor' );
	}

	/**
	 * Site Editor icon.
	 *
	 * @return string
	 */
	protected static function get_site_editor_icon() {
		return 'eicon-search-results';
	}

	/**
	 * Site Editor tooltip content.
	 *
	 * @return array<string,string>
	 */
	protected static function get_site_editor_tooltip_data() {
		return [
			'title'     => esc_html__( 'What is a Homepage Search Result Template?', 'directorist-elementor' ),
			'content'   => esc_html__( 'A homepage search result template displays searches submitted by the Directorist Homepage Search widget.', 'directorist-elementor' ),
			'tip'       => esc_html__( 'Use Homepage Search Loop in this template. Copy the Homepage Search widget from the template to any page that should send visitors here.', 'directorist-elementor' ),
			'docs'      => '',
			'video_url' => '',
		];
	}

	/**
	 * Remote library category.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'homepage search result';

		return $config;
	}
}
