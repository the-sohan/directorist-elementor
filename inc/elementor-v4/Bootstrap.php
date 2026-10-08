<?php
/**
 * Elementor v4 bootstrap.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\ElementorV4\Assets\AssetManager;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Composition\StructureDefaults;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Editor\EditorPreviewController;
use DirectoristElementor\ElementorV4\Frontend\FormgentAssetCompatibility;
use DirectoristElementor\ElementorV4\Frontend\FrontendLoopController;
use DirectoristElementor\ElementorV4\Frontend\FrontendTaxonomyController;
use DirectoristElementor\ElementorV4\Frontend\TaxonomyMenuTitleGuard;
use DirectoristElementor\ElementorV4\Query\ListingsQueryService;
use DirectoristElementor\ElementorV4\Query\QueryArgsBuilder;
use DirectoristElementor\ElementorV4\Query\QueryModeResolver;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\AuthorProfileRenderService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\ElementorV4\Render\PricingPlanRenderService;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use DirectoristElementor\ElementorV4\Render\TaxonomyCompositionRenderService;
use DirectoristElementor\ElementorV4\Support\ElementDataNormalizer;
use DirectoristElementor\ElementorV4\ThemeBuilder\TemplateCacheController;
use DirectoristElementor\ElementorV4\ThemeBuilder\ThemeBuilderSupport;
use DirectoristElementor\Services\DirectoristDependencyService;
use DirectoristElementor\Services\ExtensionStatusService;
use DirectoristElementor\Traits\Singleton;

class Bootstrap {
	use Singleton;

	/**
	 * Whether the module booted successfully.
	 *
	 * @var bool
	 */
	protected bool $booted = false;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'admin_notices', [ $this, 'render_requirement_notice' ] );
		add_action( 'elementor/loaded', [ $this, 'maybe_boot' ], 20 );
		add_action( 'elementor/init', [ $this, 'maybe_boot' ], 20 );

		if ( did_action( 'elementor/init' ) || did_action( 'elementor/loaded' ) ) {
			$this->maybe_boot();
		}
	}

	/**
	 * Boot services when all requirements pass.
	 *
	 * @return void
	 */
	public function maybe_boot(): void {
		if ( $this->booted || ! $this->can_boot() ) {
			return;
		}

		CategoryRegistrar::get_instance();
		ElementRegistrar::get_instance();
		WidgetRegistrar::get_instance();
		AssetManager::get_instance();
		ExtensionStatusService::get_instance();
		RenderContext::get_instance();
		EditorContext::get_instance();
		InstanceState::get_instance();
		QueryModeResolver::get_instance();
		QueryArgsBuilder::get_instance();
		ListingsQueryService::get_instance();
		ElementDataNormalizer::get_instance();
		StructureDefaults::get_instance();
		LoopRenderService::get_instance();
		ElementTreeRenderService::get_instance();
		AuthorProfileRenderService::get_instance();
		SearchFormRenderService::get_instance();
		TaxonomyCompositionRenderService::get_instance();
		PricingPlanRenderService::get_instance();
		EditorPreviewController::get_instance();
		FormgentAssetCompatibility::get_instance();
		FrontendLoopController::get_instance();
		FrontendTaxonomyController::get_instance();
		TaxonomyMenuTitleGuard::get_instance();
		ThemeBuilderSupport::get_instance();
		TemplateCacheController::get_instance();
		DirectoristBridge::get_instance();

		$this->booted = true;

		do_action( 'directorist_elementor/v4_booted' );
	}

	/**
	 * Check whether the module can boot.
	 *
	 * @return bool
	 */
	public function can_boot(): bool {
		if ( ! DirectoristDependencyService::get_instance()->is_directorist_active() ) {
			return false;
		}

		return Compatibility::get_instance()->is_supported();
	}

	/**
	 * Render an admin notice when Elementor compatibility checks fail.
	 *
	 * @return void
	 */
	public function render_requirement_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! DirectoristDependencyService::get_instance()->is_directorist_active() ) {
			return;
		}

		$message = Compatibility::get_instance()->get_failure_message();

		if ( '' === $message ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}
}
