<?php
/**
 * Single listing tag widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractIconTextFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class SingleListingTagWidget extends AbstractIconTextFieldWidget {

	public function get_name(): string {
		return 'directorist_single_listing_tag';
	}

	public function get_title(): string {
		return __( 'Tag', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-tags';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'tag', 'tags', 'taxonomy' ] );
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_tag_content',
			[
				'label' => __( 'Tag', 'directorist-elementor' ),
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
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-tag',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_tag_style',
			[
				'label' => __( 'Tag', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'field_align',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'field_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a i' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a svg' => 'fill: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a .directorist-icon-mask' => 'background-color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
				'condition' => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a i' => 'font-size: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a .directorist-icon-mask' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a .directorist-icon-mask::after' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'value_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'value_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'field_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-tag .directorist-single-tag-list a',
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Tag', 'directorist-elementor' ),
						__( 'Place this widget inside Listing Card Template or a Directorist single listing template to render listing tags.', 'directorist-elementor' )
					)
				);
			}
			return;
		}

		$settings = $this->get_settings_for_display();
		$bridge   = DirectoristBridge::get_instance();

		$bridge->ensure_single_listing_assets( 'single/fields/tag' );

		$output = $bridge->render_single_listing_field(
			$listing_id,
			[
				'widget_group' => 'preset',
				'widget_name'  => 'tag',
				'icon'         => $this->get_core_icon_value( $settings ),
			]
		);

		if ( '' === $output ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Tag', 'directorist-elementor' ),
						__( 'Tags are unavailable for the current preview listing.', 'directorist-elementor' )
					)
				);
			}
			return;
		}

		echo '<div class="directorist-elementor-listing-card-tag directorist-elementor-single-listing-tag">';
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Directorist template output.
		echo '</div>';
	}

	/**
	 * Resolve a Directorist core icon value from Elementor icon settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function get_core_icon_value( array $settings ): string {
		if ( 'yes' !== ( $settings['show_icon'] ?? 'yes' ) ) {
			return '';
		}

		$field_icon = $settings['field_icon'] ?? [];
		if ( is_array( $field_icon ) && ! empty( $field_icon['value'] ) && is_string( $field_icon['value'] ) ) {
			return sanitize_text_field( $field_icon['value'] );
		}

		return 'fas fa-tag';
	}

	/**
	 * Resolve listing id from runtime context.
	 *
	 * Keep the same single-template fallback behavior as single widgets while
	 * still using the preset-field control model.
	 *
	 * @return int
	 */
	protected function resolve_listing_id(): int {
		$context_listing_id = $this->get_current_listing_id();

		if ( $context_listing_id > 0 ) {
			return $context_listing_id;
		}

		$document_preview_listing_id = $this->resolve_single_listing_fallback_id();

		if ( $document_preview_listing_id > 0 ) {
			return $document_preview_listing_id;
		}

		if ( $this->is_editor_context() ) {
			return $this->resolve_editor_fallback_listing_id();
		}

		return 0;
	}

	/**
	 * Resolve an editor fallback listing id, scoped to a directory-specific
	 * single template when applicable.
	 *
	 * @return int
	 */
	protected function resolve_editor_fallback_listing_id(): int {
		$post_type = DirectoristBridge::get_instance()->get_listing_post_type();

		$query_args = [
			'post_type'           => $post_type,
			'post_status'         => 'publish',
			'posts_per_page'      => 1,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'fields'              => 'ids',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		];

		$directory_type_id = $this->resolve_document_directory_type_id();

		if ( $directory_type_id > 0 ) {
			$query_args['meta_query'] = [
				[
					'key'     => '_directory_type',
					'value'   => (string) $directory_type_id,
					'compare' => '=',
				],
			];
		}

		$fallback_query = new \WP_Query( $query_args );
		$fallback_id    = ! empty( $fallback_query->posts ) ? (int) $fallback_query->posts[0] : 0;
		wp_reset_postdata();

		if ( $fallback_id <= 0 && $directory_type_id > 0 ) {
			unset( $query_args['meta_query'] );
			$retry_query = new \WP_Query( $query_args );
			$fallback_id = ! empty( $retry_query->posts ) ? (int) $retry_query->posts[0] : 0;
			wp_reset_postdata();
		}

		return $fallback_id;
	}

	/**
	 * Resolve the current directory-specific single document id from Elementor.
	 *
	 * @return int
	 */
	protected function resolve_document_directory_type_id(): int {
		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return 0;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();
		if ( ! $current_document || ! method_exists( $current_document, 'get_name' ) ) {
			return 0;
		}

		$doc_type = $current_document->get_name();
		$prefix   = 'directorist-single-listing-directory-';

		if ( 0 !== strpos( $doc_type, $prefix ) ) {
			return 0;
		}

		return absint( substr( $doc_type, strlen( $prefix ) ) );
	}
}
