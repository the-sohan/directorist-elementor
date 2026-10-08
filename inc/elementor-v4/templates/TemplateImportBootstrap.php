<?php
/**
 * Admin bootstrap for Directorist Elementor template imports.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Core\Plugin;
use DirectoristElementor\Traits\Singleton;

class TemplateImportBootstrap {
	use Singleton;

	public const PAGE_SLUG = 'elementor-directorist-templates';
	private const MENU_SOURCE = 'wp_db_templates_menu';

	/**
	 * Tracks registration because app-style menu items use a URL as their slug.
	 *
	 * @var bool
	 */
	protected bool $editor_one_menu_registered = false;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/editor-one/menu/register', [ $this, 'register_editor_one_menu_item' ] );
		add_action( 'admin_menu', [ $this, 'register_editor_one_menu_item_early' ], 9 );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ], 30 );
		add_action( 'admin_init', [ $this, 'maybe_render_standalone_app' ], 0 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'rest_api_init', [ TemplateImportRestController::get_instance(), 'register_routes' ] );
		add_action( 'wp_loaded', [ TemplateImportService::get_instance(), 'maybe_refresh_deferred_theme_builder_state' ], 100 );
	}

	/**
	 * Get the app-style URL used by the Elementor Editor > Templates menu.
	 *
	 * @return string
	 */
	public static function get_app_url(): string {
		$return_to = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );

		return add_query_arg(
			[
				'page'      => self::PAGE_SLUG,
				'return_to' => $return_to,
				'source'    => self::MENU_SOURCE,
			],
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Register the import app under the Directorist menu.
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_submenu_page(
			'edit.php?post_type=at_biz_dir',
			__( 'Directorist Templates', 'directorist-elementor' ),
			__( 'Elementor Templates', 'directorist-elementor' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_hidden_admin_page' ]
		);
	}

	/**
	 * Register the page inside Elementor 4 Editor > Templates flyout.
	 *
	 * @param object $menu_data_provider Elementor menu data provider.
	 * @return void
	 */
	public function register_editor_one_menu_item( $menu_data_provider ): void {
		if ( $this->editor_one_menu_registered ) {
			return;
		}

		if ( ! is_object( $menu_data_provider ) || ! method_exists( $menu_data_provider, 'register_menu' ) ) {
			return;
		}

		if ( ! interface_exists( 'Elementor\Core\Admin\EditorOneMenu\Interfaces\Menu_Item_Interface' ) || ! class_exists( 'Elementor\Modules\EditorOne\Classes\Menu_Config' ) ) {
			return;
		}

		if ( method_exists( $menu_data_provider, 'is_item_already_registered' ) && $menu_data_provider->is_item_already_registered( self::PAGE_SLUG ) ) {
			return;
		}

		$menu_data_provider->register_menu( new DirectoristTemplatesMenuItem() );
		$this->editor_one_menu_registered = true;
	}

	/**
	 * Register the Editor One item before Elementor builds sidebar/flyout data.
	 *
	 * @return void
	 */
	public function register_editor_one_menu_item_early(): void {
		if ( ! class_exists( 'Elementor\Modules\EditorOne\Classes\Menu_Data_Provider' ) ) {
			return;
		}

		$this->register_editor_one_menu_item( \Elementor\Modules\EditorOne\Classes\Menu_Data_Provider::instance() );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		$style_file  = Plugin::$plugin_path . 'assets/css/template-import-admin.css';
		$script_file = Plugin::$plugin_path . 'assets/js/template-import-admin.js';
		$style_ver   = file_exists( $style_file ) ? (string) filemtime( $style_file ) : Plugin::$version;
		$script_ver  = file_exists( $script_file ) ? (string) filemtime( $script_file ) : Plugin::$version;

		wp_enqueue_style(
			'directorist-elementor-template-import',
			Plugin::$plugin_url . 'assets/css/template-import-admin.css',
			[],
			$style_ver
		);

		wp_enqueue_script(
			'directorist-elementor-template-import',
			Plugin::$plugin_url . 'assets/js/template-import-admin.js',
			[ 'wp-api-fetch', 'wp-i18n' ],
			$script_ver,
			true
		);

		$placeholder_thumbnail = defined( 'ELEMENTOR_ASSETS_URL' ) ? ELEMENTOR_ASSETS_URL . 'images/placeholder.png' : '';

		wp_localize_script(
			'directorist-elementor-template-import',
			'DirectoristElementorTemplateImport',
			[
				'restBase'             => esc_url_raw( rest_url( 'directorist-elementor/v1/template-import' ) ),
				'nonce'                => wp_create_nonce( 'wp_rest' ),
				'editBase'             => esc_url_raw( admin_url( 'post.php?action=elementor&post=' ) ),
				'returnUrl'            => esc_url_raw( $this->get_return_url() ),
				'placeholderThumbnail' => esc_url_raw( $placeholder_thumbnail ),
				'labels'               => [
					'loading'          => __( 'Loading Directorist templates...', 'directorist-elementor' ),
					'empty'            => __( 'No Directorist templates were found.', 'directorist-elementor' ),
					'refresh'          => __( 'Refresh Catalog', 'directorist-elementor' ),
					'import'           => __( 'Import', 'directorist-elementor' ),
					'upload'           => __( 'Upload', 'directorist-elementor' ),
					'recommended'      => __( '(Recommended)', 'directorist-elementor' ),
					'uploadTemplateZip' => __( 'Import From ZIP', 'directorist-elementor' ),
					'uploadTemplateZipDescription' => __( 'Import a Directorist Elementor website package ZIP exported from the template source.', 'directorist-elementor' ),
					'chooseTemplateZip' => __( 'Choose Package ZIP', 'directorist-elementor' ),
					'selectedTemplateZip' => __( 'Selected package', 'directorist-elementor' ),
					'noTemplateZipSelected' => __( 'Choose a Directorist Elementor website package ZIP before importing.', 'directorist-elementor' ),
					'uploadImport'     => __( 'Upload and Import', 'directorist-elementor' ),
					'uploading'        => __( 'Uploading...', 'directorist-elementor' ),
					'importing'        => __( 'Importing...', 'directorist-elementor' ),
					'importComplete'   => __( 'Website import complete', 'directorist-elementor' ),
					'importFailed'     => __( 'Website import failed', 'directorist-elementor' ),
					'progressRunning'  => __( 'Import in progress', 'directorist-elementor' ),
					'progressFinished' => __( 'Import finished', 'directorist-elementor' ),
					'progressProcessing' => __( 'Processing the website package', 'directorist-elementor' ),
					'progressProcessingHelp' => __( 'Importing content, templates, media, and selected site settings. The status will update when the server finishes.', 'directorist-elementor' ),
					'progressUploadHelp' => __( 'Uploading and importing the package. The status will update when the server finishes.', 'directorist-elementor' ),
					'progressCompleteHelp' => __( 'The imported items are ready on this site.', 'directorist-elementor' ),
					'progressFailedHelp' => __( 'The import could not be completed.', 'directorist-elementor' ),
					'progressLock'     => __( 'Please keep this page open while the import finishes.', 'directorist-elementor' ),
					'done'             => __( 'Done', 'directorist-elementor' ),
					'viewSite'         => __( 'View Site', 'directorist-elementor' ),
					'createdCount'     => __( 'Created', 'directorist-elementor' ),
					'updatedCount'     => __( 'Updated', 'directorist-elementor' ),
					'createdItems'     => __( 'Created Items', 'directorist-elementor' ),
					'updatedItems'     => __( 'Updated Items', 'directorist-elementor' ),
					'importedItems'    => __( 'Imported Items', 'directorist-elementor' ),
					'siteChanges'      => __( 'Site Changes', 'directorist-elementor' ),
					'needsAttention'   => __( 'Needs attention', 'directorist-elementor' ),
					'untitled'         => __( '(Untitled)', 'directorist-elementor' ),
					'preview'          => __( 'Preview', 'directorist-elementor' ),
					'showDemo'         => __( 'View Demo', 'directorist-elementor' ),
					'details'          => __( 'Details', 'directorist-elementor' ),
					'related'          => __( 'Related Items', 'directorist-elementor' ),
					'importDirectoristTemplates' => __( 'Import Directorist Templates', 'directorist-elementor' ),
					'importThisTemplate' => __( 'Import This Template', 'directorist-elementor' ),
					'imported'         => __( 'Import completed.', 'directorist-elementor' ),
					'failed'           => __( 'Import failed.', 'directorist-elementor' ),
					'noTemplateSelected' => __( 'No Directorist template is available to import in the current view.', 'directorist-elementor' ),
					'infoDescription'  => __( 'Select a Directorist Elementor website template, preview the demo, then import it into your Elementor template library.', 'directorist-elementor' ),
					'importOptions'    => __( 'Import Options', 'directorist-elementor' ),
					'importScope'      => __( 'Import Scope', 'directorist-elementor' ),
					'siteBehavior'     => __( 'Site Changes', 'directorist-elementor' ),
					'conflictBehavior' => __( 'Existing Items', 'directorist-elementor' ),
					'conflictUpdate'   => __( 'Update matching imported items', 'directorist-elementor' ),
					'conflictSkip'     => __( 'Skip existing imported items', 'directorist-elementor' ),
					'conflictDuplicate' => __( 'Import as duplicates', 'directorist-elementor' ),
					'conflictReplace'  => __( 'Replace existing imported items', 'directorist-elementor' ),
					'includeTemplates' => __( 'Elementor templates', 'directorist-elementor' ),
					'includeTemplatesHelp' => __( 'Import Elementor theme-builder templates, sections, loop items, and saved templates included in this kit.', 'directorist-elementor' ),
					'includeContent'   => __( 'Pages, listings, menus, and taxonomies', 'directorist-elementor' ),
					'includeContentHelp' => __( 'Import WordPress content, Directorist listings, taxonomy data, and menu items included in this kit.', 'directorist-elementor' ),
					'applySiteSettings' => __( 'Apply site settings', 'directorist-elementor' ),
					'setHomepage'      => __( 'Set imported homepage', 'directorist-elementor' ),
					'applyTemplateConditions' => __( 'Activate template conditions', 'directorist-elementor' ),
					'cancel'           => __( 'Cancel', 'directorist-elementor' ),
					'close'            => __( 'Close', 'directorist-elementor' ),
					'importedCount'    => __( 'Imported', 'directorist-elementor' ),
					'skippedCount'     => __( 'Skipped', 'directorist-elementor' ),
					'replacedCount'    => __( 'Replaced', 'directorist-elementor' ),
					'backToLibrary'    => __( 'Back to Library', 'directorist-elementor' ),
					'overview'         => __( 'Overview', 'directorist-elementor' ),
					'loadingPreview'   => __( 'Loading preview...', 'directorist-elementor' ),
					'desktop'          => __( 'Desktop', 'directorist-elementor' ),
					'tablet'           => __( 'Tablet', 'directorist-elementor' ),
					'mobile'           => __( 'Mobile', 'directorist-elementor' ),
					'allCategories'    => __( 'All Categories', 'directorist-elementor' ),
					'favorites'        => __( 'Favorites', 'directorist-elementor' ),
					'addFavorite'      => __( 'Add to Favorites', 'directorist-elementor' ),
					'removeFavorite'   => __( 'Remove from Favorites', 'directorist-elementor' ),
					'requiredExtensions' => __( 'Required plugins and extensions', 'directorist-elementor' ),
					'requirementsReady' => __( 'Ready', 'directorist-elementor' ),
					'requirementsBlocked' => __( 'Extensions unavailable', 'directorist-elementor' ),
					'requirementsHelp'  => __( 'You can import this template now, but sections or widgets that depend on missing or inactive extensions may not render or work until those extensions are installed and activated.', 'directorist-elementor' ),
					'requirementActive' => __( 'Active', 'directorist-elementor' ),
					'requirementInactive' => __( 'Inactive', 'directorist-elementor' ),
					'requirementMissing' => __( 'Missing', 'directorist-elementor' ),
					'requirementSourceBase' => __( 'Core requirement', 'directorist-elementor' ),
					'requirementSourceManual' => __( 'Selected by template author', 'directorist-elementor' ),
					'requirementSourceComponent' => __( 'Detected from exported content', 'directorist-elementor' ),
					'requirementSourceBuilderContent' => __( 'Detected from builder content', 'directorist-elementor' ),
					'installRequirement' => __( 'Install', 'directorist-elementor' ),
					'activateRequirement' => __( 'Activate', 'directorist-elementor' ),
					'activatingRequirement' => __( 'Activating...', 'directorist-elementor' ),
					'activateRequirementFailed' => __( 'The plugin could not be activated.', 'directorist-elementor' ),
				],
			]
		);
	}

	/**
	 * Render the Directorist Templates screen without the WordPress admin chrome.
	 *
	 * Elementor's Website Templates item opens an app-style document instead of a
	 * standard submenu page. Directorist Templates follows that same entry flow so
	 * the Editor > Templates menu behaves consistently.
	 *
	 * @return void
	 */
	public function maybe_render_standalone_app(): void {
		$page = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) ?? '';

		if ( self::PAGE_SLUG !== $page || wp_doing_ajax() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access Directorist templates.', 'directorist-elementor' ) );
		}

		$this->enqueue_admin_assets( '' );

		nocache_headers();
		?>
		<!DOCTYPE html>
		<html <?php language_attributes(); ?>>
			<head>
				<meta charset="<?php bloginfo( 'charset' ); ?>" />
				<meta name="viewport" content="width=device-width, initial-scale=1.0" />
				<title><?php echo esc_html__( 'Elementor', 'elementor' ) . ' | ' . esc_html__( 'Directorist Templates', 'directorist-elementor' ); ?></title>
				<base target="_parent" />
				<?php wp_print_styles(); ?>
				<?php wp_print_head_scripts(); ?>
			</head>
			<body class="dialog-body dialog-buttons-body dialog-container dialog-buttons-container sticky-menu directorist-template-import-app-page">
				<?php $this->render_app_shell(); ?>
				<?php wp_print_footer_scripts(); ?>
			</body>
		</html>
		<?php
		exit;
	}

	/**
	 * Fallback callback for WordPress' hidden submenu registration.
	 *
	 * @return void
	 */
	public function render_hidden_admin_page(): void {
		$this->maybe_render_standalone_app();
	}

	/**
	 * Get the safe return URL for the close action.
	 *
	 * @return string
	 */
	protected function get_return_url(): string {
		$fallback = admin_url( 'admin.php?page=elementor-app#/kit-library' );
		$return_to = isset( $_GET['return_to'] ) ? (string) wp_unslash( $_GET['return_to'] ) : '';

		if ( '' === $return_to ) {
			return $fallback;
		}

		if ( str_starts_with( $return_to, '/wp-admin/' ) ) {
			$return_to = home_url( $return_to );
		} elseif ( str_starts_with( $return_to, 'admin.php' ) ) {
			$return_to = admin_url( $return_to );
		}

		return wp_validate_redirect( $return_to, $fallback );
	}

	/**
	 * Render the template import app shell.
	 *
	 * @return void
	 */
	protected function render_app_shell(): void {
		?>
		<div class="directorist-template-import-app">
			<header class="directorist-template-import-app__topbar">
				<a class="directorist-template-import-app__brand" href="<?php echo esc_url( add_query_arg( [ 'page' => self::PAGE_SLUG ], admin_url( 'admin.php' ) ) ); ?>">
					<span class="directorist-template-import-app__brand-mark" aria-hidden="true">
						<img src="<?php echo esc_url( Plugin::$plugin_url . 'assets/images/directorist-logo.svg' ); ?>" alt="" />
					</span>
					<span class="directorist-template-import-app__brand-text"><?php esc_html_e( 'Directorist Templates', 'directorist-elementor' ); ?></span>
				</a>

				<div class="directorist-template-import-app__actions">
					<button type="button" class="directorist-template-import-app__icon-button directorist-template-import-app__icon-button--import" data-directorist-template-primary-import aria-label="<?php esc_attr_e( 'Upload Directorist Template ZIP', 'directorist-elementor' ); ?>" title="<?php esc_attr_e( 'Import Directorist Templates', 'directorist-elementor' ); ?>">
						<span class="directorist-template-import-app__icon-button-label"><?php esc_html_e( 'Upload', 'directorist-elementor' ); ?></span>
					</button>
					<button type="button" class="directorist-template-import-app__icon-button directorist-template-import-app__icon-button--refresh" data-directorist-template-refresh aria-label="<?php esc_attr_e( 'Refresh Catalog', 'directorist-elementor' ); ?>">
						<span class="directorist-template-import__sr"><?php esc_html_e( 'Refresh Catalog', 'directorist-elementor' ); ?></span>
					</button>
					<button type="button" class="directorist-template-import-app__icon-button directorist-template-import-app__icon-button--info" data-directorist-template-info aria-label="<?php esc_attr_e( 'Info', 'directorist-elementor' ); ?>">
						<span class="directorist-template-import__sr"><?php esc_html_e( 'Info', 'directorist-elementor' ); ?></span>
					</button>
					<span class="directorist-template-import-app__divider" aria-hidden="true"></span>
					<a class="directorist-template-import-app__icon-button directorist-template-import-app__icon-button--close" href="<?php echo esc_url( $this->get_return_url() ); ?>" aria-label="<?php esc_attr_e( 'Close', 'directorist-elementor' ); ?>">
						<span class="directorist-template-import__sr"><?php esc_html_e( 'Close', 'directorist-elementor' ); ?></span>
					</a>
				</div>
			</header>

			<div class="directorist-template-import-app__body">
			<aside class="directorist-template-import-app__sidebar" aria-label="<?php esc_attr_e( 'Directorist template navigation', 'directorist-elementor' ); ?>">

				<nav class="directorist-template-import-app__nav" aria-label="<?php esc_attr_e( 'Template views', 'directorist-elementor' ); ?>">
					<button type="button" class="directorist-template-import-app__nav-item directorist-template-import-app__nav-item--active" data-directorist-template-view="all">
						<span class="directorist-template-import-app__nav-icon directorist-template-import-app__nav-icon--all" aria-hidden="true"></span>
						<?php esc_html_e( 'All Directorist Templates', 'directorist-elementor' ); ?>
					</button>
					<button type="button" class="directorist-template-import-app__nav-item" data-directorist-template-view="favorites">
						<span class="directorist-template-import-app__nav-icon directorist-template-import-app__nav-icon--heart" aria-hidden="true"></span>
						<?php esc_html_e( 'Favorites', 'directorist-elementor' ); ?>
					</button>
				</nav>

				<div class="directorist-template-import-app__filters">
					<button type="button" class="directorist-template-import-app__filter-title">
						<span><?php esc_html_e( 'Categories', 'directorist-elementor' ); ?></span>
						<span class="directorist-template-import-app__filter-chevron" aria-hidden="true"></span>
					</button>
					<div class="directorist-template-import-app__filter-list" data-directorist-template-categories></div>
				</div>
			</aside>

			<main class="directorist-template-import-app__main">
				<section class="directorist-template-import-app__content">
					<div class="directorist-template-import-app__tools">
						<div class="directorist-template-import-app__search">
							<span class="directorist-template-import-app__search-icon" aria-hidden="true"></span>
							<input type="search" data-directorist-template-search placeholder="<?php esc_attr_e( 'Search all Directorist Templates...', 'directorist-elementor' ); ?>" />
						</div>
						<select data-directorist-template-sort>
							<option value="featured"><?php esc_html_e( 'Featured', 'directorist-elementor' ); ?></option>
							<option value="title"><?php esc_html_e( 'Name', 'directorist-elementor' ); ?></option>
							<option value="type"><?php esc_html_e( 'Type', 'directorist-elementor' ); ?></option>
						</select>
					</div>

					<div id="directorist-template-import-app" class="directorist-template-import__app" aria-live="polite"></div>
				</section>
			</main>
			</div>
			<div id="directorist-template-preview-app" class="directorist-template-preview" hidden></div>
		</div>
		<?php
	}

}
