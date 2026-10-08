<?php
/**
 * Single listing bookmark widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleActionWidget;

class SingleListingBookmarkWidget extends AbstractSingleActionWidget {

	public function get_name(): string {
		return 'directorist_single_listing_bookmark';
	}

	public function get_title(): string {
		return __( 'Bookmark', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-heart-o';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'bookmark', 'favorite' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_single_action_content_controls(
			'section_bookmark_content',
			__( 'Bookmark', 'directorist-elementor' ),
			[
				'default_label' => __( 'Bookmark', 'directorist-elementor' ),
				'default_icon'  => [],
			]
		);
		$this->register_single_action_style_controls( 'section_bookmark_style', __( 'Bookmark', 'directorist-elementor' ) );
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_action_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the Bookmark action.', 'directorist-elementor' )
			);
			return;
		}

		DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/fields/bookmark' );

		$settings = $this->get_settings_for_display();
		$parts    = $this->get_single_action_parts(
			$settings,
			[
				'label' => __( 'Bookmark', 'directorist-elementor' ),
				'icon'  => [],
			]
		);

		if ( $parts['show_icon'] && '' === $parts['icon_markup'] && function_exists( 'the_atbdp_favourites_link' ) ) {
			$parts['icon_markup'] = sprintf(
				'<span class="directorist-elementor-single-action__icon">%s</span>',
				wp_kses_post( (string) the_atbdp_favourites_link( $listing_id ) )
			);
			$parts['content'] = 'after' === ( $settings['icon_position'] ?? 'before' )
				? trim( $parts['label_markup'] . ' ' . $parts['icon_markup'] )
				: trim( $parts['icon_markup'] . ' ' . $parts['label_markup'] );
		}

		$classes = [
			'directorist-single-listing-action',
			'directorist-action-bookmark',
			'directorist-btn',
			'directorist-btn-sm',
			'directorist-btn-light',
			'atbdp-favourites',
		];

		if ( ! is_user_logged_in() ) {
			$classes[] = 'atbdp-require-login';
		}

		echo '<div class="directorist-elementor-single-action directorist-elementor-single-action--bookmark">';
		printf(
			'<button class="%1$s" type="button" data-listing_id="%2$d" aria-label="%3$s" data-label="%4$s">%5$s</button>',
			esc_attr( implode( ' ', $classes ) ),
			absint( $listing_id ),
			esc_attr__( 'Add to Favorite Button', 'directorist-elementor' ),
			esc_attr( $parts['label'] ),
			$parts['content'] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor Icons_Manager SVG output.
		);
		echo '</div>';
	}
}
