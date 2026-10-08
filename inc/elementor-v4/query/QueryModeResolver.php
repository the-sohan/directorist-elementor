<?php
/**
 * Query mode normalization helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Query;

use DirectoristElementor\Traits\Singleton;

class QueryModeResolver {
	use Singleton;

	/**
	 * Normalize query mode.
	 *
	 * @param mixed $query_mode Query mode value.
	 * @return string
	 */
	public function normalize_query_mode( $query_mode ): string {
		$query_mode = sanitize_key( (string) $query_mode );

		return in_array( $query_mode, [ 'default', 'custom' ], true ) ? $query_mode : 'default';
	}

	/**
	 * Normalize query type.
	 *
	 * @param mixed $query_type Query type value.
	 * @return string
	 */
	public function normalize_query_type( $query_type ): string {
		$query_type = sanitize_key( (string) $query_type );

		return in_array( $query_type, [ 'regular', 'selective' ], true ) ? $query_type : 'regular';
	}
}
