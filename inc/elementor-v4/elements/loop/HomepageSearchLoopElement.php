<?php
/**
 * Container-based homepage search loop element.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Elements\Loop;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use Elementor\Controls_Manager;

class HomepageSearchLoopElement extends ListingsLoopElement {
	/**
	 * Get element type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return 'directorist_homepage_search_loop';
	}

	/**
	 * Get element title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'Homepage Search Loop', 'directorist-elementor' );
	}

	/**
	 * Get element icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-search-results';
	}

	/**
	 * Source identifier stored in Directorist data attributes.
	 *
	 * @return string
	 */
	protected function get_loop_source(): string {
		return 'homepage-search-loop';
	}

	/**
	 * Build wrapper classes for this loop element.
	 *
	 * @param string $active_view Active view type.
	 * @param string $display_mode Active display mode.
	 * @return array<int,string>
	 */
	protected function get_loop_wrapper_classes( string $active_view, string $display_mode = 'default' ): array {
		$classes   = parent::get_loop_wrapper_classes( $active_view, $display_mode );
		$classes[] = 'directorist-elementor-homepage-search-loop';

		return $classes;
	}

	/**
	 * Build the loop notice shown in the editor.
	 *
	 * @return string
	 */
	protected function get_editor_notice(): string {
		return __( 'Compose this result template with Directory Types, Homepage Search, header, filters, pagination, and card template.', 'directorist-elementor' );
	}

	/**
	 * Get the default child widget structure for newly inserted loops.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_children_elements(): array {
		return [
			$this->get_default_child_widget( 'directorist_search_directory_types', __( 'Directory Types', 'directorist-elementor' ) ),
			$this->get_default_search_element( 'directorist_homepage_search', __( 'Homepage Search', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_listings_header', __( 'Listings Header', 'directorist-elementor' ) ),
			$this->get_default_card_template_element(),
			$this->get_default_child_widget( 'directorist_listings_pagination', __( 'Listings Pagination', 'directorist-elementor' ) ),
		];
	}

	/**
	 * Register home-search-loop controls.
	 *
	 * @return void
	 */
	protected function register_loop_controls(): void {
		$bridge                    = DirectoristBridge::get_instance();
		$directory_options         = $bridge->get_directory_options();
		$default_directory_options = [ 0 => __( 'Use First Selected Directory', 'directorist-elementor' ) ] + $directory_options;

		$this->start_controls_section(
			'section_loop_settings',
			[
				'label' => __( 'Settings', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'home_search_loop_notice',
			[
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => esc_html__( 'Homepage Search Loop controls the directory context for this result template. Standalone Homepage Search widgets inherit this saved configuration.', 'directorist-elementor' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
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
				'description' => __( 'Select one or more directories for this Homepage Search result flow.', 'directorist-elementor' ),
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

		$this->register_loop_gap_controls( false );

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
			'query_mode',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'default',
			]
		);

		$this->add_control(
			'query_type',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => 'regular',
			]
		);

		$this->add_control(
			'directorist_elementor_source',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => $this->get_loop_source(),
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
				'default'     => __( 'No listings matched the current search.', 'directorist-elementor' ),
				'rows'        => 2,
				'placeholder' => __( 'No listings matched the current search.', 'directorist-elementor' ),
				'render_type' => 'none',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Normalize settings before querying/rendering.
	 *
	 * @param array<string,mixed> $settings Raw display settings.
	 * @return array<string,mixed>
	 */
	protected function normalize_loop_settings( array $settings ): array {
		$settings = parent::normalize_loop_settings( $settings );

		$settings['query_mode'] = 'default';
		$settings['query_type'] = 'regular';
		$settings['directorist_elementor_source'] = $this->get_loop_source();

		return $settings;
	}
}
