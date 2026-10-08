<?php
/**
 * Single listing back widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleActionWidget;

class SingleListingBackWidget extends AbstractSingleActionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_back';
	}

	public function get_title(): string {
		return __( 'Back', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-arrow-left';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'back', 'action' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_single_action_content_controls(
			'section_back_content',
			__( 'Back', 'directorist-elementor' ),
			[
				'default_label' => __( 'Go Back', 'directorist-elementor' ),
				'default_icon'  => [
					'value'   => 'fas fa-arrow-left',
					'library' => 'fa-solid',
				],
			]
		);
		$this->register_single_action_style_controls( 'section_back_style', __( 'Back', 'directorist-elementor' ) );
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_action_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the Back action.', 'directorist-elementor' )
			);
			return;
		}

		DirectoristBridge::get_instance()->ensure_single_listing_assets();

		$settings = $this->get_settings_for_display();
		$parts    = $this->get_single_action_parts(
			$settings,
			[
				'label' => __( 'Go Back', 'directorist-elementor' ),
				'icon'  => [
					'value'   => 'fas fa-arrow-left',
					'library' => 'fa-solid',
				],
			]
		);

		echo '<div class="directorist-elementor-single-action directorist-elementor-single-action--back">';
		printf(
			'<a href="%1$s" class="%2$s">%3$s</a>',
			esc_attr( 'javascript:history.back()' ),
			esc_attr( 'directorist-single-listing-action directorist-return-back directorist-btn__back directorist-btn directorist-btn-sm directorist-btn-light' ),
			$parts['content'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor Icons_Manager SVG output.
		);
		echo '</div>';
	}
}
