<?php
/**
 * Elementor element registrar.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\ElementorV4\Elements\Author\AuthorProfileElement;
use DirectoristElementor\ElementorV4\Elements\Loop\HomepageSearchLoopElement;
use DirectoristElementor\ElementorV4\Elements\Loop\ListingCardTemplateElement;
use DirectoristElementor\ElementorV4\Elements\Loop\ListingsLoopElement;
use DirectoristElementor\ElementorV4\Elements\Pricing\PricingPlansElement;
use DirectoristElementor\ElementorV4\Widgets\Loop\HomepageSearchWidget;
use DirectoristElementor\ElementorV4\Widgets\Loop\ListingsSearchWidget;
use DirectoristElementor\Traits\Singleton;

class ElementRegistrar {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/elements/elements_registered', [ $this, 'register_elements' ] );
	}

	/**
	 * Register custom elements.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_elements( $elements_manager ): void {
		if ( ! Compatibility::get_instance()->is_supported() || ! FeatureDependencyManager::get_instance()->are_active() ) {
			return;
		}

		if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'register_element_type' ) ) {
			return;
		}

		$elements_manager->register_element_type( new ListingsLoopElement() );
		$elements_manager->register_element_type( new HomepageSearchLoopElement() );
		$elements_manager->register_element_type( new ListingsSearchWidget() );
		$elements_manager->register_element_type( new HomepageSearchWidget() );
		$elements_manager->register_element_type( new ListingCardTemplateElement() );
		$elements_manager->register_element_type( new PricingPlansElement() );
		$elements_manager->register_element_type( new AuthorProfileElement() );
	}
}
