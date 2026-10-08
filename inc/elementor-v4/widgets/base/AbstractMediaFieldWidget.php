<?php
/**
 * Base preset widget for listing media fields.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

abstract class AbstractMediaFieldWidget extends AbstractPresetFieldWidget {

	/**
	 * Normalize a requested image size.
	 *
	 * @param string $size Requested size.
	 * @return string
	 */
	protected function sanitize_image_size( string $size ): string {
		$size = sanitize_key( $size );

		return in_array( $size, [ 'thumbnail', 'medium', 'large', 'full' ], true ) ? $size : 'large';
	}
}
