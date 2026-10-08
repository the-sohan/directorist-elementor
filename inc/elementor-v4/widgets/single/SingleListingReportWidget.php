<?php
/**
 * Single listing report widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleActionWidget;

class SingleListingReportWidget extends AbstractSingleActionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_report';
	}

	public function get_title(): string {
		return __( 'Report', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-alert';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'report', 'abuse' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_single_action_content_controls(
			'section_report_content',
			__( 'Report', 'directorist-elementor' ),
			[
				'default_label' => __( 'Report', 'directorist-elementor' ),
				'default_icon'  => [
					'value'   => 'fas fa-flag',
					'library' => 'fa-solid',
				],
			]
		);
		$this->register_single_action_style_controls( 'section_report_style', __( 'Report', 'directorist-elementor' ) );
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_action_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the Report action.', 'directorist-elementor' )
			);
			return;
		}

		DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/fields/report' );

		$settings = $this->get_settings_for_display();
		$parts    = $this->get_single_action_parts(
			$settings,
			[
				'label' => __( 'Report', 'directorist-elementor' ),
				'icon'  => [
					'value'   => 'fas fa-flag',
					'library' => 'fa-solid',
				],
			]
		);

		$is_logged_in = is_user_logged_in();
		$classes      = [
			'directorist-single-listing-action',
			'directorist-btn',
			'directorist-btn-sm',
			'directorist-btn-light',
			'directorist-action-report',
			'directorist-btn-modal',
			'directorist-btn-modal-js',
			$is_logged_in ? 'directorist-action-report-loggedin' : 'directorist-action-report-not-loggedin',
		];

		echo '<div class="directorist-elementor-single-action directorist-elementor-single-action--report">';
		printf(
			'<button class="%1$s" type="button"%2$s aria-label="%3$s">%4$s</button>',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			$is_logged_in ? ' data-directorist_target="directorist-report-abuse-modal"' : '',
			esc_attr( $parts['label'] ),
			$parts['content'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor Icons_Manager SVG output.
		);
		printf(
			'<div class="directorist-modal directorist-modal-js directorist-fade directorist-report-abuse-modal"><div class="directorist-modal__dialog"><div class="directorist-modal__content"><form id="directorist-report-abuse-form"><header class="directorist-modal__header"><div class="directorist-modal-title" id="directorist-report-abuse-modal__label">%1$s</div><button class="directorist-modal-close directorist-modal-close-js" aria-label="%2$s"><span aria-hidden="true">&times;</span></button></header><div class="directorist-modal__body"><div class="directorist-form-group"><label for="directorist-report-message">%3$s<span class="directorist-report-star">*</span></label><textarea class="directorist-form-element" id="directorist-report-message" rows="3" placeholder="%4$s" required></textarea><input type="hidden" name="atbdp-post-id" id="atbdp-post-id" value="%5$d" /></div><div id="directorist-report-abuse-g-recaptcha"></div><div id="directorist-report-abuse-message-display"></div></div><div class="directorist-modal__footer"><button type="submit" class="directorist-btn directorist-btn-sm">%6$s</button></div></form></div></div></div>',
			esc_html__( 'Report Abuse', 'directorist-elementor' ),
			esc_attr__( 'Report Modal Close', 'directorist-elementor' ),
			esc_html__( 'Your Complaint', 'directorist-elementor' ),
			esc_attr__( 'Message...', 'directorist-elementor' ),
			absint( $listing_id ),
			esc_html__( 'Submit', 'directorist-elementor' )
		);
		echo '</div>';
	}
}
