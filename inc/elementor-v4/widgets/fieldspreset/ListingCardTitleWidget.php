<?php
/**
 * Listing card title widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class ListingCardTitleWidget extends AbstractPresetFieldWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listing_card_title';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listing Title', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-heading';
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'title' ] );
	}

	/**
	 * Register content and style controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_title_content',
			[
				'label' => __( 'Title', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h3',
				'options' => [
					'h1'   => 'H1',
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
			]
		);

		$this->add_control(
			'link_to_listing',
			[
				'label'        => __( 'Link To Listing', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_title_style',
			[
				'label' => __( 'Title', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->start_controls_tabs( 'tabs_title_colors' );

		$this->start_controls_tab(
			'tab_title_color_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'title_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-title__text' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-title__link' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_title_color_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'title_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-title__link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-title__link:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-title__text, {{WRAPPER}} .directorist-elementor-listing-card-title__link',
			]
		);

		$this->add_responsive_control(
			'title_align',
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
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-title' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the title field.
	 *
	 * @return void
	 */
	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$settings   = $this->get_settings_for_display();
		$listing_id = $this->get_current_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render a listing title.', 'directorist-elementor' )
			);
			return;
		}

		$title = DirectoristBridge::get_instance()->get_listing_title( $listing_id );

		if ( '' === trim( (string) $title ) ) {
			return;
		}

		$html_tag = strtolower( (string) ( $settings['html_tag'] ?? 'h3' ) );
		$html_tag = in_array( $html_tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'div', 'span', 'p' ], true ) ? $html_tag : 'h3';
		$permalink = DirectoristBridge::get_instance()->get_listing_permalink( $listing_id );
		$link_to_listing = 'yes' === ( $settings['link_to_listing'] ?? 'yes' ) && '' !== (string) $permalink;

		echo '<div class="directorist-elementor-listing-card-title">';
		printf( '<%1$s class="directorist-elementor-listing-card-title__text">', esc_attr( $html_tag ) );

		if ( $link_to_listing ) {
			printf(
				'<a class="directorist-elementor-listing-card-title__link" href="%1$s">%2$s</a>',
				esc_url( (string) $permalink ),
				esc_html( $title )
			);
		} else {
			echo esc_html( $title );
		}

		printf( '</%s>', esc_attr( $html_tag ) );
		echo '</div>';
	}
}
