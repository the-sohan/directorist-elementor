<?php
/**
 * Directorist dependency guard service.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\Services;

use DirectoristElementor\Traits\Singleton;

class DirectoristDependencyService {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'admin_notices', [ $this, 'render_missing_dependency_notice' ] );
	}

	/**
	 * Check whether Directorist is available.
	 *
	 * @return bool
	 */
	public function is_directorist_active(): bool {
		return defined( 'ATBDP_VERSION' ) && function_exists( 'ATBDP' );
	}

	/**
	 * Render admin notice when Directorist is missing.
	 *
	 * @return void
	 */
	public function render_missing_dependency_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( $this->is_directorist_active() ) {
			return;
		}

		if ( ! did_action( 'plugins_loaded' ) ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'Directorist Elementor requires the Directorist plugin to be active.', 'directorist-elementor' ); ?>
			</p>
		</div>
		<?php
	}
}
