<?php
/**
 * Listing description preset widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractPresetFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;

class ListingDescriptionWidget extends AbstractPresetFieldWidget {

	public function get_name(): string {
		return 'directorist_single_listing_description';
	}

	public function get_title(): string {
		return __( 'Description', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-text-area';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'description', 'content', 'details' ] );
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_description_style',
			[
				'label' => __( 'Description', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'description_align',
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
					'{{WRAPPER}} .directorist-listing-details__text' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'description_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-details__text, {{WRAPPER}} .directorist-listing-details__text p, {{WRAPPER}} .directorist-listing-details__text li' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'description_heading_color',
			[
				'label'     => __( 'Heading Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-details__text h1, {{WRAPPER}} .directorist-listing-details__text h2, {{WRAPPER}} .directorist-listing-details__text h3, {{WRAPPER}} .directorist-listing-details__text h4, {{WRAPPER}} .directorist-listing-details__text h5, {{WRAPPER}} .directorist-listing-details__text h6' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .directorist-listing-details__text',
			]
		);

		$this->start_controls_tabs( 'tabs_description_links' );

		$this->start_controls_tab(
			'tab_description_links_normal',
			[
				'label' => __( 'Link', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'description_link_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-details__text a' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_description_links_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'description_link_hover_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-listing-details__text a:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-listing-details__text a:focus' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->end_controls_section();
	}

	protected function render(): void {
		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist single listing template to render the standard listing description.', 'directorist-elementor' )
			);
			return;
		}

		$bridge = DirectoristBridge::get_instance();

		if ( ! $bridge->is_preset_widget_allowed( $listing_id, 'description' ) ) {
			return;
		}

		$output = $bridge->render_single_listing_field(
			$listing_id,
			[
				'widget_group' => 'preset_widgets',
				'widget_name'  => 'description',
			]
		);

		if ( '' === trim( $output ) ) {
			if ( $this->is_editor_context() ) {
				$this->render_preset_context_placeholder(
					__( 'Description content is unavailable for the current preview listing.', 'directorist-elementor' )
				);
			}
			return;
		}

		echo '<div class="directorist-elementor-single-listing-description">';
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * Resolve listing id from runtime context with single-template fallback.
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
	 * Resolve a fallback listing ID for editor previews.
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
