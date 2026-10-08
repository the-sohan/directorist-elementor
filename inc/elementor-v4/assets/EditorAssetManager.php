<?php
/**
 * Elementor editor asset manager.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Assets;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use DirectoristElementor\Core\Plugin;
use DirectoristElementor\Traits\Singleton;
use Elementor\Plugin as ElementorPlugin;

class EditorAssetManager {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_nested_editor_scripts' ] );
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_editor_scripts' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_editor_styles' ] );
		add_action( 'elementor/preview/enqueue_styles', [ $this, 'enqueue_preview_styles' ] );
	}

	/**
	 * Enqueue nested-elements editor bindings before Elementor bootstraps canvas views.
	 *
	 * @return void
	 */
	public function enqueue_nested_editor_scripts(): void {
		if ( ! ElementorPlugin::$instance->experiments->is_feature_active( 'nested-elements', true ) ) {
			return;
		}

		$asset_relative_path = 'assets/js/directorist-nested-editor.js';

		wp_enqueue_script(
			'directorist-elementor-v4-nested-editor',
			Plugin::$plugin_url . $asset_relative_path,
			[ 'jquery', 'elementor-editor', 'nested-elements' ],
			$this->resolve_asset_version( $asset_relative_path ),
			true
		);

		wp_add_inline_script(
			'directorist-elementor-v4-nested-editor',
			'window.directoristElementorV4Editor = ' . wp_json_encode( $this->get_editor_config() ) . ';',
			'before'
		);
	}

	/**
	 * Enqueue editor-only behavior for context-aware widget availability.
	 *
	 * @return void
	 */
	public function enqueue_editor_scripts(): void {
		// The single-map preview is rendered over AJAX, so its frontend map assets
		// must already be available inside the editor canvas before the HTML swap.
		DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/fields/map' );

		$asset_relative_path = 'assets/js/editor-context.js';

		wp_enqueue_script(
			'directorist-elementor-v4-editor',
			Plugin::$plugin_url . $asset_relative_path,
			[ 'jquery', 'elementor-editor' ],
			$this->resolve_asset_version( $asset_relative_path ),
			true
		);

		wp_add_inline_script(
			'directorist-elementor-v4-editor',
			'window.directoristElementorV4Editor = window.directoristElementorV4Editor || ' . wp_json_encode( $this->get_editor_config() ) . ';',
			'before'
		);
	}

	/**
	 * Add minimal styles for scaffold widgets in the editor.
	 *
	 * @return void
	 */
	public function enqueue_editor_styles(): void {
		AssetManager::add_inline_styles_to_handle( 'elementor-editor' );
	}

	/**
	 * Add shared styles to the Elementor preview iframe.
	 *
	 * The editor chrome and preview iframe use different style handles. The
	 * iframe renders frontend markup, so it needs the same shared rules as the
	 * public frontend.
	 *
	 * @return void
	 */
	public function enqueue_preview_styles(): void {
		AssetManager::add_inline_styles_to_handle( 'elementor-frontend' );
	}

	/**
	 * Build shared editor config for client-side branch UX.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_editor_config(): array {
		$home_search_contract = DirectoristBridge::get_instance()->resolve_home_search_contract();
		$home_search_directory_ids = $this->get_home_search_contract_directory_ids( $home_search_contract );
		$home_search_templates = $this->get_home_search_contract_search_templates( $home_search_contract );

		return [
			'directoryOptions' => array_map(
				'strval',
				DirectoristBridge::get_instance()->get_directory_options()
			),
			'directorySlugOptions' => array_map(
				'strval',
				DirectoristBridge::get_instance()->get_directory_slug_options()
			),
			'customFieldWidgetNamesByDirectory' => $this->get_custom_field_widget_names_by_directory(),
			'searchFormFieldsByDirectory' => SearchFormRenderService::get_instance()->get_search_fields_by_directory(),
			'searchFieldWidgetTypes' => SearchFormRenderService::get_instance()->get_field_widget_types(),
			'homeSearchContractDirectoryTypeIds' => array_values( array_map( 'strval', $home_search_directory_ids ) ),
			'homeSearchContractDefaultDirectoryTypeId' => (string) absint( $home_search_contract['default_directory_type_id'] ?? 0 ),
			'homeSearchContractSearchTemplates' => $home_search_templates,
			'viewLabels'       => [
				'grid' => __( 'Grid', 'directorist-elementor' ),
				'list' => __( 'List', 'directorist-elementor' ),
				'map'  => __( 'Map', 'directorist-elementor' ),
			],
			'listingPostType'  => (string) DirectoristBridge::get_instance()->get_listing_post_type(),
			'strings'          => [
				'directoryFallback'  => __( 'Directory', 'directorist-elementor' ),
				'firstSelectedDirectory' => __( 'Use First Selected Directory', 'directorist-elementor' ),
				'cardTemplateTitle'  => __( 'Listing Card Template', 'directorist-elementor' ),
				'editingGridCard'    => __( 'Editing Grid Card', 'directorist-elementor' ),
				'editingListCard'    => __( 'Editing List Card', 'directorist-elementor' ),
				'editingMapCard'     => __( 'Editing Map Card', 'directorist-elementor' ),
			],
			'preview'          => [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'directorist_elementor_v4_preview' ),
				'actions' => [
					'loop'            => 'directorist_elementor_v4_preview_loop',
					'cardTemplate'    => 'directorist_elementor_v4_preview_card_template',
					'loopUtility'     => 'directorist_elementor_v4_preview_loop_utility',
					'singleMap'       => 'directorist_elementor_v4_preview_single_map',
					'relatedListings' => 'directorist_elementor_v4_preview_related_listings',
					'taxonomyArchive' => 'directorist_elementor_v4_preview_taxonomy_archive',
					'pricingPlans'    => 'directorist_elementor_v4_preview_pricing_plans',
					'authorProfile'   => 'directorist_elementor_v4_preview_author_profile',
					'searchComposition' => 'directorist_elementor_v4_preview_search_composition',
				],
			],
		];
	}

	/**
	 * Decode the canonical per-directory Homepage Search compositions.
	 *
	 * @param array<string,mixed> $contract Homepage search contract.
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_home_search_contract_search_templates( array $contract ): array {
		$settings = is_array( $contract['search_settings'] ?? null ) ? (array) $contract['search_settings'] : [];
		$raw      = $settings['scoped_search_templates'] ?? '{}';

		if ( is_string( $raw ) ) {
			$raw = json_decode( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ), true );
		}

		$templates = [];
		foreach ( is_array( $raw ) ? $raw : [] as $key => $template ) {
			if ( ! is_string( $key ) || ! preg_match( '/^dir-\d+$/', $key ) || ! is_array( $template ) ) {
				continue;
			}

			$directory_type_id = absint( $template['directory_type_id'] ?? substr( $key, 4 ) );
			if ( $directory_type_id <= 0 ) {
				continue;
			}

			$templates[ 'dir-' . $directory_type_id ] = [
				'directory_type_id' => (string) $directory_type_id,
				'label'             => sanitize_text_field( (string) ( $template['label'] ?? '' ) ),
				'elements'          => is_array( $template['elements'] ?? null ) ? array_values( $template['elements'] ) : [],
			];
		}

		if ( ! empty( $templates ) ) {
			return $templates;
		}

		$default_id = absint( $contract['default_directory_type_id'] ?? $contract['directory_type_id'] ?? 0 );
		$elements   = is_array( $contract['search_elements'] ?? null ) ? array_values( $contract['search_elements'] ) : [];
		if ( $default_id <= 0 || empty( $elements ) ) {
			return [];
		}

		return [
			'dir-' . $default_id => [
				'directory_type_id' => (string) $default_id,
				'label'             => '',
				'elements'          => $elements,
			],
		];
	}

	/**
	 * Resolve directory IDs from the homepage-search contract.
	 *
	 * @param array<string,mixed> $contract Homepage search contract.
	 * @return array<int,int>
	 */
	protected function get_home_search_contract_directory_ids( array $contract ): array {
		$directory_ids = $this->normalize_directory_ids( $contract['directory_type_ids'] ?? [] );

		if ( ! empty( $directory_ids ) ) {
			return $directory_ids;
		}

		return $this->normalize_directory_ids(
			[
				$contract['default_directory_type_id'] ?? 0,
				$contract['directory_type_id'] ?? 0,
			]
		);
	}

	/**
	 * Normalize directory IDs.
	 *
	 * @param mixed $value Raw IDs.
	 * @return array<int,int>
	 */
	protected function normalize_directory_ids( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Build the custom field widget-name availability map per directory.
	 *
	 * @return array<string,array<int,string>>
	 */
	protected function get_custom_field_widget_names_by_directory(): array {
		$bridge = DirectoristBridge::get_instance();
		$map    = [];

		foreach ( $bridge->get_directory_options() as $directory_type_id => $directory_label ) {
			unset( $directory_label );

			$directory_type_id = absint( $directory_type_id );
			if ( $directory_type_id <= 0 ) {
				continue;
			}

			$submission_form_fields = $bridge->get_submission_form_fields( $directory_type_id );
			$fields                 = is_array( $submission_form_fields['fields'] ?? null )
				? (array) $submission_form_fields['fields']
				: ( is_array( $submission_form_fields ) ? $submission_form_fields : [] );
			$widget_names           = [];

			foreach ( $fields as $field_data ) {
				if ( ! is_array( $field_data ) ) {
					continue;
				}

				if ( 'custom' !== (string) ( $field_data['widget_group'] ?? '' ) ) {
					continue;
				}

				$widget_name = sanitize_key( (string) ( $field_data['widget_name'] ?? '' ) );
				if ( '' === $widget_name ) {
					$widget_name = sanitize_key( (string) ( $field_data['type'] ?? '' ) );
				}

				if ( '' !== $widget_name ) {
					$widget_names[] = $widget_name;
				}
			}

			$map[ (string) $directory_type_id ] = array_values( array_unique( $widget_names ) );
		}

		return $map;
	}

	/**
	 * Resolve a stable asset version with local cache busting in development.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	protected function resolve_asset_version( string $relative_path ): string {
		$asset_path = trailingslashit( Plugin::$plugin_path ) . ltrim( $relative_path, '/\\' );

		if ( file_exists( $asset_path ) ) {
			return (string) filemtime( $asset_path );
		}

		return Plugin::$version;
	}
}
