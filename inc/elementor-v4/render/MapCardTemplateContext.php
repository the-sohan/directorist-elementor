<?php
/**
 * Scoped template override context for Elementor map card rendering.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\ElementorV4\Elements\Loop\ListingCardTemplateElement;
use DirectoristElementor\Traits\Singleton;

class MapCardTemplateContext {
	use Singleton;

	/**
	 * Active card template render context stack.
	 *
	 * @var array<int,array{widget:ListingCardTemplateElement,show_map_card:bool}>
	 */
	protected array $context_stack = [];

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_filter( 'directorist_template_file_path', [ $this, 'filter_template_file_path' ], 10, 3 );
	}

	/**
	 * Push the active card template element before Directorist renders map cards.
	 *
	 * @param ListingCardTemplateElement $widget Card template element.
	 * @param bool                       $show_map_card Whether popup card markup should render.
	 * @return void
	 */
	public function push( ListingCardTemplateElement $widget, bool $show_map_card = true ): void {
		$this->context_stack[] = [
			'widget'        => $widget,
			'show_map_card' => $show_map_card,
		];
	}

	/**
	 * Pop the active card template element after Directorist finishes rendering.
	 *
	 * @return void
	 */
	public function pop(): void {
		array_pop( $this->context_stack );
	}

	/**
	 * Get the currently active card template element.
	 *
	 * @return ListingCardTemplateElement|null
	 */
	public function current_widget(): ?ListingCardTemplateElement {
		$context = end( $this->context_stack );
		$widget  = is_array( $context ) ? ( $context['widget'] ?? null ) : null;

		return $widget instanceof ListingCardTemplateElement ? $widget : null;
	}

	/**
	 * Determine whether the active map render should output popup card markup.
	 *
	 * @return bool
	 */
	public function should_render_current_map_card(): bool {
		$context = end( $this->context_stack );

		if ( ! is_array( $context ) ) {
			return true;
		}

		return ! isset( $context['show_map_card'] ) || true === (bool) $context['show_map_card'];
	}

	/**
	 * Render map card markup from the active card template element.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function render_current_map_card_markup( int $listing_id = 0 ): string {
		if ( ! $this->should_render_current_map_card() ) {
			return '';
		}

		$widget = $this->current_widget();

		if ( ! $widget ) {
			return '';
		}

		return $widget->render_map_card_markup_for_listing( $listing_id );
	}

	/**
	 * Build scoped wrapper style variables for the current map card.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_current_map_card_style( int $listing_id = 0 ): string {
		$widget = $this->current_widget();

		if ( ! $widget ) {
			return '';
		}

		return $widget->build_card_wrapper_style_for_listing( $listing_id, 'map' );
	}

	/**
	 * Override Directorist map card templates only while Elementor map rendering is active.
	 *
	 * @param string               $file Resolved template file path.
	 * @param string               $template_name Directorist template name.
	 * @param array<string,mixed>  $args Template args.
	 * @return string
	 */
	public function filter_template_file_path( string $file, string $template_name, array $args = [] ): string {
		if ( ! $this->current_widget() ) {
			return $file;
		}

		$overrides = [
			'archive/fields/openstreet-map' => dirname( __DIR__, 3 ) . '/templates/directorist/archive/fields/openstreet-map.php',
			'archive/fields/google-map'     => dirname( __DIR__, 3 ) . '/templates/directorist/archive/fields/google-map.php',
		];

		if ( empty( $overrides[ $template_name ] ) ) {
			return $file;
		}

		$override = $overrides[ $template_name ];

		return file_exists( $override ) ? $override : $file;
	}
}
