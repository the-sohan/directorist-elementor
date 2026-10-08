<?php
/**
 * Activate the Elementor features required by Directorist nested elements.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\Services\DirectoristDependencyService;
use DirectoristElementor\Traits\Singleton;

class FeatureDependencyManager {
	use Singleton;

	private const REQUIRED_FEATURES = [ 'container', 'nested-elements' ];

	private bool $changed = false;

	protected function __construct() {
		// Elementor creates its experiments manager on WordPress init. Persist the
		// site-scoped states before that manager reads them on this request.
		add_action( 'plugins_loaded', [ $this, 'activate_for_current_site' ], 5 );
		add_action( 'elementor/init', [ $this, 'verify_and_refresh' ], 1 );
		add_action( 'admin_notices', [ $this, 'render_failure_notice' ] );

		if ( did_action( 'plugins_loaded' ) ) {
			$this->activate_for_current_site();
		}
	}

	/** Ensure both features are enabled for the current site, including subsites. */
	public function activate_for_current_site(): void {
		if ( ! DirectoristDependencyService::get_instance()->is_directorist_active()
			|| ! defined( 'ELEMENTOR_VERSION' )
			|| version_compare( ELEMENTOR_VERSION, '4.0.0', '<' )
			|| version_compare( ELEMENTOR_VERSION, '5.0.0', '>=' ) ) {
			return;
		}

		foreach ( self::REQUIRED_FEATURES as $feature ) {
			$key = 'elementor_experiment-' . $feature;
			if ( 'active' !== get_option( $key ) && update_option( $key, 'active' ) ) {
				$this->changed = true;
			}
		}
	}

	/** Verify Elementor accepted both feature states and their dependency chain. */
	public function are_active(): bool {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}

		$plugin = \Elementor\Plugin::instance();
		$experiments = $plugin->experiments ?? null;
		if ( ! is_object( $experiments ) || ! method_exists( $experiments, 'is_feature_active' ) || ! method_exists( $experiments, 'get_features' ) ) {
			return false;
		}

		foreach ( self::REQUIRED_FEATURES as $feature ) {
			if ( ! $experiments->get_features( $feature ) || ! $experiments->is_feature_active( $feature, true ) ) {
				return false;
			}
		}

		return true;
	}

	/** Refresh Elementor's generated files once when feature states change. */
	public function verify_and_refresh(): void {
		if ( ! $this->changed || ! $this->are_active() ) {
			return;
		}

		$plugin = \Elementor\Plugin::instance();
		if ( isset( $plugin->files_manager ) && is_object( $plugin->files_manager ) && method_exists( $plugin->files_manager, 'clear_cache' ) ) {
			$plugin->files_manager->clear_cache();
		}
		$this->changed = false;
	}

	/** Explain why Directorist elements/imports are unavailable if activation fails. */
	public function render_failure_notice(): void {
		if ( ! current_user_can( 'manage_options' )
			|| ! DirectoristDependencyService::get_instance()->is_directorist_active()
			|| ! defined( 'ELEMENTOR_VERSION' )
			|| version_compare( ELEMENTOR_VERSION, '4.0.0', '<' )
			|| version_compare( ELEMENTOR_VERSION, '5.0.0', '>=' )
			|| $this->are_active() ) {
			return;
		}
		?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Directorist Elementor requires Elementor Container and Nested Elements. The plugin could not activate them for this site; check Elementor feature settings and database write access.', 'directorist-elementor' ); ?></p></div>
		<?php
	}
}
