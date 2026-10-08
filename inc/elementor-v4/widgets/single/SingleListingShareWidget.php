<?php
/**
 * Single listing share widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleActionWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class SingleListingShareWidget extends AbstractSingleActionWidget {

	private const VENDORS = [
		'facebook'  => [
			'label' => 'Facebook',
			'icon'  => [
				'value'   => 'fab fa-facebook-f',
				'library' => 'fa-brands',
			],
		],
		'x'         => [
			'label' => 'X',
			'icon'  => [
				'value'   => 'fab fa-twitter',
				'library' => 'fa-brands',
			],
		],
		'linkedin'  => [
			'label' => 'LinkedIn',
			'icon'  => [
				'value'   => 'fab fa-linkedin-in',
				'library' => 'fa-brands',
			],
		],
		'whatsapp'  => [
			'label' => 'WhatsApp',
			'icon'  => [
				'value'   => 'fab fa-whatsapp',
				'library' => 'fa-brands',
			],
		],
		'pinterest' => [
			'label' => 'Pinterest',
			'icon'  => [
				'value'   => 'fab fa-pinterest-p',
				'library' => 'fa-brands',
			],
		],
		'reddit'    => [
			'label' => 'Reddit',
			'icon'  => [
				'value'   => 'fab fa-reddit-alien',
				'library' => 'fa-brands',
			],
		],
		'email'     => [
			'label' => 'Email',
			'icon'  => [
				'value'   => 'fas fa-envelope',
				'library' => 'fa-solid',
			],
		],
	];

	public function get_name(): string {
		return 'directorist_single_listing_share';
	}

	public function get_title(): string {
		return __( 'Share', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-share-arrow';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'share', 'social' ] );
	}

	protected function register_widget_controls(): void {
		// =====================================================================
		// Content: Share Action Settings
		// =====================================================================
		$this->start_controls_section(
			'section_share_settings',
			[
				'label' => __( 'Share Action Settings', 'directorist-elementor' ),
			]
		);

		// --- View type ---
		$this->add_control(
			'view_type',
			[
				'label'   => __( 'View Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'popup',
				'options' => [
					'popup'  => __( 'Popup', 'directorist-elementor' ),
					'inline' => __( 'Inline', 'directorist-elementor' ),
					'block'  => __( 'Block', 'directorist-elementor' ),
				],
			]
		);

		// --- Share platforms ---
		$vendor_options = [];
		foreach ( self::VENDORS as $key => $vendor ) {
			$vendor_options[ $key ] = $vendor['label'];
		}

		$this->add_control(
			'share_vendors',
			[
				'label'       => __( 'Share Platforms', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'default'     => [],
				'options'     => $vendor_options,
				'separator'   => 'before',
				'description' => __( 'Choose only the share buttons you want to display. Leave empty to hide the widget until vendors are selected.', 'directorist-elementor' ),
			]
		);

		// --- Per-vendor settings (only for selected vendors) ---
		foreach ( self::VENDORS as $key => $vendor ) {
			$this->add_control(
				$key . '_heading',
				[
					'label'     => $vendor['label'],
					'type'      => Controls_Manager::HEADING,
					'separator' => 'before',
					'condition' => [
						'share_vendors' => $key,
					],
				]
			);

			$this->add_control(
				$key . '_label',
				[
					'label'     => sprintf(
						/* translators: %s: vendor name. */
						__( '%s Label', 'directorist-elementor' ),
						$vendor['label']
					),
					'type'      => Controls_Manager::TEXT,
					'default'   => $vendor['label'],
					'condition' => [
						'share_vendors' => $key,
					],
				]
			);

			$this->add_control(
				$key . '_icon',
				[
					'label'     => sprintf(
						/* translators: %s: vendor name. */
						__( '%s Icon', 'directorist-elementor' ),
						$vendor['label']
					),
					'type'      => Controls_Manager::ICONS,
					'default'   => $vendor['icon'],
					'condition' => [
						'share_vendors' => $key,
					],
				]
			);
		}

		$this->add_control(
			'button_separator',
			[
				'type'      => Controls_Manager::DIVIDER,
			]
		);

		$this->add_control(
			'label',
			[
				'label'   => __( 'Label', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Share', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'icon_position',
			[
				'label'     => __( 'Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'before',
				'options'   => [
					'before' => __( 'Before Label', 'directorist-elementor' ),
					'after'  => __( 'After Label', 'directorist-elementor' ),
				],
				'conditions' => [
					'relation' => 'and',
					'terms'    => [
						[ 'name' => 'show_label', 'value' => 'yes' ],
						[ 'name' => 'show_icon', 'value' => 'yes' ],
					],
				],
			]
		);

		$this->add_control(
			'action_icon',
			[
				'label'   => __( 'Icon', 'directorist-elementor' ),
				'type'    => Controls_Manager::ICONS,
				'default' => [
					'value'   => 'fas fa-share-alt',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Share Button
		// =====================================================================
		$this->start_controls_section(
			'section_style_share_container',
			[
				'label' => __( 'Block Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-action--share',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-action--share',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-single-action--share',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-action--share' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-single-action--share' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_share_button',
			[
				'label' => __( 'Share Button', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-gbi-share-trigger svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-gbi-share-trigger:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'button_background',
				'selector' => '{{WRAPPER}} .directorist-gbi-share-trigger',
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .directorist-gbi-share-trigger',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'button_border',
				'selector' => '{{WRAPPER}} .directorist-gbi-share-trigger',
			]
		);

		$this->add_responsive_control(
			'button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-share-trigger' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-share-trigger' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Button Text
		// =====================================================================
		$this->start_controls_section(
			'section_style_button_text',
			[
				'label' => __( 'Button Text', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'button_text_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger .directorist-single-listing-action__text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'button_text_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger:hover .directorist-single-listing-action__text' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'button_text_typography',
				'selector' => '{{WRAPPER}} .directorist-gbi-share-trigger .directorist-single-listing-action__text',
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Button Icon
		// =====================================================================
		$this->start_controls_section(
			'section_style_button_icon',
			[
				'label'     => __( 'Button Icon', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'button_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger .directorist-elementor-single-action__icon' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-gbi-share-trigger svg.directorist-elementor-single-action__icon' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'button_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 48 ],
					'em' => [ 'min' => 0.5, 'max' => 3, 'step' => 0.1 ],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-share-trigger svg.directorist-elementor-single-action__icon' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-gbi-share-trigger i.directorist-elementor-single-action__icon' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Dropdown Container
		// =====================================================================
		$this->start_controls_section(
			'section_style_dropdown',
			[
				'label' => __( 'Dropdown Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'dropdown_background',
				'selector' => '{{WRAPPER}} .directorist-social-share-links',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'dropdown_border',
				'selector' => '{{WRAPPER}} .directorist-social-share-links',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'dropdown_shadow',
				'selector' => '{{WRAPPER}} .directorist-social-share-links',
			]
		);

		$this->add_responsive_control(
			'dropdown_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-social-share-links' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'dropdown_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-social-share-links' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Dropdown Item
		// =====================================================================
		$this->start_controls_section(
			'section_style_dropdown_item',
			[
				'label' => __( 'Dropdown Item', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'dropdown_item_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-social-links__item a' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-social-links__item a svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dropdown_item_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-social-links__item a:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-social-links__item a:hover svg' => 'fill: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'dropdown_item_typography',
				'selector' => '{{WRAPPER}} .directorist-social-links__item a',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'dropdown_item_background',
				'selector' => '{{WRAPPER}} .directorist-social-links__item a',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'dropdown_item_border',
				'selector' => '{{WRAPPER}} .directorist-social-links__item a',
			]
		);

		$this->add_responsive_control(
			'dropdown_item_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-social-links__item a' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		// =====================================================================
		// Style: Per-Platform Icon
		// =====================================================================
		$this->start_controls_section(
			'section_style_per_vendor',
			[
				'label' => __( 'Per-Platform Icon', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		foreach ( self::VENDORS as $key => $vendor ) {
			$this->add_control(
				$key . '_icon_color',
				[
					'label'     => sprintf( '%s %s', $vendor['label'], __( 'Color', 'directorist-elementor' ) ),
					'type'      => Controls_Manager::COLOR,
					'selectors' => [
						"{{WRAPPER}} .directorist-social-links__item--{$key} a" => 'color: {{VALUE}};',
						"{{WRAPPER}} .directorist-social-links__item--{$key} a svg" => 'fill: {{VALUE}};',
					],
					'condition' => [
						'share_vendors' => $key,
					],
				]
			);

			$this->add_responsive_control(
				$key . '_icon_size',
				[
					'label'      => sprintf( '%s %s', $vendor['label'], __( 'Size', 'directorist-elementor' ) ),
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ 'px', 'em' ],
					'range'      => [
						'px' => [ 'min' => 8, 'max' => 48 ],
						'em' => [ 'min' => 0.5, 'max' => 3, 'step' => 0.1 ],
					],
					'selectors' => [
						"{{WRAPPER}} .directorist-social-links__item--{$key} a svg" => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
						"{{WRAPPER}} .directorist-social-links__item--{$key} a i" => 'font-size: {{SIZE}}{{UNIT}};',
					],
					'condition' => [
						'share_vendors' => $key,
					],
				]
			);
		}

		$this->end_controls_section();
	}

	// =========================================================================
	// Render
	// =========================================================================

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_single_action_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the Share action.', 'directorist-elementor' )
			);
			return;
		}

		DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/fields/share' );

		$settings = $this->get_settings_for_display();
		$widget_data = DirectoristBridge::get_instance()->get_single_listing_header_widget(
			$listing_id,
			'share',
			'quick-widgets-placeholder',
			'quick-action-placeholder'
		);

		// --- Main button ---
		$show_icon  = 'yes' === ( $settings['show_icon'] ?? 'yes' );
		$show_label = 'yes' === ( $settings['show_label'] ?? 'yes' );

		if ( ! $show_icon && ! $show_label ) {
			$show_label = true;
		}

		$default_label = sanitize_text_field( (string) ( $widget_data['label'] ?? __( 'Share', 'directorist-elementor' ) ) );
		if ( '' === $default_label ) {
			$default_label = __( 'Share', 'directorist-elementor' );
		}

		$label_text = sanitize_text_field( (string) ( $settings['label'] ?? '' ) );
		if ( '' === $label_text ) {
			$label_text = $default_label;
		}

		$icon_setting = $settings['action_icon'] ?? [ 'value' => 'fas fa-share-alt', 'library' => 'fa-solid' ];

		$icon_markup  = '';
		if ( $show_icon && ! empty( $icon_setting['value'] ) ) {
			ob_start();
			\Elementor\Icons_Manager::render_icon(
				$icon_setting,
				[ 'aria-hidden' => 'true', 'class' => 'directorist-elementor-single-action__icon' ]
			);
			$icon_markup = (string) ob_get_clean();
		}

		$label_markup = '';
		if ( $show_label && '' !== $label_text ) {
			$label_markup = sprintf( '<span class="directorist-single-listing-action__text">%s</span>', esc_html( $label_text ) );
		}

		$icon_position = sanitize_key( (string) ( $settings['icon_position'] ?? 'before' ) );
		$button_content = 'after' === $icon_position
			? trim( $label_markup . ' ' . $icon_markup )
			: trim( $icon_markup . ' ' . $label_markup );

		// --- View type ---
		$view_type = sanitize_key( (string) ( $settings['view_type'] ?? 'popup' ) );
		$view_type = in_array( $view_type, [ 'popup', 'inline', 'block' ], true ) ? $view_type : 'popup';

		// --- Social links ---
		$selected_vendors = (array) ( $settings['share_vendors'] ?? [] );
		$selected_vendors = array_values( array_filter( $selected_vendors, static function ( $v ): bool {
			return is_string( $v ) && isset( self::VENDORS[ $v ] );
		} ) );

		if ( empty( $selected_vendors ) ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_action_placeholder(
					__( 'Add at least one share button from widget settings.', 'directorist-elementor' )
				);
			}
			return;
		}

		$listing_title = wp_strip_all_tags( (string) get_the_title( $listing_id ) );
		if ( '' === $listing_title ) {
			$listing_title = __( 'Listing', 'directorist-elementor' );
		}

		$listing_link = (string) get_permalink( $listing_id );
		if ( '' === $listing_link ) {
			$listing_link = home_url( '/' );
		}

		$social_links_markup = '';
		foreach ( $selected_vendors as $vendor_key ) {
			$vendor_key = sanitize_key( (string) $vendor_key );
			if ( ! isset( self::VENDORS[ $vendor_key ] ) ) {
				continue;
			}

				$defaults   = self::VENDORS[ $vendor_key ];
				$item_label = sanitize_text_field( (string) ( $settings[ $vendor_key . '_label' ] ?? $defaults['label'] ) );
				if ( '' === $item_label ) {
					$item_label = $defaults['label'];
				}

				$item_link = $this->build_share_url( $vendor_key, $listing_title, $listing_link );

				$item_icon = $settings[ $vendor_key . '_icon' ] ?? $defaults['icon'];
				if ( ! is_array( $item_icon ) ) {
					$item_icon = $defaults['icon'];
				}

				ob_start();
				\Elementor\Icons_Manager::render_icon(
					$item_icon,
					[ 'aria-hidden' => 'true' ]
				);
				$item_icon_html = (string) ob_get_clean();

			$link_attrs = 0 === strpos( $item_link, 'mailto:' ) ? '' : ' target="_blank" rel="noopener noreferrer"';

			$social_links_markup .= sprintf(
				'<li class="directorist-social-links__item directorist-social-links__item--%1$s"><a href="%2$s"%3$s>%4$s<span class="directorist-social-links__label">%5$s</span></a></li>',
				esc_attr( $vendor_key ),
				esc_url( $item_link ),
				$link_attrs,
				$item_icon_html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $item_label )
			);
		}

		if ( '' === $social_links_markup ) {
			return;
		}

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon SVG output.
		echo '<div class="directorist-elementor-single-action directorist-elementor-single-action--share">';
		printf(
			'<div class="directorist-social-share directorist-gbi-share directorist-gbi-share-view-%1$s"><button type="button" class="%2$s" aria-label="%3$s">%4$s</button><ul class="directorist-social-share-links">%5$s</ul></div>',
			esc_attr( $view_type ),
			esc_attr( 'directorist-single-listing-action directorist-btn directorist-btn-sm directorist-btn-light directorist-gbi-share-trigger' ),
			esc_attr( $label_text ),
			$button_content,
			$social_links_markup
		);
		echo '</div>';
		// phpcs:enable
	}

	/**
	 * Build a share URL for a vendor.
	 *
	 * @param string $vendor Vendor key.
	 * @param string $title  Listing title.
	 * @param string $url    Listing URL.
	 * @return string
	 */
	protected function build_share_url( string $vendor, string $title, string $url ): string {
		$t = rawurlencode( $title );
		$u = rawurlencode( $url );

		switch ( $vendor ) {
			case 'facebook':
				return 'https://www.facebook.com/share.php?u=' . $u . '&title=' . $t;
			case 'x':
				return 'https://x.com/intent/tweet?text=' . $t . '&url=' . $u;
			case 'linkedin':
				return 'https://www.linkedin.com/shareArticle?mini=true&url=' . $u . '&title=' . $t;
			case 'whatsapp':
				return 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url );
			case 'pinterest':
				return 'https://pinterest.com/pin/create/button/?url=' . $u . '&description=' . $t;
			case 'reddit':
				return 'https://www.reddit.com/submit?url=' . $u . '&title=' . $t;
			case 'email':
				return 'mailto:?subject=' . $t . '&body=' . $u;
			default:
				return $url;
		}
	}
}
