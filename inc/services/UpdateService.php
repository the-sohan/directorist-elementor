<?php
/**
 * Register native WordPress updates using the Directorist account license.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\Services;

use DirectoristElementor\Traits\Singleton;
use DirectoristElementor\Updates\EDDPluginUpdater;

final class UpdateService {
	use Singleton;

	private ?EDDPluginUpdater $updater = null;

	protected function __construct() {
		add_action( 'admin_init', [ $this, 'register_updater' ], 5 );
	}

	public function register_updater(): void {
		if ( null !== $this->updater ) {
			return;
		}

		$subscriptions = get_user_meta( get_current_user_id(), '_plugins_available_in_subscriptions', true );
		$subscription  = is_array( $subscriptions ) ? ( $subscriptions['directorist-elementor'] ?? [] ) : [];
		$license       = is_array( $subscription ) && is_string( $subscription['license'] ?? null )
			? trim( $subscription['license'] )
			: '';

		$this->updater = new EDDPluginUpdater(
			'https://directorist.com/',
			DIRECTORIST_ELEMENTOR_FILE,
			[
				'version' => DIRECTORIST_ELEMENTOR_VERSION,
				'license' => $license,
				'item_id' => 372388,
				'author'  => 'WpWax',
				'url'     => home_url(),
				'beta'    => false,
			]
		);
	}
}
