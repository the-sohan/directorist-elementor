<?php
/**
 * Theme Builder root condition for Directorist listing author archives.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DirectoristListingAuthorArchiveCondition extends Condition_Base {
	/**
	 * Stable condition name.
	 */
	public const NAME = 'directorist_listing_author_archive';

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
		return esc_html__( 'Directorist Author Profile', 'directorist-elementor' );
	}

	/**
	 * Get all-items label.
	 *
	 * @return string
	 */
	public function get_all_label() {
		return esc_html__( 'All Listing Authors', 'directorist-elementor' );
	}

	/**
	 * Check whether current request matches a Directorist author profile route.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		unset( $args );

		return DirectoristBridge::get_instance()->is_author_profile_request();
	}
}
