<?php
/**
 * Theme Builder root condition for Directorist listing location archives.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;

class DirectoristListingLocationArchivesCondition extends AbstractDirectoristListingSpecificArchiveCondition {
	/**
	 * Stable condition name.
	 */
	public const NAME = 'directorist_listing_location_archives';

	/**
	 * Get parent condition type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return self::NAME;
	}

	/**
	 * Get condition name.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
	}

	/**
	 * Get UI label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'Listing Location Archives', 'directorist-elementor' );
	}

	/**
	 * Get all-items label.
	 *
	 * @return string
	 */
	public function get_all_label() {
		return esc_html__( 'All Listing Locations', 'directorist-elementor' );
	}

	/**
	 * Resolve the targeted taxonomy.
	 *
	 * @return string
	 */
	protected function get_condition_taxonomy(): string {
		return DirectoristBridge::get_instance()->get_location_taxonomy();
	}

	/**
	 * Resolve the leaf condition data.
	 *
	 * @return array<string,string>
	 */
	protected function get_taxonomy_condition_data(): array {
		return [
			'name'  => 'directorist_listing_location_archive',
			'label' => esc_html__( 'In Listing Location', 'directorist-elementor' ),
		];
	}
}
