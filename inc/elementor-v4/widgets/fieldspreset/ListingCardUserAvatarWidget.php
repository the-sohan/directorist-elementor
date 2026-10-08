<?php
/**
 * Listing card user avatar widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractMediaFieldWidget;
use Elementor\Controls_Manager;

class ListingCardUserAvatarWidget extends AbstractMediaFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_user_avatar';
	}

	public function get_title(): string {
		return __( 'Listing User Avatar', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'avatar', 'author', 'user' ] );
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_avatar_content',
			[
				'label' => __( 'Avatar', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'avatar_size',
			[
				'label'   => __( 'Avatar Size', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 72,
				'min'     => 24,
				'max'     => 320,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_avatar_style',
			[
				'label' => __( 'Avatar', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'avatar_width',
			[
				'label'      => __( 'Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-user-avatar__image' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avatar_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-user-avatar__image' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'avatar_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-user-avatar__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing author avatar.', 'directorist-elementor' )
			);
			return;
		}

		$settings   = $this->get_settings_for_display();
		$avatar_url = DirectoristBridge::get_instance()->get_listing_author_avatar_url( $listing_id, absint( $settings['avatar_size'] ?? 72 ) );

		if ( '' === $avatar_url ) {
			return;
		}

		printf(
			'<div class="directorist-elementor-listing-card-user-avatar"><img class="directorist-elementor-listing-card-user-avatar__image" src="%1$s" alt="%2$s" loading="lazy" /></div>',
			esc_url( $avatar_url ),
			esc_attr__( 'Listing author avatar', 'directorist-elementor' )
		);
	}
}
