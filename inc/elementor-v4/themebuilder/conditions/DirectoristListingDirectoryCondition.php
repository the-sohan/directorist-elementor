<?php
/**
 * Theme Builder sub-condition for Directorist listing directory types.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\QueryControl\Module as QueryModule;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DirectoristListingDirectoryCondition extends Condition_Base {
	/**
	 * Stable condition name.
	 */
	public const NAME = 'directorist_listing_directory';

	/**
	 * Get condition type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return DirectoristListingCondition::NAME;
	}

	/**
	 * Make directory-specific templates outrank the default listing template.
	 *
	 * @return int
	 */
	public static function get_priority() {
		return 30;
	}

	/**
	 * Get condition name.
	 *
	 * @return string
	 */
	public function get_name() {
		return self::NAME;
	}

	/**
	 * Get UI label.
	 *
	 * @return string
	 */
	public function get_label() {
		return esc_html__( 'In Directory Type', 'directorist-elementor' );
	}

	/**
	 * Check whether the current listing belongs to the selected directory type.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		$directory_type_id = absint( $args['id'] ?? 0 );

		if ( $directory_type_id <= 0 || ! is_singular( DirectoristBridge::get_instance()->get_listing_post_type() ) ) {
			return false;
		}

		return has_term(
			$directory_type_id,
			DirectoristBridge::get_instance()->get_directory_taxonomy(),
			get_queried_object_id()
		);
	}

	/**
	 * Register the directory-term selector control.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'directory_type',
			[
				'section' => 'settings',
				'type'    => QueryModule::QUERY_CONTROL_ID,
				'select2options' => [
					'dropdownCssClass' => 'elementor-conditions-select2-dropdown',
				],
				'autocomplete' => [
					'object'   => QueryModule::QUERY_OBJECT_TAX,
					'display'  => 'detailed',
					'by_field' => 'term_id',
					'query'    => [
						'taxonomy' => DirectoristBridge::get_instance()->get_directory_taxonomy(),
					],
				],
			]
		);
	}
}
