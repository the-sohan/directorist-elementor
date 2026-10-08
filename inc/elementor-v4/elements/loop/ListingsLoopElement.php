<?php
/**
 * Container-based listings loop element.
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
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use Elementor\Controls_Manager;
use Elementor\Includes\Elements\Container;

class ListingsLoopElement extends Container {

	/**
	 * Cached loop runtime state keyed by instance id.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $runtime_state_cache = [];

	/**
	 * Loop utility widgets that contribute runtime state.
	 *
	 * @var array<int,string>
	 */
	protected const LOOP_UTILITY_WIDGET_TYPES = [
		'directorist_search_directory_types',
		'directorist_listings_header',
		'directorist_listings_filters',
		'directorist_listings_pagination',
	];

	/**
	 * Get element type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist_listings_loop';
	}

	/**
	 * Get element name.
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
	public function get_title() {
		return __( 'Listings Loop', 'directorist-elementor' );
	}

	/**
	 * Get element icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-posts-grid';
	}

	/**
	 * Source identifier stored in Directorist data attributes.
	 *
	 * @return string
	 */
	protected function get_loop_source(): string {
		return 'listings-loop';
	}

	/**
	 * Build wrapper classes for this loop element.
	 *
	 * @param string $active_view Active view type.
	 * @param string $display_mode Active display mode.
	 * @return array<int,string>
	 */
	protected function get_loop_wrapper_classes( string $active_view, string $display_mode = 'default' ): array {
		return [
			'directorist-elementor-listings-loop',
			'directorist-elementor-loop',
			'directorist-archive-contents',
			'directorist-contents-wrap',
			'directorist-w-100',
			'directorist-elementor-loop--' . sanitize_html_class( $active_view ),
			'directorist-elementor-loop--display-' . sanitize_html_class( $display_mode ),
		];
	}

	/**
	 * Build the loop notice shown in the editor.
	 *
	 * @return string
	 */
	protected function get_editor_notice(): string {
		return __( 'Compose your loop with child widgets such as search, header, filters, pagination, and card template.', 'directorist-elementor' );
	}

	/**
	 * Normalize settings before querying/rendering.
	 *
	 * @param array<string,mixed> $settings Raw display settings.
	 * @return array<string,mixed>
	 */
	protected function normalize_loop_settings( array $settings ): array {
		$raw_data     = $this->get_raw_data();
		$raw_settings = is_array( $raw_data['settings'] ?? null ) ? (array) $raw_data['settings'] : [];

		// Elementor omits conditionally hidden controls from display settings.
		// Keep every saved view/display-mode value available to frontend switches,
		// while allowing resolved globals and dynamic values to take precedence.
		$settings = array_replace_recursive(
			$raw_settings,
			array_filter(
				$settings,
				static fn( $value ): bool => null !== $value
			)
		);
		$settings['directorist_elementor_source'] = $this->get_loop_source();
		$settings['editor_notice'] = $this->get_editor_notice();

		return $settings;
	}

	/**
	 * Prevent inherited Container presets from leaking into Listings Loop.
	 *
	 * Core Container exposes the `container_grid` panel preset. Because this
	 * element extends Container, inheriting that preset causes Elementor to
	 * register a second Grid preset whose `originalWidget` becomes
	 * `directorist_listings_loop`, which then hijacks the core Layout > Grid
	 * entry and opens Listings Loop controls instead of Container/Grid controls.
	 *
	 * @return array<string,mixed>
	 */
	public function get_panel_presets() {
		return [];
	}

	/**
	 * Keep Elementor's element cache from freezing loop output.
	 *
	 * Listings Loop depends on request/query state and nested Directorist render
	 * context, so inherited Container static caching would stale pagination,
	 * filters, cards, and listing data.
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
	 * Get the default child widget structure for newly inserted loops.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_children_elements(): array {
		return [
			$this->get_default_child_widget( 'directorist_search_directory_types', __( 'Directory Types', 'directorist-elementor' ) ),
			$this->get_default_search_element( 'directorist_listings_search', __( 'Listings Search', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_listings_header', __( 'Listings Header', 'directorist-elementor' ) ),
			$this->get_default_card_template_element(),
			$this->get_default_child_widget( 'directorist_listings_pagination', __( 'Listings Pagination', 'directorist-elementor' ) ),
		];
	}

	/**
	 * Build a default search composition element payload.
	 *
	 * @param string $element_type Elementor element type.
	 * @param string $title Element title.
	 * @return array<string,mixed>
	 */
	protected function get_default_search_element( string $element_type, string $title ): array {
		return [
			'elType'          => $element_type,
			'settings'        => [
				'_title' => $title,
			],
			'elements'        => SearchFormRenderService::get_instance()->get_default_search_child_elements(),
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Build a default listing card template element payload.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_default_card_template_element(): array {
		$title = __( 'Listing Card Template', 'directorist-elementor' );

		return [
			'elType'          => 'directorist_listing_card_template',
			'settings'        => [
				'_title' => $title,
			],
			'elements'        => StructureDefaults::get_instance()->get_default_card_field_elements(),
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Build a default child widget payload.
	 *
	 * @param string $widget_type Elementor widget type.
	 * @param string $title Widget title.
	 * @return array<string,mixed>
	 */
	protected function get_default_child_widget( string $widget_type, string $title ): array {
		return [
			'elType'          => 'widget',
			'widgetType'      => $widget_type,
			'settings'        => [
				'_title' => $title,
			],
			'elements'        => [],
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Initial editor config.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_initial_config() {
		$config = parent::get_initial_config();
		$default_children = $this->get_default_children_elements();

		$config['show_in_panel'] = true;
		$config['categories'] = [ CategoryRegistrar::CATEGORY_ARCHIVE ];
		$config['title'] = $this->get_title();
		$config['icon'] = $this->get_icon();
		$config['include_in_widgets_config'] = true;
		$config['default_children'] = $default_children;
		$config['defaults'] = array_merge(
			is_array( $config['defaults'] ?? null ) ? $config['defaults'] : [],
			[
				'elements' => $default_children,
			]
		);

		return $config;
	}

	/**
	 * Register loop controls and native container controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_loop_controls();
		parent::register_controls();
	}

	/**
	 * Register loop-specific controls.
	 *
	 * @return void
	 */
	protected function register_loop_controls(): void {
		$bridge                    = DirectoristBridge::get_instance();
		$directory_options         = $bridge->get_directory_options();
		$listing_options           = $bridge->get_recent_listing_options( 50 );
		$category_options          = $this->get_taxonomy_term_options( $bridge->get_category_taxonomy() );
		$tag_options               = $this->get_taxonomy_term_options( $bridge->get_tag_taxonomy() );
		$location_options          = $this->get_taxonomy_term_options( $bridge->get_location_taxonomy() );
		$default_directory_options = [ 0 => __( 'Use First Selected Directory', 'directorist-elementor' ) ] + $directory_options;

		$this->start_controls_section(
			'section_loop_settings',
			[
				'label' => __( 'Settings', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'query_mode',
			[
				'label'   => __( 'Query Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'custom',
				'options' => [
					'default' => [
						'title' => __( 'Default', 'directorist-elementor' ),
						'icon'  => 'eicon-post-list',
					],
					'custom' => [
						'title' => __( 'Custom', 'directorist-elementor' ),
						'icon'  => 'eicon-edit',
					],
				],
				'toggle'  => false,
			]
		);

		$this->add_control(
			'query_mode_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Default mode uses the current template query on the frontend, while still allowing directory, view, sorting, pagination, and listing state controls. Switch to Custom only when you need manual listing, category, tag, or location filters.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition' => [
					'query_mode' => 'default',
				],
			]
		);

		$this->add_control(
			'directory_type_ids',
			[
				'label'       => __( 'Directory Types', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $directory_options,
				'description' => __( 'Select one or more directories that this loop can switch between.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'default_directory_type_id',
			[
				'label'       => __( 'Default Directory', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 0,
				'options'     => $default_directory_options,
				'description' => __( 'Controls the initial active directory when multiple directories are selected.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'display_mode',
			[
				'label'   => __( 'Display Mode', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default' => __( 'Default', 'directorist-elementor' ),
					'slider'  => __( 'Slider', 'directorist-elementor' ),
					'map_list' => __( 'Listings with Map', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'view_type',
			[
				'label'   => __( 'Default View', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid' => __( 'Grid', 'directorist-elementor' ),
					'list' => __( 'List', 'directorist-elementor' ),
					'map'  => __( 'Map', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode!' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_view_type',
			[
				'label'   => __( 'Default View', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid' => __( 'Grid', 'directorist-elementor' ),
					'list' => __( 'List', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'slider_map_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Slider mode is available for grid and list views. Map view continues to render as a map.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'condition'       => [
					'display_mode' => 'slider',
					'view_type'     => 'map',
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
					'6' => '6',
				],
				'description' => __( 'Applied to grid view.', 'directorist-elementor' ),
				'conditions'  => [
					'relation' => 'and',
					'terms'    => [
						[
							'name'     => 'display_mode',
							'operator' => '!==',
							'value'    => 'slider',
						],
						[
							'relation' => 'or',
							'terms'    => [
								[
									'name'     => 'active_view_type',
									'operator' => '===',
									'value'    => 'grid',
								],
								[
									'relation' => 'and',
									'terms'    => [
										[
											'name'     => 'active_view_type',
											'operator' => '===',
											'value'    => '',
										],
										[
											'name'     => 'view_type',
											'operator' => '===',
											'value'    => 'grid',
										],
									],
								],
							],
						],
					],
				],
			]
		);

		$this->register_loop_gap_controls( true );

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
				'condition'      => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'show_arrow_navigation',
			[
				'label'        => __( 'Arrow Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'show_dot_navigation',
			[
				'label'        => __( 'Dot Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'slider_autoplay',
			[
				'label'        => __( 'Autoplay', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'slider_effect',
			[
				'label'     => __( 'Effect', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'slide',
				'options'   => [
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
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'slider_grid_rows',
			[
				'label'     => __( 'Grid Rows', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 2,
				'min'       => 1,
				'max'       => 6,
				'condition' => [
					'display_mode'  => 'slider',
					'view_type!'     => 'map',
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
					'display_mode'     => 'slider',
					'view_type!'        => 'map',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'autoplay_delay',
			[
				'label'     => __( 'Autoplay Delay (ms)', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3000,
				'min'       => 100,
				'step'      => 50,
				'condition' => [
					'display_mode'     => 'slider',
					'view_type!'        => 'map',
					'slider_autoplay' => 'yes',
				],
			]
		);

		$this->add_control(
			'transition_speed',
			[
				'label'     => __( 'Transition Speed (ms)', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 500,
				'min'       => 100,
				'step'      => 50,
				'condition' => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'slider_mode_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Slider mode uses Listings Per Page as the slide count and does not render loop pagination.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => [
					'display_mode' => 'slider',
					'view_type!'    => 'map',
				],
			]
		);

		$this->add_control(
			'map_list_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Listings with Map renders grid or list cards beside a synchronized map. Map view switches are treated as grid cards with the map pane visible.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
				'condition'       => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_map_position',
			[
				'label'     => __( 'Map Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'right',
				'options'   => [
					'right'  => __( 'Right', 'directorist-elementor' ),
					'left'   => __( 'Left', 'directorist-elementor' ),
					'top'    => __( 'Top', 'directorist-elementor' ),
					'bottom' => __( 'Bottom', 'directorist-elementor' ),
				],
				'condition' => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_map_height',
			[
				'label'     => __( 'Map Height', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 520,
				'min'       => 220,
				'max'       => 1200,
				'step'      => 10,
				'condition' => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_map_width',
			[
				'label'     => __( 'Map Width (%)', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 42,
				'min'       => 25,
				'max'       => 70,
				'step'      => 1,
				'condition' => [
					'display_mode'          => 'map_list',
					'map_list_map_position' => [ 'left', 'right' ],
				],
			]
		);

		$this->add_control(
			'map_list_sticky_map',
			[
				'label'        => __( 'Sticky Map', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'condition'    => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_fit_on_load',
			[
				'label'        => __( 'Fit Map to Listings', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_hover_focus',
			[
				'label'        => __( 'Focus Map on Listing Hover', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'map_list_hover_zoom',
			[
				'label'     => __( 'Hover Zoom Level', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 14,
				'min'       => 1,
				'max'       => 22,
				'condition' => [
					'display_mode'          => 'map_list',
					'map_list_hover_focus'  => 'yes',
				],
			]
		);

		$this->add_control(
			'map_list_show_map_card',
			[
				'label'        => __( 'Show Map Card', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_control(
			'listings_per_page',
			[
				'label'   => __( 'Listings Per Page', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 6,
				'min'     => 1,
			]
		);

		$this->add_control(
			'order_by',
			[
				'label'   => __( 'Order By', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'title' => __( 'Title', 'directorist-elementor' ),
					'date'  => __( 'Date', 'directorist-elementor' ),
					'rand'  => __( 'Random', 'directorist-elementor' ),
					'price' => __( 'Price', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => __( 'Order', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'DESC',
				'options' => [
					'ASC'  => __( 'ASC', 'directorist-elementor' ),
					'DESC' => __( 'DESC', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'pagination_type',
			[
				'label'   => __( 'Pagination Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'numbered',
				'options' => [
					'numbered'        => __( 'Numbered', 'directorist-elementor' ),
					'infinite_scroll' => __( 'Infinite Scroll', 'directorist-elementor' ),
				],
				'conditions' => [
					'relation' => 'and',
					'terms'    => [
						[
							'name'     => 'display_mode',
							'operator' => '!==',
							'value'    => 'slider',
						],
						[
							'name'     => 'display_mode',
							'operator' => '!==',
							'value'    => 'map_list',
						],
					],
				],
			]
		);

		$this->add_control(
			'featured_only',
			[
				'label'        => __( 'Featured Listings Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'popular_only',
			[
				'label'        => __( 'Popular Listings Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'logged_in_user_only',
			[
				'label'        => __( 'Logged-In Users Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
			]
		);

		$this->add_control(
			'query_type',
			[
				'label'     => __( 'Query Type', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'regular',
				'options'   => [
					'regular'   => __( 'Regular', 'directorist-elementor' ),
					'selective' => __( 'Selective', 'directorist-elementor' ),
				],
				'condition' => [
					'query_mode' => 'custom',
				],
			]
		);

		$this->add_control(
			'listing_ids',
			[
				'label'       => __( 'Listings', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $listing_options,
				'condition'   => [
					'query_mode' => 'custom',
					'query_type' => 'selective',
				],
			]
		);

		$this->add_control(
			'category_ids',
			[
				'label'       => __( 'Categories', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $category_options,
				'condition'   => [
					'query_mode' => 'custom',
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'tag_ids',
			[
				'label'       => __( 'Tags', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $tag_options,
				'condition'   => [
					'query_mode' => 'custom',
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'location_ids',
			[
				'label'       => __( 'Locations', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'options'     => $location_options,
				'condition'   => [
					'query_mode' => 'custom',
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'card_gap',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 20,
			]
		);

		$this->add_control(
			'active_directory_type_id',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 0,
			]
		);

		$this->add_control(
			'active_view_type',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->add_control(
			'editor_preview_mode',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'results',
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_loop_advanced',
			[
				'label' => __( 'Advanced', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'empty_state_message',
			[
				'label'       => __( 'Empty State Message', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => __( 'No listings matched the current settings.', 'directorist-elementor' ),
				'rows'        => 2,
				'placeholder' => __( 'No listings matched the current settings.', 'directorist-elementor' ),
				'render_type' => 'none',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Register view-aware card spacing controls.
	 *
	 * @param bool $supports_display_modes Whether this loop exposes slider/map-list modes.
	 * @return void
	 */
	protected function register_loop_gap_controls( bool $supports_display_modes = true ): void {
		$this->add_control(
			'loop_card_spacing_heading',
			[
				'label'     => __( 'Card Spacing', 'directorist-elementor' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

		$this->add_responsive_control(
			'grid_card_gap',
			[
				'label'       => __( 'Grid Card Gap', 'directorist-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em', 'rem' ],
				'default'     => [
					'unit' => 'px',
					'size' => 20,
				],
				'range'       => $this->get_loop_gap_control_range(),
				'render_type' => 'none',
				'conditions'  => $this->get_loop_view_gap_conditions( 'grid', $supports_display_modes ),
			]
		);

		$this->add_responsive_control(
			'list_card_gap',
			[
				'label'       => __( 'List Card Gap', 'directorist-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em', 'rem' ],
				'default'     => [
					'unit' => 'px',
					'size' => 20,
				],
				'range'       => $this->get_loop_gap_control_range(),
				'render_type' => 'none',
				'conditions'  => $this->get_loop_view_gap_conditions( 'list', $supports_display_modes ),
			]
		);

		if ( ! $supports_display_modes ) {
			return;
		}

		$this->add_responsive_control(
			'slider_card_gap',
			[
				'label'          => __( 'Slider Card Gap', 'directorist-elementor' ),
				'type'           => Controls_Manager::SLIDER,
				'size_units'     => [ 'px' ],
				'default'        => [
					'unit' => 'px',
					'size' => 30,
				],
				'tablet_default' => [
					'unit' => 'px',
					'size' => 20,
				],
				'mobile_default' => [
					'unit' => 'px',
					'size' => 10,
				],
				'range'          => $this->get_loop_gap_control_range(),
				'render_type'    => 'none',
				'conditions'     => [
					'relation' => 'and',
					'terms'    => [
						[
							'name'     => 'display_mode',
							'operator' => '===',
							'value'    => 'slider',
						],
						[
							'relation' => 'or',
							'terms'    => [
								[
									'name'     => 'active_view_type',
									'operator' => '===',
									'value'    => 'grid',
								],
								[
									'name'     => 'active_view_type',
									'operator' => '===',
									'value'    => 'list',
								],
								[
									'relation' => 'and',
									'terms'    => [
										[
											'name'     => 'active_view_type',
											'operator' => '===',
											'value'    => '',
										],
										[
											'name'     => 'view_type',
											'operator' => '!==',
											'value'    => 'map',
										],
									],
								],
							],
						],
					],
				],
			]
		);

		$this->add_responsive_control(
			'map_list_card_gap',
			[
				'label'       => __( 'Map List Card Gap', 'directorist-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em', 'rem' ],
				'default'     => [
					'unit' => 'px',
					'size' => 20,
				],
				'range'       => $this->get_loop_gap_control_range(),
				'render_type' => 'none',
				'condition'   => [
					'display_mode' => 'map_list',
				],
			]
		);

		$this->add_responsive_control(
			'map_list_pane_gap',
			[
				'label'       => __( 'List and Map Gap', 'directorist-elementor' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px', 'em', 'rem' ],
				'default'     => [
					'unit' => 'px',
					'size' => 24,
				],
				'range'       => $this->get_loop_gap_control_range(),
				'render_type' => 'none',
				'condition'   => [
					'display_mode' => 'map_list',
				],
			]
		);
	}

	/**
	 * Build the shared range map for loop gap sliders.
	 *
	 * @return array<string,array<string,int|float>>
	 */
	protected function get_loop_gap_control_range(): array {
		return [
			'px'  => [
				'min'  => 0,
				'max'  => 160,
				'step' => 1,
			],
			'em'  => [
				'min'  => 0,
				'max'  => 12,
				'step' => 0.1,
			],
			'rem' => [
				'min'  => 0,
				'max'  => 12,
				'step' => 0.1,
			],
		];
	}

	/**
	 * Build visibility conditions for grid/list card spacing controls.
	 *
	 * @param string $view_type View type.
	 * @param bool   $supports_display_modes Whether display mode controls exist.
	 * @return array<string,mixed>
	 */
	protected function get_loop_view_gap_conditions( string $view_type, bool $supports_display_modes = true ): array {
		$terms = [];

		if ( $supports_display_modes ) {
			$terms[] = [
				'name'     => 'display_mode',
				'operator' => '!==',
				'value'    => 'slider',
			];
			$terms[] = [
				'name'     => 'display_mode',
				'operator' => '!==',
				'value'    => 'map_list',
			];
		}

		$terms[] = [
			'relation' => 'or',
			'terms'    => [
				[
					'name'     => 'active_view_type',
					'operator' => '===',
					'value'    => $view_type,
				],
				[
					'relation' => 'and',
					'terms'    => [
						[
							'name'     => 'active_view_type',
							'operator' => '===',
							'value'    => '',
						],
						[
							'name'     => 'view_type',
							'operator' => '===',
							'value'    => $view_type,
						],
					],
				],
			],
		];

		return [
			'relation' => 'and',
			'terms'    => $terms,
		];
	}

	/**
	 * Get term options for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array<int|string,string>
	 */
	protected function get_taxonomy_term_options( string $taxonomy ): array {
		$options = [];

		if ( '' === $taxonomy ) {
			return $options;
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Extend container wrapper attributes.
	 *
	 * @return void
	 */
	protected function add_render_attributes() {
		parent::add_render_attributes();

		$settings         = $this->normalize_loop_settings( $this->get_settings_for_display() );
		$instance_id      = InstanceState::get_instance()->normalize_instance_id( 'direl-loop-' . $this->get_id() );
		$runtime_state    = $this->get_runtime_state( $settings, $instance_id );
		$query_args       = is_array( $runtime_state['query_args'] ?? null ) ? (array) $runtime_state['query_args'] : [];
		$active_directory = (int) ( $runtime_state['active_directory'] ?? 0 );
		$active_author    = (int) ( $runtime_state['active_author'] ?? 0 );
		$active_view      = (string) ( $runtime_state['active_view'] ?? 'grid' );
		$display_mode     = LoopRenderService::get_instance()->normalize_display_mode( $runtime_state['display_mode'] ?? 'default' );
		$map_list_settings = is_array( $runtime_state['map_list_settings'] ?? null )
			? (array) $runtime_state['map_list_settings']
			: LoopRenderService::get_instance()->resolve_map_list_settings( $settings );
		$listing_ids      = array_values( array_map( 'absint', (array) ( $runtime_state['listing_ids'] ?? [] ) ) );
		$data_atts        = is_array( $runtime_state['data_atts'] ?? null ) ? (array) $runtime_state['data_atts'] : [];
		$wrapper_classes  = $this->get_loop_wrapper_classes( $active_view, $display_mode );
		$loop_style       = LoopRenderService::get_instance()->build_loop_style(
			$settings,
			$active_view,
			(int) ( $query_args['columns'] ?? 0 )
		);

		if ( 'map_list' === $display_mode ) {
			$map_position      = sanitize_key( (string) ( $map_list_settings['map_position'] ?? 'right' ) );
			$map_position      = in_array( $map_position, [ 'right', 'left', 'top', 'bottom' ], true ) ? $map_position : 'right';
			$wrapper_classes[] = 'directorist-elementor-loop--map-position-' . sanitize_html_class( $map_position );

			if ( ! empty( $map_list_settings['sticky_map'] ) ) {
				$wrapper_classes[] = 'directorist-elementor-loop--map-sticky';
			}

			$loop_style .= '--direl-map-list-map-height:' . max( 220, absint( $map_list_settings['map_height'] ?? 520 ) ) . 'px;';
			$loop_style .= '--direl-map-list-map-width:' . max( 25, min( 70, absint( $map_list_settings['map_width'] ?? 42 ) ) ) . '%;';
		}

		$this->add_render_attribute(
			'_wrapper',
			[
				'class'               => $wrapper_classes,
				'style'               => $loop_style,
				'data-atts'           => ! empty( $data_atts ) ? wp_json_encode( $data_atts ) : null,
				'data-direl-view'      => $active_view,
				'data-direl-directory' => (string) $active_directory,
				'data-direl-author'    => $active_author > 0 ? (string) $active_author : null,
				'data-display-mode'    => $display_mode,
				'data-direl-loop-id'   => (string) $this->get_id(),
				'data-direl-post-id'   => (string) $this->resolve_source_post_id(),
				'data-direl-request-post-id' => (string) $this->resolve_request_post_id(),
				'data-pagination-type'=> (string) ( $query_args['pagination_type'] ?? 'numbered' ),
				'data-listings-count' => (string) count( $listing_ids ),
				'data-current-page'   => (string) (int) ( $runtime_state['current_page'] ?? 1 ),
				'data-max-pages'      => (string) (int) ( $runtime_state['max_pages'] ?? 1 ),
				'data-direl-instance'  => $instance_id,
				'data-map-list-position' => 'map_list' === $display_mode ? (string) ( $map_list_settings['map_position'] ?? 'right' ) : null,
				'data-map-list-map-height' => 'map_list' === $display_mode ? (string) max( 220, absint( $map_list_settings['map_height'] ?? 520 ) ) : null,
				'data-map-list-map-width' => 'map_list' === $display_mode ? (string) max( 25, min( 70, absint( $map_list_settings['map_width'] ?? 42 ) ) ) : null,
				'data-map-list-sticky-map' => 'map_list' === $display_mode ? ( ! empty( $map_list_settings['sticky_map'] ) ? '1' : '0' ) : null,
				'data-map-list-fit-on-load' => 'map_list' === $display_mode ? ( ! empty( $map_list_settings['fit_on_load'] ) ? '1' : '0' ) : null,
				'data-map-list-hover-focus' => 'map_list' === $display_mode ? ( ! empty( $map_list_settings['hover_focus'] ) ? '1' : '0' ) : null,
				'data-map-list-hover-zoom' => 'map_list' === $display_mode ? (string) max( 1, min( 22, absint( $map_list_settings['hover_zoom'] ?? 14 ) ) ) : null,
				'data-map-list-show-map-card' => 'map_list' === $display_mode ? ( ! empty( $map_list_settings['show_map_card'] ) ? '1' : '0' ) : null,
			]
		);
	}

	/**
	 * Print element content.
	 *
	 * @return void
	 */
	protected function print_content() {
		$instance_id      = InstanceState::get_instance()->normalize_instance_id( 'direl-loop-' . $this->get_id() );
		$settings         = $this->normalize_loop_settings( $this->get_settings_for_display() );
		$children         = array_values( $this->get_children() );
		$has_composition  = $this->has_composition_children( $children );
		$runtime_state    = $this->get_runtime_state( $settings, $instance_id );
		$loop_raw_data    = $this->get_raw_data();
		$runtime_state['utility_state'] = LoopRenderService::get_instance()->extract_utility_state_from_elements(
			array_merge(
				is_array( $loop_raw_data['elements'] ?? null ) ? (array) $loop_raw_data['elements'] : [],
				$this->get_child_utility_raw_elements( $children )
			)
		);

		if ( ! $this->is_editor_context() ) {
			$this->preload_loop_map_assets();
		}

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			if ( $this->is_editor_context() ) {
				echo LoopRenderService::get_instance()->render_editor_state_markup( $runtime_state ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}

			echo '<div class="directorist-elementor-loop__inner">';

			foreach ( $children as $child ) {
				if ( is_object( $child ) && method_exists( $child, 'print_element' ) ) {
					$child->print_element();
				}
			}

			if ( ! $has_composition ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Loop Composition Missing', 'directorist-elementor' ),
						__( 'Add child widgets directly inside Listings Loop. Insert Listing Card Template where repeated listings should appear.', 'directorist-elementor' )
					)
				);
			}

			echo '</div>';
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}
	}

	/**
	 * Build a compact raw-data list for utility child widgets.
	 *
	 * Elementor does not consistently expose nested children in get_raw_data()
	 * during editor renders, but utility widgets still need loop-wide state.
	 *
	 * @param array<int,object> $children Child elements.
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_child_utility_raw_elements( array $children ): array {
		$utility_elements = [];

		foreach ( $children as $child ) {
			if ( ! is_object( $child ) ) {
				continue;
			}

			$raw_data = [];
			try {
				if ( is_callable( [ $child, 'get_raw_data' ] ) ) {
					$raw_data = $child->get_raw_data();
				}
			} catch ( \Throwable $throwable ) {
				$raw_data = [];
			}

			if ( ! is_array( $raw_data ) ) {
				$raw_data = [];
			}

			if ( ! empty( $raw_data['hidden'] ) ) {
				continue;
			}

			$widget_type = sanitize_key( (string) ( $raw_data['widgetType'] ?? '' ) );
			if ( '' === $widget_type && is_callable( [ $child, 'get_name' ] ) ) {
				$widget_type = sanitize_key( (string) $child->get_name() );
			}

			if ( ! in_array( $widget_type, self::LOOP_UTILITY_WIDGET_TYPES, true ) ) {
				continue;
			}

			$settings = is_array( $raw_data['settings'] ?? null ) ? (array) $raw_data['settings'] : [];
			if ( empty( $settings ) && is_callable( [ $child, 'get_settings_for_display' ] ) ) {
				try {
					$settings = (array) $child->get_settings_for_display();
				} catch ( \Throwable $throwable ) {
					$settings = [];
				}
			}

			$utility_elements[] = [
				'id'         => (string) ( $raw_data['id'] ?? '' ),
				'elType'     => (string) ( $raw_data['elType'] ?? 'widget' ),
				'widgetType' => $widget_type,
				'settings'   => $settings,
				'elements'   => [],
			];
		}

		return $utility_elements;
	}

	/**
	 * Preload Directorist map assets for loops that may switch to map view.
	 *
	 * Frontend loop interactions replace the loop HTML through AJAX. If map
	 * assets are only enqueued while rendering the AJAX response, the browser
	 * receives new markup but no new script tags. Preloading the map script
	 * during the initial page render keeps map view available when users switch
	 * from grid/list to map later.
	 *
	 * @return void
	 */
	protected function preload_loop_map_assets(): void {
		$asset_loader_class = null;

		if ( class_exists( '\\Directorist\\Asset_Loader\\Asset_Loader' ) ) {
			$asset_loader_class = '\\Directorist\\Asset_Loader\\Asset_Loader';
		} elseif ( class_exists( '\\Directorist_Asset_Loader' ) ) {
			$asset_loader_class = '\\Directorist_Asset_Loader';
		}

		if ( null === $asset_loader_class ) {
			return;
		}

		if ( method_exists( $asset_loader_class, 'register_scripts' ) ) {
			$asset_loader_class::register_scripts();
		}

		if ( method_exists( $asset_loader_class, 'enqueue_map_scripts' ) ) {
			$asset_loader_class::enqueue_map_scripts();
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
		const editorConfig = window.directoristElementorV4Editor || {};
		const directoryOptions = editorConfig.directoryOptions || {};
		const viewLabels = editorConfig.viewLabels || { grid: 'Grid', list: 'List', map: 'Map' };
		const selectedDirectoryIds = Array.isArray( settings.directory_type_ids )
			? _.chain( settings.directory_type_ids )
				.map( function( directoryId ) {
					return parseInt( directoryId, 10 ) || 0;
				} )
				.filter( function( directoryId ) {
					return directoryId > 0;
				} )
				.value()
			: [];
		const previewView = settings.active_view_type || ( 'map_list' === settings.display_mode ? settings.map_list_view_type : settings.view_type ) || 'grid';
		const availableViewTypes = [ 'grid', 'list', 'map' ];
		const seenDirectories = {};
		const directoryTabs = [];
		const viewIcons = {
			grid: 'eicon-gallery-grid',
			list: 'eicon-post-list',
			map: 'eicon-google-maps',
		};

		const pushDirectoryTab = ( directoryId ) => {
			const normalizedId = String( directoryId || '0' );

			if ( '0' === normalizedId || seenDirectories[ normalizedId ] ) {
				return;
			}

			seenDirectories[ normalizedId ] = true;

			if ( undefined === directoryOptions[ normalizedId ] ) {
				return;
			}

			directoryTabs.push( {
				id: normalizedId,
				label: directoryOptions[ normalizedId ],
			} );
		};

		if ( selectedDirectoryIds.length ) {
			_.each( selectedDirectoryIds, function( directoryId ) {
				pushDirectoryTab( directoryId );
			} );
		} else {
			_.each( Object.keys( directoryOptions ), function( directoryId ) {
				pushDirectoryTab( directoryId );
			} );
		}

		const activeDirectoryCandidate = String( settings.active_directory_type_id || '0' );
		const activeDirectoryId = String(
			_.some( directoryTabs, function( tab ) {
				return String( tab.id ) === activeDirectoryCandidate;
			} )
				? activeDirectoryCandidate
				: ( directoryTabs.length ? directoryTabs[ 0 ].id : 0 )
		);
		#>
		<# if ( 'boxed' === settings.content_width ) { #>
			<div class="e-con-inner">
		<# } #>
		<div class="directorist-elementor-loop__state">
			<p class="directorist-elementor-loop__notice"><?php echo esc_html( $this->get_editor_notice() ); ?></p>
			<div class="directorist-elementor-loop__scope" role="group" aria-label="<?php echo esc_attr__( 'Listing branch scope', 'directorist-elementor' ); ?>">
				<div class="directorist-elementor-loop__directory-tabs">
					<# _.each( directoryTabs, function( tab ) { #>
						<button type="button" class="directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--directory {{ tab.id === activeDirectoryId ? 'is-active' : '' }}" data-direl-loop-directory="{{ tab.id }}">{{{ tab.label }}}</button>
					<# } ); #>
				</div>
				<div class="directorist-elementor-loop__view-tabs">
					<# _.each( availableViewTypes, function( viewType ) { #>
						<button
							type="button"
							class="directorist-elementor-loop__scope-button directorist-elementor-loop__scope-button--view {{ viewType === previewView ? 'is-active' : '' }}"
							data-direl-loop-view="{{ viewType }}"
							aria-label="{{ viewLabels[ viewType ] || viewType }}"
							title="{{ viewLabels[ viewType ] || viewType }}"
						>
							<i class="{{ viewIcons[ viewType ] || '' }}" aria-hidden="true"></i>
						</button>
					<# } ); #>
				</div>
			</div>
		</div>
		<div class="directorist-elementor-loop__inner"></div>
		<# if ( 'boxed' === settings.content_width ) { #>
			</div>
		<# } #>
		<?php
	}

	/**
	 * Resolve and cache loop runtime state for the current element instance.
	 *
	 * @param array<string,mixed> $settings Settings.
	 * @param string              $instance_id Instance id.
	 * @return array<string,mixed>
	 */
	protected function get_runtime_state( array $settings, string $instance_id ): array {
		if ( isset( $this->runtime_state_cache[ $instance_id ] ) ) {
			return $this->runtime_state_cache[ $instance_id ];
		}

		$this->runtime_state_cache[ $instance_id ] = LoopRenderService::get_instance()->build_runtime_state(
			$settings,
			$instance_id,
			$this->is_editor_context(),
			null
		);

		return $this->runtime_state_cache[ $instance_id ];
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
	 * Resolve the Elementor document id that owns this saved loop payload.
	 *
	 * Theme Builder archive renders run inside the matched archive template
	 * document, while `get_the_ID()` still points at the routed page/post. Frontend
	 * loop AJAX must load the source Elementor document that actually contains the
	 * loop in `_elementor_data`, so prefer the current Elementor document id.
	 *
	 * @return int
	 */
	protected function resolve_source_post_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();

			if ( $current_document && method_exists( $current_document, 'get_main_id' ) ) {
				$current_document_id = absint( $current_document->get_main_id() );

				if ( $current_document_id > 0 ) {
					return $current_document_id;
				}
			}
		}

		return absint( get_the_ID() );
	}

	/**
	 * Resolve the frontend request post id for rerenders.
	 *
	 * This preserves the original page/post context during AJAX loop rerenders.
	 * On Theme Builder archives the source Elementor document can differ from the
	 * request post that templates and third-party hooks expect.
	 *
	 * @return int
	 */
	protected function resolve_request_post_id(): int {
		$current_post = $GLOBALS['post'] ?? null;

		if ( $current_post instanceof \WP_Post ) {
			return absint( $current_post->ID );
		}

		return absint( get_the_ID() );
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
