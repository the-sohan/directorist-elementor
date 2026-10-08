<?php
/**
 * Listing card video widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use Directorist\Helper;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractMediaFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

class ListingCardVideoWidget extends AbstractMediaFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_video';
	}

	public function get_title(): string {
		return __( 'Listing Video', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-video-camera';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'video', 'youtube', 'vimeo', 'embed' ] );
	}

	protected function register_widget_controls(): void {
		$directory_type_id = $this->resolve_document_directory_type_id();

		$this->start_controls_section(
			'section_video_content',
			[
				'label' => __( 'Video', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'preview_listing_id',
			[
				'label'       => __( 'Preview Listing', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_listing_options( 100, $directory_type_id ),
				'description' => __( 'Editor-only preview listing for single listing templates.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'video_aspect_ratio',
			[
				'label'       => __( 'Aspect Ratio', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '16/9',
				'placeholder' => '16/9',
				'description' => __( 'Use a CSS aspect ratio such as 16/9, 4/3, or 1/1.', 'directorist-elementor' ),
				'selectors'   => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video__media' => 'aspect-ratio: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'listing_video_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [
						'min' => 120,
						'max' => 900,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video__media' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->register_container_style_controls();
		$this->register_frame_style_controls();
	}

	protected function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_video_container_style',
			[
				'label' => __( 'Video Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video',
			]
		);

		$this->add_responsive_control(
			'container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video',
			]
		);

		$this->add_control(
			'container_opacity',
			[
				'label'     => __( 'Opacity', 'directorist-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_translate_x',
			[
				'label'      => __( 'Translate X', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => '--direl-video-translate-x: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'container_translate_y',
			[
				'label'      => __( 'Translate Y', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => -200,
						'max' => 200,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video' => '--direl-video-translate-y: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_frame_style_controls(): void {
		$this->start_controls_section(
			'section_video_frame_style',
			[
				'label' => __( 'Video Frame', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'frame_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video__media',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'frame_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video__media',
			]
		);

		$this->add_responsive_control(
			'frame_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video__media' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'frame_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video__media' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'frame_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-video__media',
			]
		);

		$this->add_control(
			'frame_opacity',
			[
				'label'     => __( 'Opacity', 'directorist-elementor' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-video__media' => 'opacity: {{SIZE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		if ( $this->maybe_render_search_field() ) {
			return;
		}

		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template to render the listing video.', 'directorist-elementor' )
			);
			return;
		}

		$video_url = DirectoristBridge::get_instance()->get_listing_video_url( $listing_id );

		if ( '' === $video_url ) {
			$this->render_preset_context_placeholder(
				__( 'The selected listing does not have a video URL.', 'directorist-elementor' )
			);
			return;
		}

		$embed_url = class_exists( Helper::class ) ? Helper::parse_video( $video_url ) : '';
		$embed_url = is_string( $embed_url ) ? trim( $embed_url ) : '';

		if ( '' === $embed_url ) {
			return;
		}

		$settings     = $this->get_settings_for_display();
		$aspect_ratio = $this->sanitize_aspect_ratio( (string) ( $settings['video_aspect_ratio'] ?? $settings['aspect_ratio'] ?? '16/9' ) );
		$style        = '--direl-video-aspect-ratio:' . $aspect_ratio . ';';

		echo '<div class="directorist-elementor-listing-card-video">';
		echo '<div class="directorist-elementor-listing-card-video__media" style="' . esc_attr( $style ) . '">';

		if ( $this->is_editor_context() ) {
			$this->render_editor_preview( $video_url );
		} else {
			printf(
				'<iframe class="directorist-elementor-listing-card-video__iframe directorist-embaded-video embed-responsive-item" src="%1$s" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen title="%2$s"></iframe>',
				esc_url( $embed_url ),
				esc_attr__( 'Listing Video', 'directorist-elementor' )
			);
		}

		echo '</div>';
		echo '</div>';
	}

	protected function resolve_listing_id(): int {
		$settings           = $this->get_settings_for_display();
		$preview_listing_id = absint( $settings['preview_listing_id'] ?? 0 );

		if ( $preview_listing_id > 0 && $this->is_editor_context() && empty( $this->get_loop_context() ) ) {
			return $this->is_listing_in_document_directory_type( $preview_listing_id ) ? $preview_listing_id : 0;
		}

		return $this->get_current_listing_id();
	}

	protected function render_editor_preview( string $video_url ): void {
		$youtube_video_id = $this->get_youtube_video_id( $video_url );
		$thumbnail_url    = '' !== $youtube_video_id ? 'https://i.ytimg.com/vi/' . rawurlencode( $youtube_video_id ) . '/hqdefault.jpg' : '';

		printf(
			'<a class="directorist-elementor-listing-card-video__preview" href="%1$s" target="_blank" rel="noopener noreferrer" aria-label="%2$s">',
			esc_url( $video_url ),
			esc_attr__( 'Open listing video', 'directorist-elementor' )
		);

		if ( '' !== $thumbnail_url ) {
			printf(
				'<img class="directorist-elementor-listing-card-video__preview-image" src="%1$s" alt="" loading="lazy" />',
				esc_url( $thumbnail_url )
			);
		}

		echo '<span class="directorist-elementor-listing-card-video__preview-overlay" aria-hidden="true"><span class="directorist-elementor-listing-card-video__preview-play"></span></span>';
		echo '</a>';
	}

	protected function sanitize_aspect_ratio( string $value ): string {
		$value = strtolower( trim( wp_strip_all_tags( $value ) ) );

		if ( '' === $value ) {
			return '16 / 9';
		}

		if ( preg_match( '/^\d+(?:\.\d+)?\s*\/\s*\d+(?:\.\d+)?$/', $value ) ) {
			return (string) preg_replace( '/\s*\/\s*/', ' / ', $value );
		}

		if ( preg_match( '/^\d+(?:\.\d+)?$/', $value ) ) {
			return $value;
		}

		return '16 / 9';
	}

	protected function get_youtube_video_id( string $url ): string {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return '';
		}

		$host = strtolower( (string) $parts['host'] );
		$path = isset( $parts['path'] ) ? trim( (string) $parts['path'], '/' ) : '';

		if ( false !== strpos( $host, 'youtu.be' ) ) {
			$segments = explode( '/', $path );
			return preg_match( '/^[a-zA-Z0-9_-]{11}$/', $segments[0] ?? '' ) ? $segments[0] : '';
		}

		if ( false === strpos( $host, 'youtube.com' ) ) {
			return '';
		}

		if ( ! empty( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query_args );
			$video_id = isset( $query_args['v'] ) ? (string) $query_args['v'] : '';
			if ( preg_match( '/^[a-zA-Z0-9_-]{11}$/', $video_id ) ) {
				return $video_id;
			}
		}

		$segments = explode( '/', $path );
		foreach ( [ 'embed', 'shorts' ] as $prefix ) {
			$prefix_index = array_search( $prefix, $segments, true );
			if ( false !== $prefix_index && ! empty( $segments[ $prefix_index + 1 ] ) ) {
				$video_id = (string) $segments[ $prefix_index + 1 ];
				return preg_match( '/^[a-zA-Z0-9_-]{11}$/', $video_id ) ? $video_id : '';
			}
		}

		return '';
	}
}
