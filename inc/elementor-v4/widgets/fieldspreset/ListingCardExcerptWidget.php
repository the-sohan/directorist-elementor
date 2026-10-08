<?php
/**
 * Listing card excerpt widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class ListingCardExcerptWidget extends AbstractPresetFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_excerpt';
	}

	public function get_title(): string {
		return __( 'Excerpt', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text-area';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'excerpt', 'description', 'summary', 'content' ] );
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_excerpt_content',
			[
				'label' => __( 'Excerpt', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'words_limit',
			[
				'label'   => __( 'Words Limit', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => $this->get_default_words_limit(),
				'min'     => 5,
				'max'     => 200,
			]
		);

		$this->add_control(
			'show_readmore',
			[
				'label'        => __( 'Show Read More', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => $this->get_default_show_readmore(),
			]
		);

		$this->add_control(
			'show_readmore_text',
			[
				'label'     => __( 'Read More Text', 'directorist-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => $this->get_default_readmore_text(),
				'condition' => [
					'show_readmore' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_excerpt_style',
			[
				'label' => __( 'Excerpt', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'excerpt_align',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'left' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
					'justify' => [
						'title' => __( 'Justified', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-justify',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-excerpt' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'excerpt_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-single__info__excerpt' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'excerpt_typography',
				'selector' => '{{WRAPPER}} .directorist-listing-single__info__excerpt',
			]
		);

		$this->start_controls_tabs( 'tabs_excerpt_link_colors' );

		$this->start_controls_tab(
			'tab_excerpt_link_normal',
			[
				'label' => __( 'Link', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'excerpt_link_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-single__info__excerpt a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_excerpt_link_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'excerpt_link_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-single__info__excerpt a:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-listing-single__info__excerpt a:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing excerpt.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();

		if ( ! $bridge->is_preset_widget_allowed( $listing_id, 'excerpt' ) ) {
			return;
		}

		$excerpt = $bridge->get_listing_excerpt( $listing_id );

		if ( '' === trim( $excerpt ) ) {
			return;
		}

		$settings         = $this->get_settings_for_display();
		$words_limit      = max( 5, absint( $settings['words_limit'] ?? $this->get_default_words_limit() ) );
		$show_readmore    = 'yes' === ( $settings['show_readmore'] ?? '' );
		$readmore_text    = sanitize_text_field( (string) ( $settings['show_readmore_text'] ?? $this->get_default_readmore_text() ) );
		$excerpt_markup   = esc_html( wp_trim_words( wp_strip_all_tags( $excerpt ), $words_limit ) );
		$listing_permalink = $bridge->get_listing_permalink( $listing_id );

		echo '<div class="directorist-elementor-listing-card-excerpt"><p class="directorist-listing-single__info__excerpt">';
		echo $excerpt_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $show_readmore && '' !== $readmore_text && '' !== $listing_permalink ) {
			printf(
				' <a href="%1$s" class="directorist-elementor-listing-card-excerpt__link">%2$s</a>',
				esc_url( $listing_permalink ),
				esc_html( $readmore_text )
			);
		}

		echo '</p></div>';
	}

	protected function get_default_words_limit(): int {
		if ( function_exists( 'get_directorist_option' ) ) {
			return max( 5, absint( get_directorist_option( 'excerpt_limit', 20 ) ) );
		}

		return 20;
	}

	protected function get_default_show_readmore(): string {
		if ( function_exists( 'get_directorist_option' ) && get_directorist_option( 'display_readmore', false ) ) {
			return 'yes';
		}

		return '';
	}

	protected function get_default_readmore_text(): string {
		if ( function_exists( 'get_directorist_option' ) ) {
			$readmore_text = trim( (string) get_directorist_option( 'readmore_text', __( 'Read More', 'directorist-elementor' ) ) );

			if ( '' !== $readmore_text ) {
				return $readmore_text;
			}
		}

		return __( 'Read More', 'directorist-elementor' );
	}
}
