<?php
/**
 * Standalone Directorist all categories widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Archive;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractTaxonomyCompositionWidget;
use Elementor\Controls_Manager;

class AllCategoriesWidget extends AbstractTaxonomyCompositionWidget {

	public function get_name(): string {
		return 'directorist_all_categories';
	}

	public function get_title(): string {
		return __( 'All Categories', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-folder-o';
	}

	protected function get_taxonomy_scope(): string {
		return 'category';
	}

	protected function get_archive_shortcode(): string {
		return 'directorist_all_categories';
	}

	protected function get_card_template_title(): string {
		return __( 'Category Card Template', 'directorist-elementor' );
	}

	protected function register_widget_controls(): void {
		$bridge            = DirectoristBridge::get_instance();
		$directory_options = $bridge->get_directory_slug_options();

		// --- Content: Settings ---
		$this->start_controls_section(
			'section_settings',
			[
				'label' => __( 'Settings', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'directory_type',
			[
				'label'       => __( 'Directory Types', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $directory_options,
				'description' => __( 'Choose one or more directory types for top directory tabs.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_all_directory_tab',
			[
				'label'        => __( 'Enable All Tab', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'all_tab_icon',
			[
				'label'     => __( 'All Tab Icon', 'directorist-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'fas fa-th-large',
					'library' => 'fa-solid',
				],
				'condition' => [
					'show_all_directory_tab' => 'yes',
				],
				'description' => __( 'Icon used for the All tab only.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'default_directory_type',
			[
				'label'   => __( 'Default Directory', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => [ '' => __( 'All', 'directorist-elementor' ) ] + $directory_options,
			]
		);

		// Display mode + slider controls (inline).
		$this->register_slider_controls_inline();

		$this->add_control(
			'show_all_items',
			[
				'label'        => __( 'Show All Categories', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'display_mode' => 'pagination',
				],
			]
		);

		$this->register_taxonomy_layout_controls( __( 'Category', 'directorist-elementor' ) );

		$this->add_control(
			'cat_per_page',
			[
				'label'     => __( 'Categories Per Page', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'min'       => 1,
				'condition' => [
					'display_mode'    => 'pagination',
					'show_all_items!' => 'yes',
				],
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'     => __( 'Order By', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'id',
				'options'   => [
					'id'    => __( 'ID', 'directorist-elementor' ),
					'count' => __( 'Count', 'directorist-elementor' ),
					'name'  => __( 'Name', 'directorist-elementor' ),
					'slug'  => __( 'Selected Categories', 'directorist-elementor' ),
				],
				'separator' => 'before',
			]
		);

		$this->add_control(
			'slug',
			[
				'label'       => __( 'Category Slugs', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'description' => __( 'Comma-separated category slugs.', 'directorist-elementor' ),
				'condition'   => [
					'orderby' => 'slug',
				],
			]
		);

		$this->add_control(
			'order',
			[
				'label'   => __( 'Order', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'desc',
				'options' => [
					'asc'  => __( 'ASC', 'directorist-elementor' ),
					'desc' => __( 'DESC', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'show_image',
			[
				'label'        => __( 'Show Image', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
				'condition'    => [
					'view!' => 'list',
				],
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
				'condition'    => [
					'view!' => 'list',
				],
			]
		);

		$this->add_control(
			'show_description',
			[
				'label'        => __( 'Show Description', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'view!' => 'list',
				],
			]
		);

		$this->add_control(
			'logged_in_user_only',
			[
				'label'        => __( 'Logged In User Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'separator'    => 'before',
			]
		);

		$this->end_controls_section();

		// --- Style tab sections ---
		$this->register_taxonomy_style_sections(
			__( 'Category Item', 'directorist-elementor' )
		);
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$this->render_taxonomy_archive_widget(
			[
				'view'                   => sanitize_key( (string) ( $settings['view'] ?? 'grid' ) ),
				'columns'                => absint( $settings['columns'] ?? 3 ),
				'columns_tablet'         => absint( $settings['columns_tablet'] ?? 2 ),
				'columns_mobile'         => absint( $settings['columns_mobile'] ?? 1 ),
				'cat_per_page'           => 'yes' === ( $settings['show_all_items'] ?? '' ) ? 9999 : absint( $settings['cat_per_page'] ?? 6 ),
				'orderby'                => sanitize_key( (string) ( $settings['orderby'] ?? 'id' ) ),
				'order'                  => sanitize_key( (string) ( $settings['order'] ?? 'desc' ) ),
				'slug'                   => sanitize_text_field( (string) ( $settings['slug'] ?? '' ) ),
				'directory_type'         => implode( ',', array_filter( (array) ( $settings['directory_type'] ?? [] ) ) ),
				'default_directory_type' => sanitize_text_field( (string) ( $settings['default_directory_type'] ?? '' ) ),
				'logged_in_user_only'    => 'yes' === ( $settings['logged_in_user_only'] ?? '' ) ? 'yes' : 'no',
			]
		);
	}
}
