<?php
/**
 * Base listings-loop utility widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractLoopAwareWidget;

abstract class AbstractLoopUtilityWidget extends AbstractLoopAwareWidget {

	/**
	 * Get the current loop controller.
	 *
	 * @return object|null
	 */
	protected function get_loop_controller() {
		$loop_context = $this->get_loop_context();
		$controller   = $loop_context['controller'] ?? null;

		return is_object( $controller ) ? $controller : null;
	}

	/**
	 * Get a mutable clone of the current loop controller.
	 *
	 * @return object|null
	 */
	protected function get_cloned_loop_controller() {
		$controller = $this->get_loop_controller();

		if ( ! is_object( $controller ) ) {
			return null;
		}

		return clone $controller;
	}

	/**
	 * Get normalized loop data atts.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_loop_data_atts(): array {
		$loop_context = $this->get_loop_context();
		$data_atts    = $loop_context['data_atts'] ?? [];

		return is_array( $data_atts ) ? $data_atts : [];
	}

	/**
	 * Get loop pagination type.
	 *
	 * @return string
	 */
	protected function get_loop_pagination_type(): string {
		$loop_context     = $this->get_loop_context();
		$pagination_type  = sanitize_key( (string) ( $loop_context['pagination_type'] ?? 'numbered' ) );

		return 'infinite_scroll' === $pagination_type ? 'infinite_scroll' : 'numbered';
	}

	/**
	 * Render a standard loop-context placeholder.
	 *
	 * @param string $title Placeholder title.
	 * @param string $description Placeholder description.
	 * @return void
	 */
	protected function render_loop_context_placeholder( string $title, string $description ): void {
		if ( $this->is_editor_context() ) {
			echo wp_kses_post( $this->render_placeholder( $title, $description ) );
		}
	}

	/**
	 * Build a loop utility wrapper attribute array.
	 *
	 * @param array<int,string> $classes Additional classes.
	 * @param array<string,mixed> $attributes Extra attributes.
	 * @return array<string,mixed>
	 */
	protected function build_loop_utility_attributes( array $classes = [], array $attributes = [] ): array {
		$data_atts = $this->get_loop_data_atts();

		return array_merge(
			[
				'class'     => array_merge( [ 'directorist-elementor-loop-utility' ], $classes ),
				'data-atts' => ! empty( $data_atts ) ? wp_json_encode( $data_atts ) : null,
			],
			$attributes
		);
	}
}
