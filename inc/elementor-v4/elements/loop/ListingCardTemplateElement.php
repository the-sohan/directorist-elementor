<?php
/**
 * Listing card template element.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Elements\Loop;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Composition\StructureDefaults;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\ElementTreeStyleService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\ElementorV4\Render\MapCardTemplateContext;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Includes\Elements\Container;

class ListingCardTemplateElement extends Container {

	/**
	 * Get element type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist_listing_card_template';
	}

	/**
	 * Get element slug.
	 *
	 * @return string
	 */
	public function get_name() {
		return static::get_type();
	}

	/**
	 * Get element title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Listing Card Template', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-container-grid';
	}

	/**
	 * Get keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return [ 'directorist', 'listing', 'card', 'template' ];
	}

	/**
	 * Get default children elements.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_children_elements(): array {
		return StructureDefaults::get_instance()->get_default_card_field_elements();
	}

	/**
	 * Initial config for improved nested repeaters.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_initial_config(): array {
		$config           = parent::get_initial_config();
		$default_children = $this->get_default_children_elements();

		$config['show_in_panel'] = true;
		$config['categories'] = [ CategoryRegistrar::CATEGORY_ARCHIVE ];
		$config['title'] = $this->get_title();
		$config['icon'] = $this->get_icon();
		$config['include_in_widgets_config'] = true;
		$config['default_children'] = $default_children;
		$config['directorist_default_template_elements'] = $default_children;
		$config['defaults'] = array_merge(
			is_array( $config['defaults'] ?? null ) ? $config['defaults'] : [],
			[
				'elements' => $default_children,
			]
		);

		return $config;
	}

	/**
	 * Prevent inherited Container presets from leaking into the panel.
	 *
	 * @return array<string,mixed>
	 */
	public function get_panel_presets() {
		return [];
	}

	/**
	 * Keep Elementor's element cache from freezing card output.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Align the element with optimized markup behavior.
	 *
	 * @return bool
	 */
	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_template_status',
			[
				'label' => __( 'Status', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'template_status',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'This widget renders the active directory and view card from Listings Loop. Add field and layout widgets here and Directorist keeps them isolated per directory and view.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$this->add_control(
			'active_template_key',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->add_control(
			'scoped_templates',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '{}',
			]
		);

		$this->end_controls_section();

		$this->register_card_wrapper_style_controls();
		$this->register_directorist_advanced_controls();
	}

	/**
	 * Register card wrapper style controls for the active template scope.
	 *
	 * @return void
	 */
	protected function register_card_wrapper_style_controls(): void {
		$this->start_controls_section(
			'section_template_style_container',
			[
				'label' => __( 'Card Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'template_container_background_background',
			[
				'label'   => __( 'Background Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [
					''         => __( 'Default', 'directorist-elementor' ),
					'classic'  => __( 'Classic', 'directorist-elementor' ),
					'gradient' => __( 'Gradient', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'template_container_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [
					'template_container_background_background' => [ 'classic', 'gradient' ],
				],
			]
		);

		$this->add_control(
			'template_container_background_color_b',
			[
				'label'     => __( 'Second Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'condition' => [
					'template_container_background_background' => 'gradient',
				],
			]
		);

		$this->add_control(
			'template_container_background_gradient_type',
			[
				'label'     => __( 'Gradient Type', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'linear',
				'options'   => [
					'linear' => __( 'Linear', 'directorist-elementor' ),
					'radial' => __( 'Radial', 'directorist-elementor' ),
				],
				'condition' => [
					'template_container_background_background' => 'gradient',
				],
			]
		);

		$this->add_control(
			'template_container_background_gradient_angle',
			[
				'label'      => __( 'Angle', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'deg' ],
				'default'    => [
					'unit' => 'deg',
					'size' => 180,
				],
				'range'      => [
					'deg' => [
						'min' => 0,
						'max' => 360,
					],
				],
				'condition'  => [
					'template_container_background_background' => 'gradient',
					'template_container_background_gradient_type' => 'linear',
				],
			]
		);

		$this->add_control(
			'template_container_background_gradient_position',
			[
				'label'     => __( 'Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => [
					'center center' => __( 'Center Center', 'directorist-elementor' ),
					'center left'   => __( 'Center Left', 'directorist-elementor' ),
					'center right'  => __( 'Center Right', 'directorist-elementor' ),
					'top center'    => __( 'Top Center', 'directorist-elementor' ),
					'top left'      => __( 'Top Left', 'directorist-elementor' ),
					'top right'     => __( 'Top Right', 'directorist-elementor' ),
					'bottom center' => __( 'Bottom Center', 'directorist-elementor' ),
					'bottom left'   => __( 'Bottom Left', 'directorist-elementor' ),
					'bottom right'  => __( 'Bottom Right', 'directorist-elementor' ),
				],
				'condition' => [
					'template_container_background_background' => 'gradient',
					'template_container_background_gradient_type' => 'radial',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'template_container_border',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'template_container_box_shadow',
			]
		);

		$this->add_responsive_control(
			'template_container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
			]
		);

		$this->add_responsive_control(
			'template_container_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
			]
		);

		$this->add_responsive_control(
			'template_container_min_height',
			[
				'label'      => __( 'Min Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%', 'vh' ],
				'range'      => [
					'px'  => [
						'min' => 0,
						'max' => 1000,
					],
					'em'  => [
						'min' => 0,
						'max' => 80,
					],
					'rem' => [
						'min' => 0,
						'max' => 80,
					],
					'%'   => [
						'min' => 0,
						'max' => 100,
					],
					'vh'  => [
						'min' => 0,
						'max' => 100,
					],
				],
			]
		);

		$this->add_control(
			'template_container_overflow',
			[
				'label'     => __( 'Overflow', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => [
					''        => __( 'Default', 'directorist-elementor' ),
					'visible' => __( 'Visible', 'directorist-elementor' ),
					'hidden'  => __( 'Hidden', 'directorist-elementor' ),
					'clip'    => __( 'Clip', 'directorist-elementor' ),
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register safe wrapper controls without inheriting Container layout/design controls.
	 *
	 * @return void
	 */
	protected function register_directorist_advanced_controls(): void {
		$this->start_controls_section(
			'section_directorist_advanced',
			[
				'label' => __( 'Advanced', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'_element_id',
			[
				'label'          => __( 'CSS ID', 'elementor' ),
				'type'           => Controls_Manager::TEXT,
				'default'        => '',
				'title'          => __( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'elementor' ),
				'style_transfer' => false,
				'classes'        => 'elementor-control-direction-ltr',
				'dynamic'        => [
					'active' => true,
				],
				'ai'             => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'_css_classes',
			[
				'label'        => __( 'CSS Classes', 'elementor' ),
				'type'         => Controls_Manager::TEXT,
				'title'        => __( 'Add your custom class WITHOUT the dot. e.g: my-class', 'elementor' ),
				'classes'      => 'elementor-control-direction-ltr',
				'prefix_class' => '',
				'dynamic'      => [
					'active' => true,
				],
				'ai'           => [
					'active' => false,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'_section_responsive',
			[
				'label' => __( 'Responsive', 'elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'responsive_description',
			[
				'raw'             => sprintf(
					/* translators: 1: Link open tag, 2: Link close tag. */
					__( 'Responsive visibility will take effect only on %1$s preview mode %2$s or live page, and not while editing in Elementor.', 'elementor' ),
					'<a href="javascript: $e.run( \'panel/close\' )">',
					'</a>'
				),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_hidden_device_controls();

		$this->end_controls_section();
	}

	/**
	 * Print element content.
	 *
	 * @return void
	 */
	protected function print_content() {
		$this->print_card_template_content();
	}

	/**
	 * Compatibility renderer for direct render_content() calls.
	 *
	 * @return void
	 */
	protected function render() {
		$this->print_card_template_content();
	}

	/**
	 * Render the scoped listing card template content.
	 *
	 * @return void
	 */
	protected function print_card_template_content(): void {
		$settings        = $this->get_settings_for_display();
		$children        = array_values( $this->get_children() );
		$loop_context    = RenderContext::get_instance()->current_loop_context();
		$listing_context = RenderContext::get_instance()->current_listing_context();
		$active_view     = sanitize_key( (string) ( $loop_context['active_view'] ?? EditorContext::get_instance()->resolve_preview_view_type( $settings ) ) );
		$active_dir      = absint( $listing_context['directory_type_id'] ?? $loop_context['active_directory'] ?? EditorContext::get_instance()->resolve_preview_directory_type_id( $settings ) );
		$scope_key       = $this->build_template_key( $active_dir, $active_view );
		$instance_id     = InstanceState::get_instance()->normalize_instance_id( 'direl-card-template-' . $this->get_id() );
		$attributes      = InstanceState::get_instance()->build_widget_root_attributes(
			$instance_id,
			'listing-card-template',
			[
				'class'               => [
					'directorist-elementor-card-template',
					$this->is_editor_context() ? 'directorist-elementor-card-template--editor' : '',
					'directorist-elementor-card-template--' . sanitize_html_class( $active_view ),
				],
				'data-direl-template'  => $scope_key,
				'data-direl-view'      => $active_view,
				'data-direl-directory' => (string) $active_dir,
			]
		);

		echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
		$this->render_scoped_element_styles( $settings, $loop_context, $active_dir, $active_view );

		if ( $this->is_editor_context() && ! empty( $loop_context ) ) {
			$this->render_editor_results_preview( $children, $settings, $loop_context );
		} elseif ( $this->is_editor_context() ) {
			$this->render_editor_composition( $children, $settings, $active_dir, $active_view );
		} elseif ( ! empty( $listing_context ) ) {
			$this->render_single_listing_card( $children, $settings, $active_dir, $active_view );
		} elseif ( ! empty( $loop_context ) ) {
			$this->render_loop_cards( $settings, $loop_context );
		} else {
			$this->render_single_listing_card( $children, $settings, $active_dir, $active_view );
		}

		echo '</div>';
	}

	/**
	 * Render Elementor CSS for the active raw card-template branches.
	 *
	 * Scoped card children live outside Elementor's projected document tree, so
	 * their native responsive and global styles must travel with AJAX previews.
	 *
	 * @param array<string,mixed> $settings Card-template settings.
	 * @param array<string,mixed> $loop_context Active loop context.
	 * @param int                 $active_directory_id Active directory type id.
	 * @param string              $active_view Active view type.
	 * @return void
	 */
	protected function render_scoped_element_styles( array $settings, array $loop_context, int $active_directory_id, string $active_view ): void {
		$directory_ids = $active_directory_id > 0 ? [ $active_directory_id ] : [];

		foreach ( (array) ( $loop_context['listing_ids'] ?? [] ) as $listing_id ) {
			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( absint( $listing_id ) );

			if ( $directory_type_id > 0 ) {
				$directory_ids[] = $directory_type_id;
			}
		}

		$directory_ids = array_values( array_unique( array_filter( array_map( 'absint', $directory_ids ) ) ) );
		if ( empty( $directory_ids ) ) {
			return;
		}

		$views        = [ sanitize_key( $active_view ?: 'grid' ) ];
		$display_mode = sanitize_key( (string) ( $loop_context['display_mode'] ?? 'default' ) );
		$map_settings = is_array( $loop_context['map_list_settings'] ?? null ) ? (array) $loop_context['map_list_settings'] : [];

		if ( 'map_list' === $display_mode && ! empty( $map_settings['show_map_card'] ) ) {
			$views[] = 'map';
		}

		$elements         = [];
		$loaded_keys      = [];
		$scoped_templates = $this->get_scoped_templates( $settings );

		foreach ( $directory_ids as $directory_type_id ) {
			foreach ( array_values( array_unique( $views ) ) as $view_type ) {
				$scope_key = $this->build_template_key( $directory_type_id, $view_type );

				if ( isset( $loaded_keys[ $scope_key ] ) ) {
					continue;
				}

				$loaded_keys[ $scope_key ] = true;
				$template                  = $scoped_templates[ sanitize_key( $scope_key ) ] ?? null;

				foreach ( (array) ( $template['elements'] ?? [] ) as $element ) {
					if ( is_array( $element ) ) {
						$elements[] = $element;
					}
				}
			}
		}

		if ( empty( $elements ) ) {
			return;
		}

		echo ElementTreeStyleService::get_instance()->render_style_tag( $elements, 0, 'card-' . $this->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor's native CSS compiler produces scoped declarations.
	}

	/**
	 * Render editor preview cards for the active loop scope.
	 *
	 * @param array<int,mixed>    $children Child elements.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $loop_context Active loop context.
	 * @return void
	 */
	protected function render_editor_results_preview( array $children, array $settings, array $loop_context ): void {
		$active_view = sanitize_key( (string) ( $loop_context['active_view'] ?? 'grid' ) );
		$active_dir  = absint( $loop_context['active_directory'] ?? EditorContext::get_instance()->resolve_preview_directory_type_id( $settings ) );

		printf(
			'<div class="directorist-elementor-card-template__state"><p class="directorist-elementor-card-template__active-view">%s</p></div>',
			esc_html( $this->get_template_display_title( $active_dir, $active_view ) )
		);

		$this->render_loop_cards( $settings, $loop_context );
	}

	/**
	 * Render the active editable card in Elementor.
	 *
	 * @param array<int,mixed>    $children Child elements.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int                 $directory_type_id Directory type id.
	 * @param string              $view_type View type.
	 * @return void
	 */
	protected function render_editor_composition( array $children, array $settings, int $directory_type_id, string $view_type ): void {
		$preview_listing_id = DirectoristBridge::get_instance()->get_default_preview_listing_id( $directory_type_id );

		printf(
			'<div class="directorist-elementor-card-template__state"><p class="directorist-elementor-card-template__notice">%1$s</p><p class="directorist-elementor-card-template__active-view">%2$s</p></div>',
			esc_html(
				__( 'This card template is scoped to the active directory and view from Listings Loop.', 'directorist-elementor' )
			),
			esc_html( $this->get_template_display_title( $directory_type_id, $view_type ) )
		);

		if ( $preview_listing_id <= 0 ) {
			$this->render_single_listing_card( $children, $settings, $directory_type_id, $view_type );

			return;
		}

		$listing_directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $preview_listing_id );

		if ( $listing_directory_type_id > 0 ) {
			$directory_type_id = $listing_directory_type_id;
		}

		RenderContext::get_instance()->push_listing_context( $preview_listing_id, 0, $directory_type_id );

		try {
			$this->render_single_listing_card( $children, $settings, $directory_type_id, $view_type );
		} finally {
			RenderContext::get_instance()->pop_listing_context();
		}
	}

	/**
	 * Render a single listing card when listing context is already set.
	 *
	 * @param array<int,mixed>    $children Child elements.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int                 $directory_type_id Directory type id.
	 * @param string              $view_type View type.
	 * @return void
	 */
	protected function render_single_listing_card( array $children, array $settings, int $directory_type_id, string $view_type, bool $apply_wrapper_style = true ): void {
		$scope_key  = $this->build_template_key( $directory_type_id, $view_type );
		$attributes = [
			'class'               => [
				'directorist-elementor-card-template__content',
			],
			'data-direl-template'  => $scope_key,
			'data-direl-view'      => $view_type,
			'data-direl-directory' => (string) $directory_type_id,
		];
		$template   = $this->get_scoped_template( $settings, $scope_key );
		$style      = $apply_wrapper_style ? $this->build_card_wrapper_style( $template['settings'] ?? [] ) : '';

		if ( '' !== $style ) {
			$attributes['class'][] = 'directorist-elementor-card-template__content--card-styled';
			$attributes['style'] = $style;
		}

		echo '<div ' . $this->format_html_attributes( $attributes ) . '>';

		if ( is_array( $template ) && $this->raw_children_have_content( $template['elements'] ?? [] ) ) {
			foreach ( (array) $template['elements'] as $child ) {
				if ( is_array( $child ) ) {
					$this->render_selected_child_raw_data( $child );
				}
			}
		} elseif ( $this->has_composition_children( $children ) ) {
			foreach ( $children as $child ) {
				$this->render_selected_child_live_element( $child );
			}
		} else {
			echo wp_kses_post( StructureDefaults::get_instance()->get_fallback_card_markup() );
		}

		echo '</div>';
	}

	/**
	 * Build inline CSS variables for the selected template settings.
	 *
	 * @param array<string,mixed>|string $design Template settings payload.
	 * @return string
	 */
	protected function build_card_wrapper_style( $design ): string {
		$design = $this->decode_card_template_settings( $design );

		if ( empty( $design ) ) {
			return '';
		}

		$styles = [];

		$this->append_css_variable( $styles, '--direl-card-template-border-color', $this->get_design_color_from_keys( $design, $this->get_border_design_keys( 'color' ) ) );
		$this->append_css_variable( $styles, '--direl-card-template-border-style', $this->get_design_keyword_from_keys( $design, $this->get_border_design_keys( 'border' ), [ 'solid', 'double', 'dotted', 'dashed', 'groove' ] ) );
		$this->append_css_variable( $styles, '--direl-card-template-overflow', $this->get_design_keyword_from_keys( $design, [ 'template_container_overflow' ], [ 'visible', 'hidden', 'clip' ] ) );
		$this->append_css_variable( $styles, '--direl-card-template-box-shadow', $this->build_design_box_shadow( $design ) );

		foreach ( [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ] as $device => $suffix ) {
			$this->append_css_variable(
				$styles,
				'--direl-card-template-bg' . ( 'desktop' === $device ? '' : '-' . $device ),
				$this->build_design_background( $design, $suffix )
			);
			$this->append_css_variable(
				$styles,
				'--direl-card-template-padding' . ( 'desktop' === $device ? '' : '-' . $device ),
				$this->build_design_dimensions( $this->get_responsive_design_value_from_keys( $design, [ 'template_container_padding' ], $suffix ) )
			);
			$this->append_css_variable(
				$styles,
				'--direl-card-template-radius' . ( 'desktop' === $device ? '' : '-' . $device ),
				$this->build_design_dimensions( $this->get_responsive_design_value_from_keys( $design, [ 'template_container_border_radius' ], $suffix ) )
			);
			$this->append_css_variable(
				$styles,
				'--direl-card-template-min-height' . ( 'desktop' === $device ? '' : '-' . $device ),
				$this->build_design_size( $this->get_responsive_design_value_from_keys( $design, [ 'template_container_min_height' ], $suffix ) )
			);
			$this->append_css_variable(
				$styles,
				'--direl-card-template-border-width' . ( 'desktop' === $device ? '' : '-' . $device ),
				$this->build_design_dimensions( $this->get_responsive_design_value_from_keys( $design, $this->get_border_design_keys( 'width' ), $suffix ) )
			);
		}

		return implode( '', $styles );
	}

	/**
	 * Build card wrapper style variables for a directory/view scope.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param int                 $directory_type_id Directory type id.
	 * @param string              $view_type View type.
	 * @return string
	 */
	protected function build_card_wrapper_style_for_scope( array $settings, int $directory_type_id, string $view_type ): string {
		$scope_key = $this->build_template_key( $directory_type_id, $view_type );
		$template  = $this->get_scoped_template( $settings, $scope_key );

		return $this->build_card_wrapper_style( $template['settings'] ?? [] );
	}

	/**
	 * Build card wrapper style variables for a listing map popup.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $view_type View type.
	 * @return string
	 */
	public function build_card_wrapper_style_for_listing( int $listing_id, string $view_type = 'map' ): string {
		$listing_id = absint( $listing_id );

		if ( $listing_id <= 0 ) {
			return '';
		}

		$settings          = $this->get_settings_for_display();
		$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );

		return $this->build_card_wrapper_style_for_scope( $settings, $directory_type_id, $view_type );
	}

	/**
	 * Decode template settings payload from scoped storage.
	 *
	 * @param mixed $value Raw settings value.
	 * @return array<string,mixed>
	 */
	protected function decode_card_template_settings( $value ): array {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return [];
		}

		$decoded = json_decode( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ), true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Append a CSS variable declaration.
	 *
	 * @param array<int,string> $styles Style declarations.
	 * @param string            $name Variable name.
	 * @param string            $value Variable value.
	 * @return void
	 */
	protected function append_css_variable( array &$styles, string $name, string $value ): void {
		if ( '' === $value ) {
			return;
		}

		$styles[] = $name . ':' . esc_attr( $value ) . ';';
	}

	/**
	 * Get a sanitized color from design data.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param string              $key Key.
	 * @return string
	 */
	protected function get_design_color( array $design, string $key ): string {
		$global_color = $this->get_design_global_color( $design, $key );

		if ( '' !== $global_color ) {
			return $global_color;
		}

		$value = (string) ( $design[ $key ] ?? '' );

		return preg_match( '/^(#[0-9a-fA-F]{3,8}|rgba?\\([^)]+\\)|hsla?\\([^)]+\\)|var\\([^)]+\\))$/', $value )
			? $value
			: '';
	}

	/**
	 * Check if a scoped Elementor value has meaningful visual data.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	protected function has_design_value( $value ): bool {
		if ( null === $value || '' === $value || false === $value || 0 === $value || '0' === $value ) {
			return false;
		}

		if ( is_array( $value ) ) {
			$meaningful_keys = array_filter(
				array_keys( $value ),
				static function( $key ) {
					return ! in_array( (string) $key, [ 'unit', 'isLinked', 'sizes' ], true );
				}
			);

			if ( empty( $meaningful_keys ) ) {
				return false;
			}

			foreach ( $meaningful_keys as $key ) {
				if ( $this->has_design_value( $value[ $key ] ) ) {
					return true;
				}
			}

			return false;
		}

		return true;
	}

	/**
	 * Return the first meaningful value from a list of possible Elementor keys.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param array<int,string>   $keys Possible keys.
	 * @return mixed
	 */
	protected function get_design_value_from_keys( array $design, array $keys ) {
		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $design ) && $this->has_design_value( $design[ $key ] ) ) {
				return $design[ $key ];
			}
		}

		return '';
	}

	/**
	 * Return the first meaningful responsive value from possible Elementor keys.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param array<int,string>   $keys Possible keys.
	 * @param string              $suffix Responsive suffix.
	 * @return mixed
	 */
	protected function get_responsive_design_value_from_keys( array $design, array $keys, string $suffix = '' ) {
		foreach ( $keys as $key ) {
			$value = $this->get_responsive_design_value( $design, $key, $suffix );

			if ( $this->has_design_value( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Resolve a color from possible scoped keys.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param array<int,string>   $keys Possible keys.
	 * @return string
	 */
	protected function get_design_color_from_keys( array $design, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = $this->get_design_color( $design, $key );

			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Resolve a keyword from possible scoped keys.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param array<int,string>   $keys Possible keys.
	 * @param array<int,string>   $allowed Allowed values.
	 * @return string
	 */
	protected function get_design_keyword_from_keys( array $design, array $keys, array $allowed ): string {
		$value = sanitize_key( (string) $this->get_design_value_from_keys( $design, $keys ) );

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Build background key aliases for Directorist and Elementor-native controls.
	 *
	 * @param string $property Property suffix.
	 * @return array<int,string>
	 */
	protected function get_background_design_keys( string $property ): array {
		return [
			'template_container_background_' . $property,
		];
	}

	/**
	 * Build border key aliases for Directorist and Elementor-native controls.
	 *
	 * @param string $property Property suffix.
	 * @return array<int,string>
	 */
	protected function get_border_design_keys( string $property ): array {
		return [
			'template_container_border_' . $property,
		];
	}

	/**
	 * Build box-shadow key aliases for Directorist and Elementor-native controls.
	 *
	 * @param string $property Property suffix.
	 * @return array<int,string>
	 */
	protected function get_box_shadow_design_keys( string $property ): array {
		return [
			'template_container_box_shadow_box_shadow_' . $property,
		];
	}

	/**
	 * Resolve an Elementor global color reference to its CSS variable.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param string              $key Control key.
	 * @return string
	 */
	protected function get_design_global_color( array $design, string $key ): string {
		$globals = is_array( $design['__globals__'] ?? null ) ? (array) $design['__globals__'] : [];
		$value   = (string) ( $globals[ $key ] ?? '' );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^var\\(--e-global-color-[^)]+\\)$/', $value ) ) {
			return $value;
		}

		if ( ! preg_match( '/(?:^|[?&])id=([^&]+)/', $value, $matches ) ) {
			return '';
		}

		$id = sanitize_key( rawurldecode( (string) $matches[1] ) );

		return '' !== $id ? 'var(--e-global-color-' . $id . ')' : '';
	}

	/**
	 * Get a whitelisted keyword from design data.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param string              $key Key.
	 * @param array<int,string>   $allowed Allowed values.
	 * @return string
	 */
	protected function get_design_keyword( array $design, string $key, array $allowed ): string {
		$value = sanitize_key( (string) ( $design[ $key ] ?? '' ) );

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Build a CSS background value from Elementor background data.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @return string
	 */
	protected function build_design_background( array $design, string $suffix = '' ): string {
		$type = $this->get_design_keyword_from_keys( $design, $this->get_background_design_keys( 'background' ), [ 'classic', 'gradient' ] );

		if ( 'gradient' === $type ) {
			return $this->build_design_gradient_background( $design, $suffix );
		}

		return 'classic' === $type ? $this->get_design_color_from_keys( $design, $this->get_background_design_keys( 'color' ) ) : '';
	}

	/**
	 * Build a CSS gradient background value.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @return string
	 */
	protected function build_design_gradient_background( array $design, string $suffix = '' ): string {
		$color_a = $this->get_design_color_from_keys( $design, $this->get_background_design_keys( 'color' ) );
		$color_b = $this->get_design_color_from_keys( $design, $this->get_background_design_keys( 'color_b' ) );

		if ( '' === $color_a || '' === $color_b ) {
			return '' !== $color_a ? $color_a : $color_b;
		}

		$stop_a       = '0';
		$stop_b       = '100';

		if ( 'radial' === $this->get_design_keyword_from_keys( $design, $this->get_background_design_keys( 'gradient_type' ), [ 'linear', 'radial' ] ) ) {
			$position = $this->normalize_design_position( (string) $this->get_design_value_from_keys( $design, $this->get_background_design_keys( 'gradient_position' ) ) );

			return sprintf(
				'radial-gradient(at %s, %s %s%%, %s %s%%)',
				'' !== $position ? $position : 'center center',
				$color_a,
				$stop_a,
				$color_b,
				$stop_b
			);
		}

		$angle_value = $this->get_design_value_from_keys( $design, $this->get_background_design_keys( 'gradient_angle' ) );
		$angle       = $this->sanitize_design_number( is_array( $angle_value ) ? ( $angle_value['size'] ?? 180 ) : ( $angle_value ?: 180 ) );

		return sprintf(
			'linear-gradient(%sdeg, %s %s%%, %s %s%%)',
			$angle,
			$color_a,
			$stop_a,
			$color_b,
			$stop_b
		);
	}

	/**
	 * Normalize a CSS background/gradient position.
	 *
	 * @param string $value Raw position.
	 * @return string
	 */
	protected function normalize_design_position( string $value ): string {
		$value = strtolower( trim( str_replace( [ '_', '-' ], ' ', $value ) ) );
		$allowed = [
			'center center',
			'center left',
			'center right',
			'top center',
			'top left',
			'top right',
			'bottom center',
			'bottom left',
			'bottom right',
		];

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Resolve a responsive design value with desktop fallback.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @param string              $key Base key.
	 * @param string              $suffix Device suffix.
	 * @return mixed
	 */
	protected function get_responsive_design_value( array $design, string $key, string $suffix = '' ) {
		$responsive_key = $key . $suffix;

		if ( '' !== $suffix && array_key_exists( $responsive_key, $design ) && '' !== $design[ $responsive_key ] && null !== $design[ $responsive_key ] ) {
			return $design[ $responsive_key ];
		}

		return $design[ $key ] ?? '';
	}

	/**
	 * Get a whitelisted keyword for a raw value.
	 *
	 * @param mixed             $value Raw value.
	 * @param array<int,string> $allowed Allowed values.
	 * @return string
	 */
	protected function get_design_keyword_for_value( $value, array $allowed ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Build a CSS dimensions value from Elementor dimensions data.
	 *
	 * @param mixed $value Dimensions value.
	 * @return string
	 */
	protected function build_design_dimensions( $value ): string {
		if ( ! is_array( $value ) ) {
			return '';
		}

		$unit = $this->sanitize_design_unit( (string) ( $value['unit'] ?? 'px' ), [ 'px', 'em', 'rem', '%' ] );

		if ( '' === $unit ) {
			return '';
		}

		$parts = [];

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$raw = $value[ $side ] ?? '';

			if ( '' === $raw && '0' !== (string) $raw ) {
				return '';
			}

			$parts[] = $this->sanitize_design_number( $raw ) . $unit;
		}

		return implode( ' ', $parts );
	}

	/**
	 * Build a CSS size value from Elementor slider data.
	 *
	 * @param mixed $value Slider value.
	 * @return string
	 */
	protected function build_design_size( $value ): string {
		if ( ! is_array( $value ) || ! isset( $value['size'] ) || '' === (string) $value['size'] ) {
			return '';
		}

		$unit = $this->sanitize_design_unit( (string) ( $value['unit'] ?? 'px' ), [ 'px', 'em', 'rem', '%', 'vh' ] );

		if ( '' === $unit ) {
			return '';
		}

		return $this->sanitize_design_number( $value['size'] ) . $unit;
	}

	/**
	 * Build a CSS box-shadow value from Elementor shadow data.
	 *
	 * @param array<string,mixed> $design Design data.
	 * @return string
	 */
	protected function build_design_box_shadow( array $design ): string {
		if ( 'yes' !== (string) $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'type' ) ) ) {
			return '';
		}

		$color = $this->get_design_color_from_keys( $design, $this->get_box_shadow_design_keys( 'color' ) );

		if ( '' === $color ) {
			$color = 'rgba(0,0,0,.15)';
		}

		$horizontal = $this->sanitize_design_number( $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'horizontal' ) ) ?: 0 ) . 'px';
		$vertical   = $this->sanitize_design_number( $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'vertical' ) ) ?: 0 ) . 'px';
		$blur       = $this->sanitize_design_number( $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'blur' ) ) ?: 0 ) . 'px';
		$spread     = $this->sanitize_design_number( $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'spread' ) ) ?: 0 ) . 'px';
		$position   = 'inset' === (string) $this->get_design_value_from_keys( $design, $this->get_box_shadow_design_keys( 'position' ) ) ? ' inset' : '';

		return $horizontal . ' ' . $vertical . ' ' . $blur . ' ' . $spread . ' ' . $color . $position;
	}

	/**
	 * Sanitize a CSS numeric value.
	 *
	 * @param mixed $value Raw number.
	 * @return string
	 */
	protected function sanitize_design_number( $value ): string {
		return (string) (float) $value;
	}

	/**
	 * Sanitize a CSS unit.
	 *
	 * @param string            $unit Raw unit.
	 * @param array<int,string> $allowed Allowed units.
	 * @return string
	 */
	protected function sanitize_design_unit( string $unit, array $allowed ): string {
		return in_array( $unit, $allowed, true ) ? $unit : '';
	}

	/**
	 * Render the current listing with the widget's map card composition.
	 *
	 * Directorist core map views build marker/popup payloads outside the normal
	 * repeated card loop. This method lets the temporary map template overrides
	 * reuse the same scoped Elementor rendering pipeline used
	 * by grid/list cards.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function render_map_card_markup_for_listing( int $listing_id = 0 ): string {
		$listing_id = absint( $listing_id );

		if ( $listing_id <= 0 ) {
			return '';
		}

		$settings          = $this->get_settings_for_display();
		$loop_context      = RenderContext::get_instance()->current_loop_context();
		$listing_ids       = array_values( array_map( 'absint', (array) ( $loop_context['listing_ids'] ?? [] ) ) );
		$listing_index     = array_search( $listing_id, $listing_ids, true );
		$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );

		if ( empty( $loop_context ) ) {
			return '';
		}

		RenderContext::get_instance()->push_listing_context(
			$listing_id,
			false !== $listing_index ? (int) $listing_index : 0,
			$directory_type_id
		);

		ob_start();

		try {
			// Map popups must render only their dedicated scoped branch. Projected
			// editor children belong to the currently edited branch and may be grid/list.
			$this->render_single_listing_card( [], $settings, $directory_type_id, 'map' );
		} finally {
			RenderContext::get_instance()->pop_listing_context();
		}

		return trim( (string) ob_get_clean() );
	}

	/**
	 * Determine whether raw element data contains meaningful composition.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return bool
	 */
	protected function has_raw_composition_content( array $element_data ): bool {
		$el_type  = (string) ( $element_data['elType'] ?? '' );
		$children = (array) ( $element_data['elements'] ?? [] );

		if ( 'container' !== $el_type ) {
			return true;
		}

		foreach ( $children as $child ) {
			if ( is_array( $child ) && $this->has_raw_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Render a fresh Elementor element instance for a selected raw child.
	 *
	 * The same saved scope payload is reused across many listings, so runtime
	 * rendering uses raw payloads rather than one live nested element tree.
	 *
	 * @param array<string,mixed> $selected_raw Selected raw element data.
	 * @return void
	 */
	protected function render_selected_child_raw_data( array $selected_raw ): void {
		$fresh_element = ElementTreeRenderService::get_instance()->create_element_instance( $selected_raw );

		if ( $fresh_element && method_exists( $fresh_element, 'print_element' ) ) {
			$fresh_element->print_element();
		}
	}

	/**
	 * Fallback renderer for the live nested child element.
	 *
	 * @param mixed $selected_child Selected child element.
	 * @return void
	 */
	protected function render_selected_child_live_element( $selected_child ): void {
		if ( method_exists( $selected_child, 'print_element' ) ) {
			$selected_child->print_element();
		}
	}

	/**
	 * Determine whether raw child element list contains meaningful composition.
	 *
	 * @param array<int,mixed> $children Raw children.
	 * @return bool
	 */
	protected function raw_children_have_content( array $children ): bool {
		foreach ( $children as $child ) {
			if ( is_array( $child ) && $this->has_raw_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the storage key for a directory/view card template.
	 *
	 * @param int    $directory_type_id Directory type id.
	 * @param string $view_type View type.
	 * @return string
	 */
	protected function build_template_key( int $directory_type_id, string $view_type ): string {
		return 'dir-' . absint( $directory_type_id ) . '-' . sanitize_key( $view_type ?: 'grid' );
	}

	/**
	 * Decode all scoped card templates from widget settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_scoped_templates( array $settings ): array {
		$raw = $settings['scoped_templates'] ?? '{}';

		if ( is_string( $raw ) ) {
			$decoded = json_decode( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ), true );
		} else {
			$decoded = $raw;
		}

		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$templates = [];

		foreach ( $decoded as $key => $template ) {
			if ( ! is_string( $key ) || ! is_array( $template ) ) {
				continue;
			}

			$template_settings = [];
			$scope_identity    = $this->parse_template_key( $key );
			$directory_type_id = absint( $template['directory_type_id'] ?? 0 );
			$view_type         = sanitize_key( (string) ( $template['view_type'] ?? 'grid' ) );

			if ( is_array( $template['settings'] ?? null ) ) {
				$template_settings = (array) $template['settings'];
			}

			if ( $scope_identity['directory_type_id'] > 0 ) {
				$directory_type_id = $scope_identity['directory_type_id'];
			}

			if ( '' !== $scope_identity['view_type'] ) {
				$view_type = $scope_identity['view_type'];
			}

			$templates[ sanitize_key( $key ) ] = [
				'directory_type_id' => $directory_type_id,
				'view_type'         => $view_type,
				'label'             => sanitize_text_field( (string) ( $template['label'] ?? '' ) ),
				'settings'          => $template_settings,
				'elements'          => array_values( array_filter( (array) ( $template['elements'] ?? [] ), 'is_array' ) ),
			];
		}

		return $templates;
	}

	/**
	 * Parse a scoped template key into its directory/view identity.
	 *
	 * @param string $scope_key Scoped template key.
	 * @return array{directory_type_id:int,view_type:string}
	 */
	protected function parse_template_key( string $scope_key ): array {
		if ( ! preg_match( '/^dir-(\d+)-(grid|list|map)$/', $scope_key, $matches ) ) {
			return [
				'directory_type_id' => 0,
				'view_type'         => '',
			];
		}

		return [
			'directory_type_id' => absint( $matches[1] ),
			'view_type'         => sanitize_key( $matches[2] ),
		];
	}

	/**
	 * Resolve a scoped template by storage key.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string              $scope_key Scope key.
	 * @return array<string,mixed>|null
	 */
	protected function get_scoped_template( array $settings, string $scope_key ): ?array {
		$templates = $this->get_scoped_templates( $settings );
		$scope_key = sanitize_key( $scope_key );

		return isset( $templates[ $scope_key ] ) && is_array( $templates[ $scope_key ] )
			? $templates[ $scope_key ]
			: null;
	}

	/**
	 * Render repeated cards at the template position inside Listings Loop.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $loop_context Loop context.
	 * @return void
	 */
	protected function render_loop_cards( array $settings, array $loop_context ): void {
		$active_view = sanitize_key( (string) ( $loop_context['active_view'] ?? 'grid' ) );
		$display_mode = sanitize_key( (string) ( $loop_context['display_mode'] ?? 'default' ) );

		if ( 'map' === $active_view && $this->is_editor_context() ) {
			if ( 'map_list' === $display_mode ) {
				$loop_context['map_list_settings'] = is_array( $loop_context['map_list_settings'] ?? null )
					? (array) $loop_context['map_list_settings']
					: [];
				$loop_context['map_list_settings']['show_map_card'] = true;
			}

			$this->render_loop_map_view( $loop_context, true, 'directorist-elementor-card-template__map-preview' );

			return;
		}

		if ( 'map_list' === $display_mode ) {
			$map_list_view = 'map' === $active_view ? 'grid' : $active_view;
			$listing_ids   = array_values( array_map( 'absint', (array) ( $loop_context['listing_ids'] ?? [] ) ) );

			$this->render_loop_map_list_cards( $settings, $loop_context, $listing_ids, $map_list_view );

			return;
		}

		if ( 'map' === $active_view ) {
			$this->render_loop_map_view( $loop_context );

			return;
		}

		$listing_ids    = array_values( array_map( 'absint', (array) ( $loop_context['listing_ids'] ?? [] ) ) );
		$empty_message  = trim( (string) ( $loop_context['empty_message'] ?? '' ) );
		$empty_message  = '' !== $empty_message ? $empty_message : __( 'No listings matched the current settings.', 'directorist-elementor' );
		$pagination_type = sanitize_key( (string) ( $loop_context['pagination_type'] ?? 'numbered' ) );
		$cards_classes   = [
			'directorist-elementor-loop__cards',
			'directorist-archive-items',
			'directorist-archive-' . sanitize_html_class( $active_view ) . '-view',
		];

		if ( 'infinite_scroll' === $pagination_type ) {
			$cards_classes[] = 'directorist-infinite-scroll';
		}

		if ( empty( $listing_ids ) ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Listing Card Template', 'directorist-elementor' ),
					$empty_message,
					$empty_message
				)
			);

			return;
		}

		if ( 'slider' === $display_mode ) {
			$this->render_loop_slider_cards( $settings, $loop_context, $listing_ids, $active_view );

			return;
		}

		printf(
			'<div class="%s">',
			esc_attr( implode( ' ', $cards_classes ) )
		);

		$this->render_loop_card_items( $settings, $loop_context, $listing_ids, $active_view );

		echo '</div>';
	}

	/**
	 * Render listings and map side-by-side from the same loop query.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $loop_context Loop context.
	 * @param array<int,int>      $listing_ids Listing ids.
	 * @param string              $active_view Active card view.
	 * @return void
	 */
	protected function render_loop_map_list_cards( array $settings, array $loop_context, array $listing_ids, string $active_view ): void {
		$map_settings = is_array( $loop_context['map_list_settings'] ?? null ) ? (array) $loop_context['map_list_settings'] : [];
		$position     = sanitize_key( (string) ( $map_settings['map_position'] ?? 'right' ) );
		$position     = in_array( $position, [ 'right', 'left', 'top', 'bottom' ], true ) ? $position : 'right';
		$empty_message = trim( (string) ( $loop_context['empty_message'] ?? '' ) );
		$empty_message = '' !== $empty_message ? $empty_message : __( 'No listings matched the current settings.', 'directorist-elementor' );
		$map_first     = in_array( $position, [ 'left', 'top' ], true );
		$map_height    = max( 220, absint( $map_settings['map_height'] ?? 520 ) );
		$map_width     = max( 25, min( 70, absint( $map_settings['map_width'] ?? 42 ) ) );
		$gap_values    = is_array( $loop_context['gap_values'] ?? null )
			? (array) $loop_context['gap_values']
			: LoopRenderService::get_instance()->resolve_loop_gap_values( [] );
		$gap_style     = LoopRenderService::get_instance()->build_loop_gap_style_variables( $gap_values );
		$wrapper_attrs = [
			'class'                  => [
				'directorist-elementor-map-list',
				'directorist-elementor-map-list--' . sanitize_html_class( $position ),
				'directorist-elementor-map-list--view-' . sanitize_html_class( $active_view ),
				! empty( $map_settings['sticky_map'] ) ? 'directorist-elementor-map-list--sticky' : '',
			],
			'data-directorist-elementor-map-list' => '1',
			'data-map-list-position' => $position,
			'style'                  => $gap_style . '--direl-map-list-map-height:' . $map_height . 'px;--direl-map-list-map-width:' . $map_width . '%;',
			'data-fit-on-load'       => ! empty( $map_settings['fit_on_load'] ) ? '1' : '0',
			'data-hover-focus'       => ! empty( $map_settings['hover_focus'] ) ? '1' : '0',
			'data-hover-zoom'        => (string) max( 1, min( 22, absint( $map_settings['hover_zoom'] ?? 14 ) ) ),
			'data-show-map-card'     => ! empty( $map_settings['show_map_card'] ) ? '1' : '0',
		];

		echo '<div ' . $this->format_html_attributes( $wrapper_attrs ) . '>';

		if ( $map_first ) {
			$this->render_loop_map_list_map_pane( $loop_context );
		}

		echo '<div class="directorist-elementor-map-list__list-pane">';

		if ( empty( $listing_ids ) ) {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Listing Card Template', 'directorist-elementor' ),
					$empty_message,
					$empty_message
				)
			);
		} else {
			printf(
				'<div class="%s">',
				esc_attr(
					implode(
						' ',
						[
							'directorist-elementor-loop__cards',
							'directorist-archive-items',
							'directorist-archive-' . sanitize_html_class( $active_view ) . '-view',
						]
					)
				)
			);
			$this->render_loop_card_items( $settings, $loop_context, $listing_ids, $active_view );
			echo '</div>';
		}

		echo '</div>';

		if ( ! $map_first ) {
			$this->render_loop_map_list_map_pane( $loop_context );
		}

		echo '</div>';

		$this->render_loop_fallback_pagination( $loop_context );
	}

	/**
	 * Render the map pane for listings-with-map.
	 *
	 * @param array<string,mixed> $loop_context Loop context.
	 * @return void
	 */
	protected function render_loop_map_list_map_pane( array $loop_context ): void {
		echo '<div class="directorist-elementor-map-list__map-pane">';
		$this->render_loop_map_view( $loop_context, false, 'directorist-elementor-map-list__map' );
		echo '</div>';
	}

	/**
	 * Render loop cards as a Swiper slider.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $loop_context Loop context.
	 * @param array<int,int>      $listing_ids Listing ids.
	 * @param string              $active_view Active view.
	 * @return void
	 */
	protected function render_loop_slider_cards( array $settings, array $loop_context, array $listing_ids, string $active_view ): void {
		$slider_settings = is_array( $loop_context['slider_settings'] ?? null ) ? (array) $loop_context['slider_settings'] : [];
		$desktop         = max( 1, absint( $slider_settings['slides_per_view'] ?? 3 ) );
		$tablet          = max( 1, absint( $slider_settings['slides_per_view_tablet'] ?? 2 ) );
		$mobile          = max( 1, absint( $slider_settings['slides_per_view_mobile'] ?? 1 ) );
		$show_arrows     = ! empty( $slider_settings['show_arrow_navigation'] );
		$show_dots       = ! empty( $slider_settings['show_dot_navigation'] );
		$autoplay        = ! empty( $slider_settings['autoplay'] );
		$effect          = sanitize_key( (string) ( $slider_settings['effect'] ?? 'slide' ) );
		$grid_rows       = max( 1, min( 6, absint( $slider_settings['grid_rows'] ?? 2 ) ) );
		$pause           = ! empty( $slider_settings['pause_on_hover'] );
		$delay           = max( 100, absint( $slider_settings['autoplay_delay'] ?? 3000 ) );
		$speed           = max( 100, absint( $slider_settings['transition_speed'] ?? 500 ) );
		$should_loop     = count( $listing_ids ) > $desktop;
		$gap_values      = is_array( $loop_context['gap_values'] ?? null )
			? (array) $loop_context['gap_values']
			: LoopRenderService::get_instance()->resolve_loop_gap_values( [] );
		$slider_gaps     = is_array( $gap_values['slider_card_gap'] ?? null )
			? (array) $gap_values['slider_card_gap']
			: [
				'desktop' => '30px',
				'tablet'  => '20px',
				'mobile'  => '10px',
			];
		$gap_desktop     = LoopRenderService::get_instance()->loop_gap_css_value_to_swiper_px( (string) ( $slider_gaps['desktop'] ?? '30px' ), 30 );
		$gap_tablet      = LoopRenderService::get_instance()->loop_gap_css_value_to_swiper_px( (string) ( $slider_gaps['tablet'] ?? '20px' ), 20 );
		$gap_mobile      = LoopRenderService::get_instance()->loop_gap_css_value_to_swiper_px( (string) ( $slider_gaps['mobile'] ?? '10px' ), 10 );
		$breakpoints     = wp_json_encode(
			[
				0    => [ 'slidesPerView' => $mobile, 'spaceBetween' => $gap_mobile ],
				768  => [ 'slidesPerView' => $tablet, 'spaceBetween' => $gap_tablet ],
				1200 => [ 'slidesPerView' => $desktop, 'spaceBetween' => $gap_desktop ],
			]
		);
		$slider_attrs    = [
			'class'                  => [
				'directorist-swiper',
				'directorist-elementor-listings-loop-slider',
				'directorist-elementor-listings-loop-slider--' . sanitize_html_class( $active_view ),
			],
			'data-sw-items'          => (string) $desktop,
			'data-sw-margin'         => (string) $gap_desktop,
			'data-sw-loop'           => $should_loop ? 'true' : 'false',
			'data-sw-perslide'       => '1',
			'data-sw-speed'          => (string) $speed,
			'data-sw-delay'          => (string) $delay,
			'data-sw-autoplay'       => $autoplay ? 'true' : 'false',
			'data-sw-effect'         => $effect,
			'data-sw-grid-rows'      => (string) $grid_rows,
			'data-sw-pause-on-hover' => $pause ? 'true' : 'false',
			'data-sw-responsive'     => is_string( $breakpoints ) ? $breakpoints : '{}',
		];

		echo '<div ' . $this->format_html_attributes( $slider_attrs ) . '>';
		echo '<div class="swiper-wrapper">';
		$this->render_loop_card_items( $settings, $loop_context, $listing_ids, $active_view, true );
		echo '</div>';

		$nav_hidden = $show_arrows ? '' : ' directorist-elementor-listings-loop-slider__navigation--hidden';
		echo '<div class="directorist-swiper__navigation directorist-elementor-listings-loop-slider__navigation' . esc_attr( $nav_hidden ) . '">';
		echo '<div class="directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-loop"><span aria-hidden="true">&#8249;</span></div>';
		echo '<div class="directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-loop"><span aria-hidden="true">&#8250;</span></div>';
		echo '</div>';

		$dot_hidden = $show_dots ? '' : ' directorist-elementor-listings-loop-slider__pagination--hidden';
		echo '<div class="directorist-swiper__pagination directorist-swiper__pagination--loop' . esc_attr( $dot_hidden ) . '"></div>';
		echo '</div>';
	}

	/**
	 * Render repeated card items.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $loop_context Loop context.
	 * @param array<int,int>      $listing_ids Listing ids.
	 * @param string              $active_view Active view.
	 * @param bool                $as_slides Whether to wrap each card as a Swiper slide.
	 * @return void
	 */
	protected function render_loop_card_items( array $settings, array $loop_context, array $listing_ids, string $active_view, bool $as_slides = false ): void {
		$controller    = is_object( $loop_context['controller'] ?? null ) ? $loop_context['controller'] : null;
		$previous_post = $GLOBALS['post'] ?? null;

		foreach ( $listing_ids as $index => $listing_id ) {
			$listing_id = absint( $listing_id );

			if ( $listing_id <= 0 ) {
				continue;
			}

			$listing_post = get_post( $listing_id );

			if ( $listing_post instanceof \WP_Post ) {
				$GLOBALS['post'] = $listing_post;
				setup_postdata( $listing_post );
			}

			if ( $controller && method_exists( $controller, 'set_loop_data' ) ) {
				$controller->set_loop_data();
			}

			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );
			$card_style        = $this->build_card_wrapper_style_for_scope( $settings, $directory_type_id, $active_view );

			RenderContext::get_instance()->push_listing_context( $listing_id, $index, $directory_type_id );

			try {
				$article_attrs = [
					'class'                    => [
						'directorist-elementor-loop__item',
						$as_slides ? 'directorist-elementor-loop__item--slider' : '',
						'' !== $card_style ? 'directorist-elementor-loop__item--card-styled' : '',
					],
					'data-directorist-listing-id' => (string) $listing_id,
				];

				if ( '' !== $card_style ) {
					$article_attrs['style'] = $card_style;
				}

				if ( $as_slides ) {
					echo '<div class="swiper-slide">';
					echo '<article ' . $this->format_html_attributes( $article_attrs ) . '>';
				} else {
					echo '<article ' . $this->format_html_attributes( $article_attrs ) . '>';
				}

				$this->render_single_listing_card( [], $settings, $directory_type_id, $active_view, false );
				echo '</article>';

				if ( $as_slides ) {
					echo '</div>';
				}
			} finally {
				RenderContext::get_instance()->pop_listing_context();
			}
		}

		if ( $previous_post instanceof \WP_Post ) {
			$GLOBALS['post'] = $previous_post;
			setup_postdata( $previous_post );
		} else {
			unset( $GLOBALS['post'] );
			wp_reset_postdata();
		}
	}

	/**
	 * Render frontend map output through the active Directorist listings controller.
	 *
	 * @param array<string,mixed> $loop_context Active loop context.
	 * @return void
	 */
	protected function render_loop_map_view( array $loop_context, bool $render_pagination = true, string $extra_class = '' ): void {
		$controller            = $loop_context['controller'] ?? null;
		$asset_loader_class    = null;
		$map_template_context  = MapCardTemplateContext::get_instance();
		$map_list_settings     = is_array( $loop_context['map_list_settings'] ?? null ) ? (array) $loop_context['map_list_settings'] : [];
		$display_mode          = sanitize_key( (string) ( $loop_context['display_mode'] ?? 'default' ) );
		$show_map_card         = 'map_list' !== $display_mode || ! array_key_exists( 'show_map_card', $map_list_settings ) || ! empty( $map_list_settings['show_map_card'] );
		$classes               = array_filter(
			[
				'directorist-elementor-loop__map',
				sanitize_html_class( $extra_class ),
			]
		);

		if ( class_exists( '\\Directorist\\Asset_Loader\\Asset_Loader' ) ) {
			$asset_loader_class = '\\Directorist\\Asset_Loader\\Asset_Loader';
		} elseif ( class_exists( '\\Directorist_Asset_Loader' ) ) {
			$asset_loader_class = '\\Directorist_Asset_Loader';
		}

		echo '<div class="' . esc_attr( implode( ' ', $classes ) ) . '">';

		if ( null !== $asset_loader_class ) {
			if ( method_exists( $asset_loader_class, 'enqueue_map_styles' ) ) {
				$asset_loader_class::enqueue_map_styles();
			}

			if ( method_exists( $asset_loader_class, 'enqueue_map_scripts' ) ) {
				$asset_loader_class::enqueue_map_scripts();
			}
		}

		if ( is_object( $controller ) && method_exists( $controller, 'render_map' ) ) {
			$map_template_context->push( $this, $show_map_card );

			try {
				ob_start();
				$controller->render_map();
				$map_markup = trim( (string) ob_get_clean() );

				if ( '' !== $map_markup ) {
					echo $map_markup; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				} else {
					echo wp_kses_post(
						$this->render_placeholder(
							__( 'Map Preview', 'directorist-elementor' ),
							__( 'The current loop scope did not return any map output.', 'directorist-elementor' )
						)
					);
				}

				if ( $render_pagination ) {
					$this->render_loop_fallback_pagination( $loop_context );
				}
			} finally {
				$map_template_context->pop();
			}
		} else {
			echo wp_kses_post(
				$this->render_placeholder(
					__( 'Map Preview', 'directorist-elementor' ),
					__( 'Map rendering is unavailable for the current loop context.', 'directorist-elementor' )
				)
			);
		}

		echo '</div>';
	}

	/**
	 * Render controller pagination when no pagination child widget exists.
	 *
	 * @param array<string,mixed> $loop_context Active loop context.
	 * @return void
	 */
	protected function render_loop_fallback_pagination( array $loop_context ): void {
		$controller            = $loop_context['controller'] ?? null;
		$utility_state         = is_array( $loop_context['utility_state'] ?? null ) ? (array) $loop_context['utility_state'] : [];
		$has_pagination_widget = ! empty( $utility_state['has_pagination_widget'] );

		if (
			! $has_pagination_widget &&
			is_object( $controller ) &&
			! empty( $controller->show_pagination ) &&
			method_exists( $controller, 'pagination' )
		) {
			$controller->pagination();
		}
	}

	/**
	 * Editor template.
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<#
			const previewView = settings.active_view_type || 'grid';
			const activeTemplateKey = settings.active_template_key || '';
			#>
			<div class="directorist-elementor-card-template directorist-elementor-card-template--editor directorist-elementor-card-template--{{ previewView }}" data-direl-template="{{ activeTemplateKey }}" data-direl-view="{{ previewView }}">
				<div class="directorist-elementor-card-template__preview-surface">
					<div class="directorist-elementor-placeholder directorist-elementor-card-template__preview-loading">
						<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
						<p><?php echo esc_html__( 'Rendering listing cards for the current directory and view scope.', 'directorist-elementor' ); ?></p>
					</div>
				</div>
				<div class="directorist-elementor-card-template__content directorist-elementor-card-template__content--editor directorist-elementor-card-template__storage" aria-hidden="true"></div>
			</div>
			<?php
	}

	/**
	 * Get the editor-facing template title.
	 *
	 * @param int    $directory_type_id Directory type id.
	 * @param string $view_type View type.
	 * @return string
	 */
	protected function get_template_display_title( int $directory_type_id, string $view_type ): string {
		$directory_label   = DirectoristBridge::get_instance()->get_directory_label( $directory_type_id );
		$view_type         = sanitize_key( $view_type ?: 'grid' );

		return sprintf(
			'%1$s / %2$s',
			$directory_label,
			ucfirst( $view_type )
		);
	}

	/**
	 * Check editor request.
	 *
	 * @return bool
	 */
	protected function is_editor_context(): bool {
		return EditorContext::get_instance()->is_editor_request();
	}

	/**
	 * Format attribute array.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	protected function format_html_attributes( array $attributes ): string {
		$pairs = [];

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ' ', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf(
				'%1$s="%2$s"',
				esc_attr( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		return implode( ' ', $pairs );
	}

	/**
	 * Render placeholder markup.
	 *
	 * @param string $title Title.
	 * @param string $description Description.
	 * @param string $meta Meta.
	 * @return string
	 */
	protected function render_placeholder( string $title, string $description, string $meta = '' ): string {
		$meta_markup = '' !== $meta
			? sprintf( '<p class="directorist-elementor-placeholder__meta">%s</p>', esc_html( $meta ) )
			: '';

		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%1$s</p><p>%2$s</p>%3$s</div>',
			esc_html( $title ),
			esc_html( $description ),
			$meta_markup
		);
	}

	/**
	 * Determine whether child elements contain meaningful composition.
	 *
	 * @param array<int,mixed> $children Child elements.
	 * @return bool
	 */
	protected function has_composition_children( array $children ): bool {
		foreach ( $children as $child ) {
			if ( $this->has_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Determine whether an element has real composition content.
	 *
	 * @param mixed $element Element instance.
	 * @return bool
	 */
	protected function has_composition_content( $element ): bool {
		if ( ! is_object( $element ) ) {
			return false;
		}

		if ( ! $this->is_container_element( $element ) ) {
			return true;
		}

		if ( ! method_exists( $element, 'get_children' ) ) {
			return false;
		}

		foreach ( (array) $element->get_children() as $child ) {
			if ( $this->has_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if an element is a container node.
	 *
	 * @param mixed $element Element instance.
	 * @return bool
	 */
	protected function is_container_element( $element ): bool {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_data' ) ) {
			return false;
		}

		$data = (array) $element->get_data();

		return 'container' === (string) ( $data['elType'] ?? '' ) || static::get_type() === (string) ( $data['elType'] ?? '' );
	}

}
