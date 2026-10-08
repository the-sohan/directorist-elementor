<?php
/**
 * Directorist rule-based custom badge widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractBadgeFieldWidget;
use Elementor\Controls_Manager;

class ListingCardBadgeCustomWidget extends AbstractBadgeFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_badge_custom';
	}

	public function get_title(): string {
		return __( 'Custom Badge', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-badge';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'badge', 'custom', 'rules' ] );
	}

	public function show_in_panel(): bool {
		return ! empty( DirectoristBridge::get_instance()->get_custom_badge_definitions() );
	}

	protected function register_widget_controls(): void {
		$bridge        = DirectoristBridge::get_instance();
		$badge_options = [ '' => __( 'Select a custom badge', 'directorist-elementor' ) ];

		foreach ( $bridge->get_custom_badge_definitions() as $badge_key => $definition ) {
			$label = trim( (string) ( $definition['internalName'] ?? $definition['label'] ?? $badge_key ) );
			if ( array_key_exists( 'enabled', $definition ) && empty( $definition['enabled'] ) ) {
				$label .= ' ' . __( '(Disabled)', 'directorist-elementor' );
			}
			$badge_options[ sanitize_key( (string) $badge_key ) ] = $label;
		}

		$this->start_controls_section(
			'section_custom_badge_content',
			[
				'label' => __( 'Custom Badge', 'directorist-elementor' ),
			]
		);

		if ( $this->is_single_listing_template_context() ) {
			$this->add_control(
				'preview_listing_id',
				[
					'label'       => __( 'Preview Listing', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => '',
					'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + $bridge->get_recent_listing_options( 100, $this->resolve_document_directory_type_id() ),
					'description' => __( 'Editor preview only. Frontend single listing pages use the current listing.', 'directorist-elementor' ),
				]
			);
		}

		$this->add_control(
			'badge_key',
			[
				'label'       => __( 'Badge', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => $badge_options,
			]
		);

		$this->add_control(
			'badge_display_type',
			[
				'label'   => __( 'Display Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'inherit',
				'options' => [
					'inherit' => __( 'Inherit', 'directorist-elementor' ),
					'text'    => __( 'Text', 'directorist-elementor' ),
					'icon'    => __( 'Icon Only', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'badge_text',
			[
				'label'       => __( 'Label Override', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'condition'   => [
					'badge_display_type!' => 'icon',
				],
			]
		);

		$this->add_control(
			'badge_tooltip',
			[
				'label'       => __( 'Tooltip Override', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'label_block' => true,
				'condition'   => [
					'badge_display_type!' => 'text',
				],
			]
		);

		$this->add_control(
			'badge_show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'badge_content_direction',
			[
				'label'   => __( 'Content Direction', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'row',
				'options' => [
					'row'    => __( 'Row', 'directorist-elementor' ),
					'column' => __( 'Column', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'badge_icon',
			[
				'label'       => __( 'Icon Override', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'badge_show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'badge_icon_position',
			[
				'label'     => __( 'Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'left',
				'options'   => [
					'left'  => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'badge_show_icon'         => 'yes',
					'badge_display_type!'     => 'icon',
					'badge_content_direction' => 'row',
				],
			]
		);

		$this->end_controls_section();

		$this->register_badge_style_controls(
			'section_custom_badge_style',
			__( 'Badge', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-custom__badge',
			true
		);
		$this->register_badge_icon_style_controls(
			'section_custom_badge_icon_style',
			__( 'Icon', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-custom__badge'
		);
		$this->register_badge_text_style_controls(
			'section_custom_badge_text_style',
			__( 'Text', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge__text'
		);
		$this->register_badge_tooltip_style_controls(
			'section_custom_badge_tooltip_style',
			__( 'Tooltip', 'directorist-elementor' ),
			'.directorist-elementor-listing-card-badge-custom__tooltip'
		);
	}

	protected function render(): void {
		$settings  = $this->get_settings_for_display();
		$badge_key = sanitize_key( (string) ( $settings['badge_key'] ?? '' ) );
		$bridge    = DirectoristBridge::get_instance();

		if ( '' === $badge_key ) {
			$this->render_editor_state( __( 'Select a custom badge from the widget controls.', 'directorist-elementor' ) );
			return;
		}

		$definition = $bridge->get_custom_badge_definition( $badge_key );
		if ( empty( $definition ) ) {
			$message = method_exists( '\\Directorist\\Helper', 'custom_badge_definitions' )
				? __( 'The selected custom badge is unavailable. Select another badge or restore it in Directorist Badge Manager.', 'directorist-elementor' )
				: __( 'Custom badges require Directorist 8.9 or later.', 'directorist-elementor' );
			$this->render_editor_state( $message );
			return;
		}

		$listing_id = $this->resolve_listing_id();
		if ( $listing_id <= 0 ) {
			$this->render_editor_state( __( 'Place this widget inside a Directorist listing card or single listing template.', 'directorist-elementor' ) );
			return;
		}

		if ( ! $bridge->is_listing_badge_visible( $listing_id, $badge_key ) ) {
			$this->render_editor_state( __( 'This custom badge does not match the current preview listing.', 'directorist-elementor' ) );
			return;
		}

		$display_type = sanitize_key( (string) ( $settings['badge_display_type'] ?? 'inherit' ) );
		if ( ! in_array( $display_type, [ 'text', 'icon' ], true ) ) {
			$display_type = 'icon' === ( $definition['type'] ?? '' ) ? 'icon' : 'text';
		}
		$content_direction = sanitize_key( (string) ( $settings['badge_content_direction'] ?? 'row' ) );
		if ( ! in_array( $content_direction, [ 'row', 'column' ], true ) ) {
			$content_direction = 'row';
		}

		$definition_style = is_array( $definition['style'] ?? null ) ? (array) $definition['style'] : [];
		$definition_hover = is_array( $definition['hover'] ?? null ) ? (array) $definition['hover'] : [];
		$label            = '' !== trim( (string) ( $settings['badge_text'] ?? '' ) )
			? (string) $settings['badge_text']
			: (string) ( $definition['label'] ?? '' );
		$tooltip          = '' !== trim( (string) ( $settings['badge_tooltip'] ?? '' ) )
			? (string) $settings['badge_tooltip']
			: (string) ( $definition_hover['text'] ?? $label );
		if ( '' === trim( $tooltip ) ) {
			$tooltip = $label;
		}

		$icon_markup = '';
		if ( 'yes' === ( $settings['badge_show_icon'] ?? 'yes' ) ) {
			$local_icon = is_array( $settings['badge_icon'] ?? null ) ? (array) $settings['badge_icon'] : [];
			$icon_markup = ! empty( $local_icon['value'] )
				? $this->get_badge_icon_markup( $local_icon, [] )
				: $bridge->get_badge_definition_icon_markup( $definition );
		}

		if ( 'icon' === $display_type && '' === trim( $icon_markup ) ) {
			$display_type = 'text';
		}

		$background_color = sanitize_hex_color( (string) ( $definition_style['bg'] ?? '' ) ) ?: '#3e62f5';
		$text_color       = sanitize_hex_color( (string) ( $definition_style['text'] ?? '' ) ) ?: '#ffffff';
		$border_color     = sanitize_hex_color( (string) ( $definition_style['border'] ?? '' ) ) ?: $background_color;
		$tooltip_bg       = sanitize_hex_color( (string) ( $definition_hover['bg'] ?? '' ) ) ?: $background_color;
		$tooltip_color    = sanitize_hex_color( (string) ( $definition_hover['textColor'] ?? '' ) ) ?: $text_color;
		$badge_classes = [
			'directorist-elementor-listing-card-badge-custom__badge',
			'directorist-elementor-listing-card-badge-custom__badge--' . $content_direction,
		];

		if ( 'row' === $content_direction && 'right' === ( $settings['badge_icon_position'] ?? 'left' ) && 'text' === $display_type ) {
			$badge_classes[] = 'directorist-elementor-listing-card-badge-custom__badge--icon-right';
		}

		$style = sprintf(
			'--direl-custom-badge-bg:%1$s;--direl-custom-badge-color:%2$s;--direl-custom-badge-border:%3$s;--direl-custom-badge-tooltip-bg:%4$s;--direl-custom-badge-tooltip-color:%5$s;',
			$background_color,
			$text_color,
			$border_color,
			$tooltip_bg,
			$tooltip_color
		);

		$badge_attributes = [
			'class'                      => implode( ' ', $badge_classes ),
			'style'                      => $style,
			'data-directorist-badge-key' => $badge_key,
		];
		if ( 'icon' === $display_type && '' !== trim( $tooltip ) ) {
			$badge_attributes['tabindex']   = '0';
			$badge_attributes['role']       = 'img';
			$badge_attributes['aria-label'] = $tooltip;
		}

		echo '<div class="directorist-elementor-listing-card-badge directorist-elementor-listing-card-badge-custom"><span ' . $this->format_html_attributes( $badge_attributes ) . '>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes are escaped by format_html_attributes().

		if ( '' !== trim( $icon_markup ) ) {
			echo '<span class="directorist-elementor-listing-card-badge__icon" aria-hidden="true">' . $icon_markup . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted markup from Elementor Icons_Manager or Directorist's badge icon renderer.
		}

		if ( 'text' === $display_type && '' !== trim( $label ) ) {
			echo '<span class="directorist-elementor-listing-card-badge__text">' . esc_html( $label ) . '</span>';
		}

		if ( 'icon' === $display_type && '' !== trim( $tooltip ) ) {
			echo '<span class="directorist-elementor-listing-card-badge-custom__tooltip" aria-hidden="true">' . esc_html( $tooltip ) . '</span>';
		}

		echo '</span></div>';
	}

	protected function resolve_listing_id(): int {
		if ( $this->is_editor_context() && empty( $this->get_loop_context() ) && $this->is_single_listing_template_context() ) {
			$settings           = $this->get_settings_for_display();
			$preview_listing_id = absint( $settings['preview_listing_id'] ?? 0 );

			if ( $preview_listing_id > 0 ) {
				return $this->is_listing_in_document_directory_type( $preview_listing_id ) ? $preview_listing_id : 0;
			}
		}

		return $this->get_current_listing_id();
	}

	protected function render_editor_state( string $message ): void {
		if ( ! $this->is_editor_context() ) {
			return;
		}

		echo wp_kses_post( $this->render_placeholder( $this->get_title(), $message ) );
	}
}
