<?php
/**
 * Import session persistence.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;

class ImportSessionLogger {
	use Singleton;

	private const OPTION_NAME = 'directorist_elementor_template_import_sessions';
	private const MAX_RECORDS = 30;

	/**
	 * Persist an import session summary.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $status Import status.
	 * @param string        $message User-facing message.
	 * @return void
	 */
	public function store( ImportSession $session, string $status, string $message = '' ): void {
		$sessions = get_option( self::OPTION_NAME, [] );

		if ( ! is_array( $sessions ) ) {
			$sessions = [];
		}

		$record            = $session->to_array();
		$record['status']  = sanitize_key( $status );
		$record['message'] = sanitize_text_field( $message );

		array_unshift( $sessions, $record );
		$sessions = array_slice( $sessions, 0, self::MAX_RECORDS );

		update_option( self::OPTION_NAME, $sessions, false );
	}

	/**
	 * Get stored session history.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function all(): array {
		$sessions = get_option( self::OPTION_NAME, [] );

		return is_array( $sessions ) ? $sessions : [];
	}
}
