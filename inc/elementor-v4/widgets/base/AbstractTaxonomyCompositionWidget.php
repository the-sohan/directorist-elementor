<?php
/**
 * Base nested widget for category/location archive composition.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Render\TaxonomyCompositionRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Repeater;

abstract class AbstractTaxonomyCompositionWidget extends AbstractDirectoristNestedWidget {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	/**
	 * Taxonomy scope: category or location.
	 *
	 * @return string
	 */
	abstract protected function get_taxonomy_scope(): string;

	/**
	 * Shortcode tag used for legacy fallback.
	 *
	 * @return string
	 */
	abstract protected function get_archive_shortcode(): string;

	/**
	 * Template title.
	 *
	 * @return string
	 */
	abstract protected function get_card_template_title(): string;

	protected function get_default_children_elements() {
		return [
			[
				'elType'   => 'container',
				'isInner'  => true,
				'settings' => [
					'_title' => $this->get_card_template_title(),
				],
				'elements' => [],
			],
		];
	}

	protected function get_default_repeater_title_setting_key() {
		return '_title';
	}

	protected function get_default_children_title() {
		return $this->get_card_template_title();
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-taxonomy-composition__template-storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-taxonomy-composition__template-canvas';
	}

	protected function get_initial_config(): array {
		return array_merge(
			parent::get_initial_config(),
			[
				'target_container' => [ '.directorist-elementor-taxonomy-composition__template-storage' ],
				'node'             => 'div',
				'is_interlaced'    => true,
			]
		);
	}

	/**
	 * Register slider controls inline.
	 *
	 * @return void
	 */
	protected function register_slider_controls_inline(): void {
		$this->add_control( 'imported_term_order', [ 'type' => Controls_Manager::HIDDEN, 'default' => '' ] );
		$this->add_control(
			'display_mode',
			[
				'label'   => __( 'Display Mode', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'pagination',
				'options' => [
					'pagination' => __( 'Pagination', 'directorist-elementor' ),
					'slider'     => __( 'Slider', 'directorist-elementor' ),
				],
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'slides_per_view',
			[
				'label'          => __( 'Slides Per View', 'directorist-elementor' ),
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
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);

		foreach (
			[
				'show_arrow_navigation' => __( 'Arrow Navigation', 'directorist-elementor' ),
				'show_dot_navigation'   => __( 'Dot Navigation', 'directorist-elementor' ),
			] as $control_name => $label
		) {
			$this->add_control(
				$control_name,
				[
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Show', 'directorist-elementor' ),
					'label_off'    => __( 'Hide', 'directorist-elementor' ),
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => [
						'display_mode' => 'slider',
					],
				]
			);
		}

		$this->add_control(
			'slider_autoplay',
			[
				'label'        => __( 'Autoplay', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'slider_effect',
			[
				'label'   => __( 'Effect', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'slide',
				'options' => [
					'slide'     => __( 'Slide', 'directorist-elementor' ),
					'fade'      => __( 'Fade', 'directorist-elementor' ),
					'grid'      => __( 'Grid', 'directorist-elementor' ),
					'thumb'     => __( 'Thumb', 'directorist-elementor' ),
					'creative'  => __( 'Creative', 'directorist-elementor' ),
					'cards'     => __( 'Cards', 'directorist-elementor' ),
					'cube'      => __( 'Cube', 'directorist-elementor' ),
					'flip'      => __( 'Flip', 'directorist-elementor' ),
					'coverflow' => __( 'Coverflow', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'slider_grid_rows',
			[
				'label'   => __( 'Grid Rows', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 2,
				'min'     => 1,
				'max'     => 6,
				'condition' => [
					'display_mode'  => 'slider',
					'slider_effect' => 'grid',
				],
			]
		);

		$this->add_control(
			'pause_on_hover',
			[
				'label'        => __( 'Pause on Hover', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode'    => 'slider',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'autoplay_delay',
			[
				'label'   => __( 'Autoplay Delay (ms)', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3000,
				'min'     => 100,
				'step'    => 50,
				'condition' => [
					'display_mode'    => 'slider',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'   => __( 'Transition Speed (ms)', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 500,
				'min'     => 100,
				'step'    => 50,
				'condition' => [
					'display_mode' => 'slider',
				],
			]
		);
	}

	/**
	 * Register shared grid/flex layout controls for category/location archives.
	 *
	 * @param string $item_label Item label for repeater copy.
	 * @return void
	 */
	protected function register_taxonomy_layout_controls( string $item_label ): void {
		$this->add_control(
			'view',
			[
				'label'       => __( 'View Type', 'directorist-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'default'     => 'grid',
				'toggle'      => false,
				'label_block' => true,
				'options'     => [
					'grid' => [
						'title' => __( 'Grid', 'directorist-elementor' ),
						'icon'  => 'eicon-gallery-grid',
					],
					'flex' => [
						'title' => __( 'Flex', 'directorist-elementor' ),
						'icon'  => 'eicon-align-stretch-h',
					],
					'list' => [
						'title' => __( 'List', 'directorist-elementor' ),
						'icon'  => 'eicon-editor-list-ul',
					],
				],
				'condition'   => [
					'display_mode!' => 'slider',
				],
				'separator'   => 'before',
			]
		);

		$this->add_control(
			'grid_columns_mode',
			[
				'label'     => __( 'Column Widths', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'equal',
				'options'   => [
					'equal'         => __( 'Equal Columns', 'directorist-elementor' ),
					'equal_minimum' => __( 'Minimum Width', 'directorist-elementor' ),
					'equal_fixed'   => __( 'Fixed Width', 'directorist-elementor' ),
					'auto'          => __( 'Auto Width', 'directorist-elementor' ),
					'manual'        => __( 'Manual Template', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
				],
			]
		);

		$this->add_responsive_control(
			'grid_column_min_width',
			[
				'label'      => __( 'Minimum Column Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'default'    => [ 'size' => 220, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 800 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'condition'  => [
					'display_mode!'     => 'slider',
					'view'              => 'grid',
					'grid_columns_mode' => 'equal_minimum',
				],
			]
		);

		$this->add_responsive_control(
			'grid_column_width',
			[
				'label'      => __( 'Fixed Column Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'default'    => [ 'size' => 240, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 800 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
				'condition'  => [
					'display_mode!'     => 'slider',
					'view'              => 'grid',
					'grid_columns_mode' => 'equal_fixed',
				],
			]
		);

		$this->add_responsive_control(
			'columns',
			[
				'label'          => __( 'Number of Columns', 'directorist-elementor' ),
				'type'           => Controls_Manager::NUMBER,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'min'            => 1,
				'max'            => 12,
				'step'           => 1,
				'condition'      => [
					'display_mode!'     => 'slider',
					'view'              => [ 'grid', 'list' ],
					'grid_columns_mode' => [ 'equal', 'auto' ],
				],
			]
		);

		$this->add_responsive_control(
			'grid_columns_custom',
			[
				'label'       => __( 'Grid Column Template', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'repeat(3, 1fr)',
				'description' => __( 'Accepts CSS grid tracks such as "2fr 1fr" or "minmax(180px, 1fr) 2fr".', 'directorist-elementor' ),
				'condition'   => [
					'display_mode!'     => 'slider',
					'view'              => 'grid',
					'grid_columns_mode' => 'manual',
				],
			]
		);

		$this->add_control(
			'grid_rows_mode',
			[
				'label'     => __( 'Row Heights', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'auto',
				'options'   => [
					'auto'    => __( 'Auto', 'directorist-elementor' ),
					'equal'   => __( 'Equal Rows', 'directorist-elementor' ),
					'minimum' => __( 'Minimum Height', 'directorist-elementor' ),
					'fixed'   => __( 'Fixed Height', 'directorist-elementor' ),
					'manual'  => __( 'Manual Template', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
				],
			]
		);

		$this->add_responsive_control(
			'grid_rows',
			[
				'label'     => __( 'Number of Rows', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 12,
				'step'      => 1,
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
					'grid_rows_mode' => [ 'auto', 'equal', 'minimum', 'fixed' ],
				],
			]
		);

		$this->add_responsive_control(
			'grid_row_min_height',
			[
				'label'      => __( 'Minimum Row Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh' ],
				'default'    => [ 'size' => 160, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 40, 'max' => 900 ],
					'vh' => [ 'min' => 5, 'max' => 100 ],
				],
				'condition'  => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
					'grid_rows_mode' => 'minimum',
				],
			]
		);

		$this->add_responsive_control(
			'grid_row_height',
			[
				'label'      => __( 'Fixed Row Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh' ],
				'default'    => [ 'size' => 180, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 40, 'max' => 900 ],
					'vh' => [ 'min' => 5, 'max' => 100 ],
				],
				'condition'  => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
					'grid_rows_mode' => 'fixed',
				],
			]
		);

		$this->add_responsive_control(
			'grid_rows_custom',
			[
				'label'       => __( 'Grid Row Template', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'placeholder' => 'repeat(2, auto)',
				'description' => __( 'Accepts CSS grid tracks such as "180px 120px" or "auto 1fr".', 'directorist-elementor' ),
				'condition'   => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
					'grid_rows_mode' => 'manual',
				],
			]
		);

		foreach (
			[
				'grid_auto_columns' => __( 'Auto Columns', 'directorist-elementor' ),
				'grid_auto_rows'    => __( 'Auto Rows', 'directorist-elementor' ),
			] as $control_name => $label
		) {
			$this->add_responsive_control(
				$control_name,
				[
					'label'       => $label,
					'type'        => Controls_Manager::TEXT,
					'default'     => 'auto',
					'placeholder' => 'auto',
					'condition'   => [
						'display_mode!' => 'slider',
						'view'          => 'grid',
					],
				]
			);
		}

		foreach (
			[
				'grid_row_gap'    => __( 'Row Gap', 'directorist-elementor' ),
				'grid_column_gap' => __( 'Column Gap', 'directorist-elementor' ),
			] as $control_name => $label
		) {
			$this->add_responsive_control(
				$control_name,
				[
					'label'      => $label,
					'type'       => Controls_Manager::SLIDER,
					'size_units' => [ 'px', 'em', 'rem' ],
					'default'    => [ 'size' => 16, 'unit' => 'px' ],
					'range'      => [
						'px' => [ 'min' => 0, 'max' => 120 ],
					],
					'condition'  => [
						'display_mode!' => 'slider',
						'view'          => [ 'grid', 'flex', 'list' ],
					],
				]
			);
		}

		$this->add_responsive_control(
			'grid_auto_flow',
			[
				'label'     => __( 'Auto Flow', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'row',
				'toggle'    => false,
				'options'   => [
					'row'    => [
						'title' => __( 'Row', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'column' => [
						'title' => __( 'Column', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
				],
			]
		);

		$this->add_responsive_control(
			'grid_density',
			[
				'label'     => __( 'Density', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'auto',
				'toggle'    => false,
				'options'   => [
					'auto'  => [
						'title' => __( 'Auto', 'directorist-elementor' ),
						'icon'  => 'eicon-align-start-h',
					],
					'dense' => [
						'title' => __( 'Dense', 'directorist-elementor' ),
						'icon'  => 'eicon-menu-bar',
					],
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
				],
			]
		);

		foreach (
			[
				'grid_justify_items'   => [ __( 'Justify Items', 'directorist-elementor' ), $this->get_grid_alignment_choose_options( 'horizontal' ) ],
				'grid_align_items'     => [ __( 'Align Items', 'directorist-elementor' ), $this->get_grid_alignment_choose_options( 'vertical' ) ],
				'grid_justify_content' => [ __( 'Justify Content', 'directorist-elementor' ), $this->get_grid_content_choose_options( 'horizontal' ) ],
				'grid_align_content'   => [ __( 'Align Content', 'directorist-elementor' ), $this->get_grid_content_choose_options( 'vertical' ) ],
			] as $control_name => $config
		) {
			$this->add_responsive_control(
				$control_name,
				[
					'label'     => $config[0],
					'type'      => Controls_Manager::CHOOSE,
					'default'   => false !== strpos( $control_name, '_content' ) ? 'normal' : 'stretch',
					'toggle'    => false,
					'options'   => $config[1],
					'condition' => [
						'display_mode!' => 'slider',
						'view'          => 'grid',
					],
				]
			);
		}

		$this->add_control(
			'grid_item_span_rules',
			[
				'label'       => __( 'Grid Placement Rules', 'directorist-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $this->get_taxonomy_grid_placement_repeater( $item_label )->get_controls(),
				'title_field' => '{{{ target === "nth" ? "' . esc_js( $item_label ) . ' " + nth : target }}}',
				'condition'   => [
					'display_mode!' => 'slider',
					'view'          => 'grid',
				],
			]
		);

		$this->add_responsive_control(
			'flex_direction',
			[
				'label'     => __( 'Direction', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'row',
				'toggle'    => false,
				'options'   => [
					'row'            => [ 'title' => __( 'Row', 'directorist-elementor' ), 'icon' => 'eicon-arrow-right' ],
					'row-reverse'    => [ 'title' => __( 'Row Reverse', 'directorist-elementor' ), 'icon' => 'eicon-arrow-left' ],
					'column'         => [ 'title' => __( 'Column', 'directorist-elementor' ), 'icon' => 'eicon-arrow-down' ],
					'column-reverse' => [ 'title' => __( 'Column Reverse', 'directorist-elementor' ), 'icon' => 'eicon-arrow-up' ],
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'flex',
				],
			]
		);

		$this->add_responsive_control(
			'flex_wrap',
			[
				'label'     => __( 'Wrap', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'wrap',
				'toggle'    => false,
				'options'   => [
					'nowrap'       => [ 'title' => __( 'No Wrap', 'directorist-elementor' ), 'icon' => 'eicon-ellipsis-h' ],
					'wrap'         => [ 'title' => __( 'Wrap', 'directorist-elementor' ), 'icon' => 'eicon-menu-bar' ],
					'wrap-reverse' => [ 'title' => __( 'Wrap Reverse', 'directorist-elementor' ), 'icon' => 'eicon-v-align-bottom' ],
				],
				'condition' => [
					'display_mode!' => 'slider',
					'view'          => 'flex',
				],
			]
		);

		foreach (
			[
				'flex_justify_content' => [ __( 'Justify Content', 'directorist-elementor' ), $this->get_flex_justify_choose_options() ],
				'flex_align_items'     => [ __( 'Align Items', 'directorist-elementor' ), $this->get_flex_align_choose_options() ],
				'flex_align_content'   => [ __( 'Align Content', 'directorist-elementor' ), $this->get_flex_content_choose_options() ],
			] as $control_name => $config
		) {
			$this->add_responsive_control(
				$control_name,
				[
					'label'     => $config[0],
					'type'      => Controls_Manager::CHOOSE,
					'default'   => 'flex_justify_content' === $control_name ? 'flex-start' : 'stretch',
					'toggle'    => false,
					'options'   => $config[1],
					'condition' => [
						'display_mode!' => 'slider',
						'view'          => 'flex',
					],
				]
			);
		}

		$this->add_responsive_control(
			'flex_item_basis',
			[
				'label'      => __( 'Item Basis', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'default'    => [ 'size' => 240, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 60, 'max' => 900 ],
					'%'  => [ 'min' => 5, 'max' => 100 ],
				],
				'condition'  => [
					'display_mode!' => 'slider',
					'view'          => 'flex',
				],
			]
		);

		$this->add_control(
			'flex_item_grow',
			[
				'label'        => __( 'Item Growth', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Grow', 'directorist-elementor' ),
				'label_off'    => __( 'Fixed', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode!' => 'slider',
					'view'          => 'flex',
				],
			]
		);
	}

	/**
	 * Register taxonomy archive style sections.
	 *
	 * @param string $item_label Item label.
	 * @return void
	 */
	protected function register_taxonomy_style_sections( string $item_label ): void {
		$this->start_controls_section(
			'section_style_card_wrapper',
			[
				'label' => __( 'Card Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_wrapper_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-card',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_wrapper_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-card',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_wrapper_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-taxonomy-card',
			]
		);

		$this->add_responsive_control(
			'card_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-card__content' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'card_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-card' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'card_wrapper_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-taxonomy-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_item',
			[
				'label' => $item_label,
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$item_selector    = '{{WRAPPER}} .directorist-categories__single, {{WRAPPER}} .directorist-location__single, {{WRAPPER}} .directorist-taxonomy-list__card, {{WRAPPER}} .directorist-elementor-taxonomy-card';
		$content_selector = '{{WRAPPER}} .directorist-categories__single__content, {{WRAPPER}} .directorist-location__content, {{WRAPPER}} .directorist-taxonomy-list__card, {{WRAPPER}} .directorist-elementor-taxonomy-card__content';

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'item_border',
				'selector' => $item_selector,
			]
		);

		$this->add_control(
			'item_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					$item_selector => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'item_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					$content_selector => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'item_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					$item_selector => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'item_box_shadow',
				'selector' => $item_selector,
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_directory_tabs_wrapper',
			[
				'label' => __( 'Directory Tabs Wrapper', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'tabs_wrapper_background',
				'selector' => '{{WRAPPER}} .directorist-type-nav, {{WRAPPER}} .directorist-type-nav__list',
			]
		);

		$this->add_responsive_control(
			'tabs_wrapper_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-type-nav, {{WRAPPER}} .directorist-type-nav__list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tabs_wrapper_margin',
			[
				'label'      => __( 'Margin', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-type-nav' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'tabs_wrapper_gap',
			[
				'label'      => __( 'Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 60 ],
					'em' => [ 'min' => 0, 'max' => 5, 'step' => 0.1 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-type-nav__list' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_directory_tabs',
			[
				'label' => __( 'Directory Tabs', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'tab_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_hover_color',
			[
				'label'     => __( 'Hover Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__link:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__link:hover .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'tab_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-type-nav__list__current .directorist-type-nav__link' => 'color: {{VALUE}};',
					'{{WRAPPER}} .directorist-type-nav__list__current .directorist-type-nav__link .directorist-icon-mask::after' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'tab_typography',
				'selector' => '{{WRAPPER}} .directorist-type-nav__link',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'tab_border',
				'selector' => '{{WRAPPER}} .directorist-type-nav__link',
			]
		);

		$this->add_responsive_control(
			'tab_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-type-nav__link' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_slider_arrows',
			[
				'label'     => __( 'Slider Arrows', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_mode'          => 'slider',
					'show_arrow_navigation' => 'yes',
				],
			]
		);

		$this->add_control(
			'arrow_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'arrow_background_color',
			[
				'label'     => __( 'Background Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'arrow_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 12, 'max' => 60 ] ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__arrow' => 'font-size: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_slider_dots',
			[
				'label'     => __( 'Slider Dots', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'display_mode'        => 'slider',
					'show_dot_navigation' => 'yes',
				],
			]
		);

		$this->add_control(
			'dot_color',
			[
				'label'     => __( 'Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'dot_active_color',
			[
				'label'     => __( 'Active Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_responsive_control(
			'dot_size',
			[
				'label'      => __( 'Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [ 'px' => [ 'min' => 4, 'max' => 24 ] ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-gbi-taxonomy-slider__pagination .swiper-pagination-bullet' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render taxonomy archive widget.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return void
	 */
	protected function render_taxonomy_archive_widget( array $attributes ): void {
		$settings            = $this->get_settings_for_display();
		$slider_settings     = $this->resolve_slider_settings( $settings );
		$visibility_settings = $this->resolve_visibility_settings( $settings );
		$tab_settings        = $this->resolve_tab_settings( $settings );
		$template_raw        = $this->get_template_raw_data();
		$has_template        = is_array( $template_raw ) && $this->has_raw_composition_content( $template_raw );
		$attributes          = array_merge( $attributes, $this->resolve_taxonomy_layout_attributes( $settings ) );
		$attributes['_elementor_widget_id'] = (string) $this->get_id();
		$attributes['imported_term_order'] = (string) ( $settings['imported_term_order'] ?? '' );

		if ( ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' ) ) {
			$attributes['view'] = 'grid';
			foreach ( [ 'cat_per_page', 'loc_per_page' ] as $per_page_key ) {
				if ( array_key_exists( $per_page_key, $attributes ) ) {
					$attributes[ $per_page_key ] = 9999;
				}
			}
		}

		$service = TaxonomyCompositionRenderService::get_instance();
		$content = $has_template
			? $service->render_composed_content( $this->get_archive_shortcode(), $attributes, $slider_settings, $tab_settings, $template_raw, $this->get_taxonomy_scope() )
			: $service->render_legacy_content( $this->get_archive_shortcode(), $attributes, $slider_settings, $tab_settings );
		$attrs   = $service->build_wrapper_attributes(
			$this->get_archive_shortcode(),
			$attributes,
			$slider_settings,
			$visibility_settings,
			$tab_settings,
			$has_template ? $template_raw : [],
			$this->get_taxonomy_scope()
		);

		echo $service->build_taxonomy_placement_style_tag( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<div ' . $this->format_html_attributes( $attrs ) . '>';
		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	protected function resolve_tab_settings( array $settings ): array {
		return [
			'show_all_directory_tab' => 'yes' === ( $settings['show_all_directory_tab'] ?? 'yes' ),
			'all_tab_icon'           => $settings['all_tab_icon'] ?? [],
		];
	}

	protected function resolve_slider_settings( array $settings ): array {
		$display_mode = sanitize_key( (string) ( $settings['display_mode'] ?? 'pagination' ) );

		if ( 'slider' !== $display_mode ) {
			return [];
		}

		return [
			'display_mode'           => 'slider',
			'slides_per_view'        => max( 1, absint( $settings['slides_per_view'] ?? 3 ) ),
			'slides_per_view_tablet' => max( 1, absint( $settings['slides_per_view_tablet'] ?? 2 ) ),
			'slides_per_view_mobile' => max( 1, absint( $settings['slides_per_view_mobile'] ?? 1 ) ),
			'slider_effect'          => sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) ),
			'slider_grid_rows'       => max( 1, min( 6, absint( $settings['slider_grid_rows'] ?? 2 ) ) ),
			'show_arrow_navigation'  => 'yes' === ( $settings['show_arrow_navigation'] ?? 'yes' ),
			'show_dot_navigation'    => 'yes' === ( $settings['show_dot_navigation'] ?? 'yes' ),
			'slider_autoplay'        => 'yes' === ( $settings['slider_autoplay'] ?? '' ),
			'autoplay_delay'         => max( 100, absint( $settings['autoplay_delay'] ?? 3000 ) ),
			'pause_on_hover'         => 'yes' === ( $settings['pause_on_hover'] ?? 'yes' ),
			'transition_speed'       => max( 100, absint( $settings['transition_speed'] ?? 500 ) ),
		];
	}

	protected function resolve_visibility_settings( array $settings ): array {
		return [
			'show_image'       => 'yes' === ( $settings['show_image'] ?? 'yes' ),
			'show_icon'        => 'yes' === ( $settings['show_icon'] ?? 'yes' ),
			'show_description' => 'yes' === ( $settings['show_description'] ?? '' ),
		];
	}

	/**
	 * Resolve Elementor-only taxonomy layout metadata.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function resolve_taxonomy_layout_attributes( array $settings ): array {
		$columns_desktop = $this->normalize_taxonomy_int( $settings['columns'] ?? 3, 1, 12 );
		$columns_tablet  = $this->normalize_taxonomy_int( $settings['columns_tablet'] ?? $columns_desktop, 1, 12 );
		$columns_mobile  = $this->normalize_taxonomy_int( $settings['columns_mobile'] ?? $columns_tablet, 1, 12 );

		return [
			'view'                   => $this->sanitize_taxonomy_choice( $settings['view'] ?? 'grid', [ 'grid', 'flex', 'list' ], 'grid' ),
			'columns'                => $columns_desktop,
			'columns_tablet'         => $columns_tablet,
			'columns_mobile'         => $columns_mobile,
			'_direl_layout_style'    => $this->build_taxonomy_layout_inline_style( $settings ),
			'_direl_placement_style' => $this->build_taxonomy_grid_placement_css( $settings ),
		];
	}

	/**
	 * Build layout CSS variables consumed by AssetManager styles.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function build_taxonomy_layout_inline_style( array $settings ): string {
		$style = array_merge(
			$this->build_taxonomy_grid_style_values( $settings ),
			$this->build_taxonomy_flex_style_values( $settings )
		);

		$output = '';
		foreach ( $style as $property => $value ) {
			$output .= $property . ':' . $value . ';';
		}

		return $output;
	}

	/**
	 * Build grid CSS variables.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,string>
	 */
	protected function build_taxonomy_grid_style_values( array $settings ): array {
		$devices = [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ];
		$style   = [];

		foreach ( $devices as $device => $suffix ) {
			$style[ '--direl-taxonomy-grid-template-columns-' . $device ] = $this->resolve_taxonomy_grid_column_template( $settings, $suffix );
			$style[ '--direl-taxonomy-grid-template-rows-' . $device ]    = $this->resolve_taxonomy_grid_row_template( $settings, $suffix );
			$style[ '--direl-taxonomy-grid-auto-columns-' . $device ]     = $this->sanitize_taxonomy_track_list( $settings[ 'grid_auto_columns' . $suffix ] ?? ( $settings['grid_auto_columns'] ?? 'auto' ), 'auto' );
			$style[ '--direl-taxonomy-grid-auto-rows-' . $device ]        = $this->sanitize_taxonomy_track_list( $settings[ 'grid_auto_rows' . $suffix ] ?? ( $settings['grid_auto_rows'] ?? 'auto' ), 'auto' );
			$style[ '--direl-taxonomy-grid-auto-flow-' . $device ]        = $this->resolve_taxonomy_grid_auto_flow( $settings[ 'grid_auto_flow' . $suffix ] ?? ( $settings['grid_auto_flow'] ?? 'row' ), $settings[ 'grid_density' . $suffix ] ?? ( $settings['grid_density'] ?? 'auto' ) );
			$style[ '--direl-taxonomy-grid-row-gap-' . $device ]          = $this->taxonomy_value_to_css( $settings[ 'grid_row_gap' . $suffix ] ?? ( $settings['grid_row_gap'] ?? [] ), '16px' );
			$style[ '--direl-taxonomy-grid-column-gap-' . $device ]       = $this->taxonomy_value_to_css( $settings[ 'grid_column_gap' . $suffix ] ?? ( $settings['grid_column_gap'] ?? [] ), '16px' );
			$style[ '--direl-taxonomy-grid-justify-items-' . $device ]    = $this->sanitize_taxonomy_grid_alignment( $settings[ 'grid_justify_items' . $suffix ] ?? ( $settings['grid_justify_items'] ?? 'stretch' ), 'stretch' );
			$style[ '--direl-taxonomy-grid-align-items-' . $device ]      = $this->sanitize_taxonomy_grid_alignment( $settings[ 'grid_align_items' . $suffix ] ?? ( $settings['grid_align_items'] ?? 'stretch' ), 'stretch' );
			$style[ '--direl-taxonomy-grid-justify-content-' . $device ]  = $this->sanitize_taxonomy_grid_content_alignment( $settings[ 'grid_justify_content' . $suffix ] ?? ( $settings['grid_justify_content'] ?? 'normal' ), 'normal' );
			$style[ '--direl-taxonomy-grid-align-content-' . $device ]    = $this->sanitize_taxonomy_grid_content_alignment( $settings[ 'grid_align_content' . $suffix ] ?? ( $settings['grid_align_content'] ?? 'normal' ), 'normal' );
		}

		return $style;
	}

	/**
	 * Build flex CSS variables.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,string>
	 */
	protected function build_taxonomy_flex_style_values( array $settings ): array {
		$devices = [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ];
		$style   = [
			'--direl-taxonomy-flex-item-grow' => 'yes' === ( $settings['flex_item_grow'] ?? 'yes' ) ? '1' : '0',
		];

		foreach ( $devices as $device => $suffix ) {
			$style[ '--direl-taxonomy-flex-direction-' . $device ]       = $this->sanitize_taxonomy_choice( $settings[ 'flex_direction' . $suffix ] ?? ( $settings['flex_direction'] ?? 'row' ), [ 'row', 'row-reverse', 'column', 'column-reverse' ], 'row' );
			$style[ '--direl-taxonomy-flex-wrap-' . $device ]            = $this->sanitize_taxonomy_choice( $settings[ 'flex_wrap' . $suffix ] ?? ( $settings['flex_wrap'] ?? 'wrap' ), [ 'nowrap', 'wrap', 'wrap-reverse' ], 'wrap' );
			$style[ '--direl-taxonomy-flex-justify-content-' . $device ] = $this->sanitize_taxonomy_flex_alignment( $settings[ 'flex_justify_content' . $suffix ] ?? ( $settings['flex_justify_content'] ?? 'flex-start' ), [ 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ], 'flex-start' );
			$style[ '--direl-taxonomy-flex-align-items-' . $device ]     = $this->sanitize_taxonomy_flex_alignment( $settings[ 'flex_align_items' . $suffix ] ?? ( $settings['flex_align_items'] ?? 'stretch' ), [ 'stretch', 'flex-start', 'center', 'flex-end', 'baseline' ], 'stretch' );
			$style[ '--direl-taxonomy-flex-align-content-' . $device ]   = $this->sanitize_taxonomy_flex_alignment( $settings[ 'flex_align_content' . $suffix ] ?? ( $settings['flex_align_content'] ?? 'stretch' ), [ 'stretch', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ], 'stretch' );
			$style[ '--direl-taxonomy-flex-item-basis-' . $device ]      = $this->taxonomy_value_to_css( $settings[ 'flex_item_basis' . $suffix ] ?? ( $settings['flex_item_basis'] ?? [] ), '240px' );
		}

		return $style;
	}

	/**
	 * Resolve grid column CSS template.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string              $suffix Responsive suffix.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_column_template( array $settings, string $suffix ): string {
		$mode  = $this->sanitize_taxonomy_choice( $settings['grid_columns_mode'] ?? 'equal', [ 'equal', 'equal_minimum', 'equal_fixed', 'auto', 'manual' ], 'equal' );
		$count = $this->normalize_taxonomy_int( $settings[ 'columns' . $suffix ] ?? ( $settings['columns'] ?? 3 ), 1, 12 );

		if ( 'manual' === $mode ) {
			return $this->sanitize_taxonomy_track_list( $settings[ 'grid_columns_custom' . $suffix ] ?? ( $settings['grid_columns_custom'] ?? '' ), 'repeat(' . $count . ', minmax(0, 1fr))' );
		}

		if ( 'equal_minimum' === $mode ) {
			$min_width = $this->taxonomy_value_to_css( $settings[ 'grid_column_min_width' . $suffix ] ?? ( $settings['grid_column_min_width'] ?? [] ), '220px' );
			return 'repeat(auto-fill,minmax(min(100%,' . $min_width . '),1fr))';
		}

		if ( 'equal_fixed' === $mode ) {
			$width = $this->taxonomy_value_to_css( $settings[ 'grid_column_width' . $suffix ] ?? ( $settings['grid_column_width'] ?? [] ), '240px' );
			return 'repeat(auto-fit,minmax(min(100%,' . $width . '),' . $width . '))';
		}

		if ( 'auto' === $mode ) {
			return 'repeat(' . $count . ',auto)';
		}

		return 'repeat(' . $count . ',minmax(0,1fr))';
	}

	/**
	 * Resolve grid row CSS template.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param string              $suffix Responsive suffix.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_row_template( array $settings, string $suffix ): string {
		$mode  = $this->sanitize_taxonomy_choice( $settings['grid_rows_mode'] ?? 'auto', [ 'auto', 'equal', 'minimum', 'fixed', 'manual' ], 'auto' );
		$count = $this->normalize_taxonomy_int( $settings[ 'grid_rows' . $suffix ] ?? ( $settings['grid_rows'] ?? 2 ), 1, 12 );

		if ( 'manual' === $mode ) {
			return $this->sanitize_taxonomy_track_list( $settings[ 'grid_rows_custom' . $suffix ] ?? ( $settings['grid_rows_custom'] ?? '' ), 'repeat(' . $count . ', auto)' );
		}

		if ( 'equal' === $mode ) {
			return 'repeat(' . $count . ',minmax(0,1fr))';
		}

		if ( 'minimum' === $mode ) {
			return 'repeat(' . $count . ',minmax(' . $this->taxonomy_value_to_css( $settings[ 'grid_row_min_height' . $suffix ] ?? ( $settings['grid_row_min_height'] ?? [] ), '160px' ) . ',auto))';
		}

		if ( 'fixed' === $mode ) {
			return 'repeat(' . $count . ',' . $this->taxonomy_value_to_css( $settings[ 'grid_row_height' . $suffix ] ?? ( $settings['grid_row_height'] ?? [] ), '180px' ) . ')';
		}

		return 'repeat(' . $count . ',auto)';
	}

	/**
	 * Build scoped placement CSS.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function build_taxonomy_grid_placement_css( array $settings ): string {
		$scope_class = 'elementor-element-' . sanitize_html_class( (string) $this->get_id() );
		$rules       = is_array( $settings['grid_item_span_rules'] ?? null ) ? $settings['grid_item_span_rules'] : [];
		$css         = '';

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$property = $this->resolve_taxonomy_grid_rule_property( $rule['rule'] ?? 'column_span' );
			$value    = $this->resolve_taxonomy_grid_rule_value( $rule['value'] ?? 1, (string) ( $rule['rule'] ?? 'column_span' ) );
			$selector = $this->resolve_taxonomy_grid_rule_selector( $rule );
			$legacy   = $this->resolve_taxonomy_grid_legacy_selector( $rule );

			if ( '' === $property || '' === $value || '' === $selector ) {
				continue;
			}

			$css .= '.' . $scope_class . ' ' . $selector . ',.' . $scope_class . ' .taxonomy-category-wrapper' . $legacy . ',.' . $scope_class . ' .taxonomy-location-wrapper' . $legacy . '{' . $property . ':' . $value . ';}';
		}

		return $css;
	}

	/**
	 * Build grid placement repeater controls.
	 *
	 * @param string $item_label Item label.
	 * @return Repeater
	 */
	protected function get_taxonomy_grid_placement_repeater( string $item_label ): Repeater {
		$repeater = new Repeater();

		$repeater->add_control(
			'target',
			[
				'label'   => __( 'Target', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'nth',
				'options' => [
					'first'  => sprintf( __( 'First %s', 'directorist-elementor' ), $item_label ),
					'last'   => sprintf( __( 'Last %s', 'directorist-elementor' ), $item_label ),
					'nth'    => sprintf( __( '%s Number', 'directorist-elementor' ), $item_label ),
					'custom' => __( 'nth-child Expression', 'directorist-elementor' ),
				],
			]
		);

		$repeater->add_control(
			'nth',
			[
				'label'       => $item_label,
				'type'        => Controls_Manager::TEXT,
				'default'     => '1',
				'placeholder' => '1',
				'condition'   => [
					'target' => [ 'nth', 'custom' ],
				],
			]
		);

		$repeater->add_control(
			'rule',
			[
				'label'   => __( 'Rule', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'column_span',
				'options' => [
					'column_span'  => __( 'Column Span', 'directorist-elementor' ),
					'row_span'     => __( 'Row Span', 'directorist-elementor' ),
					'column_start' => __( 'Column Start', 'directorist-elementor' ),
					'column_end'   => __( 'Column End', 'directorist-elementor' ),
					'row_start'    => __( 'Row Start', 'directorist-elementor' ),
					'row_end'      => __( 'Row End', 'directorist-elementor' ),
				],
			]
		);

		$repeater->add_control(
			'value',
			[
				'label'   => __( 'Value', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '2',
			]
		);

		return $repeater;
	}

	/**
	 * Resolve placement selector for composed output.
	 *
	 * @param array<string,mixed> $rule Rule.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_rule_selector( array $rule ): string {
		$target = $this->sanitize_taxonomy_choice( $rule['target'] ?? 'nth', [ 'first', 'last', 'nth', 'custom' ], 'nth' );

		if ( 'first' === $target ) {
			return '.directorist-elementor-taxonomy-grid-item--first';
		}

		if ( 'last' === $target ) {
			return '.directorist-elementor-taxonomy-grid-item--last';
		}

		if ( 'custom' === $target ) {
			$expression = $this->sanitize_taxonomy_nth_expression( $rule['nth'] ?? '' );
			return '' === $expression ? '' : '.directorist-elementor-taxonomy-grid-item:nth-child(' . $expression . ')';
		}

		$nth = $this->normalize_taxonomy_int( $rule['nth'] ?? 1, 1, 99 );
		return '.directorist-elementor-taxonomy-grid-item--' . $nth;
	}

	/**
	 * Resolve placement selector for legacy shortcode output.
	 *
	 * @param array<string,mixed> $rule Rule.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_legacy_selector( array $rule ): string {
		$target = $this->sanitize_taxonomy_choice( $rule['target'] ?? 'nth', [ 'first', 'last', 'nth', 'custom' ], 'nth' );

		if ( 'first' === $target ) {
			return '>:first-child';
		}

		if ( 'last' === $target ) {
			return '>:last-child';
		}

		if ( 'custom' === $target ) {
			$expression = $this->sanitize_taxonomy_nth_expression( $rule['nth'] ?? '' );
			return '' === $expression ? '>*' : '>:nth-child(' . $expression . ')';
		}

		$nth = $this->normalize_taxonomy_int( $rule['nth'] ?? 1, 1, 99 );
		return '>:nth-child(' . $nth . ')';
	}

	/**
	 * Resolve placement property.
	 *
	 * @param mixed $value Rule key.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_rule_property( $value ): string {
		$key = str_replace( '-', '_', sanitize_key( (string) $value ) );
		$map = [
			'column_span'  => 'grid-column',
			'column_start' => 'grid-column-start',
			'column_end'   => 'grid-column-end',
			'row_span'     => 'grid-row',
			'row_start'    => 'grid-row-start',
			'row_end'      => 'grid-row-end',
		];

		return $map[ $key ] ?? '';
	}

	/**
	 * Resolve placement value.
	 *
	 * @param mixed  $value Value.
	 * @param string $rule Rule key.
	 * @return string
	 */
	protected function resolve_taxonomy_grid_rule_value( $value, string $rule ): string {
		$rule = str_replace( '-', '_', sanitize_key( $rule ) );
		if ( in_array( $rule, [ 'column_span', 'row_span' ], true ) ) {
			$span = $this->normalize_taxonomy_int( $value, 1, 12 );
			return $span > 1 ? 'span ' . $span : '';
		}

		if ( 'auto' === strtolower( trim( (string) $value ) ) ) {
			return 'auto';
		}

		$line = (int) $value;

		return 0 === $line ? '' : (string) max( -99, min( 99, $line ) );
	}

	protected function get_grid_alignment_choose_options( string $axis ): array {
		return [
			'start'   => [ 'title' => __( 'Start', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-left' : 'eicon-v-align-top' ],
			'center'  => [ 'title' => __( 'Center', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-center' : 'eicon-v-align-middle' ],
			'end'     => [ 'title' => __( 'End', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-right' : 'eicon-v-align-bottom' ],
			'stretch' => [ 'title' => __( 'Stretch', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-stretch' : 'eicon-v-align-stretch' ],
		];
	}

	protected function get_grid_content_choose_options( string $axis ): array {
		return [
			'normal'        => [ 'title' => __( 'Normal', 'directorist-elementor' ), 'icon' => 'eicon-ban' ],
			'start'         => [ 'title' => __( 'Start', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-left' : 'eicon-v-align-top' ],
			'center'        => [ 'title' => __( 'Center', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-center' : 'eicon-v-align-middle' ],
			'end'           => [ 'title' => __( 'End', 'directorist-elementor' ), 'icon' => 'horizontal' === $axis ? 'eicon-h-align-right' : 'eicon-v-align-bottom' ],
			'space-between' => [ 'title' => __( 'Between', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-between-h' ],
			'space-around'  => [ 'title' => __( 'Around', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-around-h' ],
			'space-evenly'  => [ 'title' => __( 'Evenly', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-evenly-h' ],
		];
	}

	protected function get_flex_justify_choose_options(): array {
		return [
			'flex-start'    => [ 'title' => __( 'Start', 'directorist-elementor' ), 'icon' => 'eicon-h-align-left' ],
			'center'        => [ 'title' => __( 'Center', 'directorist-elementor' ), 'icon' => 'eicon-h-align-center' ],
			'flex-end'      => [ 'title' => __( 'End', 'directorist-elementor' ), 'icon' => 'eicon-h-align-right' ],
			'space-between' => [ 'title' => __( 'Between', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-between-h' ],
			'space-around'  => [ 'title' => __( 'Around', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-around-h' ],
			'space-evenly'  => [ 'title' => __( 'Evenly', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-evenly-h' ],
		];
	}

	protected function get_flex_align_choose_options(): array {
		return [
			'stretch'    => [ 'title' => __( 'Stretch', 'directorist-elementor' ), 'icon' => 'eicon-v-align-stretch' ],
			'flex-start' => [ 'title' => __( 'Start', 'directorist-elementor' ), 'icon' => 'eicon-v-align-top' ],
			'center'     => [ 'title' => __( 'Center', 'directorist-elementor' ), 'icon' => 'eicon-v-align-middle' ],
			'flex-end'   => [ 'title' => __( 'End', 'directorist-elementor' ), 'icon' => 'eicon-v-align-bottom' ],
			'baseline'   => [ 'title' => __( 'Baseline', 'directorist-elementor' ), 'icon' => 'eicon-editor-alignleft' ],
		];
	}

	protected function get_flex_content_choose_options(): array {
		return [
			'stretch'       => [ 'title' => __( 'Stretch', 'directorist-elementor' ), 'icon' => 'eicon-v-align-stretch' ],
			'flex-start'    => [ 'title' => __( 'Start', 'directorist-elementor' ), 'icon' => 'eicon-v-align-top' ],
			'center'        => [ 'title' => __( 'Center', 'directorist-elementor' ), 'icon' => 'eicon-v-align-middle' ],
			'flex-end'      => [ 'title' => __( 'End', 'directorist-elementor' ), 'icon' => 'eicon-v-align-bottom' ],
			'space-between' => [ 'title' => __( 'Between', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-between-v' ],
			'space-around'  => [ 'title' => __( 'Around', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-around-v' ],
			'space-evenly'  => [ 'title' => __( 'Evenly', 'directorist-elementor' ), 'icon' => 'eicon-justify-space-evenly-v' ],
		];
	}

	protected function normalize_taxonomy_int( $value, int $min, int $max ): int {
		return max( $min, min( $max, absint( $value ) ) );
	}

	protected function sanitize_taxonomy_choice( $value, array $allowed, string $fallback ): string {
		$value = trim( (string) $value );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	protected function sanitize_taxonomy_grid_alignment( $value, string $fallback ): string {
		$value = str_replace( [ '_', 'flex-start', 'flex-end' ], [ '-', 'start', 'end' ], trim( (string) $value ) );

		return in_array( $value, [ 'normal', 'stretch', 'start', 'center', 'end' ], true ) ? $value : $fallback;
	}

	protected function sanitize_taxonomy_grid_content_alignment( $value, string $fallback ): string {
		$value = str_replace( [ '_', 'flex-start', 'flex-end' ], [ '-', 'start', 'end' ], trim( (string) $value ) );

		return in_array( $value, [ 'normal', 'stretch', 'start', 'center', 'end', 'space-between', 'space-around', 'space-evenly' ], true ) ? $value : $fallback;
	}

	protected function sanitize_taxonomy_flex_alignment( $value, array $allowed, string $fallback ): string {
		$value = str_replace( [ '_', 'start', 'end' ], [ '-', 'flex-start', 'flex-end' ], trim( (string) $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	protected function resolve_taxonomy_grid_auto_flow( $direction, $density ): string {
		$direction = $this->sanitize_taxonomy_choice( $direction, [ 'row', 'column' ], 'row' );
		$density   = $this->sanitize_taxonomy_choice( $density, [ 'auto', 'dense' ], 'auto' );

		return 'dense' === $density ? $direction . ' dense' : $direction;
	}

	protected function sanitize_taxonomy_track_list( $value, string $fallback ): string {
		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $value ) {
			return $fallback;
		}

		$lower_value = strtolower( $value );
		foreach ( [ ';', '{', '}', '<', '>', 'url', 'expression', '@import' ] as $blocked ) {
			if ( false !== strpos( $lower_value, $blocked ) ) {
				return $fallback;
			}
		}

		return preg_match( '/^[a-z0-9\s.,%()[\]\/_\-+*#]+$/i', $value ) ? $value : $fallback;
	}

	protected function sanitize_taxonomy_nth_expression( $value ): string {
		$value = trim( (string) $value );
		$lower = strtolower( $value );

		if ( in_array( $lower, [ 'odd', 'even' ], true ) ) {
			return $lower;
		}

		if ( preg_match( '/^\d{1,2}$/', $value ) ) {
			return (string) $this->normalize_taxonomy_int( $value, 1, 99 );
		}

		return preg_match( '/^[0-9n+\-\s]+$/i', $value ) ? $value : '';
	}

	protected function taxonomy_value_to_css( $value, string $fallback ): string {
		if ( is_array( $value ) ) {
			$size = $value['size'] ?? null;
			$unit = isset( $value['unit'] ) && '' !== $value['unit'] ? (string) $value['unit'] : 'px';

			if ( is_numeric( $size ) ) {
				$number = max( 0, (float) $size );
				$number_text = 0.0 === fmod( $number, 1.0 ) ? (string) (int) $number : rtrim( rtrim( (string) $number, '0' ), '.' );
				return $number_text . $unit;
			}

			return $fallback;
		}

		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $value ) {
			return $fallback;
		}

		if ( 'none' === strtolower( $value ) || 'auto' === strtolower( $value ) ) {
			return strtolower( $value );
		}

		if ( is_numeric( $value ) ) {
			$number = max( 0, (float) $value );
			$number_text = 0.0 === fmod( $number, 1.0 ) ? (string) (int) $number : rtrim( rtrim( (string) $number, '0' ), '.' );
			return $number_text . 'px';
		}

		return preg_match( '/^\d+(?:\.\d+)?(?:px|%|em|rem|vw|vh)$/i', $value ) ? $value : $fallback;
	}

	protected function get_template_raw_data(): ?array {
		$raw_children = $this->get_render_child_raw_elements();
		if ( empty( $raw_children[0] ) || ! is_array( $raw_children[0] ) ) {
			return null;
		}
		return $raw_children[0];
	}

	protected function get_render_child_raw_elements(): array {
		if ( is_array( $this->render_child_raw_elements ) ) {
			return $this->render_child_raw_elements;
		}

		$raw_data = $this->get_current_widget_raw_data();
		if ( null === $raw_data && method_exists( $this, 'get_raw_data' ) ) {
			$raw_data = $this->get_raw_data();
		}

		$normalized = [];
		foreach ( array_values( (array) ( $raw_data['elements'] ?? [] ) ) as $raw_child ) {
			if ( is_array( $raw_child ) ) {
				$normalized[] = $raw_child;
			}
		}

		$this->render_child_raw_elements = $normalized;
		return $this->render_child_raw_elements;
	}

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

	protected function find_raw_element_by_id( array $elements, string $target_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}
			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $element;
			}
			$children = (array) ( $element['elements'] ?? [] );
			if ( ! empty( $children ) ) {
				$found = $this->find_raw_element_by_id( $children, $target_id );
				if ( is_array( $found ) ) {
					return $found;
				}
			}
		}
		return null;
	}

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

	protected function content_template() {
		?>
		<div class="directorist-elementor-taxonomy-composition directorist-elementor-taxonomy-composition--editor">
			<div class="directorist-elementor-taxonomy-composition__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-taxonomy-composition__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the category/location preview from the current widget settings.', 'directorist-elementor' ); ?></p>
				</div>
			</div>
			<div class="directorist-elementor-taxonomy-composition__template-storage" aria-hidden="true">
				<div class="directorist-elementor-taxonomy-composition__template">
					<div class="directorist-elementor-taxonomy-composition__template-canvas"></div>
				</div>
			</div>
		</div>
		<?php
	}
}
