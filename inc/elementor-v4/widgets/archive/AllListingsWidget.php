<?php
/**
 * Standalone Directorist all listings widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Archive;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractArchiveShortcodeWidget;
use Elementor\Controls_Manager;

class AllListingsWidget extends AbstractArchiveShortcodeWidget {

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_all_listings';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'All Listings', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-post-list';
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$bridge            = DirectoristBridge::get_instance();
		$directory_options = $bridge->get_directory_slug_options();

		$this->start_controls_section(
			'section_all_listings_content',
			[
				'label' => __( 'All Listings', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'header',
			[
				'label'        => __( 'Show Header', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'header_title',
			[
				'label'     => __( 'Header Title', 'directorist-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Listings Found', 'directorist-elementor' ),
				'condition' => [
					'header' => 'yes',
				],
			]
		);

		$this->add_control(
			'view',
			[
				'label'   => __( 'View', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'grid',
				'options' => [
					'grid' => __( 'Grid', 'directorist-elementor' ),
					'list' => __( 'List', 'directorist-elementor' ),
					'map'  => __( 'Map', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'columns',
			[
				'label'     => __( 'Columns', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '3',
				'options'   => [
					'1' => '1',
					'2' => '2',
					'3' => '3',
					'4' => '4',
					'5' => '5',
					'6' => '6',
				],
				'condition' => [
					'view' => 'grid',
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
			'show_pagination',
			[
				'label'        => __( 'Show Pagination', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'query_type',
			[
				'label'   => __( 'Query Type', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'regular',
				'options' => [
					'regular'   => __( 'Regular', 'directorist-elementor' ),
					'selective' => __( 'Selective', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'listing_ids',
			[
				'label'       => __( 'Listings', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $bridge->get_recent_listing_options( 100 ),
				'condition'   => [
					'query_type' => 'selective',
				],
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
			]
		);

		$this->add_control(
			'default_directory_type',
			[
				'label'       => __( 'Default Directory', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => [ '' => __( 'Use First Selected Directory', 'directorist-elementor' ) ] + $directory_options,
			]
		);

		$this->add_control(
			'category',
			[
				'label'       => __( 'Categories', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $bridge->get_category_options(),
				'condition'   => [
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'location',
			[
				'label'       => __( 'Locations', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $bridge->get_location_options(),
				'condition'   => [
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'tag',
			[
				'label'       => __( 'Tags', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => $bridge->get_tag_options(),
				'condition'   => [
					'query_type' => 'regular',
				],
			]
		);

		$this->add_control(
			'featured_only',
			[
				'label'        => __( 'Featured Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'popular_only',
			[
				'label'        => __( 'Popular Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'logged_in_user_only',
			[
				'label'        => __( 'Logged In Users Only', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'orderby',
			[
				'label'   => __( 'Order By', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'date',
				'options' => [
					'date'  => __( 'Date', 'directorist-elementor' ),
					'title' => __( 'Title', 'directorist-elementor' ),
					'price' => __( 'Price', 'directorist-elementor' ),
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

		$this->end_controls_section();
	}

	/**
	 * Render the shortcode widget.
	 *
	 * @return void
	 */
	protected function render(): void {
		$settings = $this->get_settings_for_display();

		$this->render_archive_shortcode(
			'directorist_all_listing',
			[
				'header'                => 'yes' === ( $settings['header'] ?? 'yes' ) ? 'yes' : 'no',
				'header_title'          => sanitize_text_field( (string) ( $settings['header_title'] ?? '' ) ),
				'view'                  => sanitize_key( (string) ( $settings['view'] ?? 'grid' ) ),
				'columns'               => absint( $settings['columns'] ?? 3 ),
				'listings_per_page'     => absint( $settings['listings_per_page'] ?? 6 ),
				'show_pagination'       => 'yes' === ( $settings['show_pagination'] ?? '' ) ? 'yes' : 'no',
				'query_type'            => sanitize_key( (string) ( $settings['query_type'] ?? 'regular' ) ),
				'ids'                   => implode( ',', array_filter( (array) ( $settings['listing_ids'] ?? [] ) ) ),
				'directory_type'        => implode( ',', array_filter( (array) ( $settings['directory_type'] ?? [] ) ) ),
				'default_directory_type'=> sanitize_text_field( (string) ( $settings['default_directory_type'] ?? '' ) ),
				'category'              => implode( ',', array_filter( (array) ( $settings['category'] ?? [] ) ) ),
				'location'              => implode( ',', array_filter( (array) ( $settings['location'] ?? [] ) ) ),
				'tag'                   => implode( ',', array_filter( (array) ( $settings['tag'] ?? [] ) ) ),
				'featured_only'         => 'yes' === ( $settings['featured_only'] ?? '' ) ? 'yes' : 'no',
				'popular_only'          => 'yes' === ( $settings['popular_only'] ?? '' ) ? 'yes' : 'no',
				'logged_in_user_only'   => 'yes' === ( $settings['logged_in_user_only'] ?? '' ) ? 'yes' : 'no',
				'orderby'               => sanitize_key( (string) ( $settings['orderby'] ?? 'date' ) ),
				'order'                 => sanitize_key( (string) ( $settings['order'] ?? 'desc' ) ),
			]
		);
	}
}
