<?php
/**
 * Elementor v4 compatibility checks.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\Traits\Singleton;

class Compatibility {
	use Singleton;

	/**
	 * Check whether Elementor is loaded.
	 *
	 * @return bool
	 */
	public function is_elementor_loaded(): bool {
		return did_action( 'elementor/loaded' ) || class_exists( '\\Elementor\\Plugin' );
	}

	/**
	 * Check whether Elementor major version 4 is active.
	 *
	 * @return bool
	 */
	public function is_elementor_v4(): bool {
		if ( ! $this->is_elementor_loaded() || ! defined( 'ELEMENTOR_VERSION' ) ) {
			return false;
		}

		return version_compare( ELEMENTOR_VERSION, '4.0.0', '>=' ) && version_compare( ELEMENTOR_VERSION, '5.0.0', '<' );
	}

	/**
	 * Check whether nested elements APIs are available.
	 *
	 * @return bool
	 */
	public function has_nested_elements_api(): bool {
		if ( ! $this->is_elementor_v4() ) {
			return false;
		}

		if ( 0 === did_action( 'elementor/loaded' ) ) {
			return false;
		}

		if ( ! class_exists( '\\Elementor\\Widget_Base' ) || ! class_exists( '\\Elementor\\Controls_Manager' ) ) {
			return false;
		}

		$required_files = [
			defined( 'ELEMENTOR_PATH' ) ? trailingslashit( ELEMENTOR_PATH ) . 'modules/nested-elements/base/widget-nested-base.php' : '',
			defined( 'ELEMENTOR_PATH' ) ? trailingslashit( ELEMENTOR_PATH ) . 'modules/nested-elements/controls/control-nested-repeater.php' : '',
			defined( 'ELEMENTOR_PATH' ) ? trailingslashit( ELEMENTOR_PATH ) . 'modules/nested-tabs/widgets/nested-tabs.php' : '',
		];

		foreach ( $required_files as $required_file ) {
			if ( '' === $required_file || ! file_exists( $required_file ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check full module support.
	 *
	 * @return bool
	 */
	public function is_supported(): bool {
		return $this->is_elementor_v4() && $this->has_nested_elements_api();
	}

	/**
	 * Get the reason booting failed.
	 *
	 * @return string
	 */
	public function get_failure_message(): string {
		if ( $this->is_supported() ) {
			return '';
		}

		if ( ! $this->is_elementor_loaded() ) {
			return __( 'Directorist Elementor requires the Elementor plugin to be active.', 'directorist-elementor' );
		}

		if ( ! $this->is_elementor_v4() ) {
			$version = defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : __( 'unknown', 'directorist-elementor' );

			return sprintf(
				/* translators: %s: Detected Elementor version. */
				__( 'Directorist Elementor requires Elementor 4.x. Detected version: %s.', 'directorist-elementor' ),
				$version
			);
		}

		return __( 'Directorist Elementor requires Elementor nested elements APIs to be available.', 'directorist-elementor' );
	}

	/**
	 * Fallback strategy message.
	 *
	 * @return string
	 */
	public function get_fallback_strategy_message(): string {
		return __( 'Nested elements support is unavailable, so the composition widgets will remain disabled until Elementor 4 nested APIs are ready.', 'directorist-elementor' );
	}
}
