<?php
/**
 * Singleton trait.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\Traits;

trait Singleton {

	/**
	 * Protected class constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
	}

	/**
	 * Get singleton instance for the called class.
	 *
	 * @return object
	 */
	final public static function get_instance() {
		static $instances = [];

		$called_class = get_called_class();

		if ( ! isset( $instances[ $called_class ] ) ) {
			$instances[ $called_class ] = new $called_class();
		}

		return $instances[ $called_class ];
	}

	/**
	 * Prevent clone.
	 *
	 * @return void
	 */
	final protected function __clone() {
	}
}
