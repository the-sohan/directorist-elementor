<?php
/**
 * Plugin Name:     Directorist Elementor
 * Plugin URI:      https://directorist.com
 * Description:     Elementor integration for Directorist archive layouts, listing cards, and single listing templates.
 * Author:          wpWax
 * Author URI:      https://directorist.com
 * Text Domain:     directorist-elementor
 * Domain Path:     /languages
 * Version:         1.1
 * Requires PHP:    8.0
 * Requires at least: 6.5
 * Tested up to:    6.9
 * Requires Plugins: directorist, elementor
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor;

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'DIRECTORIST_ELEMENTOR_FILE' ) ) {
	define( 'DIRECTORIST_ELEMENTOR_FILE', __FILE__ );
}

if ( ! defined( 'DIRECTORIST_ELEMENTOR_VERSION' ) ) {
	define( 'DIRECTORIST_ELEMENTOR_VERSION', '1.1' );
}

if ( ! defined( 'DIRECTORIST_ELEMENTOR_PATH' ) ) {
	define( 'DIRECTORIST_ELEMENTOR_PATH', trailingslashit( plugin_dir_path( DIRECTORIST_ELEMENTOR_FILE ) ) );
}

if ( ! defined( 'DIRECTORIST_ELEMENTOR_URL' ) ) {
	define( 'DIRECTORIST_ELEMENTOR_URL', trailingslashit( plugin_dir_url( DIRECTORIST_ELEMENTOR_FILE ) ) );
}

if ( ! defined( 'DIRECTORIST_ELEMENTOR_BASENAME' ) ) {
	define( 'DIRECTORIST_ELEMENTOR_BASENAME', plugin_basename( DIRECTORIST_ELEMENTOR_FILE ) );
}

$autoload_file = __DIR__ . '/vendor/autoload.php';

if ( file_exists( $autoload_file ) ) {
	require_once $autoload_file;
}

spl_autoload_register(
	static function ( string $class ): void {
		$prefix = __NAMESPACE__ . '\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$class_parts    = explode( '\\', $relative_class );
		$class_name     = array_pop( $class_parts );
		$directory      = implode(
			'/',
			array_map(
				static function ( string $class_part ): string {
					if ( 'ElementorV4' === $class_part ) {
						return 'elementor-v4';
					}

					return strtolower( $class_part );
				},
				$class_parts
			)
		);
		$file_path      = __DIR__ . '/inc/' . ( $directory ? $directory . '/' : '' ) . $class_name . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;
		}
	}
);

use DirectoristElementor\Core\Plugin;

/**
 * Initialize the plugin.
 *
 * @return void
 */
function init(): void {
	Plugin::get_instance();
}

init();
