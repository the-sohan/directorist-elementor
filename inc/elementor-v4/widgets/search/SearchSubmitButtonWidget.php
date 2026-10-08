<?php
/**
 * Search submit button widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Search;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractDirectoristWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;

class SearchSubmitButtonWidget extends AbstractDirectoristWidget {

	public function get_name(): string {
		return 'directorist_search_submit_button';
	}

	public function get_title(): string {
		return __( 'Search Button', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-button';
	}

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_SEARCH_FIELDS;
	}

	/**
	 * Mark the button wrapper as a search composition item before Elementor prints it.
	 *
	 * @return void
	 */
	public function before_render() {
		if ( ! empty( RenderContext::get_instance()->current_search_form_context() ) ) {
			$this->add_render_attribute(
				'_wrapper',
				'class',
				[
					'directorist-elementor-search-composition-item',
					'directorist-elementor-search-submit-widget',
				]
			);
		}

		parent::before_render();
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_button_content',
			[
				'label' => __( 'Button', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'button_text',
			[
				'label'       => __( 'Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Search', 'directorist-elementor' ),
				'placeholder' => __( 'Search', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'directorist_home_search_inherited_settings',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_button_style',
			[
				'label' => __( 'Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-submit__button',
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-submit__button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-search-submit__button' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-search-submit__button',
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-submit__button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-search-submit__button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$context = RenderContext::get_instance()->current_search_form_context();

		if ( ! empty( $context['suppress_submit'] ) ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$text     = trim( (string) ( $settings['button_text'] ?? '' ) );

		if ( '' === $text ) {
			$text = __( 'Search', 'directorist-elementor' );
		}

		echo '<div class="directorist-search-form-action directorist-elementor-search-submit">';
		printf(
			'<button type="submit" class="directorist-btn directorist-btn-lg directorist-btn-dark directorist-btn-search directorist-elementor-search-submit__button">%s</button>',
			esc_html( $text )
		);
		echo '</div>';
	}
}
