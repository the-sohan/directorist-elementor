<?php
/**
 * Pricing plans composer element.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Pricing;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\PricingPlanRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Includes\Elements\Container;
use Elementor\Modules\NestedElements\Controls\Control_Nested_Repeater;
use Elementor\Repeater;

class PricingPlansWidget extends Container {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	public static function get_type() {
		return 'directorist_pricing_plans';
	}

	public function get_name() {
		return static::get_type();
	}

	public function get_title() {
		return __( 'Pricing Plans', 'directorist-elementor' );
	}

	public function get_icon() {
		return 'directorist-eicon directorist-eicon--pricing';
	}

	public function get_keywords(): array {
		return [ 'directorist', 'directory', 'listing', 'pricing', 'plans', 'package', 'membership' ];
	}

	public function get_panel_presets() {
		return [];
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function get_default_children_elements() {
		$rows = $this->get_default_plan_rows();

		if ( empty( $rows ) ) {
			$rows = [
				[
					'plan_id' => 0,
					'label'   => __( 'Pricing Plan', 'directorist-elementor' ),
				],
			];
		}

		return array_map(
			function( array $row ): array {
				return [
					'elType'   => 'container',
					'isInner'  => true,
					'settings' => [
						'_title' => (string) ( $row['label'] ?? __( 'Pricing Plan', 'directorist-elementor' ) ),
					],
					'elements' => $this->get_default_pricing_field_elements( absint( $row['plan_id'] ?? 0 ) ),
				];
			},
			$rows
		);
	}

	protected function get_default_repeater_title_setting_key() {
		return 'label';
	}

	protected function get_default_children_title() {
		return __( 'Pricing Plan', 'directorist-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-pricing-plans__storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-pricing-plans__card-canvas';
	}

	protected function get_initial_config() {
		$config           = parent::get_initial_config();
		$default_children = $this->get_default_children_elements();

		$config['show_in_panel'] = true;
		$config['categories'] = [ CategoryRegistrar::CATEGORY_ARCHIVE ];
		$config['title'] = $this->get_title();
		$config['icon'] = 'directorist-eicon directorist-eicon--pricing';
		$config['include_in_widgets_config'] = true;
		$config['default_children'] = $default_children;
		$config['defaults'] = array_merge(
			is_array( $config['defaults'] ?? null ) ? $config['defaults'] : [],
			[
				'elements'                             => $default_children,
				'elements_title'                       => $this->get_default_children_title(),
				'elements_placeholder_selector'        => $this->get_default_children_placeholder_selector(),
				'child_container_placeholder_selector' => $this->get_default_children_container_placeholder_selector(),
				'repeater_title_setting'               => $this->get_default_repeater_title_setting_key(),
			]
		);
		$config['support_improved_repeaters'] = true;
		$config['target_container'] = [ '.directorist-elementor-pricing-plans__storage' ];
		$config['node'] = 'div';
		$config['is_interlaced'] = true;
		$config['directorist_default_plan_elements'] = $this->get_default_pricing_field_elements();
		$config['directorist_pricing_plan_meta'] = $this->get_editor_plan_meta();

		return $config;
	}

	protected function register_controls() {
		$this->register_widget_controls();
		parent::register_controls();
	}

	protected function register_widget_controls(): void {
		$plan_options = PricingPlanRenderService::get_instance()->get_plan_options();

		$this->start_controls_section(
			'section_pricing_plans_content',
			[
				'label' => __( 'Pricing Plans', 'directorist-elementor' ),
			]
		);

		if ( ! PricingPlanRenderService::get_instance()->is_active() ) {
			$this->add_control(
				'pricing_plans_inactive_notice',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'Directorist Pricing Plans must be installed and active to render this widget.', 'directorist-elementor' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				]
			);
		} elseif ( empty( $plan_options ) ) {
			$this->add_control(
				'pricing_plans_empty_notice',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => esc_html__( 'No published pricing plans were found. Create plans before composing this widget.', 'directorist-elementor' ),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				]
			);
		}

		$repeater = new Repeater();

		$repeater->add_control(
			'plan_id',
			[
				'label'       => __( 'Plan', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'options'     => $plan_options,
			]
		);

		$repeater->add_control(
			'label',
			[
				'label'       => __( 'Canvas Label', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Pricing Plan', 'directorist-elementor' ),
				'label_block' => true,
			]
		);

		$this->add_control(
			'plans',
			[
				'label'       => __( 'Plan Templates', 'directorist-elementor' ),
				'type'        => Control_Nested_Repeater::CONTROL_TYPE,
				'fields'      => $repeater->get_controls(),
				'default'     => $this->get_default_plan_rows(),
				'title_field' => '{{{ label || "Pricing Plan" }}}',
				'button_text' => __( 'Add Plan', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'enable_duration_tabs',
			[
				'label'        => __( 'Enable Tabs', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'tab_type',
			[
				'label'     => __( 'Group By', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'duration',
				'options'   => [
					'duration'     => __( 'Duration', 'directorist-elementor' ),
					'package_type' => __( 'Package Type', 'directorist-elementor' ),
				],
				'condition' => [
					'enable_duration_tabs' => 'yes',
				],
			]
		);

		$this->add_control(
			'default_duration_key',
			[
				'label'       => __( 'Default Duration', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [ '' => __( 'First Matching Duration', 'directorist-elementor' ) ] + $this->get_tab_options_from_plan_options( $plan_options, 'duration' ),
				'description' => __( 'Used when tabs are grouped by duration.', 'directorist-elementor' ),
				'condition'   => [
					'enable_duration_tabs' => 'yes',
					'tab_type'             => 'duration',
				],
			]
		);

		$this->add_control(
			'default_package_type_key',
			[
				'label'       => __( 'Default Package Type', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [ '' => __( 'First Matching Package Type', 'directorist-elementor' ) ] + $this->get_tab_options_from_plan_options( $plan_options, 'package_type' ),
				'description' => __( 'Used when tabs are grouped by package type.', 'directorist-elementor' ),
				'condition'   => [
					'enable_duration_tabs' => 'yes',
					'tab_type'             => 'package_type',
				],
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => __( 'Columns', 'directorist-elementor' ),
				'type'           => Controls_Manager::SELECT,
				'default'        => '3',
				'tablet_default' => '2',
				'mobile_default' => '1',
				'options'        => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'selectors'      => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans' => '--direl-pricing-plans-columns: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [
					'size' => 30,
					'unit' => 'px',
				],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 120,
					],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans' => '--direl-pricing-plans-gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->register_container_style_controls();
		$this->register_grid_style_controls();
		$this->register_card_style_controls();
		$this->register_tabs_style_controls();
	}

	/**
	 * Register container style controls.
	 *
	 * @return void
	 */
	protected function register_container_style_controls(): void {
		$this->start_controls_section(
			'section_pricing_plans_container_style',
			[
				'label' => __( 'Container', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'container_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans',
			]
		);

		$this->add_responsive_control(
			'container_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
					'{{WRAPPER}} .directorist-elementor-pricing-plans' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register grid style controls.
	 *
	 * @return void
	 */
	protected function register_grid_style_controls(): void {
		$this->start_controls_section(
			'section_pricing_plans_grid_style',
			[
				'label' => __( 'Plans Grid', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'grid_justify_content',
			[
				'label'     => __( 'Justify Content', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => [
					'start'  => [
						'title' => __( 'Start', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center' => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-center',
					],
					'end'    => [
						'title' => __( 'End', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__grid' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'grid_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__grid' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register card style controls.
	 *
	 * @return void
	 */
	protected function register_card_style_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_card_style',
			[
				'label' => __( 'Plan Card', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__card',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__card',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__card',
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__card-inner' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register plan tab style controls.
	 *
	 * @return void
	 */
	protected function register_tabs_style_controls(): void {
		$this->start_controls_section(
			'section_pricing_plan_tabs_style',
			[
				'label'     => __( 'Plan Tabs', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'enable_duration_tabs' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'tabs_alignment',
			[
				'label'     => __( 'Alignment', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'center',
				'options'   => [
					'flex-start' => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'center'     => [
						'title' => __( 'Center', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-center',
					],
					'flex-end'   => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__tabs-wrap' => 'justify-content: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tabs_container_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tabs',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'tabs_container_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tabs',
			]
		);

		$this->add_responsive_control(
			'tabs_container_padding',
			[
				'label'      => __( 'Container Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tabs' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'tab_text_color',
			[
				'label'     => __( 'Tab Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tab' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'active_tab_text_color',
			[
				'label'     => __( 'Active Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tab.is-active' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'active_tab_background_color',
			[
				'label'     => __( 'Active Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tab.is-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tabs_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tab',
			]
		);

		$this->add_responsive_control(
			'tab_padding',
			[
				'label'      => __( 'Tab Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-pricing-plans__duration-tab' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget.
	 *
	 * @return void
	 */
	protected function print_content() {
		if ( ! PricingPlanRenderService::get_instance()->is_active() ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Pricing Plans', 'directorist-elementor' ),
						__( 'Directorist Pricing Plans must be installed and active to render this widget.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		PricingPlanRenderService::get_instance()->enqueue_assets();

		$settings      = $this->get_settings_for_display();
		$children      = array_values( $this->get_children() );
		$rows          = $this->normalize_plan_rows( $settings['plans'] ?? [] );
		$prepared_rows = $this->prepare_plan_rows( $rows );

		if ( empty( $prepared_rows ) ) {
			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Pricing Plans', 'directorist-elementor' ),
						__( 'Select one or more pricing plans to compose this widget.', 'directorist-elementor' )
					)
				);
			}

			return;
		}

		$tab_type        = PricingPlanRenderService::get_instance()->normalize_tab_type( (string) ( $settings['tab_type'] ?? 'duration' ) );
		$tab_options     = $this->get_tab_options_from_prepared_rows( $prepared_rows, $tab_type );
		$enable_tabs     = 'yes' === (string) ( $settings['enable_duration_tabs'] ?? '' ) && count( $tab_options ) > 1;
		$default_tab_key = 'package_type' === $tab_type
			? sanitize_key( (string) ( $settings['default_package_type_key'] ?? ( $settings['default_tab_key'] ?? '' ) ) )
			: sanitize_key( (string) ( $settings['default_duration_key'] ?? ( $settings['default_tab_key'] ?? '' ) ) );

		if ( $enable_tabs && ( '' === $default_tab_key || ! isset( $tab_options[ $default_tab_key ] ) ) ) {
			$tab_keys        = array_keys( $tab_options );
			$default_tab_key = (string) ( $tab_keys[0] ?? '' );
		}

		$default_duration_key = 'duration' === $tab_type ? $default_tab_key : '';

		$instance_id = InstanceState::get_instance()->normalize_instance_id( 'direl-pricing-plans-' . $this->get_id() );
		$attributes  = InstanceState::get_instance()->build_widget_root_attributes(
			$instance_id,
			'pricing-plans',
			[
				'class'                     => [
					'directorist-elementor-pricing-plans',
					$enable_tabs ? 'directorist-elementor-pricing-plans--tabs-enabled' : '',
				],
				'data-plan-tabs'            => $enable_tabs ? 'enabled' : 'disabled',
				'data-tab-type'             => $tab_type,
				'data-default-tab-key'      => $default_tab_key,
				'data-duration-tabs'        => $enable_tabs ? 'enabled' : 'disabled',
				'data-default-duration-key' => $default_duration_key,
			]
		);

		echo '<div ' . $this->format_html_attributes( $attributes ) . '>';

		if ( $enable_tabs ) {
			$this->render_plan_tabs( $tab_options, $default_tab_key, $tab_type );
		}

		echo '<div class="directorist-elementor-pricing-plans__grid">';

		foreach ( $prepared_rows as $index => $row ) {
			$this->render_plan_card( $children, $index, $row, $enable_tabs, $default_tab_key, $tab_type );
		}

		echo '</div>';

		if ( $this->is_editor_context() ) {
			echo '<div class="directorist-elementor-pricing-plans__storage" aria-hidden="true"></div>';
		}

		echo '</div>';
	}

	/**
	 * Render plan tabs.
	 *
	 * @param array<string,string> $tab_options Tab options.
	 * @param string               $default_tab_key Default tab key.
	 * @param string               $tab_type Tab grouping type.
	 * @return void
	 */
	protected function render_plan_tabs( array $tab_options, string $default_tab_key, string $tab_type = 'duration' ): void {
		echo '<div class="directorist-elementor-pricing-plans__tabs-wrap">';
		echo '<div class="directorist-elementor-pricing-plans__duration-tabs" role="tablist">';

		foreach ( $tab_options as $tab_key => $tab_label ) {
			$is_active = $tab_key === $default_tab_key;

			printf(
				'<button type="button" class="directorist-elementor-pricing-plans__duration-tab%1$s" data-tab-key="%2$s" data-duration-key="%3$s" role="tab" aria-selected="%4$s" tabindex="%5$s">%6$s</button>',
				$is_active ? ' is-active' : '',
				esc_attr( $tab_key ),
				esc_attr( 'duration' === $tab_type ? $tab_key : '' ),
				$is_active ? 'true' : 'false',
				$is_active ? '0' : '-1',
				esc_html( $tab_label )
			);
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Template for a newly inserted pricing plan row in the editor.
	 *
	 * @return void
	 */
	protected function content_template_single_repeater_item() {
		?>
		<#
		const planIndex = view.collection.length;
		const item = data || {};
		const planId = String( item.plan_id || '0' );
		const planLabel = item.label || '<?php echo esc_js( __( 'Pricing Plan', 'directorist-elementor' ) ); ?> ' + ( planIndex + 1 );
		const planKey = 'directorist-pricing-plan-row-' + planIndex;

		view.addRenderAttribute( planKey, {
			'class': [ 'directorist-elementor-pricing-plans__card' ],
			'data-plan-id': planId,
			'data-tab-key': '',
			'data-duration-key': '',
			'data-package-type-key': '',
			'data-direl-plan-index': planIndex,
			'data-direl-plan-title': planLabel,
		}, null, true );
		#>
		<div {{{ view.getRenderAttributeString( planKey ) }}}>
			<div class="directorist-elementor-pricing-plans__card-inner">
				<div class="directorist-elementor-pricing-plans__card-canvas"></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Editor template.
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<#
		const pricingConfig = ( view.model && view.model.config ) || {};
		const planMeta = pricingConfig.directorist_pricing_plan_meta || {};
		const rawPlans = settings.plans || [];
		const planRows = Array.isArray( rawPlans ) ? rawPlans : ( rawPlans.toJSON ? rawPlans.toJSON() : [] );
		const preparedPlans = planRows.map( function( item, index ) {
			const planId = String( item.plan_id || '0' );
			const meta = planMeta[ planId ] || {};
			const label = item.label || meta.label || '<?php echo esc_js( __( 'Pricing Plan', 'directorist-elementor' ) ); ?> ' + ( index + 1 );

			return {
				index: index,
				planId: planId,
				label: label,
				durationKey: meta.duration_key || '',
				durationLabel: meta.duration_label || label,
				packageTypeKey: meta.package_type_key || 'package',
				packageTypeLabel: meta.package_type_label || meta.type_label || '<?php echo esc_js( __( 'Package', 'directorist-elementor' ) ); ?>',
				recommended: !! meta.is_marked_recommended,
			};
		} ).filter( function( item ) {
			return '0' !== item.planId;
		} );
		const tabType = 'package_type' === settings.tab_type ? 'package_type' : 'duration';
		const tabOptions = [];
		const tabKeys = {};
		const getItemTabKey = function( item ) {
			return 'package_type' === tabType ? item.packageTypeKey : item.durationKey;
		};
		const getItemTabLabel = function( item ) {
			return 'package_type' === tabType ? item.packageTypeLabel : item.durationLabel;
		};

		preparedPlans.forEach( function( item ) {
			const tabKey = getItemTabKey( item );
			if ( ! tabKey || tabKeys[ tabKey ] ) {
				return;
			}

			tabKeys[ tabKey ] = true;
			tabOptions.push( {
				key: tabKey,
				label: getItemTabLabel( item ),
			} );
		} );

		const tabsEnabled = 'yes' === settings.enable_duration_tabs && tabOptions.length > 1;
		let defaultTabKey = 'package_type' === tabType
			? ( settings.default_package_type_key || settings.default_tab_key || '' )
			: ( settings.default_duration_key || settings.default_tab_key || '' );

		if ( tabsEnabled && ( ! defaultTabKey || ! tabKeys[ defaultTabKey ] ) ) {
			defaultTabKey = tabOptions[0] ? tabOptions[0].key : '';
		}
		const defaultDurationKey = 'duration' === tabType ? defaultTabKey : '';
		#>
		<div
			class="directorist-elementor-pricing-plans directorist-elementor-pricing-plans--editor<# if ( tabsEnabled ) { #> directorist-elementor-pricing-plans--tabs-enabled<# } #>"
			data-plan-tabs="{{ tabsEnabled ? 'enabled' : 'disabled' }}"
			data-tab-type="{{ tabType }}"
			data-default-tab-key="{{ defaultTabKey }}"
			data-duration-tabs="{{ tabsEnabled ? 'enabled' : 'disabled' }}"
			data-default-duration-key="{{ defaultDurationKey }}"
		>
			<div class="directorist-elementor-pricing-plans__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-pricing-plans__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the pricing plans preview from the current widget settings.', 'directorist-elementor' ); ?></p>
				</div>
			</div>

			<div class="directorist-elementor-pricing-plans__grid directorist-elementor-pricing-plans__storage" aria-hidden="true">
				<# if ( preparedPlans.length ) { #>
					<# _.each( preparedPlans, function( item ) {
						const itemTabKey = getItemTabKey( item );
						const isHidden = tabsEnabled && defaultTabKey && itemTabKey !== defaultTabKey;
						const planKey = 'directorist-pricing-plan-row-' + item.index;
						const planAttributes = {
							'class': [
								'directorist-elementor-pricing-plans__card',
								item.recommended ? 'directorist-elementor-pricing-plans__card--recommended' : '',
								tabsEnabled && ! isHidden ? 'is-active' : '',
							],
							'data-plan-id': item.planId,
							'data-tab-key': itemTabKey,
							'data-duration-key': item.durationKey,
							'data-package-type-key': item.packageTypeKey,
							'data-direl-plan-index': item.index,
							'data-direl-plan-title': item.label,
						};

						if ( isHidden ) {
							planAttributes.hidden = 'hidden';
						}

						view.addRenderAttribute( planKey, planAttributes, null, true );
					#>
						<div {{{ view.getRenderAttributeString( planKey ) }}}>
							<div class="directorist-elementor-pricing-plans__card-inner">
								<div class="directorist-elementor-pricing-plans__card-canvas"></div>
							</div>
						</div>
					<# } ); #>
				<# } else { #>
					<div class="directorist-elementor-placeholder">
						<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Pricing Plans', 'directorist-elementor' ); ?></p>
						<p><?php echo esc_html__( 'Select one or more pricing plans to compose this widget.', 'directorist-elementor' ); ?></p>
					</div>
				<# } #>
			</div>
		</div>
		<?php
	}

	/**
	 * Render one plan card.
	 *
	 * @param array<int,mixed>    $children Child elements.
	 * @param int                 $index Plan index.
	 * @param array<string,mixed> $row Prepared row.
	 * @param bool                $tabs_enabled Whether tabs are enabled.
	 * @param string              $default_tab_key Default tab key.
	 * @param string              $tab_type Tab grouping type.
	 * @return void
	 */
	protected function render_plan_card( array $children, int $index, array $row, bool $tabs_enabled, string $default_tab_key, string $tab_type = 'duration' ): void {
		$plan_id          = absint( $row['plan_id'] ?? 0 );
		$data             = is_array( $row['data'] ?? null ) ? (array) $row['data'] : [];
		$duration_key     = sanitize_key( (string) ( $data['duration_key'] ?? '' ) );
		$package_type_key = sanitize_key( (string) ( $data['package_type_key'] ?? '' ) );
		$tab_type         = PricingPlanRenderService::get_instance()->normalize_tab_type( $tab_type );
		$tab_key          = PricingPlanRenderService::get_instance()->tab_key( $data, $tab_type );
		$is_hidden        = $tabs_enabled && '' !== $default_tab_key && $tab_key !== $default_tab_key;
		$classes          = [
			'directorist-elementor-pricing-plans__card',
		];

		if ( ! empty( $data['is_marked_recommended'] ) ) {
			$classes[] = 'directorist-elementor-pricing-plans__card--recommended';
		}

		if ( $tabs_enabled && ! $is_hidden ) {
			$classes[] = 'is-active';
		}

		printf(
			'<div class="%1$s" data-plan-id="%2$d" data-tab-key="%3$s" data-duration-key="%4$s" data-package-type-key="%5$s"%6$s>',
			esc_attr( implode( ' ', $classes ) ),
			$plan_id,
			esc_attr( $tab_key ),
			esc_attr( $duration_key ),
			esc_attr( $package_type_key ),
			$is_hidden ? ' hidden' : ''
		);

		echo '<div class="directorist-elementor-pricing-plans__card-inner">';

		RenderContext::get_instance()->push_pricing_plan_context(
			$plan_id,
			$index,
			[
				'data'             => $data,
				'duration_key'     => $duration_key,
				'package_type_key' => $package_type_key,
				'tab_key'          => $tab_key,
				'tab_type'         => $tab_type,
			]
		);

		try {
			$rendered = $this->render_plan_child( $children, $index );

			if ( '' !== trim( $rendered ) ) {
				echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor child output is already rendered markup.
			} elseif ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Empty Pricing Plan', 'directorist-elementor' ),
						__( 'Add pricing plan field widgets into this plan canvas.', 'directorist-elementor' )
					)
				);
			}
		} finally {
			RenderContext::get_instance()->pop_pricing_plan_context();
		}

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Render child composition for a plan.
	 *
	 * @param array<int,mixed> $children Child elements.
	 * @param int              $index Plan index.
	 * @return string
	 */
	protected function render_plan_child( array $children, int $index ): string {
		$child = $children[ $index ] ?? null;

		if ( $this->is_editor_context() && is_object( $child ) && method_exists( $child, 'print_element' ) ) {
			ob_start();
			$child->print_element();

			return trim( (string) ob_get_clean() );
		}

		$raw_children = $this->get_render_child_raw_elements();

		if ( isset( $raw_children[ $index ] ) && is_array( $raw_children[ $index ] ) && $this->has_raw_composition_content( $raw_children[ $index ] ) ) {
			$fresh_element = ElementTreeRenderService::get_instance()->create_element_instance( $raw_children[ $index ] );

			if ( $fresh_element && method_exists( $fresh_element, 'print_element' ) ) {
				ob_start();
				$fresh_element->print_element();

				return trim( (string) ob_get_clean() );
			}
		}

		if ( is_object( $child ) && $this->has_composition_content( $child ) && method_exists( $child, 'print_element' ) ) {
			ob_start();
			$child->print_element();

			return trim( (string) ob_get_clean() );
		}

		return '';
	}

	/**
	 * Get cached raw child payloads.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements(): array {
		if ( is_array( $this->render_child_raw_elements ) ) {
			return $this->render_child_raw_elements;
		}

		$raw_data = $this->get_current_widget_raw_data();

		if ( null === $raw_data ) {
			$raw_data = $this->get_raw_data();
		}

		$raw_children = array_values( (array) ( $raw_data['elements'] ?? [] ) );
		$normalized   = [];

		foreach ( $raw_children as $raw_child ) {
			if ( is_array( $raw_child ) ) {
				$normalized[] = $raw_child;
			}
		}

		$this->render_child_raw_elements = $normalized;

		return $this->render_child_raw_elements;
	}

	/**
	 * Resolve current widget raw data from the active document.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function get_current_widget_raw_data(): ?array {
		if ( $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_elements_data' ) ) {
			return null;
		}

		$element_data = $this->find_raw_element_by_id(
			(array) $current_document->get_elements_data(),
			(string) $this->get_id()
		);

		return is_array( $element_data ) ? $element_data : null;
	}

	/**
	 * Recursively find raw element by id.
	 *
	 * @param array<int,array<string,mixed>> $elements Elements.
	 * @param string                         $target_id Target id.
	 * @return array<string,mixed>|null
	 */
	protected function find_raw_element_by_id( array $elements, string $target_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $element;
			}

			$found = $this->find_raw_element_by_id( (array) ( $element['elements'] ?? [] ), $target_id );

			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Check raw composition content.
	 *
	 * @param array<string,mixed> $element_data Raw element.
	 * @return bool
	 */
	protected function has_raw_composition_content( array $element_data ): bool {
		if ( 'container' !== (string) ( $element_data['elType'] ?? '' ) ) {
			return true;
		}

		foreach ( (array) ( $element_data['elements'] ?? [] ) as $child ) {
			if ( is_array( $child ) && $this->has_raw_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Normalize plan repeater rows.
	 *
	 * @param mixed $rows Raw rows.
	 * @return array<int,array<string,mixed>>
	 */
	protected function normalize_plan_rows( $rows ): array {
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			$rows = $this->get_default_plan_rows();
		}

		$normalized = [];
		$seen       = [];

		foreach ( array_values( $rows ) as $index => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$plan_id = absint( $row['plan_id'] ?? 0 );
			if ( $plan_id <= 0 || in_array( $plan_id, $seen, true ) ) {
				continue;
			}

			$label = trim( (string) ( $row['label'] ?? '' ) );
			if ( '' === $label ) {
				$label = sprintf(
					/* translators: %d: row number. */
					__( 'Plan %d', 'directorist-elementor' ),
					$index + 1
				);
			}

			$normalized[] = [
				'plan_id' => $plan_id,
				'label'   => $label,
			];
			$seen[]       = $plan_id;
		}

		return $normalized;
	}

	/**
	 * Prepare valid plan rows.
	 *
	 * @param array<int,array<string,mixed>> $rows Rows.
	 * @return array<int,array<string,mixed>>
	 */
	protected function prepare_plan_rows( array $rows ): array {
		$prepared = [];

		foreach ( $rows as $row ) {
			$plan_id = absint( $row['plan_id'] ?? 0 );
			$data    = PricingPlanRenderService::get_instance()->prepare( $plan_id );

			if ( ! $data ) {
				continue;
			}

			$row['data'] = $data;
			$prepared[]  = $row;
		}

		return $prepared;
	}

	/**
	 * Get default plan rows.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_plan_rows(): array {
		$options = PricingPlanRenderService::get_instance()->get_plan_options( 3 );
		$rows    = [];

		foreach ( $options as $plan_id => $label ) {
			$rows[] = [
				'plan_id' => (string) absint( $plan_id ),
				'label'   => (string) $label,
			];
		}

		return $rows;
	}

	/**
	 * Get default field widget payloads for each plan canvas.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_pricing_field_elements( int $preview_plan_id = 0 ): array {
		return [
			$this->get_default_child_widget( 'directorist_pricing_plan_recommended_badge', __( 'Recommended Badge', 'directorist-elementor' ), $preview_plan_id ),
			$this->get_default_child_widget( 'directorist_pricing_plan_title', __( 'Plan Title', 'directorist-elementor' ), $preview_plan_id ),
			$this->get_default_child_widget( 'directorist_pricing_plan_description', __( 'Plan Description', 'directorist-elementor' ), $preview_plan_id ),
			$this->get_default_child_widget( 'directorist_pricing_plan_price', __( 'Plan Price', 'directorist-elementor' ), $preview_plan_id ),
			$this->get_default_child_widget( 'directorist_pricing_plan_features', __( 'Plan Features', 'directorist-elementor' ), $preview_plan_id ),
			$this->get_default_child_widget( 'directorist_pricing_plan_action_button', __( 'Action Button', 'directorist-elementor' ), $preview_plan_id ),
		];
	}

	/**
	 * Build default child widget payload.
	 *
	 * @param string $widget_type Widget type.
	 * @param string $title Widget title.
	 * @param int    $preview_plan_id Editor preview plan id.
	 * @return array<string,mixed>
	 */
	protected function get_default_child_widget( string $widget_type, string $title, int $preview_plan_id = 0 ): array {
		$settings = [
			'_title' => $title,
		];

		if ( $preview_plan_id > 0 ) {
			$settings['preview_plan_id'] = (string) $preview_plan_id;
		}

		return [
			'elType'          => 'widget',
			'widgetType'      => $widget_type,
			'settings'        => $settings,
			'elements'        => [],
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Get editor-safe metadata for pricing plan previews.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_editor_plan_meta(): array {
		$meta = [];

		foreach ( PricingPlanRenderService::get_instance()->get_plan_options() as $plan_id => $label ) {
			$data = PricingPlanRenderService::get_instance()->prepare( absint( $plan_id ) );

			$meta[ (string) absint( $plan_id ) ] = [
				'label'                 => (string) $label,
				'duration_key'          => $data ? sanitize_key( (string) ( $data['duration_key'] ?? '' ) ) : '',
				'duration_label'        => $data ? (string) ( $data['duration_label'] ?? '' ) : '',
				'package_type_key'      => $data ? sanitize_key( (string) ( $data['package_type_key'] ?? '' ) ) : '',
				'package_type_label'    => $data ? (string) ( $data['package_type_label'] ?? $data['type_label'] ?? '' ) : '',
				'type_label'            => $data ? (string) ( $data['type_label'] ?? '' ) : '',
				'is_marked_recommended' => $data ? ! empty( $data['is_marked_recommended'] ) : false,
			];
		}

		return $meta;
	}

	/**
	 * Get tab options from all plan options.
	 *
	 * @param array<int|string,string> $plan_options Plan options.
	 * @param string                   $tab_type Tab grouping type.
	 * @return array<string,string>
	 */
	protected function get_tab_options_from_plan_options( array $plan_options, string $tab_type = 'duration' ): array {
		$rows     = [];
		$tab_type = PricingPlanRenderService::get_instance()->normalize_tab_type( $tab_type );

		foreach ( array_keys( $plan_options ) as $plan_id ) {
			$data = PricingPlanRenderService::get_instance()->prepare( absint( $plan_id ) );

			if ( ! $data ) {
				continue;
			}

			$tab_key = PricingPlanRenderService::get_instance()->tab_key( $data, $tab_type );
			if ( '' !== $tab_key ) {
				$rows[ $tab_key ] = PricingPlanRenderService::get_instance()->tab_label( $data, $tab_type );
			}
		}

		return $rows;
	}

	/**
	 * Get tab options from prepared rows.
	 *
	 * @param array<int,array<string,mixed>> $prepared_rows Prepared rows.
	 * @param string                         $tab_type Tab grouping type.
	 * @return array<string,string>
	 */
	protected function get_tab_options_from_prepared_rows( array $prepared_rows, string $tab_type = 'duration' ): array {
		$options  = [];
		$tab_type = PricingPlanRenderService::get_instance()->normalize_tab_type( $tab_type );

		foreach ( $prepared_rows as $row ) {
			$data = is_array( $row['data'] ?? null ) ? (array) $row['data'] : [];
			$key  = PricingPlanRenderService::get_instance()->tab_key( $data, $tab_type );

			if ( '' === $key || isset( $options[ $key ] ) ) {
				continue;
			}

			$options[ $key ] = PricingPlanRenderService::get_instance()->tab_label( $data, $tab_type );
		}

		return $options;
	}

	/**
	 * Check whether the current render runs inside the editor.
	 *
	 * @return bool
	 */
	protected function is_editor_context(): bool {
		return EditorContext::get_instance()->is_editor_request();
	}

	/**
	 * Format HTML attributes.
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
	 * Render a standard placeholder block.
	 *
	 * @param string $title Placeholder title.
	 * @param string $description Placeholder description.
	 * @param string $meta Optional meta text.
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
	 * Check whether the supplied element is a container node.
	 *
	 * @param mixed $element Elementor element instance.
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
