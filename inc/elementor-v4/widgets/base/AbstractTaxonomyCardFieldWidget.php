<?php
/**
 * Base widget for category/location card composition fields.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use Elementor\Controls_Manager;

abstract class AbstractTaxonomyCardFieldWidget extends AbstractDirectoristWidget {

	/**
	 * Taxonomy card fields live with the preset card fields in the panel.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRESET;
	}

	/**
	 * Expected taxonomy scope for this widget.
	 *
	 * @return string
	 */
	abstract protected function get_taxonomy_scope(): string;

	/**
	 * Add shared taxonomy-card keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'taxonomy', 'category', 'location', 'card' ] );
	}

	/**
	 * Get current taxonomy card context.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_taxonomy_context(): array {
		return RenderContext::get_instance()->current_taxonomy_context();
	}

	/**
	 * Check whether the active context matches this field widget.
	 *
	 * @return bool
	 */
	protected function has_matching_taxonomy_context(): bool {
		$context = $this->get_taxonomy_context();

		return $this->get_taxonomy_scope() === (string) ( $context['scope'] ?? '' );
	}

	/**
	 * Get the current taxonomy item payload.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_taxonomy_item(): array {
		$context = $this->get_taxonomy_context();
		$item    = $context['item'] ?? [];

		return is_array( $item ) ? $item : [];
	}

	/**
	 * Get a string field from the current taxonomy item.
	 *
	 * @param string $key Field key.
	 * @return string
	 */
	protected function get_item_string( string $key ): string {
		$item = $this->get_taxonomy_item();

		return isset( $item[ $key ] ) ? (string) $item[ $key ] : '';
	}

	/**
	 * Get an integer field from the current taxonomy item.
	 *
	 * @param string $key Field key.
	 * @return int
	 */
	protected function get_item_int( string $key ): int {
		$item = $this->get_taxonomy_item();

		return absint( $item[ $key ] ?? 0 );
	}

	/**
	 * Render an editor-only context placeholder.
	 *
	 * @param string $description Placeholder description.
	 * @return void
	 */
	protected function render_taxonomy_context_placeholder( string $description ): void {
		if ( ! $this->is_editor_context() ) {
			return;
		}

		echo wp_kses_post(
			$this->render_placeholder(
				$this->get_title(),
				$description
			)
		);
	}

	/**
	 * Register shared position controls for taxonomy card field widgets.
	 *
	 * @param string $selector CSS selector.
	 * @return void
	 */
	protected function register_position_controls( string $selector = '{{WRAPPER}}' ): void {
		$this->start_controls_section(
			'section_position',
			[
				'label' => __( 'Position', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'position_preset',
			[
				'label'   => __( 'Position', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default'      => __( 'Default', 'directorist-elementor' ),
					'top-left'     => __( 'Top Left', 'directorist-elementor' ),
					'top-center'   => __( 'Top Center', 'directorist-elementor' ),
					'top-right'    => __( 'Top Right', 'directorist-elementor' ),
					'center-left'  => __( 'Left', 'directorist-elementor' ),
					'center'       => __( 'Center', 'directorist-elementor' ),
					'center-right' => __( 'Right', 'directorist-elementor' ),
					'bottom-left'  => __( 'Bottom Left', 'directorist-elementor' ),
					'bottom-center' => __( 'Bottom Center', 'directorist-elementor' ),
					'bottom-right' => __( 'Bottom Right', 'directorist-elementor' ),
				],
				'selectors_dictionary' => [
					'default'       => 'position:relative;top:auto;right:auto;bottom:auto;left:auto;',
					'top-left'      => 'position:absolute;top:0;right:auto;bottom:auto;left:0;',
					'top-center'    => 'position:absolute;top:0;right:auto;bottom:auto;left:50%;--direl-taxonomy-field-x:-50%;',
					'top-right'     => 'position:absolute;top:0;right:0;bottom:auto;left:auto;',
					'center-left'   => 'position:absolute;top:50%;right:auto;bottom:auto;left:0;--direl-taxonomy-field-y:-50%;',
					'center'        => 'position:absolute;top:50%;right:auto;bottom:auto;left:50%;--direl-taxonomy-field-x:-50%;--direl-taxonomy-field-y:-50%;',
					'center-right'  => 'position:absolute;top:50%;right:0;bottom:auto;left:auto;--direl-taxonomy-field-y:-50%;',
					'bottom-left'   => 'position:absolute;top:auto;right:auto;bottom:0;left:0;',
					'bottom-center' => 'position:absolute;top:auto;right:auto;bottom:0;left:50%;--direl-taxonomy-field-x:-50%;',
					'bottom-right'  => 'position:absolute;top:auto;right:0;bottom:0;left:auto;',
				],
				'selectors' => [
					$selector => '{{VALUE}};z-index:2;transform:translate(var(--direl-taxonomy-field-x,0px),var(--direl-taxonomy-field-y,0px));',
				],
			]
		);

		$this->add_responsive_control(
			'position_x',
			[
				'label'      => __( 'Transform X', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => -300, 'max' => 300 ],
					'%'  => [ 'min' => -100, 'max' => 100 ],
					'em' => [ 'min' => -20, 'max' => 20 ],
				],
				'selectors'  => [
					$selector => '--direl-taxonomy-field-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'position_y',
			[
				'label'      => __( 'Transform Y', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => -300, 'max' => 300 ],
					'%'  => [ 'min' => -100, 'max' => 100 ],
					'em' => [ 'min' => -20, 'max' => 20 ],
				],
				'selectors'  => [
					$selector => '--direl-taxonomy-field-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}
}
