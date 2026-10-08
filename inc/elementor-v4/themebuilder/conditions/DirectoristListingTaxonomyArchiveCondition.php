<?php
/**
 * Theme Builder taxonomy condition for Directorist archive templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Conditions;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use ElementorPro\Modules\QueryControl\Module as QueryModule;
use ElementorPro\Modules\ThemeBuilder\Conditions\Condition_Base;

class DirectoristListingTaxonomyArchiveCondition extends Condition_Base {
	/**
	 * Taxonomy slug.
	 *
	 * @var string
	 */
	protected string $taxonomy = '';

	/**
	 * Stable condition name.
	 *
	 * @var string
	 */
	protected string $condition_name = '';

	/**
	 * Condition label.
	 *
	 * @var string
	 */
	protected string $condition_label = '';

	/**
	 * Get parent condition type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return DirectoristListingArchiveCondition::NAME;
	}

	/**
	 * Make taxonomy-specific templates outrank the default archive template.
	 *
	 * @return int
	 */
	public static function get_priority() {
		return 30;
	}

	/**
	 * Constructor.
	 *
	 * @param array<string,mixed> $data Condition data.
	 */
	public function __construct( array $data = [] ) {
		$this->taxonomy        = sanitize_key( (string) ( $data['taxonomy'] ?? '' ) );
		$this->condition_name  = sanitize_key( (string) ( $data['name'] ?? '' ) );
		$this->condition_label = (string) ( $data['label'] ?? '' );

		parent::__construct();
	}

	/**
	 * Get condition name.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->condition_name;
	}

	/**
	 * Get UI label.
	 *
	 * @return string
	 */
	public function get_label() {
		return '' !== $this->condition_label ? $this->condition_label : $this->taxonomy;
	}

	/**
	 * Get the label for the "all terms" variant of this taxonomy condition.
	 *
	 * @return string
	 */
	public function get_all_label() {
		$taxonomy_object = get_taxonomy( $this->taxonomy );

		if ( ! $taxonomy_object ) {
			return parent::get_all_label();
		}

		return sprintf(
			/* translators: %s: Taxonomy plural label. */
			esc_html__( 'All %s', 'directorist-elementor' ),
			(string) ( $taxonomy_object->labels->name ?? $taxonomy_object->label )
		);
	}

	/**
	 * Check whether the current archive matches the selected term.
	 *
	 * @param array<string,mixed> $args Condition args.
	 * @return bool
	 */
	public function check( $args ) {
		if ( '' === $this->taxonomy ) {
			return false;
		}

		$current_term = DirectoristBridge::get_instance()->get_current_archive_term_for_taxonomy( $this->taxonomy );

		if ( ! $current_term instanceof \WP_Term ) {
			return false;
		}

		$term_id = absint( $args['id'] ?? 0 );

		if ( $term_id <= 0 ) {
			return true;
		}

		return absint( $current_term->term_id ) === $term_id;
	}

	/**
	 * Register the taxonomy term selector control.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->add_control(
			'taxonomy',
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
						'taxonomy' => $this->taxonomy,
					],
				],
			]
		);
	}
}
