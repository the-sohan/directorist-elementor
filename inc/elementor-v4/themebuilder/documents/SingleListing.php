<?php
/**
 * Base document for directory-scoped Directorist single-listing templates.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingCondition;
use Elementor\Controls_Manager;
use Elementor\DB;
use ElementorPro\Modules\ThemeBuilder\Documents\Single_Base;
use ElementorPro\Modules\ThemeBuilder\Documents\Theme_Document;
use ElementorPro\Modules\ThemeBuilder\Documents\Theme_Page_Document;
use ElementorPro\Modules\ThemeBuilder\Module as ThemeBuilderModule;

abstract class SingleListing extends Single_Base {
	/**
	 * Get document properties.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_properties() {
		$properties = parent::get_properties();

		$properties['location']       = 'single';
		$properties['condition_type'] = DirectoristListingCondition::NAME;
		$properties['export_group']   = Theme_Document::EXPORT_GROUP;

		return $properties;
	}

	/**
	 * Get document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return esc_html__( 'Single Listing', 'directorist-elementor' );
	}

	/**
	 * Get document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return esc_html__( 'Single Listings', 'directorist-elementor' );
	}

	/**
	 * Site Editor icon.
	 *
	 * @return string
	 */
	protected static function get_site_editor_icon() {
		return 'eicon-single-post';
	}

	/**
	 * Site Editor thumbnail.
	 *
	 * Elementor expects a real image URL here. Directorist custom document types
	 * do not have matching static Elementor thumbnails, so reuse the default
	 * directory type preview image or icon asset from Directorist core.
	 *
	 * @return string
	 */
	protected static function get_site_editor_thumbnail_url() {
		$visuals = static::get_default_directory_site_editor_visuals();

		if ( ! empty( $visuals['thumbnail'] ) ) {
			return $visuals['thumbnail'];
		}

		return parent::get_site_editor_thumbnail_url();
	}

	/**
	 * Site Editor tooltip content.
	 *
	 * @return array<string,string>
	 */
	protected static function get_site_editor_tooltip_data() {
		return [
			'title'     => esc_html__( 'What is a Single Listing Template?', 'directorist-elementor' ),
			'content'   => esc_html__( 'A single listing template lets you design the layout for one Directorist directory type.', 'directorist-elementor' ),
			'tip'       => esc_html__( 'Each directory type has its own template, field composition, and preview context.', 'directorist-elementor' ),
			'docs'      => '',
			'video_url' => '',
		];
	}

	/**
	 * Single-listing templates are fully custom; they do not require the core
	 * Post Content widget the generic Single document expects.
	 *
	 * @param string $status Data status.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_elements_data( $status = DB::STATUS_PUBLISH ) {
		return Theme_Page_Document::get_elements_data( $status );
	}

	/**
	 * Save the template type and its intrinsic directory condition.
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

		$sub_type = static::get_sub_type();
		if ( '' === $sub_type ) {
			return;
		}

		$condition = array_merge(
			[
				'include',
				DirectoristListingCondition::NAME,
			],
			array_values( array_filter( explode( '/', $sub_type ) ) )
		);

		$conditions_manager->save_conditions( $this->get_main_id(), [ $condition ] );
	}

	/**
	 * Register the common preview type for directory-scoped documents.
	 *
	 * @return void
	 */
	protected function register_controls() {
		parent::register_controls();

		$this->update_control(
			'preview_type',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'single/' . DirectoristBridge::get_instance()->get_listing_post_type(),
			]
		);
	}

	/**
	 * Remote library category.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();

		$config['category'] = 'single listing';

		return $config;
	}

	/**
	 * Resolve the default directory type visuals used by the generic single
	 * listing template type.
	 *
	 * @return array{thumbnail:string}
	 */
	protected static function get_default_directory_site_editor_visuals(): array {
		$directory_type_id = 0;

		if ( function_exists( 'directorist_get_directories' ) ) {
			$default_directory = directorist_get_directories( [ 'default_only' => true ] );

			if ( ! empty( $default_directory[0] ) && $default_directory[0] instanceof \WP_Term ) {
				$directory_type_id = (int) $default_directory[0]->term_id;
			}
		}

		if ( $directory_type_id <= 0 ) {
			$terms = get_terms(
				[
					'taxonomy'   => DirectoristBridge::get_instance()->get_directory_taxonomy(),
					'hide_empty' => false,
					'number'     => 1,
				]
			);

			if ( ! is_wp_error( $terms ) && ! empty( $terms[0] ) && $terms[0] instanceof \WP_Term ) {
				$directory_type_id = (int) $terms[0]->term_id;
			}
		}

		if ( $directory_type_id <= 0 || ! function_exists( 'directorist_get_directory_general_settings' ) ) {
			return [
				'thumbnail' => '',
			];
		}

		$settings        = directorist_get_directory_general_settings( $directory_type_id );
		$raw_icon        = is_array( $settings ) ? (string) ( $settings['icon'] ?? '' ) : '';
		$preview_image   = is_array( $settings ) ? (string) ( $settings['preview_image'] ?? '' ) : '';
		$normalized_icon = static::normalize_site_editor_icon_class( $raw_icon );
		$thumbnail       = $preview_image;

		if ( ! $thumbnail && $normalized_icon && class_exists( '\\Directorist\\Helper' ) && method_exists( '\\Directorist\\Helper', 'get_icon_src' ) ) {
			$thumbnail = (string) \Directorist\Helper::get_icon_src( $normalized_icon );
		}

		return [
			'thumbnail' => $thumbnail,
		];
	}

	/**
	 * Normalize Directorist icon values into plain CSS classes.
	 *
	 * @param string $icon Raw stored icon value.
	 * @return string
	 */
	protected static function normalize_site_editor_icon_class( string $icon ): string {
		$icon = trim( $icon );

		if ( '' === $icon ) {
			return '';
		}

		if ( false === strpos( $icon, ':' ) ) {
			return $icon;
		}

		[ $library, $name ] = array_pad( explode( ':', $icon, 2 ), 2, '' );
		$library = trim( strtolower( $library ) );
		$name    = trim( $name );

		if ( '' === $name ) {
			return '';
		}

		if ( 'font-awesome' === $library ) {
			return str_starts_with( $name, 'fa-' ) ? 'fa ' . $name : $name;
		}

		if ( 'line-awesome' === $library ) {
			return str_starts_with( $name, 'la-' ) ? 'la ' . $name : $name;
		}

		return $icon;
	}
}
