<?php
/**
 * Theme Builder root condition for Directorist archive templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DirectoristListingArchiveCondition extends Condition_Base {
	/**
	 * Stable condition name.
	 */
	public const NAME = 'directorist_listing_archive';

	/**
	 * Get condition type.
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
		return esc_html__( 'Directorist Listing Archive', 'directorist-elementor' );
	}

	/**
	 * Get all-items label.
	 *
	 * @return string
	 */
	public function get_all_label() {
		return esc_html__( 'All Listing Archives', 'directorist-elementor' );
	}

	/**
	 * Register taxonomy-specific sub-conditions.
	 *
	 * @return void
	 */
	public function register_sub_conditions() {
		$bridge = DirectoristBridge::get_instance();
		$conditions = [
			[
				'name' => 'directorist_listing_category_archive',
				'label' => esc_html__( 'In Listing Category', 'directorist-elementor' ),
				'taxonomy' => $bridge->get_category_taxonomy(),
			],
			[
				'name' => 'directorist_listing_location_archive',
				'label' => esc_html__( 'In Listing Location', 'directorist-elementor' ),
				'taxonomy' => $bridge->get_location_taxonomy(),
			],
			[
				'name' => 'directorist_listing_tag_archive',
				'label' => esc_html__( 'In Listing Tag', 'directorist-elementor' ),
				'taxonomy' => $bridge->get_tag_taxonomy(),
			],
		];

		foreach ( $conditions as $condition ) {
			if ( empty( $condition['taxonomy'] ) || ! taxonomy_exists( (string) $condition['taxonomy'] ) ) {
				continue;
			}

			$this->register_sub_condition( new DirectoristListingTaxonomyArchiveCondition( $condition ) );
		}
	}

	/**
	 * Check whether current request matches a Directorist listing archive.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		unset( $args );

		return DirectoristBridge::get_instance()->get_current_archive_term() instanceof \WP_Term;
	}
}
