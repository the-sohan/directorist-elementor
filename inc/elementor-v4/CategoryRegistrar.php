<?php
/**
 * Elementor widget category registrar.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4;

use DirectoristElementor\Traits\Singleton;

class CategoryRegistrar {
	use Singleton;

	/**
	 * Directorist archive widget category slug.
	 */
	public const CATEGORY_ARCHIVE = 'directorist-listings-archive';

	/**
	 * Directorist single/other widget category slug.
	 */
	public const CATEGORY_OTHERS = 'directorist-others-fields';

	/**
	 * Directorist preset field widget category slug.
	 */
	public const CATEGORY_PRESET = 'directorist-listing-card-preset-fields';

	/**
	 * Directorist custom field widget category slug.
	 */
	public const CATEGORY_CUSTOM = 'directorist-listing-card-custom-fields';

	/**
	 * Directorist pricing plan widget category slug.
	 */
	public const CATEGORY_PRICING_PLAN = 'directorist-pricing-plan-fields';

	/**
	 * Directorist author profile widget category slug.
	 */
	public const CATEGORY_AUTHOR = 'directorist-author-fields';

	/**
	 * Directorist search form field widget category slug.
	 */
	public const CATEGORY_SEARCH_FIELDS = 'directorist-search-form-fields';

	/**
	 * Ordered category definitions matching Gutenberg.
	 *
	 * @var array<int,array{slug:string,title:string,icon:string}>
	 */
	protected const CATEGORY_DEFINITIONS = [
		[
			'slug'  => self::CATEGORY_ARCHIVE,
			'title' => 'Directorist Listings Archive',
			'icon'  => 'fa fa-th-large',
		],
		[
			'slug'  => self::CATEGORY_OTHERS,
			'title' => 'Directorist Others Fields',
			'icon'  => 'fa fa-list-alt',
		],
		[
			'slug'  => self::CATEGORY_PRESET,
			'title' => 'Directorist Preset Fields',
			'icon'  => 'fa fa-th-list',
		],
		[
			'slug'  => self::CATEGORY_CUSTOM,
			'title' => 'Directorist Custom Fields',
			'icon'  => 'fa fa-sliders',
		],
		[
			'slug'  => self::CATEGORY_SEARCH_FIELDS,
			'title' => 'Directory Search Fields',
			'icon'  => 'fa fa-search',
		],
		[
			'slug'  => self::CATEGORY_PRICING_PLAN,
			'title' => 'Directorist Pricing Plan Fields',
			'icon'  => 'fa fa-dollar-sign',
		],
		[
			'slug'  => self::CATEGORY_AUTHOR,
			'title' => 'Directorist Author Fields',
			'icon'  => 'fa fa-user',
		],
	];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_category' ] );

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$plugin = \Elementor\Plugin::instance();

			if ( isset( $plugin->elements_manager ) ) {
				$this->register_category( $plugin->elements_manager );
			}
		}
	}

	/**
	 * Register the Directorist Elementor categories.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_category( $elements_manager ): void {
		if ( ! is_object( $elements_manager ) || ! method_exists( $elements_manager, 'add_category' ) ) {
			return;
		}

		$categories = method_exists( $elements_manager, 'get_categories' )
			? (array) $elements_manager->get_categories()
			: [];

		foreach ( self::CATEGORY_DEFINITIONS as $definition ) {
			$slug = $definition['slug'];

			if ( $this->category_exists( $categories, $slug ) ) {
				continue;
			}

			$elements_manager->add_category(
				$slug,
				[
					'title' => __( $definition['title'], 'directorist-elementor' ),
					'icon'  => $definition['icon'],
				]
			);
		}

		$this->promote_categories_to_top( $elements_manager );
	}

	/**
	 * Check whether a category already exists in Elementor.
	 *
	 * @param array<string,mixed> $categories Existing categories.
	 * @param string              $slug Category slug.
	 * @return bool
	 */
	protected function category_exists( array $categories, string $slug ): bool {
		foreach ( $categories as $category_key => $category ) {
			if ( $slug === (string) $category_key ) {
				return true;
			}

			if ( is_array( $category ) && $slug === (string) ( $category['name'] ?? $category['slug'] ?? '' ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Move the Directorist category group to the start of Elementor's registry.
	 *
	 * @param object $elements_manager Elementor elements manager.
	 * @return void
	 */
	protected function promote_categories_to_top( $elements_manager ): void {
		if ( ! method_exists( $elements_manager, 'get_categories' ) ) {
			return;
		}

		$categories = (array) $elements_manager->get_categories();
		$directorist_categories = [];

		foreach ( self::CATEGORY_DEFINITIONS as $definition ) {
			$slug = $definition['slug'];

			if ( ! isset( $categories[ $slug ] ) ) {
				continue;
			}

			$directorist_categories[ $slug ] = $categories[ $slug ];
			unset( $categories[ $slug ] );
		}

		if ( empty( $directorist_categories ) ) {
			return;
		}

		try {
			$reflection = new \ReflectionObject( $elements_manager );

			while ( $reflection ) {
				if ( $reflection->hasProperty( 'categories' ) ) {
					$property = $reflection->getProperty( 'categories' );
					$property->setAccessible( true );
					$property->setValue( $elements_manager, array_merge( $directorist_categories, $categories ) );

					return;
				}

				$reflection = $reflection->getParentClass();
			}
		} catch ( \ReflectionException $exception ) {
			return;
		}
	}
}
