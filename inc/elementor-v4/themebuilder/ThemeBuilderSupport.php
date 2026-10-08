<?php
/**
 * Directorist theme builder integration.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristHomeSearchResultCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingArchiveCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingAuthorArchiveCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingCategoryArchivesCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingLocationArchivesCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Conditions\DirectoristListingTagArchivesCondition;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\DirectorySingleListing;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\HomeSearchResult;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\ListingAuthorArchive;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\ListingCategoryArchive;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\ListingLocationArchive;
use DirectoristElementor\ElementorV4\ThemeBuilder\Documents\ListingTagArchive;
use DirectoristElementor\Traits\Singleton;

class ThemeBuilderSupport {
	use Singleton;

	/**
	 * Track whether legacy directory-template conditions have been normalized.
	 *
	 * @var bool
	 */
	protected bool $normalized_directory_single_conditions = false;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		if ( ! $this->is_available() ) {
			return;
		}

		add_action( 'elementor/documents/register', [ $this, 'register_documents' ] );
		add_action( 'elementor/theme/register_conditions', [ $this, 'register_conditions' ] );
		add_filter( 'elementor_pro/utils/get_public_post_types', [ $this, 'register_listing_post_type' ] );
		add_filter( 'elementor-pro/site-editor/data/template', [ $this, 'filter_site_editor_template_data' ] );
		add_filter( 'elementor/theme/get_location_templates/template_id', [ $this, 'ignore_generic_single_listing_template' ], 20, 2 );
		add_filter( 'template_include', [ $this, 'render_page_based_archive_theme_template' ], 100 );
		add_filter( 'template_include', [ $this, 'prevent_directorist_archive_template_override' ], 998 );
	}

	/**
	 * Check whether Elementor Theme Builder is available.
	 *
	 * @return bool
	 */
	protected function is_available(): bool {
		return class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' );
	}

	/**
	 * Ensure Directorist listing post type is exposed to Theme Builder helpers.
	 *
	 * @param array<string,string> $post_types Public post types.
	 * @return array<string,string>
	 */
	public function register_listing_post_type( array $post_types ): array {
		$post_type_object = get_post_type_object( DirectoristBridge::get_instance()->get_listing_post_type() );

		if ( $post_type_object ) {
			$post_types[ $post_type_object->name ] = (string) ( $post_type_object->labels->singular_name ?? $post_type_object->label );
		}

		return $post_types;
	}

	/**
	 * Register directory-scoped Directorist single-listing documents.
	 *
	 * @param object $documents_manager Elementor documents manager.
	 * @return void
	 */
	public function register_documents( $documents_manager ): void {
		if ( ! is_object( $documents_manager ) || ! method_exists( $documents_manager, 'register_document_type' ) ) {
			return;
		}

		$this->register_directory_single_documents( $documents_manager );
		$documents_manager->register_document_type( ListingCategoryArchive::get_type(), ListingCategoryArchive::class );
		$documents_manager->register_document_type( ListingLocationArchive::get_type(), ListingLocationArchive::class );
		$documents_manager->register_document_type( ListingTagArchive::get_type(), ListingTagArchive::class );
		$documents_manager->register_document_type( ListingAuthorArchive::get_type(), ListingAuthorArchive::class );
		$documents_manager->register_document_type( HomeSearchResult::get_type(), HomeSearchResult::class );
	}

	/**
	 * Register document types for directory terms created after Elementor booted.
	 *
	 * @return void
	 */
	public function refresh_directory_single_documents(): void {
		if ( ! $this->is_available() || ! class_exists( '\Elementor\Plugin' ) || empty( \Elementor\Plugin::$instance->documents ) ) {
			return;
		}

		$this->register_directory_single_documents( \Elementor\Plugin::$instance->documents );
	}

	/**
	 * Keep legacy generic single-listing documents out of frontend resolution.
	 *
	 * Removing the generic document registration prevents new templates and hides
	 * existing ones from Site Editor queries. This guard also handles template IDs
	 * that remain in Elementor's persisted Theme Builder condition cache.
	 *
	 * @param int|string $template_id Cached Theme Builder template ID.
	 * @param string     $location Theme Builder location.
	 * @return int
	 */
	public function ignore_generic_single_listing_template( $template_id, string $location ): int {
		$template_id = absint( $template_id );
		if ( 'single' !== $location || $template_id <= 0 ) {
			return $template_id;
		}

		$template_type = (string) get_post_meta( $template_id, '_elementor_template_type', true );

		return 'directorist-single-listing' === $template_type ? -1 : $template_id;
	}

	/**
	 * Register Directorist Theme Builder conditions.
	 *
	 * @param object $conditions_manager Elementor Theme Builder conditions manager.
	 * @return void
	 */
	public function register_conditions( $conditions_manager ): void {
		if ( ! is_object( $conditions_manager ) || ! method_exists( $conditions_manager, 'get_condition' ) ) {
			return;
		}

		$general_condition = $conditions_manager->get_condition( 'general' );

		if ( ! $general_condition || ! method_exists( $general_condition, 'register_sub_condition' ) ) {
			return;
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingCondition::NAME ) ) {
			$general_condition->register_sub_condition( new DirectoristListingCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingArchiveCondition::NAME ) ) {
			$general_condition->register_sub_condition( new DirectoristListingArchiveCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingAuthorArchiveCondition::NAME ) ) {
			$general_condition->register_sub_condition( new DirectoristListingAuthorArchiveCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristHomeSearchResultCondition::NAME ) ) {
			$general_condition->register_sub_condition( new DirectoristHomeSearchResultCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingCategoryArchivesCondition::NAME ) ) {
			$conditions_manager->register_condition_instance( new DirectoristListingCategoryArchivesCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingLocationArchivesCondition::NAME ) ) {
			$conditions_manager->register_condition_instance( new DirectoristListingLocationArchivesCondition() );
		}

		if ( ! $conditions_manager->get_condition( DirectoristListingTagArchivesCondition::NAME ) ) {
			$conditions_manager->register_condition_instance( new DirectoristListingTagArchivesCondition() );
		}

		$this->normalize_directory_single_template_conditions( $conditions_manager );
	}

	/**
	 * Prevent Directorist's late taxonomy template override from replacing a
	 * matched Elementor archive template.
	 *
	 * Directorist hooks `template_include` at priority 999 for native taxonomy
	 * archives. When Theme Builder already has a matching archive template for the
	 * current Directorist taxonomy request, remove that late override before it
	 * runs so the Elementor template stays in control.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function prevent_directorist_archive_template_override( string $template ): string {
		$supported_taxonomies = DirectoristBridge::get_instance()->get_supported_archive_taxonomies();

		if ( empty( $supported_taxonomies ) || ! is_tax( $supported_taxonomies ) ) {
			return $template;
		}

		if ( ! class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' ) ) {
			return $template;
		}

		$theme_builder = \ElementorPro\Modules\ThemeBuilder\Module::instance();
		$conditions_manager = $theme_builder->get_conditions_manager();

		if (
			! $conditions_manager ||
			empty( $conditions_manager->get_documents_for_location( 'archive' ) )
		) {
			return $template;
		}

		if ( class_exists( '\\Directorist\\Directorist_Template_Hooks' ) ) {
			remove_filter(
				'template_include',
				[ \Directorist\Directorist_Template_Hooks::instance(), 'single_template_path' ],
				999
			);
		}

		return $template;
	}

	/**
	 * Render archive Theme Builder templates on Directorist's legacy page-based
	 * category/location/tag routes, author profile route, and home-search route.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	public function render_page_based_archive_theme_template( string $template ): string {
		$bridge = DirectoristBridge::get_instance();

		if ( $bridge->is_home_search_result_request() ) {
			return $this->render_home_search_result_theme_template( $template );
		}

		if ( ! $bridge->is_page_based_archive_request() && ! $bridge->is_author_profile_request() && ! $bridge->is_home_search_result_request() ) {
			return $template;
		}

		if ( ! class_exists( '\\ElementorPro\\Modules\\ThemeBuilder\\Module' ) ) {
			return $template;
		}

		$theme_builder      = \ElementorPro\Modules\ThemeBuilder\Module::instance();
		$conditions_manager = $theme_builder->get_conditions_manager();
		$locations_manager  = $theme_builder->get_locations_manager();

		if ( ! $conditions_manager || ! $locations_manager ) {
			return $template;
		}

		$conditions_manager->clear_location_cache();
		$location_documents = $conditions_manager->get_documents_for_location( 'archive' );

		if ( empty( $location_documents ) ) {
			return $template;
		}

		$page_templates_module = \Elementor\Plugin::instance()->modules_manager->get_modules( 'page-templates' );

		if ( ! $page_templates_module ) {
			return $template;
		}

		$page_template = $page_templates_module::TEMPLATE_HEADER_FOOTER;
		$first_key     = key( $location_documents );
		$theme_document = $location_documents[ $first_key ] ?? null;

		if ( $theme_document && method_exists( $theme_document, 'get_settings' ) ) {
			$document_page_template = $theme_document->get_settings( 'page_template' );

			if ( $document_page_template ) {
				$page_template = $document_page_template;
			}
		}

		$template_path = $page_templates_module->get_template_path( $page_template );

		if ( ! $template_path ) {
			return $template;
		}

		$page_templates_module->set_print_callback(
			static function () use ( $locations_manager ) {
				$locations_manager->do_location( 'archive' );
			}
		);

		return $template_path;
	}

	/**
	 * Render the dedicated Homepage Search Result document for the canonical
	 * standalone search route, even when no Theme Builder condition was saved.
	 *
	 * @param string $template Resolved template path.
	 * @return string
	 */
	protected function render_home_search_result_theme_template( string $template ): string {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return $template;
		}

		$bridge      = DirectoristBridge::get_instance();
		$contract    = $bridge->resolve_home_search_contract();
		$template_id = absint( $contract['template_id'] ?? 0 );

		if ( $template_id <= 0 ) {
			return $template;
		}

		$elementor = \Elementor\Plugin::instance();

		if ( empty( $elementor->modules_manager ) || ! method_exists( $elementor->modules_manager, 'get_modules' ) ) {
			return $template;
		}

		$page_templates_module = $elementor->modules_manager->get_modules( 'page-templates' );

		if ( ! $page_templates_module ) {
			return $template;
		}

		$page_template = $page_templates_module::TEMPLATE_HEADER_FOOTER;
		$document      = ! empty( $elementor->documents ) && method_exists( $elementor->documents, 'get' )
			? $elementor->documents->get( $template_id )
			: null;

		if ( $document && method_exists( $document, 'get_settings' ) ) {
			$document_page_template = $document->get_settings( 'page_template' );

			if ( $document_page_template ) {
				$page_template = $document_page_template;
			}
		}

		$template_path = $page_templates_module->get_template_path( $page_template );

		if ( ! $template_path ) {
			return $template;
		}

		$page_templates_module->set_print_callback(
			static function () use ( $bridge, $contract, $template_id, $elementor ) {
				$bridge->enqueue_home_search_contract_styles( $contract );

				if ( empty( $elementor->frontend ) || ! method_exists( $elementor->frontend, 'get_builder_content_for_display' ) ) {
					return;
				}

				echo $elementor->frontend->get_builder_content_for_display( $template_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor renders saved template content.
			}
		);

		return $template_path;
	}

	/**
	 * Register one Theme Builder single-template document type per directory.
	 *
	 * @param object $documents_manager Elementor documents manager.
	 * @return void
	 */
	protected function register_directory_single_documents( $documents_manager ): void {
		$directory_terms = get_terms(
			[
				'taxonomy'   => DirectoristBridge::get_instance()->get_directory_taxonomy(),
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $directory_terms ) || empty( $directory_terms ) ) {
			return;
		}

		$registered_types = method_exists( $documents_manager, 'get_document_types' )
			? (array) $documents_manager->get_document_types()
			: [];

		foreach ( $directory_terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$class_name = $this->ensure_directory_single_document_class( $term );

			if ( '' === $class_name ) {
				continue;
			}

			$type = 'directorist-single-listing-directory-' . absint( $term->term_id );

			if ( isset( $registered_types[ $type ] ) ) {
				continue;
			}

			$documents_manager->register_document_type( $type, $class_name );
			$registered_types[ $type ] = $class_name;
		}
	}

	/**
	 * Ensure the runtime class exists for a directory-specific single template.
	 *
	 * @param \WP_Term $term Directory term.
	 * @return string
	 */
	protected function ensure_directory_single_document_class( \WP_Term $term ): string {
		$class_name = __NAMESPACE__ . '\\Documents\\SingleListingDirectory_' . absint( $term->term_id );

		if ( ! class_exists( $class_name ) ) {
			eval( 'namespace ' . __NAMESPACE__ . '\\Documents; class SingleListingDirectory_' . absint( $term->term_id ) . ' extends \\DirectoristElementor\\ElementorV4\\ThemeBuilder\\Documents\\DirectorySingleListing {}' );
		}

		if ( ! class_exists( $class_name ) ) {
			return '';
		}

		$directory_visuals = $this->get_directory_site_editor_visuals( $term->term_id );

		DirectorySingleListing::register_directory_config(
			$class_name,
			[
				'type'              => 'directorist-single-listing-directory-' . absint( $term->term_id ),
				'title'             => sprintf(
					/* translators: %s: Directory type name. */
					esc_html__( 'Single Listing: %s', 'directorist-elementor' ),
					$term->name
				),
				'plural_title'      => sprintf(
					/* translators: %s: Directory type name. */
					esc_html__( 'Single Listings: %s', 'directorist-elementor' ),
					$term->name
				),
				'directory_type_id' => absint( $term->term_id ),
				'site_editor_thumbnail' => $directory_visuals['thumbnail'],
			]
		);

		return $class_name;
	}

	/**
	 * Resolve the Site Editor thumbnail for a directory-scoped single template.
	 *
	 * Prefer the configured directory preview image when available. Otherwise
	 * fall back to the core directory icon SVG so the Site Editor cards still
	 * reflect the directory type visually.
	 *
	 * @param int $directory_type_id Directory term id.
	 * @return array{thumbnail:string}
	 */
	protected function get_directory_site_editor_visuals( int $directory_type_id ): array {
		$thumbnail = '';

		if ( $directory_type_id <= 0 || ! function_exists( 'directorist_get_directory_general_settings' ) ) {
			return [
				'thumbnail' => $thumbnail,
			];
		}

		$settings       = directorist_get_directory_general_settings( $directory_type_id );
		$raw_icon       = is_array( $settings ) ? (string) ( $settings['icon'] ?? '' ) : '';
		$preview_image  = is_array( $settings ) ? (string) ( $settings['preview_image'] ?? '' ) : '';
		$normalized_icon = $this->normalize_directory_icon_class( $raw_icon );

		if ( $normalized_icon && class_exists( '\\Directorist\\Helper' ) && method_exists( '\\Directorist\\Helper', 'get_icon_src' ) ) {
			if ( ! $preview_image ) {
				$thumbnail = (string) \Directorist\Helper::get_icon_src( $normalized_icon );
			}
		}

		if ( $preview_image ) {
			$thumbnail = $preview_image;
		}

		return [
			'thumbnail' => $thumbnail,
		];
	}

	/**
	 * Normalize stored Directorist icon values into CSS class strings.
	 *
	 * Directory icons are usually stored as standard classes like
	 * `las la-chart-bar`, but older values may use `font-awesome:fa-id-card`
	 * style notation. Normalize those values so Directorist core can resolve
	 * the corresponding SVG path.
	 *
	 * @param string $icon Raw stored icon value.
	 * @return string
	 */
	protected function normalize_directory_icon_class( string $icon ): string {
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

	/**
	 * Replace opaque condition instance ids with human-readable Directorist term
	 * labels inside the Site Editor template cards.
	 *
	 * Directory-scoped single templates also hide the instances footer because
	 * their condition is intrinsic to the document type.
	 *
	 * @param array<string,mixed> $data Normalized Site Editor template payload.
	 * @return array<string,mixed>
	 */
	public function filter_site_editor_template_data( array $data ): array {
		if ( ! empty( $data['type'] ) && 0 === strpos( (string) $data['type'], 'directorist-single-listing-directory-' ) ) {
			$data['showInstances'] = false;
		}

		$data = $this->normalize_specific_archive_template_data( $data );

		if ( empty( $data['conditions'] ) || ! is_array( $data['conditions'] ) ) {
			return $data;
		}

		$instances = is_array( $data['instances'] ?? null ) ? $data['instances'] : [];

		foreach ( $data['conditions'] as $condition ) {
			if ( ! is_array( $condition ) ) {
				continue;
			}

			$instance_key = sanitize_key( (string) ( $condition['sub'] ?? $condition['name'] ?? '' ) );
			$instance_label = $this->resolve_site_editor_condition_label( $condition );

			if ( '' === $instance_key || '' === $instance_label ) {
				continue;
			}

			$instances[ $instance_key ] = $instance_label;
		}

		if ( ! empty( $instances ) ) {
			$data['instances'] = $instances;
		}

		return $data;
	}

	/**
	 * Resolve a human-readable Site Editor instance label.
	 *
	 * @param array<string,mixed> $condition Parsed condition payload.
	 * @return string
	 */
	protected function resolve_site_editor_condition_label( array $condition ): string {
		$sub_name = sanitize_key( (string) ( $condition['sub'] ?? '' ) );
		$sub_id   = absint( $condition['subId'] ?? 0 );

		if ( $sub_id <= 0 ) {
			return '';
		}

		switch ( $sub_name ) {
			case 'directorist_listing_directory':
				return $this->build_term_condition_label(
					$sub_id,
					DirectoristBridge::get_instance()->get_directory_taxonomy(),
					__( 'In Directory Type', 'directorist-elementor' )
				);

			case 'directorist_listing_category_archive':
				return $this->build_term_condition_label(
					$sub_id,
					DirectoristBridge::get_instance()->get_category_taxonomy(),
					__( 'In Listing Category', 'directorist-elementor' )
				);

			case 'directorist_listing_location_archive':
				return $this->build_term_condition_label(
					$sub_id,
					DirectoristBridge::get_instance()->get_location_taxonomy(),
					__( 'In Listing Location', 'directorist-elementor' )
				);

			case 'directorist_listing_tag_archive':
				return $this->build_term_condition_label(
					$sub_id,
					DirectoristBridge::get_instance()->get_tag_taxonomy(),
					__( 'In Listing Tag', 'directorist-elementor' )
				);
		}

		return '';
	}

	/**
	 * Build an instance label from a term id and taxonomy.
	 *
	 * @param int    $term_id Term id.
	 * @param string $taxonomy Taxonomy slug.
	 * @param string $prefix Label prefix.
	 * @return string
	 */
	protected function build_term_condition_label( int $term_id, string $taxonomy, string $prefix ): string {
		if ( $term_id <= 0 || '' === $taxonomy ) {
			return '';
		}

		$term = get_term( $term_id, $taxonomy );

		if ( ! $term instanceof \WP_Term ) {
			return '';
		}

		return sprintf( '%1$s: %2$s', $prefix, $term->name );
	}

	/**
	 * Normalize older shared-root archive conditions into the document-specific
	 * condition roots used by the dedicated category/location/tag archive types.
	 *
	 * This keeps existing templates editable in the updated Site Editor flow
	 * without requiring a manual migration first.
	 *
	 * @param array<string,mixed> $data Normalized Site Editor template payload.
	 * @return array<string,mixed>
	 */
	protected function normalize_specific_archive_template_data( array $data ): array {
		if ( empty( $data['conditions'] ) || ! is_array( $data['conditions'] ) ) {
			return $data;
		}

		$mapping = $this->get_specific_archive_condition_mapping( sanitize_key( (string) ( $data['type'] ?? '' ) ) );

		if ( empty( $mapping ) ) {
			return $data;
		}

		$data['conditions'] = array_map(
			fn( $condition ) => is_array( $condition ) ? $this->normalize_specific_archive_condition( $condition, $mapping ) : $condition,
			$data['conditions']
		);

		return $data;
	}

	/**
	 * Repair directory-scoped single templates created before their implicit
	 * directory condition was persisted.
	 *
	 * @param object $conditions_manager Elementor conditions manager.
	 * @return void
	 */
	protected function normalize_directory_single_template_conditions( $conditions_manager ): void {
		if ( $this->normalized_directory_single_conditions ) {
			return;
		}

		$this->normalized_directory_single_conditions = true;

		$template_ids = get_posts(
			[
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_query'     => [
					[
						'key'     => '_elementor_template_type',
						'value'   => 'directorist-single-listing-directory-',
						'compare' => 'LIKE',
					],
				],
			]
		);

		if ( empty( $template_ids ) ) {
			return;
		}

		$changed = false;

		foreach ( $template_ids as $template_id ) {
			$template_id = absint( $template_id );
			$template_type = (string) get_post_meta( $template_id, '_elementor_template_type', true );

			if ( ! preg_match( '/^directorist-single-listing-directory-(\d+)$/', $template_type, $matches ) ) {
				continue;
			}

			$conditions = get_post_meta( $template_id, '_elementor_conditions', true );

			if ( ! $this->should_normalize_directory_single_conditions( $conditions ) ) {
				continue;
			}

			update_post_meta(
				$template_id,
				'_elementor_conditions',
				[
					'include/directorist_listing/directorist_listing_directory/' . absint( $matches[1] ),
				]
			);
			delete_post_meta( $template_id, '_elementor_element_cache' );

			$changed = true;
		}

		if ( ! $changed || ! is_object( $conditions_manager ) ) {
			return;
		}

		if ( method_exists( $conditions_manager, 'get_cache' ) ) {
			$cache = $conditions_manager->get_cache();

			if ( is_object( $cache ) && method_exists( $cache, 'regenerate' ) ) {
				$cache->regenerate();
			}
		}

		if ( method_exists( $conditions_manager, 'clear_location_cache' ) ) {
			$conditions_manager->clear_location_cache();
		}
	}

	/**
	 * Check whether saved conditions are the legacy generic single-listing set.
	 *
	 * @param mixed $conditions Raw _elementor_conditions meta value.
	 * @return bool
	 */
	protected function should_normalize_directory_single_conditions( $conditions ): bool {
		if ( empty( $conditions ) ) {
			return true;
		}

		if ( ! is_array( $conditions ) || 1 !== count( $conditions ) ) {
			return false;
		}

		return 'include/directorist_listing' === rtrim( (string) reset( $conditions ), '/' );
	}

	/**
	 * Resolve the root/leaf condition mapping for a dedicated archive document.
	 *
	 * @param string $document_type Document type slug.
	 * @return array<string,string>
	 */
	protected function get_specific_archive_condition_mapping( string $document_type ): array {
		switch ( $document_type ) {
			case 'directorist-listing-category-archive':
				return [
					'root' => DirectoristListingCategoryArchivesCondition::NAME,
					'leaf' => 'directorist_listing_category_archive',
				];

			case 'directorist-listing-location-archive':
				return [
					'root' => DirectoristListingLocationArchivesCondition::NAME,
					'leaf' => 'directorist_listing_location_archive',
				];

			case 'directorist-listing-tag-archive':
				return [
					'root' => DirectoristListingTagArchivesCondition::NAME,
					'leaf' => 'directorist_listing_tag_archive',
				];
		}

		return [];
	}

	/**
	 * Normalize a legacy shared-root condition into a dedicated archive root.
	 *
	 * @param array<string,mixed> $condition Parsed condition payload.
	 * @param array<string,string> $mapping Root/leaf mapping.
	 * @return array<string,mixed>
	 */
	protected function normalize_specific_archive_condition( array $condition, array $mapping ): array {
		$condition_name = sanitize_key( (string) ( $condition['name'] ?? '' ) );
		$sub_name       = sanitize_key( (string) ( $condition['sub'] ?? $condition['sub_name'] ?? '' ) );
		$sub_id         = absint( $condition['subId'] ?? $condition['sub_id'] ?? 0 );

		if ( DirectoristListingArchiveCondition::NAME !== $condition_name ) {
			return $condition;
		}

		if ( '' !== $sub_name && $mapping['leaf'] !== $sub_name ) {
			return $condition;
		}

		$condition['name'] = $mapping['root'];

		if ( $sub_id > 0 ) {
			$condition['sub']      = $mapping['leaf'];
			$condition['sub_name'] = $mapping['leaf'];
		} else {
			$condition['sub']      = '';
			$condition['sub_name'] = '';
		}

		return $condition;
	}
}
