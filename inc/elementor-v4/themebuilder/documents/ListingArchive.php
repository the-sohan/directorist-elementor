<?php
/**
 * Directorist listing archive Theme Builder document.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingArchiveCondition;
use ElementorPro\Modules\ThemeBuilder\Documents\Archive;
use ElementorPro\Modules\ThemeBuilder\Documents\Theme_Document;
use ElementorPro\Modules\ThemeBuilder\Module as ThemeBuilderModule;

class ListingArchive extends Archive {
	/**
	 * Optional Theme Builder subtype used to pre-save an archive condition.
	 *
	 * @return string
	 */
	public static function get_sub_type() {
		return static::get_archive_condition_sub_type();
	}

	/**
	 * Get document properties.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['location']       = 'archive';
		$properties['condition_type'] = static::get_archive_condition_type();
		$properties['export_group']   = Theme_Document::EXPORT_GROUP;

		return $properties;
	}

	/**
	 * Get document type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist-listing-archive';
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return esc_html__( 'Listing Archive', 'directorist-elementor' );
	}

	/**
	 * Get document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return esc_html__( 'Listing Archives', 'directorist-elementor' );
	}

	/**
	 * Site Editor icon.
	 *
	 * @return string
	 */
	protected static function get_site_editor_icon() {
		return 'eicon-archive-posts';
	}

	/**
	 * Site Editor tooltip content.
	 *
	 * @return array<string,string>
	 */
	protected static function get_site_editor_tooltip_data() {
		return [
			'title'     => esc_html__( 'What is a Listing Archive Template?', 'directorist-elementor' ),
			'content'   => esc_html__( 'A listing archive template lets you design Directorist taxonomy archive pages such as listing categories, locations, and tags.', 'directorist-elementor' ),
			'tip'       => esc_html__( 'Create one default archive template, then add term-specific templates for listing categories, locations, or tags.', 'directorist-elementor' ),
			'docs'      => '',
			'video_url' => '',
		];
	}

	/**
	 * Get default archive preview target.
	 *
	 * @return string
	 */
	public static function get_preview_as_default() {
		$taxonomies = static::get_supported_preview_taxonomies();

		return ! empty( $taxonomies[0] ) ? 'taxonomy/' . $taxonomies[0] : parent::get_preview_as_default();
	}

	/**
	 * Limit preview surfaces to Directorist listing archives.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_preview_as_options() {
		$options = [];

		foreach ( static::get_supported_preview_taxonomies() as $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy );

			if ( ! $taxonomy_object ) {
				continue;
			}

			$options[ 'taxonomy/' . $taxonomy ] = sprintf(
				/* translators: %s: Taxonomy label. */
				esc_html__( '%s archive', 'directorist-elementor' ),
				(string) ( $taxonomy_object->labels->singular_name ?? $taxonomy_object->label )
			);
		}

		if ( empty( $options ) ) {
			return parent::get_preview_as_options();
		}

		return [
			'archive' => [
				'label'   => esc_html__( 'Listing Archive', 'directorist-elementor' ),
				'options' => $options,
			],
		];
	}

	/**
	 * Save the template type and ensure a new template defaults to all
	 * Directorist listing archives when the user has not chosen a more specific
	 * condition.
	 *
	 * @return void
	 */
	public function save_template_type() {
		parent::save_template_type();

		if ( ! class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' ) ) {
			return;
		}

		$conditions_manager = ThemeBuilderModule::instance()->get_conditions_manager();

		if ( ! $conditions_manager || ! empty( $conditions_manager->get_document_conditions( $this ) ) ) {
			return;
		}

		$conditions_manager->save_conditions(
			$this->get_main_id(),
			[
				[
					'include',
					static::get_archive_condition_type(),
				],
			]
		);
	}

	/**
	 * Register preview defaults for Directorist archives.
	 *
	 * @return void
	 */
	protected function register_controls() {
		parent::register_controls();

		$default_preview_type = static::get_preview_as_default();

		$this->update_control(
			'preview_type',
			[
				'default' => $default_preview_type,
			]
		);

		$default_preview_id = $this->resolve_default_preview_term_id( $default_preview_type );

		if ( $default_preview_id > 0 ) {
			$this->update_control(
				'preview_id',
				[
					'default' => $default_preview_id,
				]
			);
		}
	}

	/**
	 * Remote library category.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'listing archive';

		return $config;
	}

	/**
	 * Resolve supported preview taxonomies.
	 *
	 * @return array<int,string>
	 */
	protected static function get_supported_preview_taxonomies(): array {
		$taxonomy = static::get_archive_taxonomy();

		if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) ) {
			return [ $taxonomy ];
		}

		$bridge = DirectoristBridge::get_instance();

		return array_values(
			array_filter(
				[
					$bridge->get_category_taxonomy(),
					$bridge->get_location_taxonomy(),
					$bridge->get_tag_taxonomy(),
				],
				static fn( $taxonomy ) => is_string( $taxonomy ) && '' !== $taxonomy && taxonomy_exists( $taxonomy )
			)
		);
	}

	/**
	 * Resolve a preview term id for the requested taxonomy preview type.
	 *
	 * @param string $preview_type Preview type.
	 * @return int
	 */
	protected function resolve_default_preview_term_id( string $preview_type ): int {
		if ( 0 !== strpos( $preview_type, 'taxonomy/' ) ) {
			return 0;
		}

		$taxonomy = sanitize_key( substr( $preview_type, strlen( 'taxonomy/' ) ) );

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms[0] ) || ! $terms[0] instanceof \WP_Term ) {
			return 0;
		}

		return (int) $terms[0]->term_id;
	}

	/**
	 * Resolve the Directorist archive taxonomy for this document type.
	 *
	 * Empty string means "all supported archive taxonomies".
	 *
	 * @return string
	 */
	protected static function get_archive_taxonomy(): string {
		return '';
	}

	/**
	 * Resolve the Theme Builder root condition for this document type.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_type(): string {
		return DirectoristListingArchiveCondition::NAME;
	}

	/**
	 * Resolve the Theme Builder sub condition for this document type.
	 *
	 * @return string
	 */
	protected static function get_archive_condition_sub_type(): string {
		return '';
	}
}
