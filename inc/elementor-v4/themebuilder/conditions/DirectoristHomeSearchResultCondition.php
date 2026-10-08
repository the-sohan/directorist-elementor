<?php
/**
 * Theme Builder condition for Directorist Homepage Search results.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DirectoristHomeSearchResultCondition extends Condition_Base {
	/**
	 * Stable condition name.
	 */
	public const NAME = 'directorist_home_search_result';

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
		return esc_html__( 'Directorist Homepage Search Result', 'directorist-elementor' );
	}

	/**
	 * Get all-items label.
	 *
	 * @return string
	 */
	public function get_all_label() {
		return esc_html__( 'Homepage Search Result', 'directorist-elementor' );
	}

	/**
	 * Check whether current request matches the home-search result route.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		unset( $args );

		return DirectoristBridge::get_instance()->is_home_search_result_request();
	}
}
