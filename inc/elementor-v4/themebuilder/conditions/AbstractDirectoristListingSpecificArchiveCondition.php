<?php
/**
 * Theme Builder root condition for a specific Directorist archive taxonomy.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\ThemeBuilder\Module as ThemeBuilderModule;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

abstract class AbstractDirectoristListingSpecificArchiveCondition extends Condition_Base {
	/**
	 * Make taxonomy-specific archive templates outrank the default archive template.
	 *
	 * @return int
	 */
	public static function get_priority() {
		return 30;
	}

	/**
	 * Check whether the current request matches this taxonomy archive group.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		unset( $args );

		$taxonomy = $this->get_condition_taxonomy();

		return DirectoristBridge::get_instance()->get_current_archive_term_for_taxonomy( $taxonomy ) instanceof \WP_Term;
	}

	/**
	 * Register the leaf taxonomy condition used for term-specific matching.
	 *
	 * @return void
	 */
	public function register_sub_conditions() {
		$taxonomy = $this->get_condition_taxonomy();

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return;
		}

		$condition_data = $this->get_taxonomy_condition_data();
		$condition_name = sanitize_key( (string) ( $condition_data['name'] ?? '' ) );

		if ( '' === $condition_name ) {
			return;
		}

		$conditions_manager = ThemeBuilderModule::instance()->get_conditions_manager();

		if ( $conditions_manager && $conditions_manager->get_condition( $condition_name ) ) {
			$this->sub_conditions[] = $condition_name;
			return;
		}

		$condition_data['taxonomy'] = $taxonomy;

		$this->register_sub_condition( new DirectoristListingTaxonomyArchiveCondition( $condition_data ) );
	}

	/**
	 * Resolve the taxonomy this condition group targets.
	 *
	 * @return string
	 */
	abstract protected function get_condition_taxonomy(): string;

	/**
	 * Resolve the leaf taxonomy-condition data.
	 *
	 * @return array<string,string>
	 */
	abstract protected function get_taxonomy_condition_data(): array;
}
