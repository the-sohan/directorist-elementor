<?php
/**
 * Per-instance helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Context;

use DirectoristElementor\Traits\Singleton;

class InstanceState {
	use Singleton;

	/**
	 * Normalize an instance id.
	 *
	 * @param string $instance_id Raw instance id.
	 * @return string
	 */
	public function normalize_instance_id( string $instance_id ): string {
		$normalized = sanitize_key( $instance_id );

		return '' !== $normalized ? $normalized : sanitize_key( wp_unique_id( 'direl-' ) );
	}

	/**
	 * Build a short deterministic hash for an instance.
	 *
	 * @param string $instance_id Instance id.
	 * @return string
	 */
	public function build_hash( string $instance_id ): string {
		return substr( md5( $this->normalize_instance_id( $instance_id ) ), 0, 12 );
	}

	/**
	 * Build widget root attributes for JS instance isolation.
	 *
	 * @param string               $instance_id Instance id.
	 * @param string               $widget_slug Widget slug.
	 * @param array<string,string> $extra_attributes Extra attributes.
	 * @return array<string,string>
	 */
	public function build_widget_root_attributes( string $instance_id, string $widget_slug, array $extra_attributes = [] ): array {
		$instance_id = $this->normalize_instance_id( $instance_id );

		return array_merge(
			[
				'data-direl-instance' => $instance_id,
				'data-direl-widget'   => sanitize_key( $widget_slug ),
				'data-direl-hash'     => $this->build_hash( $instance_id ),
			],
			$extra_attributes
		);
	}
}
