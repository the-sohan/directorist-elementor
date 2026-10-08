<?php
/**
 * Homepage search widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use Directorist\Directorist_Listing_Search_Form;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;

class HomepageSearchWidget extends ListingsSearchWidget {
	private const TAB_SEARCH_STYLE = 'directorist_homepage_search_style';
	private const TAB_DIRECTORY_TYPES_STYLE = 'directorist_homepage_directory_types_style';
	private const INHERITED_SETTINGS_KEY = 'directorist_home_search_inherited_settings';

	/**
	 * Get element type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist_homepage_search';
	}

	/**
	 * Get element slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return static::get_type();
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Homepage Search', 'directorist-elementor' );
	}

	/**
	 * Keep Elementor's element cache from freezing template-derived search context.
	 *
	 * @return bool
	 */
	protected function is_dynamic_content(): bool {
		return true;
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->register_homepage_search_style_tabs();
		$this->register_hidden_homepage_search_state_controls();

		parent::register_widget_controls();

		$this->register_standalone_directory_types_style_controls();
	}

	/**
	 * Homepage Search keeps hidden template state in the Settings section.
	 *
	 * @return bool
	 */
	protected function should_register_template_state_section(): bool {
		return false;
	}

	/**
	 * Get the Elementor top-level tab used by inherited search design controls.
	 *
	 * @return string
	 */
	protected function get_search_style_controls_tab(): string {
		return self::TAB_SEARCH_STYLE;
	}

	/**
	 * Register Homepage Search top-level design tabs.
	 *
	 * @return void
	 */
	protected function register_homepage_search_style_tabs(): void {
		Controls_Manager::add_tab(
			self::TAB_SEARCH_STYLE,
			sprintf(
				'<i class="eicon-search" aria-hidden="true"></i><span>%s</span>',
				esc_html__( 'Search', 'directorist-elementor' )
			)
		);

		if ( ! $this->should_show_directory_types_style_controls() ) {
			return;
		}

		Controls_Manager::add_tab(
			self::TAB_DIRECTORY_TYPES_STYLE,
			sprintf(
				'<i class="eicon-folder" aria-hidden="true"></i><span>%s</span>',
				esc_html__( 'Directory Types', 'directorist-elementor' )
			)
		);
	}

	/**
	 * Register hidden template-state controls without exposing a Content tab.
	 *
	 * @return void
	 */
	protected function register_hidden_homepage_search_state_controls(): void {
		$this->start_controls_section(
			'section_homepage_search_template_state',
			[
				'label'     => __( 'Template State', 'directorist-elementor' ),
				'tab'       => self::TAB_SEARCH_STYLE,
				'condition' => [
					'directorist_template_state_visible' => 'yes',
				],
			]
		);

		$this->register_template_state_controls();

		$this->add_control(
			'standalone_design_directory_type_id',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 0,
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register style controls for the Directory Types markup injected by
	 * standalone Homepage Search widgets.
	 *
	 * @return void
	 */
	protected function register_standalone_directory_types_style_controls(): void {
		if ( ! $this->should_show_directory_types_style_controls() ) {
			return;
		}

		$wrapper_selector = '{{WRAPPER}} .directorist-elementor-homepage-search .directorist-elementor-homepage-search__directory-types';
		$nav_selector     = $wrapper_selector . ' .directorist-type-nav, ' . $wrapper_selector . ' .directorist-type-nav ul';
		$link_selector    = $wrapper_selector . ' .directorist-type-nav__link';
		$active_selector  = $wrapper_selector . ' .directorist-type-nav__list__current .directorist-type-nav__link';
		$icon_selector    = $link_selector . ' .directorist-icon-mask';
		$svg_selector     = $link_selector . ' svg';

		$this->start_controls_section(
			'section_homepage_directory_types_wrapper_style',
			[
				'label' => __( 'Directory Types Wrapper', 'directorist-elementor' ),
				'tab'   => self::TAB_DIRECTORY_TYPES_STYLE,
			]
		);

		$this->add_responsive_control(
			'homepage_directory_types_alignment',
			[
				'label'   => __( 'Alignment', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
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
					$nav_selector => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_types_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 80,
					],
				],
				'selectors'  => [
					$nav_selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'homepage_directory_types_wrapper_background',
				'selector' => $wrapper_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'homepage_directory_types_wrapper_border',
				'selector' => $wrapper_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'homepage_directory_types_wrapper_shadow',
				'selector' => $wrapper_selector,
			]
		);

		$this->add_responsive_control(
			'homepage_directory_types_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$wrapper_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_types_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%', 'em', 'rem' ],
				'selectors'  => [
					$wrapper_selector => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_types_wrapper_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$wrapper_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_homepage_directory_type_item_style',
			[
				'label' => __( 'Directory Type', 'directorist-elementor' ),
				'tab'   => self::TAB_DIRECTORY_TYPES_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'homepage_directory_type_typography',
				'selector' => $link_selector,
			]
		);

		$this->start_controls_tabs( 'tabs_homepage_directory_type_states' );

		$this->start_controls_tab(
			'tab_homepage_directory_type_normal',
			[
				'label' => __( 'Normal', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'homepage_directory_type_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_homepage_directory_type_hover',
			[
				'label' => __( 'Hover', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'homepage_directory_type_hover_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector . ':hover' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_hover_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector . ':hover' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_hover_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector . ':hover' => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'tab_homepage_directory_type_active',
			[
				'label' => __( 'Active', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'homepage_directory_type_active_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$active_selector => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_active_background',
			[
				'label'     => __( 'Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$active_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_active_border_color',
			[
				'label'     => __( 'Border Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$active_selector => 'border-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_tab();
		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'homepage_directory_type_border',
				'selector' => $link_selector,
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'homepage_directory_type_shadow',
				'selector' => $link_selector,
			]
		);

		$this->add_responsive_control(
			'homepage_directory_type_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					$link_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_type_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$link_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_homepage_directory_type_icon_style',
			[
				'label' => __( 'Directory Type Icon', 'directorist-elementor' ),
				'tab'   => self::TAB_DIRECTORY_TYPES_STYLE,
			]
		);

		$this->add_control(
			'homepage_directory_type_icon_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$icon_selector . '::after' => 'background-color: {{VALUE}};',
					$svg_selector              => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_icon_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$link_selector . ':hover .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					$link_selector . ':hover svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'homepage_directory_type_icon_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$active_selector . ' .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
					$active_selector . ' svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_type_icon_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 8,
						'max' => 80,
					],
				],
				'selectors'  => [
					$icon_selector => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
					$svg_selector  => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'homepage_directory_type_icon_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 40,
					],
				],
				'selectors'  => [
					$link_selector => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render the homepage search widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$rendered = false;

		$this->with_active_loop_context(
			function() use ( &$rendered ) {
				$rendered = $this->render_search_markup(
					$this->get_homepage_search_classes(),
					$this->get_homepage_search_attributes()
				);
			}
		);

		if ( $rendered ) {
			return;
		}

		$settings = $this->get_homepage_loop_settings();
		$instance_id = InstanceState::get_instance()->normalize_instance_id( 'direl-home-search-' . $this->get_id() );
		$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
			$settings,
			$instance_id,
			$this->is_editor_context(),
			$this->is_editor_context() ? 1 : null
		);

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			$rendered = $this->render_search_markup(
				$this->get_homepage_search_classes(),
				$this->get_homepage_search_attributes()
			);
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}

		if ( ! $rendered ) {
			$this->render_loop_context_placeholder(
				__( 'Homepage Search', 'directorist-elementor' ),
				__( 'Unable to render the homepage search form for the selected directory context.', 'directorist-elementor' )
			);
		}
	}

	/**
	 * Build wrapper classes.
	 *
	 * @return array<int,string>
	 */
	protected function get_homepage_search_classes(): array {
		return [
			'directorist-elementor-listings-search',
			'directorist-elementor-listings-archive-search',
			'directorist-elementor-homepage-search',
		];
	}

	/**
	 * Build wrapper attributes consumed by frontend search routing.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_homepage_search_attributes(): array {
		$bridge            = DirectoristBridge::get_instance();
		$directory_settings = $this->get_homepage_search_directory_settings();
		$directory_ids      = $directory_settings['directory_type_ids'];
		$default_id         = $directory_settings['default_directory_type_id'];
		$is_result_context  = $bridge->is_home_search_result_request() || $this->is_homepage_search_loop_context();

		return [
			'data-home-search-result-url'     => $bridge->get_home_search_result_url(),
			'data-home-search-result-context' => $is_result_context ? '1' : '0',
			'data-home-search-contract-status'=> $directory_settings['contract_status'],
			'data-directory-type-ids'         => implode( ',', $directory_ids ),
			'data-default-directory-type-id'  => $default_id > 0 ? (string) $default_id : '',
			'data-home-search-widget-id'      => (string) $this->get_id(),
			'data-home-search-post-id'        => (string) $this->resolve_homepage_search_document_id(),
		];
	}

	/**
	 * Resolve the document that owns this standalone widget.
	 *
	 * @return int
	 */
	protected function resolve_homepage_search_document_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $document ) {
				if ( method_exists( $document, 'get_main_id' ) ) {
					$document_id = absint( $document->get_main_id() );
					if ( $document_id > 0 ) {
						return $document_id;
					}
				}

				if ( method_exists( $document, 'get_id' ) ) {
					$document_id = absint( $document->get_id() );
					if ( $document_id > 0 ) {
						return $document_id;
					}
				}
			}
		}

		foreach ( [ 'editor_post_id', 'post_id', 'post' ] as $request_key ) {
			if ( empty( $_REQUEST[ $request_key ] ) ) {
				continue;
			}

			$document_id = absint( wp_unslash( $_REQUEST[ $request_key ] ) );
			if ( $document_id > 0 ) {
				return $document_id;
			}
		}

		return absint( get_the_ID() );
	}

	/**
	 * Render search UI and omit the submit action inside the instant result template.
	 *
	 * @param array<int,string>   $classes Additional wrapper classes.
	 * @param array<string,mixed> $attributes Additional wrapper attributes.
	 * @return bool
	 */
	protected function render_search_markup( array $classes = [], array $attributes = [] ): bool {
		$is_result_context = '1' === (string) ( $attributes['data-home-search-result-context'] ?? '' );

		return $this->render_search_composition( $classes, $attributes, $is_result_context );
	}

	/**
	 * Build loop settings used by standalone search renders.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_homepage_loop_settings(): array {
		$directory_settings = $this->get_homepage_search_directory_settings();
		$default_id         = $directory_settings['default_directory_type_id'];

		return [
			'query_mode'                  => 'default',
			'query_type'                  => 'regular',
			'directory_type_ids'          => $directory_settings['directory_type_ids'],
			'default_directory_type_id'   => $default_id,
			'active_directory_type_id'    => $default_id,
			'view_type'                   => 'grid',
			'columns'                     => 3,
			'listings_per_page'           => 1,
			'order_by'                    => 'date',
			'order'                       => 'DESC',
			'pagination_type'             => 'numbered',
			'directorist_elementor_source'=> 'homepage-search',
		];
	}

	/**
	 * Homepage Search should use the core search-form model so Directorist's
	 * home-search advanced-filter settings and labels are respected.
	 *
	 * @param object $controller Loop controller.
	 * @return Directorist_Listing_Search_Form|null
	 */
	protected function create_search_form( object $controller ): ?Directorist_Listing_Search_Form {
		if ( ! class_exists( Directorist_Listing_Search_Form::class ) ) {
			return null;
		}

		$search_field_atts = array_filter(
			(array) ( $controller->atts ?? [] ),
			static function( $key ): bool {
				return 0 === strpos( (string) $key, 'filter_' );
			},
			ARRAY_FILTER_USE_KEY
		);

		return new Directorist_Listing_Search_Form(
			'search_form',
			$this->get_controller_directory_type_id( $controller ),
			$search_field_atts
		);
	}

	/**
	 * Get directory settings for this render.
	 *
	 * @return array{directory_type_ids:array<int,int>,default_directory_type_id:int,contract_status:string}
	 */
	protected function get_homepage_search_directory_settings(): array {
		if ( $this->is_homepage_search_loop_context() ) {
			$loop_context  = $this->get_loop_context();
			$loop_settings = is_array( $loop_context['settings'] ?? null ) ? (array) $loop_context['settings'] : [];
			$directory_ids = $this->normalize_directory_ids( $loop_settings['directory_type_ids'] ?? $loop_context['directory_type_ids'] ?? [] );
			$default_id    = $this->resolve_default_directory_id(
				$directory_ids,
				absint( $loop_settings['default_directory_type_id'] ?? $loop_context['active_directory'] ?? 0 )
			);

			return [
				'directory_type_ids'        => $directory_ids,
				'default_directory_type_id' => $default_id,
				'contract_status'           => 'inline',
			];
		}

		$contract      = DirectoristBridge::get_instance()->resolve_home_search_contract();
		$directory_ids = $this->normalize_directory_ids( $contract['directory_type_ids'] ?? [] );
		$default_id    = $this->resolve_default_directory_id( $directory_ids, absint( $contract['default_directory_type_id'] ?? 0 ) );
		$preview_id    = $this->is_editor_context()
			? absint( $this->get_settings( 'standalone_design_directory_type_id' ) )
			: 0;

		if ( $preview_id > 0 && ( empty( $directory_ids ) || in_array( $preview_id, $directory_ids, true ) ) ) {
			$default_id = $preview_id;
		}

		return [
			'directory_type_ids'        => $directory_ids,
			'default_directory_type_id' => $default_id,
			'contract_status'           => sanitize_key( (string) ( $contract['status'] ?? 'missing_template' ) ),
		];
	}

	/**
	 * Render template-owned Directory Types before standalone search forms.
	 *
	 * @return void
	 */
	protected function render_before_search_form(): void {
		$bridge = DirectoristBridge::get_instance();
		$action = isset( $_REQUEST['action'] )
			? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) )
			: '';

		if (
			$this->is_homepage_search_loop_context() ||
			$bridge->is_home_search_result_request() ||
			'directorist_elementor_v4_render_homepage_search_form' === $action
		) {
			return;
		}

		$contract           = $bridge->resolve_home_search_contract();
		$element            = is_array( $contract['directory_types_element'] ?? null ) ? (array) $contract['directory_types_element'] : [];
		$directory_settings = $this->get_homepage_search_directory_settings();
		$directory_ids      = $directory_settings['directory_type_ids'];
		$default_id         = $directory_settings['default_directory_type_id'];

		if ( empty( $element ) || empty( $directory_ids ) ) {
			return;
		}

		$bridge->enqueue_home_search_contract_styles( $contract );

		$loop_settings = is_array( $contract['loop_settings'] ?? null ) ? (array) $contract['loop_settings'] : [];
		$loop_settings['query_mode'] = 'default';
		$loop_settings['query_type'] = 'regular';
		$loop_settings['directory_type_ids'] = $directory_ids;
		$loop_settings['default_directory_type_id'] = $default_id;
		$loop_settings['active_directory_type_id'] = $default_id;
		$loop_settings['directorist_elementor_source'] = 'homepage-search-loop';

		$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
			$loop_settings,
			InstanceState::get_instance()->normalize_instance_id( 'direl-home-search-directory-types' ),
			$this->is_editor_context(),
			$this->is_editor_context() ? 1 : null
		);
		$runtime_state['active_directory'] = $default_id;

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			echo '<div class="directorist-elementor-homepage-search__directory-types">';
			echo ElementTreeRenderService::get_instance()->render_element( $element ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor renders saved Directory Types widget output.
			echo '</div>';
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}
	}


	/**
	 * Standalone Homepage Search can render template-owned Directory Types even
	 * before search fields are composed.
	 *
	 * @return bool
	 */
	protected function should_render_empty_search_composition(): bool {
		return ! $this->is_homepage_search_loop_context();
	}

	/**
	 * Homepage Search submits to the canonical result route.
	 *
	 * @return string
	 */
	protected function get_search_action_url(): string {
		return DirectoristBridge::get_instance()->get_home_search_result_url();
	}

	/**
	 * Standalone Homepage Search mirrors the result template composition.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements_for_directory( int $directory_type_id ): array {
		if ( $this->is_homepage_search_loop_context() ) {
			return parent::get_render_child_raw_elements_for_directory( $directory_type_id );
		}

		$contract = DirectoristBridge::get_instance()->resolve_home_search_contract();
		$settings = is_array( $contract['search_settings'] ?? null ) ? (array) $contract['search_settings'] : [];
		$elements = is_array( $contract['search_elements'] ?? null ) ? (array) $contract['search_elements'] : [];

		if ( empty( $contract['configured'] ) && empty( $settings ) && empty( $elements ) ) {
			return parent::get_render_child_raw_elements_for_directory( $directory_type_id );
		}

		$canonical_elements = $this->resolve_search_children_from_payload(
			$settings,
			$elements,
			$directory_type_id > 0 ? $directory_type_id : $this->get_active_search_directory_type_id()
		);
		$local_elements = parent::get_render_child_raw_elements_for_directory( $directory_type_id );

		return $this->merge_standalone_search_child_designs( $canonical_elements, $local_elements );
	}

	/**
	 * Merge standalone child changes over the canonical result-template branch.
	 *
	 * Each projected child stores the canonical settings it inherited. Only
	 * values changed from that snapshot survive as standalone overrides.
	 *
	 * @param array<int,array<string,mixed>> $canonical_elements Canonical children.
	 * @param array<int,array<string,mixed>> $local_elements Standalone projections.
	 * @return array<int,array<string,mixed>>
	 */
	protected function merge_standalone_search_child_designs( array $canonical_elements, array $local_elements ): array {
		$local_by_identity = [];
		foreach ( $local_elements as $index => $local_element ) {
			if ( ! is_array( $local_element ) ) {
				continue;
			}

			$identity = $this->get_search_child_identity( $local_element, (int) $index );
			$local_by_identity[ $identity ][] = $local_element;
		}

		$merged = [];
		foreach ( $canonical_elements as $index => $canonical_element ) {
			if ( ! is_array( $canonical_element ) ) {
				continue;
			}

			$identity      = $this->get_search_child_identity( $canonical_element, (int) $index );
			$local_element = ! empty( $local_by_identity[ $identity ] )
				? array_shift( $local_by_identity[ $identity ] )
				: [];
			$canonical_settings = is_array( $canonical_element['settings'] ?? null ) ? (array) $canonical_element['settings'] : [];
			$local_settings     = is_array( $local_element['settings'] ?? null ) ? (array) $local_element['settings'] : [];
			$inherited_settings = $this->decode_inherited_search_settings( $local_settings[ self::INHERITED_SETTINGS_KEY ] ?? '' );
			$overrides          = [];

			if ( ! empty( $inherited_settings ) ) {
				foreach ( $local_settings as $setting_key => $setting_value ) {
					if (
						self::INHERITED_SETTINGS_KEY === $setting_key ||
						$this->is_protected_search_child_setting( (string) $setting_key )
					) {
						continue;
					}

					if (
						! array_key_exists( $setting_key, $inherited_settings ) ||
						wp_json_encode( $setting_value ) !== wp_json_encode( $inherited_settings[ $setting_key ] )
					) {
						$overrides[ $setting_key ] = $setting_value;
					}
				}
			}

			$next             = $canonical_element;
			$next['settings'] = array_replace_recursive( $canonical_settings, $overrides );
			$next['settings'][ self::INHERITED_SETTINGS_KEY ] = wp_json_encode( $canonical_settings );

			if ( ! empty( $local_element['id'] ) ) {
				$next['id'] = (string) $local_element['id'];
			}

			$merged[] = $next;
		}

		return $merged;
	}

	/**
	 * Build a stable identity for one canonical search child.
	 *
	 * @param array<string,mixed> $element Child payload.
	 * @param int                 $index Fallback index.
	 * @return string
	 */
	protected function get_search_child_identity( array $element, int $index ): string {
		$settings    = is_array( $element['settings'] ?? null ) ? (array) $element['settings'] : [];
		$widget_type = sanitize_key( (string) ( $element['widgetType'] ?? $element['elType'] ?? '' ) );
		$field_key   = sanitize_key( (string) ( $settings['directorist_search_field_key'] ?? '' ) );
		$custom_key  = sanitize_text_field( (string) ( $settings['custom_field'] ?? '' ) );

		if ( '' !== $field_key ) {
			return $widget_type . '|field:' . $field_key;
		}

		if ( '' !== $custom_key ) {
			return $widget_type . '|custom:' . $custom_key;
		}

		return $widget_type . '|index:' . $index;
	}

	/**
	 * Decode a projected child's inherited canonical settings.
	 *
	 * @param mixed $value Encoded settings.
	 * @return array<string,mixed>
	 */
	protected function decode_inherited_search_settings( $value ): array {
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
	 * Keep composition and field bindings owned by the result template.
	 *
	 * @param string $setting_key Setting key.
	 * @return bool
	 */
	protected function is_protected_search_child_setting( string $setting_key ): bool {
		return in_array(
			$setting_key,
			[
				'_title',
				'directorist_search_context',
				'directorist_search_field_key',
				'directorist_search_field_label',
				'directorist_search_widget_name',
				'directorist_search_design_type',
				'directory_type_id',
				'custom_field',
				'button_text',
				'show_icon',
				'icon_only',
			],
			true
		);
	}

	/**
	 * Check whether this widget is rendering inside Homepage Search Loop.
	 *
	 * @return bool
	 */
	protected function is_homepage_search_loop_context(): bool {
		$loop_context = $this->get_loop_context();
		$loop_source  = (string) (
			$loop_context['query_args']['directorist_elementor_source']
			?? $loop_context['settings']['directorist_elementor_source']
			?? ''
		);

		return 'homepage-search-loop' === $loop_source;
	}

	/**
	 * Determine whether standalone Directory Types design controls are useful.
	 *
	 * @return bool
	 */
	protected function should_show_directory_types_style_controls(): bool {
		$contract      = DirectoristBridge::get_instance()->resolve_home_search_contract();
		$directory_ids = $this->get_home_search_contract_directory_ids( $contract );

		if ( empty( $directory_ids ) ) {
			$directory_ids = array_values(
				array_filter(
					array_map(
						'absint',
						array_keys( DirectoristBridge::get_instance()->get_directory_options() )
					)
				)
			);
		}

		return count( array_unique( $directory_ids ) ) > 1;
	}

	/**
	 * Resolve directory IDs from the homepage-search contract.
	 *
	 * @param array<string,mixed> $contract Homepage search contract.
	 * @return array<int,int>
	 */
	protected function get_home_search_contract_directory_ids( array $contract ): array {
		$directory_ids = $this->normalize_directory_ids( $contract['directory_type_ids'] ?? [] );

		if ( ! empty( $directory_ids ) ) {
			return $directory_ids;
		}

		return $this->normalize_directory_ids(
			[
				$contract['default_directory_type_id'] ?? 0,
				$contract['directory_type_id'] ?? 0,
			]
		);
	}

	/**
	 * Normalize selected directory IDs.
	 *
	 * @param mixed $value Raw setting.
	 * @return array<int,int>
	 */
	protected function normalize_directory_ids( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Resolve default directory from selected IDs.
	 *
	 * @param array<int,int> $directory_ids Selected IDs.
	 * @param int            $default_id Saved default.
	 * @return int
	 */
	protected function resolve_default_directory_id( array $directory_ids, int $default_id ): int {
		if ( $default_id > 0 && ( empty( $directory_ids ) || in_array( $default_id, $directory_ids, true ) ) ) {
			return $default_id;
		}

		return ! empty( $directory_ids[0] ) ? (int) $directory_ids[0] : 0;
	}
}
