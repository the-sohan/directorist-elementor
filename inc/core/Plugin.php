<?php
/**
 * Main plugin bootstrap class.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\Core;

use DirectoristElementor\ElementorV4\Bootstrap;
use DirectoristElementor\ElementorV4\FeatureDependencyManager;
use DirectoristElementor\ElementorV4\Templates\TemplateImportBootstrap;
use DirectoristElementor\Services\DirectoristDependencyService;
use DirectoristElementor\Services\UpdateService;
use DirectoristElementor\Traits\Singleton;

final class Plugin {
	use Singleton;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public static string $plugin_url;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	public static string $plugin_path;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public static string $version = '1.1';

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		$this->define_constants();
		$this->check_requirements();
		$this->setup_hooks();
		$this->init_services();
	}

	/**
	 * Define plugin constants.
	 *
	 * @return void
	 */
	protected function define_constants(): void {
		self::$plugin_path = \defined( 'DIRECTORIST_ELEMENTOR_PATH' )
			? DIRECTORIST_ELEMENTOR_PATH
			: trailingslashit( dirname( __DIR__, 2 ) );
		self::$plugin_url  = \defined( 'DIRECTORIST_ELEMENTOR_URL' )
			? DIRECTORIST_ELEMENTOR_URL
			: plugin_dir_url( self::$plugin_path . 'directorist-elementor.php' );
	}

	/**
	 * Register generic WordPress/PHP requirement notices.
	 *
	 * @return void
	 */
	protected function check_requirements(): void {
		global $wp_version;

		if ( version_compare( $wp_version, '6.5', '<' ) ) {
			add_action( 'admin_notices', [ $this, 'render_wp_version_notice' ] );
		}

		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			add_action( 'admin_notices', [ $this, 'render_php_version_notice' ] );
		}
	}

	/**
	 * Set up hooks.
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ], 1 );
		add_action( 'plugins_loaded', [ $this, 'boot_integration_services' ], 20 );
	}

	/**
	 * Initialize always-on services.
	 *
	 * @return void
	 */
	protected function init_services(): void {
		DirectoristDependencyService::get_instance();
		UpdateService::get_instance();
		FeatureDependencyManager::get_instance();
		TemplateImportBootstrap::get_instance();
		Bootstrap::get_instance();
	}

	/**
	 * Boot integration services once Directorist is active.
	 *
	 * @return void
	 */
	public function boot_integration_services(): void {
		Bootstrap::get_instance()->maybe_boot();
	}

	/**
	 * Load text domain.
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'directorist-elementor',
			false,
			dirname(
				\defined( 'DIRECTORIST_ELEMENTOR_BASENAME' )
					? DIRECTORIST_ELEMENTOR_BASENAME
					: plugin_basename( self::$plugin_path . 'directorist-elementor.php' )
			) . '/languages'
		);
	}

	/**
	 * Render WordPress version notice.
	 *
	 * @return void
	 */
	public function render_wp_version_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: Required WordPress version. */
					esc_html__( 'Directorist Elementor requires WordPress version %s or higher.', 'directorist-elementor' ),
					'6.5'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Render PHP version notice.
	 *
	 * @return void
	 */
	public function render_php_version_notice(): void {
		?>
		<div class="notice notice-error">
			<p>
				<?php
				printf(
					/* translators: %s: Required PHP version. */
					esc_html__( 'Directorist Elementor requires PHP version %s or higher.', 'directorist-elementor' ),
					'8.0'
				);
				?>
			</p>
		</div>
		<?php
	}
}
