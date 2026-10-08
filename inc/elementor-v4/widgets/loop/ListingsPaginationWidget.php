<?php
/**
 * Listings pagination widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;

class ListingsPaginationWidget extends AbstractLoopUtilityWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_listings_pagination';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listings Pagination', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-post-navigation';
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_pagination_content',
			[
				'label' => __( 'Pagination', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'pagination_mode_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Pagination mode is inherited from the parent Listings Loop. Numbered settings apply in Numbered mode, and infinite settings apply in Infinite Scroll mode.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_previous_icon',
			[
				'label' => __( 'Previous Page Icon', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'previous_page_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-chevron-left',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'previous_page_icon_color',
			[
				'label' => __( 'Icon Color', 'directorist-elementor' ),
				'type'  => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'previous_page_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 64,
					],
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_next_icon',
			[
				'label' => __( 'Next Page Icon', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'next_page_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-chevron-right',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'next_page_icon_color',
			[
				'label' => __( 'Icon Color', 'directorist-elementor' ),
				'type'  => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'next_page_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 14,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 64,
					],
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_infinite',
			[
				'label' => __( 'Infinite Scroll Settings', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_infinite_loading_text',
			[
				'label'        => __( 'Show Loading Text', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'infinite_loading_text',
			[
				'label'       => __( 'Loading Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Loading more...', 'directorist-elementor' ),
				'placeholder' => __( 'Loading more...', 'directorist-elementor' ),
				'condition'   => [
					'show_infinite_loading_text' => 'yes',
				],
			]
		);

		$this->add_control(
			'infinite_loading_icon',
			[
				'label'       => __( 'Loading Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-spinner',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
			]
		);

		$this->add_control(
			'infinite_loading_icon_color',
			[
				'label' => __( 'Loading Icon Color', 'directorist-elementor' ),
				'type'  => Controls_Manager::COLOR,
			]
		);

		$this->add_control(
			'infinite_loading_icon_size',
			[
				'label'      => __( 'Loading Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 18,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 96,
					],
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_style_numbered',
			[
				'label' => __( 'Numbered Pagination', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_numbers_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers',
			]
		);

		$this->start_controls_tabs( 'tabs_pagination_numbered_states' );

		$this->start_controls_tab(
			'tab_pagination_numbered_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'pagination_number_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_pagination_numbered_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'pagination_number_hover_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_hover_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:hover' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:focus' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:hover' => 'border-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination a.page-numbers:focus' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_pagination_numbered_current',
			[
				'label' => __( 'Current', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'pagination_number_current_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers.current' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_current_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers.current' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_number_current_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers.current' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_responsive_control(
			'pagination_number_padding',
			[
				'label'      => __( 'Item Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_number_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination .directorist-pagination .page-numbers' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_style_infinite',
			[
				'label' => __( 'Infinite Loader', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'pagination_infinite_background_color',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination__loading' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_infinite_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination__loading' => 'border-color: {{VALUE}};border-style: solid;border-width: 1px;',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_infinite_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination__loading' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'pagination_infinite_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination__loading' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_pagination_style_loading',
			[
				'label' => __( 'Infinite Loader Text', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'pagination_loading_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listings-pagination__loading-text',
			]
		);

		$this->add_control(
			'pagination_loading_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listings-pagination__loading-text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the pagination widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings         = $this->get_settings_for_display();
		$rendered         = false;
		$had_loop_context = $this->with_active_loop_context(
			function() use ( $settings, &$rendered ) {
				$controller      = $this->get_cloned_loop_controller();
				$loop_context    = $this->get_loop_context();
				$is_editor       = $this->is_editor_context();
				$pagination_type = $this->get_loop_pagination_type();
				$display_mode    = sanitize_key( (string) ( $loop_context['display_mode'] ?? 'default' ) );
				$current_page    = max( 1, absint( $loop_context['current_page'] ?? 1 ) );
				$max_pages       = max( 1, absint( $loop_context['max_pages'] ?? 1 ) );
				$previous_markup = $this->get_pagination_arrow_markup( 'prev', $settings );
				$next_markup     = $this->get_pagination_arrow_markup( 'next', $settings );
				$previous_markup_for_frontend = $this->get_pagination_arrow_markup( 'prev', $settings, true );
				$next_markup_for_frontend     = $this->get_pagination_arrow_markup( 'next', $settings, true );

				if ( 'slider' === $display_mode ) {
					return;
				}

				if ( ! $controller ) {
					return;
				}

				$attributes = $this->build_loop_utility_attributes(
					[
						'directorist-elementor-listings-pagination',
						'directorist-elementor-listings-pagination--' . sanitize_html_class( $pagination_type ),
						$is_editor ? 'directorist-elementor-listings-pagination--editor-preview' : '',
					],
					[
						'data-pagination-mode' => $pagination_type,
						'data-current-page'    => (string) $current_page,
						'data-max-pages'       => (string) $max_pages,
					]
				);

				if ( ! $is_editor && $max_pages <= 1 ) {
					return;
				}

				if ( ! $is_editor && 'infinite_scroll' === $pagination_type && $current_page >= $max_pages ) {
					return;
				}

				if ( 'infinite_scroll' === $pagination_type ) {
					$show_loading_text = 'yes' === (string) ( $settings['show_infinite_loading_text'] ?? 'yes' );
					$loading_text = trim( (string) ( $settings['infinite_loading_text'] ?? '' ) );
					$loading_text = '' !== $loading_text ? $loading_text : __( 'Loading more...', 'directorist-elementor' );

					$attributes['data-infinite-loading-text']      = $loading_text;
					$attributes['data-infinite-show-loading-text'] = $show_loading_text ? '1' : '0';
				}

				echo '<div ' . $this->format_html_attributes( $attributes ) . '>';

				if ( 'infinite_scroll' === $pagination_type ) {
					echo '<div class="directorist-elementor-listings-pagination__inner directorist-elementor-listings-pagination__inner--infinite">';
					echo $this->get_infinite_loading_markup( $settings, $loading_text, $show_loading_text, true, 'directorist-elementor-listings-pagination__loading-template' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper markup with Elementor icon output.

					if ( $is_editor ) {
						echo $this->get_infinite_loading_markup( $settings, $loading_text, $show_loading_text, false, 'directorist-elementor-listings-pagination__loading-preview' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper markup with Elementor icon output.
					}

					echo '</div>';
				} elseif ( $is_editor ) {
					printf(
						'<div class="directorist-elementor-listings-pagination__inner directorist-elementor-listings-pagination__inner--numbered"><nav class="directorist-pagination" aria-label="%1$s"><span class="page-numbers directorist-elementor-listings-pagination__preview-prev" aria-label="%2$s">%3$s</span><span class="page-numbers current" aria-current="page">1</span><span class="page-numbers">2</span><span class="page-numbers directorist-elementor-listings-pagination__preview-next" aria-label="%4$s">%5$s</span></nav></div>',
						esc_attr__( 'Listings Pagination', 'directorist-elementor' ),
						esc_attr__( 'Previous page', 'directorist-elementor' ),
						$previous_markup,
						esc_attr__( 'Next page', 'directorist-elementor' ),
						$next_markup
					);
				} elseif ( method_exists( $controller, 'pagination' ) ) {
					echo '<div class="directorist-elementor-listings-pagination__inner directorist-elementor-listings-pagination__inner--numbered">';

					if ( function_exists( 'add_filter' ) && function_exists( 'remove_filter' ) ) {
						$previous_text_filter = static function( $current_text ) use ( $previous_markup_for_frontend ) {
							return '' !== $previous_markup_for_frontend ? $previous_markup_for_frontend : $current_text;
						};
						$next_text_filter     = static function( $current_text ) use ( $next_markup_for_frontend ) {
							return '' !== $next_markup_for_frontend ? $next_markup_for_frontend : $current_text;
						};

						add_filter( 'directorist_pagination_prev_text', $previous_text_filter, 9999 );
						add_filter( 'directorist_pagination_next_text', $next_text_filter, 9999 );
						$controller->pagination();
						remove_filter( 'directorist_pagination_prev_text', $previous_text_filter, 9999 );
						remove_filter( 'directorist_pagination_next_text', $next_text_filter, 9999 );
					} else {
						$controller->pagination();
					}

					echo '</div>';
				}

				echo '</div>';

				$rendered = true;
			}
		);

		if ( ! $had_loop_context ) {
			$this->render_loop_context_placeholder(
				__( 'Listings Pagination', 'directorist-elementor' ),
				__( 'Place this widget inside Listings Loop to render numbered or infinite pagination.', 'directorist-elementor' )
			);
		}
	}

	/**
	 * Get preview pagination arrow markup.
	 *
	 * @param string $direction Arrow direction.
	 * @return string
	 */
	protected function get_pagination_arrow_markup( string $direction, array $settings = [], bool $safe_for_wp_kses = false ): string {
		$icon_setting = 'prev' === $direction
			? (array) ( $settings['previous_page_icon'] ?? [] )
			: (array) ( $settings['next_page_icon'] ?? [] );
		$color       = 'prev' === $direction
			? (string) ( $settings['previous_page_icon_color'] ?? '' )
			: (string) ( $settings['next_page_icon_color'] ?? '' );
		$size        = 'prev' === $direction
			? $this->normalize_icon_size_value( $settings['previous_page_icon_size'] ?? null, '14px' )
			: $this->normalize_icon_size_value( $settings['next_page_icon_size'] ?? null, '14px' );
		$fallback    = 'prev' === $direction
			? [
				'value'   => 'fas fa-chevron-left',
				'library' => 'fa-solid',
			]
			: [
				'value'   => 'fas fa-chevron-right',
				'library' => 'fa-solid',
			];
		$markup      = $this->render_configured_icon_markup(
			$icon_setting,
			$fallback,
			'directorist-elementor-listings-pagination__arrow-icon directorist-elementor-listings-pagination__arrow-icon--' . $direction,
			$color,
			$size,
			false,
			$safe_for_wp_kses
		);

		return '' !== $markup ? $markup : ( 'prev' === $direction ? '&lsaquo;' : '&rsaquo;' );
	}

	/**
	 * Build infinite loading markup.
	 *
	 * @param string $loading_text Loading label text.
	 * @param bool   $hidden Whether the markup should be hidden by default.
	 * @param string $modifier_class Modifier class name.
	 * @return string
	 */
	protected function get_infinite_loading_markup( array $settings, string $loading_text, bool $show_text, bool $hidden, string $modifier_class ): string {
		$icon_markup = $this->render_configured_icon_markup(
			(array) ( $settings['infinite_loading_icon'] ?? [] ),
			[
				'value'   => 'fas fa-spinner',
				'library' => 'fa-solid',
			],
			'directorist-elementor-listings-pagination__loading-icon',
			(string) ( $settings['infinite_loading_icon_color'] ?? '' ),
			$this->normalize_icon_size_value( $settings['infinite_loading_icon_size'] ?? null, '18px' ),
			true
		);

		if ( '' === $icon_markup ) {
			$icon_markup = '<span class="directorist-elementor-listings-pagination__loading-icon is-spinner-fallback" aria-hidden="true"></span>';
		}

		return sprintf(
			'<div class="directorist-elementor-listings-pagination__loading directorist-on-scroll-loading %1$s"%2$s role="status" aria-live="polite">%3$s%4$s</div>',
			esc_attr( $modifier_class ),
			$hidden ? ' hidden' : '',
			$icon_markup,
			$show_text ? sprintf( '<span class="directorist-elementor-listings-pagination__loading-text">%s</span>', esc_html( $loading_text ) ) : ''
		);
	}

	/**
	 * Render an icon control to HTML.
	 *
	 * @param array<string,mixed> $icon_setting Selected Elementor icon control value.
	 * @param array<string,mixed> $fallback_icon Fallback icon when setting is empty.
	 * @param string              $wrapper_class Wrapper class string.
	 * @param string              $color Optional icon color.
	 * @param string              $size Optional icon size.
	 * @param bool                $spin Whether to add spinning behavior.
	 * @return string
	 */
	protected function render_configured_icon_markup(
		array $icon_setting,
		array $fallback_icon,
		string $wrapper_class,
		string $color = '',
		string $size = '',
		bool $spin = false,
		bool $safe_for_wp_kses = false
	): string {
		$icon_setting = ! empty( $icon_setting['value'] ) ? $icon_setting : $fallback_icon;

		if ( empty( $icon_setting['value'] ) ) {
			return '';
		}

		$icon_html             = '';
		$icon_value            = is_string( $icon_setting['value'] ?? null ) ? trim( (string) $icon_setting['value'] ) : '';
		$fallback_icon_value   = is_string( $fallback_icon['value'] ?? null ) ? trim( (string) $fallback_icon['value'] ) : '';
		$use_directorist_icon  = '' !== $icon_value
			&& function_exists( 'directorist_icon' )
			&& ( $safe_for_wp_kses || $icon_value === $fallback_icon_value );

		if ( $use_directorist_icon ) {
			$icon_html = (string) directorist_icon( $icon_value, false );
		}

		if ( '' === trim( $icon_html ) && class_exists( Icons_Manager::class ) ) {
			ob_start();
			Icons_Manager::render_icon(
				$icon_setting,
				[
					'aria-hidden' => 'true',
				]
			);
			$icon_html = (string) ob_get_clean();
		}

		if ( '' === trim( $icon_html ) && '' !== $icon_value && function_exists( 'directorist_icon' ) ) {
			$icon_html = (string) directorist_icon( $icon_value, false );
		}

		if ( '' === trim( $icon_html ) && '' !== $icon_value ) {
			$icon_html = sprintf( '<i class="%s" aria-hidden="true"></i>', esc_attr( $icon_value ) );
		}

		if ( '' === trim( $icon_html ) ) {
			return '';
		}

		if ( ! $safe_for_wp_kses ) {
			$mask_icon_markup = $this->build_mask_icon_markup( $icon_html, $color, $size );

			if ( '' !== $mask_icon_markup ) {
				$icon_html = $mask_icon_markup;
				$color     = '';
				$size      = '';
			}
		}

		$style = [];

		if ( '' !== trim( $color ) ) {
			$style[] = 'color:' . sanitize_text_field( trim( $color ) );
		}

		if ( '' !== trim( $size ) ) {
			$style[] = 'font-size:' . sanitize_text_field( trim( $size ) );
		}

		if ( $spin ) {
			$wrapper_class .= ' is-spinning';
		}

		return sprintf(
			'<span class="%1$s"%2$s>%3$s</span>',
			esc_attr( $wrapper_class ),
			! empty( $style ) ? ' style="' . esc_attr( implode( ';', $style ) . ';' ) . '"' : '',
			$icon_html
		);
	}

	/**
	 * Convert Directorist mask icon markup into an inline-size/color-safe icon.
	 *
	 * Directorist core mask icons are painted through `.directorist-icon-mask:after`
	 * with fixed core styles. Non-sanitized editor/loader output can use direct
	 * inline width/height/background handling so the visible icon responds to the
	 * selected color and size values. Frontend pagination keeps native Directorist
	 * markup because Directorist sanitizes pagination with `wp_kses_post()`.
	 *
	 * @param string $icon_html Original icon markup.
	 * @param string $color Optional icon color.
	 * @param string $size Optional icon size.
	 * @return string
	 */
	protected function build_mask_icon_markup( string $icon_html, string $color = '', string $size = '' ): string {
		if ( false === strpos( $icon_html, 'directorist-icon-mask' ) ) {
			return '';
		}

		if ( ! preg_match( '/--directorist-icon:\s*url\(([^)]+)\)/', $icon_html, $matches ) ) {
			return '';
		}

		$icon_url = trim( (string) ( $matches[1] ?? '' ), " \t\n\r\0\x0B'\"" );

		if ( '' === $icon_url ) {
			return '';
		}

		$normalized_size = '' !== trim( $size ) ? sanitize_text_field( trim( $size ) ) : '18px';
		$style_parts     = [
			'display:inline-block',
			'width:' . $normalized_size,
			'height:' . $normalized_size,
			'background-color:' . ( '' !== trim( $color ) ? sanitize_text_field( trim( $color ) ) : 'currentColor' ),
			'-webkit-mask-repeat:no-repeat',
			'mask-repeat:no-repeat',
			'-webkit-mask-position:center',
			'mask-position:center',
			'-webkit-mask-size:contain',
			'mask-size:contain',
			'-webkit-mask-image:url(' . esc_url_raw( $icon_url ) . ')',
			'mask-image:url(' . esc_url_raw( $icon_url ) . ')',
			'vertical-align:middle',
		];

		return sprintf(
			'<span class="directorist-elementor-listings-pagination__mask-icon" aria-hidden="true" style="%s"></span>',
			esc_attr( implode( ';', $style_parts ) . ';' )
		);
	}

	/**
	 * Normalize an icon size control value.
	 *
	 * @param mixed  $control_value Icon size control value.
	 * @param string $default Default size.
	 * @return string
	 */
	protected function normalize_icon_size_value( $control_value, string $default = '' ): string {
		if ( is_array( $control_value ) && isset( $control_value['size'] ) && '' !== (string) $control_value['size'] ) {
			$size = trim( (string) $control_value['size'] );
			$unit = trim( (string) ( $control_value['unit'] ?? 'px' ) );

			if ( is_numeric( $size ) ) {
				return sanitize_text_field( $size . $unit );
			}
		}

		if ( is_string( $control_value ) && '' !== trim( $control_value ) ) {
			return sanitize_text_field( trim( $control_value ) );
		}

		return $default;
	}
}
