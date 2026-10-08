<?php
/**
 * Listing card image slider widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\FieldsPreset;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractMediaFieldWidget;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Icons_Manager;
use Elementor\Repeater;

class ListingCardImagesSliderWidget extends AbstractMediaFieldWidget {

	public function get_name(): string {
		return 'directorist_listing_card_images_slider';
	}

	public function get_title(): string {
		return __( 'Listing Images Slider', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-slides';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'slider', 'gallery', 'image', 'grid', 'masonry' ] );
	}

	protected function register_widget_controls(): void {
		$this->register_content_controls();
		$this->register_grid_controls();
		$this->register_flex_controls();
		$this->register_masonry_controls();
		$this->register_lightbox_button_controls();
		$this->register_image_style_controls();
		$this->register_photo_button_style_controls();
		$this->register_navigation_style_controls();
	}

	protected function register_content_controls(): void {
		$directory_type_id = $this->resolve_document_directory_type_id();

		$this->start_controls_section(
			'section_images_slider_content',
			[
				'label' => __( 'Images Slider', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'image_quality',
			[
				'label'   => __( 'Image Quality', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'default',
				'options' => [
					'default'   => __( 'Default', 'directorist-elementor' ),
					'thumbnail' => __( 'Thumbnail', 'directorist-elementor' ),
					'medium'    => __( 'Medium', 'directorist-elementor' ),
					'large'     => __( 'Large', 'directorist-elementor' ),
					'full'      => __( 'Full', 'directorist-elementor' ),
				],
			]
		);

		$this->add_control(
			'preview_image_first',
			[
				'label'        => __( 'Show Preview Image First', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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
			'view_mode',
			[
				'label'       => __( 'View Mode', 'directorist-elementor' ),
				'type'        => Controls_Manager::CHOOSE,
				'default'     => 'slider',
				'toggle'      => false,
				'label_block' => true,
				'options'     => [
					'slider'  => [
						'title' => __( 'Slider', 'directorist-elementor' ),
						'icon'  => 'eicon-slider-push',
					],
					'grid'    => [
						'title' => __( 'Grid', 'directorist-elementor' ),
						'icon'  => 'eicon-gallery-grid',
					],
					'flex'    => [
						'title' => __( 'Flex', 'directorist-elementor' ),
						'icon'  => 'eicon-align-stretch-h',
					],
					'masonry' => [
						'title' => __( 'Masonry', 'directorist-elementor' ),
						'icon'  => 'eicon-gallery-masonry',
					],
				],
			]
		);

		$this->add_responsive_control(
			'slides_per_view',
			[
				'label'          => __( 'Number of Slides', 'directorist-elementor' ),
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
					'view_mode' => 'slider',
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
					'cube'      => __( 'Cube', 'directorist-elementor' ),
					'coverflow' => __( 'Coverflow', 'directorist-elementor' ),
					'flip'      => __( 'Flip', 'directorist-elementor' ),
					'cards'     => __( 'Cards', 'directorist-elementor' ),
				],
				'condition' => [
					'view_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'autoplay',
			[
				'label'        => __( 'Autoplay', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
				'condition'    => [
					'view_mode' => 'slider',
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
					'view_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'show_navigation',
			[
				'label'        => __( 'Show Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'view_mode' => 'slider',
				],
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
				'default'      => 'yes',
				'condition'    => [
					'view_mode' => 'slider',
				],
			]
		);

		$this->add_responsive_control(
			'items_to_show',
			[
				'label'       => __( 'Images to Show', 'directorist-elementor' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 99,
				'step'        => 1,
				'description' => __( 'Set 0 to render all images in grid, flex, or masonry mode.', 'directorist-elementor' ),
				'condition'   => [
					'view_mode!' => 'slider',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_grid_controls(): void {
		$this->start_controls_section(
			'section_images_slider_grid',
			[
				'label'     => __( 'Grid Layout', 'directorist-elementor' ),
				'condition' => [
					'view_mode' => 'grid',
				],
			]
		);

		$this->add_control(
			'grid_columns_mode',
			[
				'label'   => __( 'Column Widths', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'equal',
				'options' => [
					'equal'         => __( 'Equal Columns', 'directorist-elementor' ),
					'equal_minimum' => __( 'Minimum Width', 'directorist-elementor' ),
					'equal_fixed'   => __( 'Fixed Width', 'directorist-elementor' ),
					'auto'          => __( 'Auto Width', 'directorist-elementor' ),
					'manual'        => __( 'Manual Template', 'directorist-elementor' ),
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
					'view_mode'         => 'grid',
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
					'view_mode'         => 'grid',
					'grid_columns_mode' => 'equal_fixed',
				],
			]
		);

		$this->add_responsive_control(
			'grid_columns',
			[
				'label'     => __( 'Number of Columns', 'directorist-elementor' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 12,
				'step'      => 1,
				'condition' => [
					'view_mode'         => 'grid',
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
					'view_mode'         => 'grid',
					'grid_columns_mode' => 'manual',
				],
			]
		);

		$this->add_control(
			'grid_rows_mode',
			[
				'label'   => __( 'Row Heights', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'auto',
				'options' => [
					'auto'    => __( 'Auto', 'directorist-elementor' ),
					'equal'   => __( 'Equal Rows', 'directorist-elementor' ),
					'minimum' => __( 'Minimum Height', 'directorist-elementor' ),
					'fixed'   => __( 'Fixed Height', 'directorist-elementor' ),
					'manual'  => __( 'Manual Template', 'directorist-elementor' ),
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
					'view_mode'      => 'grid',
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
					'view_mode'      => 'grid',
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
					'view_mode'      => 'grid',
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
					'view_mode'      => 'grid',
					'grid_rows_mode' => 'manual',
				],
			]
		);

		$this->add_responsive_control(
			'grid_auto_columns',
			[
				'label'       => __( 'Auto Columns', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'auto',
				'placeholder' => 'auto',
			]
		);

		$this->add_responsive_control(
			'grid_auto_rows',
			[
				'label'       => __( 'Auto Rows', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => 'auto',
				'placeholder' => 'auto',
			]
		);

		$this->add_responsive_control(
			'grid_row_gap',
			[
				'label'      => __( 'Row Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [ 'size' => 16, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 120 ],
				],
			]
		);

		$this->add_responsive_control(
			'grid_column_gap',
			[
				'label'      => __( 'Column Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'default'    => [ 'size' => 16, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 120 ],
				],
			]
		);

		$this->add_responsive_control(
			'grid_auto_flow',
			[
				'label'   => __( 'Auto Flow', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'row',
				'toggle'  => false,
				'options' => [
					'row'    => [
						'title' => __( 'Row', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'column' => [
						'title' => __( 'Column', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
				],
			]
		);

		$this->add_responsive_control(
			'grid_density',
			[
				'label'   => __( 'Density', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'auto',
				'toggle'  => false,
				'options' => [
					'auto'  => [
						'title' => __( 'Auto', 'directorist-elementor' ),
						'icon'  => 'eicon-align-start-h',
					],
					'dense' => [
						'title' => __( 'Dense', 'directorist-elementor' ),
						'icon'  => 'eicon-menu-bar',
					],
				],
			]
		);

		$this->add_responsive_control(
			'grid_justify_items',
			[
				'label'   => __( 'Justify Items', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'stretch',
				'toggle'  => false,
				'options' => $this->get_alignment_choose_options( 'horizontal' ),
			]
		);

		$this->add_responsive_control(
			'grid_align_items',
			[
				'label'   => __( 'Align Items', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'stretch',
				'toggle'  => false,
				'options' => $this->get_alignment_choose_options( 'vertical' ),
			]
		);

		$this->add_responsive_control(
			'grid_justify_content',
			[
				'label'   => __( 'Justify Content', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'normal',
				'toggle'  => false,
				'options' => $this->get_content_alignment_choose_options( 'horizontal' ),
			]
		);

		$this->add_responsive_control(
			'grid_align_content',
			[
				'label'   => __( 'Align Content', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'normal',
				'toggle'  => false,
				'options' => $this->get_content_alignment_choose_options( 'vertical' ),
			]
		);

		$this->add_control(
			'grid_item_span_rules',
			[
				'label'       => __( 'Grid Placement Rules', 'directorist-elementor' ),
				'type'        => Controls_Manager::REPEATER,
				'fields'      => $this->get_grid_placement_repeater()->get_controls(),
				'title_field' => '{{{ target === "nth" ? "Image " + nth : target }}}',
			]
		);

		$this->end_controls_section();
	}

	protected function register_flex_controls(): void {
		$this->start_controls_section(
			'section_images_slider_flex',
			[
				'label'     => __( 'Flex Layout', 'directorist-elementor' ),
				'condition' => [
					'view_mode' => 'flex',
				],
			]
		);

		$this->add_responsive_control(
			'flex_direction',
			[
				'label'   => __( 'Direction', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'row',
				'toggle'  => false,
				'options' => [
					'row'            => [
						'title' => __( 'Row', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-right',
					],
					'row-reverse'    => [
						'title' => __( 'Row Reverse', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-left',
					],
					'column'         => [
						'title' => __( 'Column', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-down',
					],
					'column-reverse' => [
						'title' => __( 'Column Reverse', 'directorist-elementor' ),
						'icon'  => 'eicon-arrow-up',
					],
				],
			]
		);

		$this->add_responsive_control(
			'flex_wrap',
			[
				'label'   => __( 'Wrap', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'wrap',
				'toggle'  => false,
				'options' => [
					'nowrap'       => [
						'title' => __( 'No Wrap', 'directorist-elementor' ),
						'icon'  => 'eicon-ellipsis-h',
					],
					'wrap'         => [
						'title' => __( 'Wrap', 'directorist-elementor' ),
						'icon'  => 'eicon-menu-bar',
					],
					'wrap-reverse' => [
						'title' => __( 'Wrap Reverse', 'directorist-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
			]
		);

		$this->add_responsive_control(
			'flex_justify_content',
			[
				'label'   => __( 'Justify Content', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'flex-start',
				'toggle'  => false,
				'options' => $this->get_flex_justify_choose_options(),
			]
		);

		$this->add_responsive_control(
			'flex_align_items',
			[
				'label'   => __( 'Align Items', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'stretch',
				'toggle'  => false,
				'options' => $this->get_flex_align_choose_options(),
			]
		);

		$this->add_responsive_control(
			'flex_align_content',
			[
				'label'   => __( 'Align Content', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'stretch',
				'toggle'  => false,
				'options' => $this->get_flex_content_choose_options(),
			]
		);

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
			]
		);

		$this->end_controls_section();
	}

	protected function register_masonry_controls(): void {
		$this->start_controls_section(
			'section_images_slider_masonry',
			[
				'label'     => __( 'Masonry Layout', 'directorist-elementor' ),
				'condition' => [
					'view_mode' => 'masonry',
				],
			]
		);

		$this->add_responsive_control(
			'masonry_column_width',
			[
				'label'      => __( 'Column Width', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'default'    => [ 'size' => 220, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 800 ],
					'%'  => [ 'min' => 10, 'max' => 100 ],
				],
			]
		);

		$this->add_responsive_control(
			'masonry_row_height',
			[
				'label'      => __( 'Row Height Unit', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'default'    => [ 'size' => 8, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 1, 'max' => 40 ],
				],
			]
		);

		$this->add_responsive_control(
			'masonry_item_column_span',
			[
				'label'   => __( 'Item Column Span', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 1,
				'min'     => 1,
				'max'     => 6,
				'step'    => 1,
			]
		);

		$this->add_responsive_control(
			'masonry_item_min_height',
			[
				'label'      => __( 'Minimum Item Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh' ],
				'default'    => [ 'size' => 0, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 0, 'max' => 900 ],
					'vh' => [ 'min' => 0, 'max' => 100 ],
				],
			]
		);

		$this->add_responsive_control(
			'masonry_item_max_height',
			[
				'label'      => __( 'Maximum Item Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 1200 ],
					'vh' => [ 'min' => 10, 'max' => 100 ],
				],
			]
		);

		$this->add_control(
			'masonry_height_mode',
			[
				'label'   => __( 'Height Mode', 'directorist-elementor' ),
				'type'    => Controls_Manager::CHOOSE,
				'default' => 'natural',
				'toggle'  => false,
				'options' => [
					'natural' => [
						'title' => __( 'Natural', 'directorist-elementor' ),
						'icon'  => 'eicon-image',
					],
					'fixed'   => [
						'title' => __( 'Fixed', 'directorist-elementor' ),
						'icon'  => 'eicon-v-align-stretch',
					],
					'ratio'   => [
						'title' => __( 'Ratio', 'directorist-elementor' ),
						'icon'  => 'eicon-frame-expand',
					],
				],
			]
		);

		$this->add_responsive_control(
			'masonry_item_height',
			[
				'label'      => __( 'Fixed Item Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'vh' ],
				'default'    => [ 'size' => 260, 'unit' => 'px' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 1200 ],
					'vh' => [ 'min' => 10, 'max' => 100 ],
				],
				'condition'  => [
					'view_mode'           => 'masonry',
					'masonry_height_mode' => 'fixed',
				],
			]
		);

		$this->add_control(
			'masonry_item_aspect_ratio',
			[
				'label'       => __( 'Image Ratio', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '4 / 3',
				'placeholder' => '4 / 3',
				'condition'   => [
					'view_mode'           => 'masonry',
					'masonry_height_mode' => 'ratio',
				],
			]
		);

		$this->add_control(
			'masonry_object_fit',
			[
				'label'   => __( 'Object Fit', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cover',
				'options' => $this->get_object_fit_options(),
			]
		);

		$this->add_control(
			'masonry_object_position',
			[
				'label'   => __( 'Object Position', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'center center',
				'options' => $this->get_object_position_options(),
			]
		);

		$this->end_controls_section();
	}

	protected function register_lightbox_button_controls(): void {
		$this->start_controls_section(
			'section_images_slider_lightbox_button',
			[
				'label'     => __( 'Photo Button & Lightbox', 'directorist-elementor' ),
				'condition' => [
					'view_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'photo_button_enabled',
			[
				'label'        => __( 'Show Photo Button', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'photo_button_label',
			[
				'label'     => __( 'Button Label', 'directorist-elementor' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'See photos', 'directorist-elementor' ),
				'condition' => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_show_count',
			[
				'label'        => __( 'Show Photo Count', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_show_icon',
			[
				'label'        => __( 'Use Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'condition'    => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_icon',
			[
				'label'     => __( 'Icon', 'directorist-elementor' ),
				'type'      => Controls_Manager::ICONS,
				'default'   => [
					'value'   => 'far fa-images',
					'library' => 'fa-regular',
				],
				'condition' => [
					'photo_button_enabled' => 'yes',
					'photo_button_show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_icon_position',
			[
				'label'     => __( 'Icon Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'before',
				'toggle'    => false,
				'options'   => [
					'before' => [
						'title' => __( 'Before', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'after'  => [
						'title' => __( 'After', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'photo_button_enabled' => 'yes',
					'photo_button_show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_vertical_position',
			[
				'label'     => __( 'Vertical Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'bottom',
				'toggle'    => false,
				'options'   => [
					'top'    => [
						'title' => __( 'Top', 'directorist-elementor' ),
						'icon'  => 'eicon-v-align-top',
					],
					'bottom' => [
						'title' => __( 'Bottom', 'directorist-elementor' ),
						'icon'  => 'eicon-v-align-bottom',
					],
				],
				'condition' => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'photo_button_horizontal_position',
			[
				'label'     => __( 'Horizontal Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'default'   => 'right',
				'toggle'    => false,
				'options'   => [
					'left'  => [
						'title' => __( 'Left', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-left',
					],
					'right' => [
						'title' => __( 'Right', 'directorist-elementor' ),
						'icon'  => 'eicon-h-align-right',
					],
				],
				'condition' => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'photo_button_translate_x',
			[
				'label'      => __( 'Translate X', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => -200, 'max' => 200 ],
					'%'  => [ 'min' => -100, 'max' => 100 ],
				],
				'condition'  => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'photo_button_translate_y',
			[
				'label'      => __( 'Translate Y', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'em' ],
				'range'      => [
					'px' => [ 'min' => -200, 'max' => 200 ],
					'%'  => [ 'min' => -100, 'max' => 100 ],
				],
				'condition'  => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_control(
			'lightbox_animation',
			[
				'label'     => __( 'Lightbox Animation', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'elastic',
				'options'   => [
					'elastic' => __( 'Elastic', 'directorist-elementor' ),
					'zoom'    => __( 'Zoom', 'directorist-elementor' ),
					'fade'    => __( 'Fade', 'directorist-elementor' ),
					'none'    => __( 'None', 'directorist-elementor' ),
				],
				'condition' => [
					'photo_button_enabled' => 'yes',
				],
			]
		);

		foreach (
			[
				'lightbox_show_thumbnails' => __( 'Show Thumbnails', 'directorist-elementor' ),
				'lightbox_show_counter'    => __( 'Show Counter', 'directorist-elementor' ),
				'lightbox_show_navigation' => __( 'Show Navigation', 'directorist-elementor' ),
				'lightbox_close_on_overlay' => __( 'Close on Overlay', 'directorist-elementor' ),
				'lightbox_loop'            => __( 'Loop Images', 'directorist-elementor' ),
			] as $control_name => $label
		) {
			$this->add_control(
				$control_name,
				[
					'label'        => $label,
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Yes', 'directorist-elementor' ),
					'label_off'    => __( 'No', 'directorist-elementor' ),
					'return_value' => 'yes',
					'default'      => 'yes',
					'condition'    => [
						'photo_button_enabled' => 'yes',
					],
				]
			);
		}

		$this->end_controls_section();
	}

	protected function register_image_style_controls(): void {
		$this->start_controls_section(
			'section_images_slider_style',
			[
				'label' => __( 'Images', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_responsive_control(
			'slider_height',
			[
				'label'      => __( 'Height', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh' ],
				'range'      => [
					'px' => [ 'min' => 80, 'max' => 900 ],
					'vh' => [ 'min' => 10, 'max' => 100 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__image' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'slider_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__single, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__slide, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__grid-item, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__flex-item, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__masonry-item, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__image' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'slider_object_fit',
			[
				'label'     => __( 'Object Fit', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => $this->get_object_fit_options(),
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__image' => 'object-fit: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'slider_object_position',
			[
				'label'     => __( 'Object Position', 'directorist-elementor' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'center center',
				'options'   => $this->get_object_position_options(),
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__image' => 'object-position: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'image_box_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-images-slider__single, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__slide, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__grid-item, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__flex-item, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__masonry-item',
			]
		);

		$this->end_controls_section();
	}

	protected function register_photo_button_style_controls(): void {
		$this->start_controls_section(
			'section_photo_button_style',
			[
				'label'     => __( 'Photo Button', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'view_mode' => 'slider',
					'photo_button_enabled' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'photo_button_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button',
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'photo_button_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button',
			]
		);

		$this->add_control(
			'photo_button_text_color',
			[
				'label'     => __( 'Text Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'photo_button_icon_color',
			[
				'label'     => __( 'Icon Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon i, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon svg' => 'color: {{VALUE}}; fill: {{VALUE}};',
				],
				'condition' => [
					'photo_button_show_icon' => 'yes',
				],
			]
		);

		$this->add_responsive_control(
			'photo_button_icon_size',
			[
				'label'      => __( 'Icon Size', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em' ],
				'range'      => [
					'px' => [ 'min' => 8, 'max' => 80 ],
					'em' => [ 'min' => 0.5, 'max' => 5, 'step' => 0.1 ],
				],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon i, {{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button-icon svg' => 'font-size: {{SIZE}}{{UNIT}}; width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'photo_button_show_icon' => 'yes',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'photo_button_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button',
			]
		);

		$this->add_responsive_control(
			'photo_button_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_control(
			'photo_button_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__photo-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function register_navigation_style_controls(): void {
		$this->start_controls_section(
			'section_images_slider_navigation_style',
			[
				'label'     => __( 'Slider Navigation', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'view_mode' => 'slider',
				],
			]
		);

		$this->add_control(
			'navigation_color',
			[
				'label'     => __( 'Arrow Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider .directorist-swiper__nav' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'navigation_background',
			[
				'label'     => __( 'Arrow Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider .directorist-swiper__nav' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_background',
			[
				'label'     => __( 'Pagination Background', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__pagination' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_dot_color',
			[
				'label'     => __( 'Dot Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__pagination .swiper-pagination-bullet' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->add_control(
			'pagination_dot_active_color',
			[
				'label'     => __( 'Active Dot Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-listing-card-images-slider__pagination .swiper-pagination-bullet-active' => 'background-color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings   = $this->get_settings_for_display();
		$listing_id = $this->resolve_render_listing_id( $settings );

		if ( $listing_id <= 0 ) {
			$this->render_preset_context_placeholder(
				__( 'Place this widget inside a Directorist listing card template or single listing template to render listing images.', 'directorist-elementor' )
			);

			return;
		}

		$image_quality_raw   = (string) ( $settings['image_quality'] ?? 'default' );
		$image_quality       = 'default' === $image_quality_raw ? 'default' : $this->sanitize_image_size( $image_quality_raw );
		$preview_image_first = 'yes' === ( $settings['preview_image_first'] ?? 'yes' );
		$slides              = DirectoristBridge::get_instance()->get_listing_slider_slides( $listing_id, $image_quality, $preview_image_first );

		if ( empty( $slides ) ) {
			$this->render_preset_context_placeholder(
				__( 'The selected listing does not have preview or gallery images.', 'directorist-elementor' )
			);

			return;
		}

		$view_mode = $this->resolve_view_mode( $settings );
		$slides    = $this->limit_structured_slides( $slides, $settings, $view_mode );

		$this->enqueue_slider_assets();

		$root_classes = [
			'directorist-elementor-listing-card-images-slider',
			'directorist-elementor-listing-card-images-slider--' . $view_mode,
		];
		$root_style        = $this->build_layout_inline_style( $settings, $view_mode );
		$placement_css     = 'grid' === $view_mode ? $this->build_grid_placement_css( $settings ) : '';
		$root_attrs        = [
			'class' => implode( ' ', $root_classes ),
			'style' => $root_style,
		];

		echo '<div ' . $this->format_html_attributes( $root_attrs ) . '>';

		if ( '' !== $placement_css ) {
			echo '<style>' . $placement_css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( 'grid' === $view_mode ) {
			$this->render_structured_items( $slides, 'grid' );
		} elseif ( 'flex' === $view_mode ) {
			$this->render_structured_items( $slides, 'flex' );
		} elseif ( 'masonry' === $view_mode ) {
			$this->render_structured_items( $slides, 'masonry' );
		} else {
			$this->render_slider_items( $slides, $settings );
			$this->render_photo_button( $slides, $settings );
		}

		echo '</div>';
	}

	/**
	 * Resolve the listing used by this media widget.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return int
	 */
	protected function resolve_render_listing_id( array $settings ): int {
		$preview_listing_id = absint( $settings['preview_listing_id'] ?? 0 );

		if ( $preview_listing_id > 0 && $this->is_editor_context() && empty( $this->get_loop_context() ) ) {
			if ( ! $this->is_listing_in_document_directory_type( $preview_listing_id ) ) {
				return $this->get_current_listing_id();
			}

			return $preview_listing_id;
		}

		return $this->get_current_listing_id();
	}

	protected function render_slider_items( array $slides, array $settings ): void {
		$slide_count       = count( $slides );
		$should_use_swiper = $slide_count > 1;

		if ( ! $should_use_swiper ) {
			$this->render_single_item( $slides[0] );
			return;
		}

		$autoplay         = 'yes' === ( $settings['autoplay'] ?? '' );
		$transition_speed = max( 100, absint( $settings['transition_speed'] ?? 500 ) );
		$show_navigation  = 'yes' === ( $settings['show_navigation'] ?? 'yes' );
		$show_pagination  = 'yes' === ( $settings['show_pagination'] ?? 'yes' );
		$slider_effect    = $this->sanitize_choice( $settings['slider_effect'] ?? 'slide', [ 'slide', 'fade', 'cube', 'coverflow', 'flip', 'cards' ], 'slide' );
		$slides_desktop   = $this->normalize_int( $settings['slides_per_view'] ?? 3, 1, 6 );
		$slides_tablet    = $this->normalize_int( $settings['slides_per_view_tablet'] ?? 2, 1, 6 );
		$slides_mobile    = $this->normalize_int( $settings['slides_per_view_mobile'] ?? 1, 1, 6 );
		$single_effect    = in_array( $slider_effect, [ 'fade', 'cube', 'flip', 'cards' ], true );

		if ( $single_effect ) {
			$slides_desktop = 1;
			$slides_tablet  = 1;
			$slides_mobile  = 1;
		}

		$breakpoints = $single_effect
			? (object) []
			: [
				0    => [ 'slidesPerView' => min( $slide_count, $slides_mobile ) ],
				768  => [ 'slidesPerView' => min( $slide_count, $slides_tablet ) ],
				1200 => [ 'slidesPerView' => min( $slide_count, $slides_desktop ) ],
			];
		$previous_icon = function_exists( 'directorist_icon' ) ? directorist_icon( 'las la-angle-left', false ) : '&lsaquo;';
		$next_icon     = function_exists( 'directorist_icon' ) ? directorist_icon( 'las la-angle-right', false ) : '&rsaquo;';

		printf(
			'<div class="directorist-swiper directorist-swiper-listing directorist-elementor-listing-card-images-slider__swiper" data-sw-items="%1$s" data-sw-margin="2" data-sw-loop="%2$s" data-sw-perslide="1" data-sw-speed="%3$s" data-sw-autoplay="%4$s" data-sw-effect="%5$s" data-sw-responsive="%6$s" data-gbi-show-arrows="%7$s" data-gbi-show-dots="%8$s">',
			esc_attr( (string) min( $slide_count, $slides_desktop ) ),
			esc_attr( $slide_count > $slides_desktop && ! $single_effect ? 'true' : 'false' ),
			esc_attr( (string) $transition_speed ),
			esc_attr( $autoplay ? 'true' : 'false' ),
			esc_attr( $slider_effect ),
			esc_attr( wp_json_encode( $breakpoints ) ),
			esc_attr( $show_navigation ? 'true' : 'false' ),
			esc_attr( $show_pagination ? 'true' : 'false' )
		);

		echo '<div class="swiper-wrapper">';

		foreach ( $slides as $slide ) {
			echo '<div class="swiper-slide directorist-elementor-listing-card-images-slider__slide">';
			$this->render_image_markup( $slide );
			echo '</div>';
		}

		echo '</div>';

		if ( $show_navigation ) {
			echo '<div class="directorist-swiper__navigation">';
			echo '<div class="directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-listing">' . $previous_icon . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '<div class="directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-listing">' . $next_icon . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo '</div>';
		}

		if ( $show_pagination ) {
			echo '<div class="directorist-swiper__pagination directorist-swiper__pagination--listing directorist-elementor-listing-card-images-slider__pagination"></div>';
		}

		echo '</div>';
	}

	protected function render_single_item( array $slide ): void {
		echo '<div class="directorist-elementor-listing-card-images-slider__single">';
		$this->render_image_markup( $slide );
		echo '</div>';
	}

	protected function render_structured_items( array $slides, string $mode ): void {
		$track_class = 'directorist-elementor-listing-card-images-slider__' . $mode;

		if ( 'masonry' === $mode ) {
			echo '<div class="' . esc_attr( $track_class ) . '" data-directorist-elementor-masonry-layout>';
		} else {
			echo '<div class="' . esc_attr( $track_class ) . '">';
		}

		foreach ( $slides as $index => $slide ) {
			$item_classes = [
				'directorist-elementor-listing-card-images-slider__' . $mode . '-item',
				'directorist-elementor-listing-card-images-slider__grid-item--' . ( $index + 1 ),
				0 === $index ? 'directorist-elementor-listing-card-images-slider__grid-item--first' : '',
				$index + 1 === count( $slides ) ? 'directorist-elementor-listing-card-images-slider__grid-item--last' : '',
			];

			echo '<div class="' . esc_attr( implode( ' ', array_filter( $item_classes ) ) ) . '">';
			$this->render_image_markup( $slide );
			echo '</div>';
		}

		echo '</div>';
	}

	protected function render_image_markup( array $slide ): void {
		printf(
			'<img class="directorist-elementor-listing-card-images-slider__image" src="%1$s" alt="%2$s" loading="lazy" />',
			esc_url( $slide['src'] ?? '' ),
			esc_attr( $slide['alt'] ?? '' )
		);
	}

	protected function render_photo_button( array $slides, array $settings ): void {
		if ( 'yes' !== ( $settings['photo_button_enabled'] ?? '' ) ) {
			return;
		}

		$slide_count = count( $slides );
		if ( $slide_count <= 0 ) {
			return;
		}

		$label       = trim( sanitize_text_field( (string) ( $settings['photo_button_label'] ?? __( 'See photos', 'directorist-elementor' ) ) ) );
		$label       = '' === $label ? __( 'See photos', 'directorist-elementor' ) : $label;
		$show_count  = 'yes' === ( $settings['photo_button_show_count'] ?? 'yes' );
		$show_icon   = 'yes' === ( $settings['photo_button_show_icon'] ?? 'yes' );
		$icon_pos    = $this->sanitize_choice( $settings['photo_button_icon_position'] ?? 'before', [ 'before', 'after' ], 'before' );
		$button_text = $show_count ? sprintf( '%1$s %2$d', $label, $slide_count ) : $label;
		$button_style = sprintf(
			'top:%1$s;bottom:%2$s;left:%3$s;right:%4$s;transform:translate(%5$s,%6$s);',
			'top' === ( $settings['photo_button_vertical_position'] ?? 'bottom' ) ? '20px' : 'auto',
			'bottom' === ( $settings['photo_button_vertical_position'] ?? 'bottom' ) ? '20px' : 'auto',
			'left' === ( $settings['photo_button_horizontal_position'] ?? 'right' ) ? '20px' : 'auto',
			'right' === ( $settings['photo_button_horizontal_position'] ?? 'right' ) ? '20px' : 'auto',
			$this->slider_value_to_css( $settings['photo_button_translate_x'] ?? [], '0px', true ),
			$this->slider_value_to_css( $settings['photo_button_translate_y'] ?? [], '0px', true )
		);
		$icon_markup = $show_icon ? $this->render_elementor_icon( $settings['photo_button_icon'] ?? [] ) : '';
		$animation   = $this->sanitize_choice( $settings['lightbox_animation'] ?? 'elastic', [ 'elastic', 'zoom', 'fade', 'none' ], 'elastic' );

		echo '<div class="directorist-elementor-listing-card-images-slider__photo-button-wrap" style="' . esc_attr( $button_style ) . '">';
		printf(
			'<button type="button" class="directorist-elementor-listing-card-images-slider__photo-button directorist-elementor-listing-card-images-slider__photo-button--icon-%1$s" data-directorist-gbi-lightbox-trigger data-directorist-gbi-lightbox-animation="%2$s" data-directorist-gbi-lightbox-show-thumbnails="%3$s" data-directorist-gbi-lightbox-show-counter="%4$s" data-directorist-gbi-lightbox-show-navigation="%5$s" data-directorist-gbi-lightbox-close-on-overlay="%6$s" data-directorist-gbi-lightbox-loop="%7$s" aria-label="%8$s">',
			esc_attr( $icon_pos ),
			esc_attr( $animation ),
			esc_attr( 'yes' === ( $settings['lightbox_show_thumbnails'] ?? 'yes' ) ? 'true' : 'false' ),
			esc_attr( 'yes' === ( $settings['lightbox_show_counter'] ?? 'yes' ) ? 'true' : 'false' ),
			esc_attr( 'yes' === ( $settings['lightbox_show_navigation'] ?? 'yes' ) ? 'true' : 'false' ),
			esc_attr( 'yes' === ( $settings['lightbox_close_on_overlay'] ?? 'yes' ) ? 'true' : 'false' ),
			esc_attr( 'yes' === ( $settings['lightbox_loop'] ?? 'yes' ) ? 'true' : 'false' ),
			esc_attr( $button_text )
		);

		if ( $show_icon && 'before' === $icon_pos && '' !== $icon_markup ) {
			echo '<span class="directorist-elementor-listing-card-images-slider__photo-button-icon">' . $icon_markup . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<span class="directorist-elementor-listing-card-images-slider__photo-button-label">' . esc_html( $label ) . '</span>';

		if ( $show_count ) {
			echo '<span class="directorist-elementor-listing-card-images-slider__photo-button-count">' . esc_html( (string) $slide_count ) . '</span>';
		}

		if ( $show_icon && 'after' === $icon_pos && '' !== $icon_markup ) {
			echo '<span class="directorist-elementor-listing-card-images-slider__photo-button-icon">' . $icon_markup . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</button></div>';

		echo '<div class="directorist-elementor-listing-card-images-slider__lightbox-sources" hidden>';
		foreach ( $slides as $slide ) {
			printf(
				'<span data-directorist-gbi-lightbox-source data-src="%1$s" data-alt="%2$s"></span>',
				esc_url( $slide['src'] ?? '' ),
				esc_attr( $slide['alt'] ?? '' )
			);
		}
		echo '</div>';
	}

	protected function resolve_view_mode( array $settings ): string {
		$view_mode = $this->sanitize_choice( $settings['view_mode'] ?? 'slider', [ 'slider', 'grid', 'flex', 'masonry' ], 'slider' );

		if ( 'slider' !== $view_mode && ! $this->is_structured_layout_available() ) {
			return 'slider';
		}

		return $view_mode;
	}

	protected function is_structured_layout_available(): bool {
		return empty( $this->get_loop_context() );
	}

	protected function limit_structured_slides( array $slides, array $settings, string $view_mode ): array {
		if ( 'slider' === $view_mode ) {
			return $slides;
		}

		$limit = absint( $settings['items_to_show'] ?? 0 );

		return $limit > 0 ? array_slice( $slides, 0, $limit ) : $slides;
	}

	protected function build_layout_inline_style( array $settings, string $view_mode ): string {
		$style = [];

		if ( 'grid' === $view_mode ) {
			$style = array_merge( $style, $this->build_grid_style_values( $settings ) );
		} elseif ( 'flex' === $view_mode ) {
			$style = array_merge( $style, $this->build_flex_style_values( $settings ) );
		} elseif ( 'masonry' === $view_mode ) {
			$style = array_merge( $style, $this->build_masonry_style_values( $settings ) );
		}

		$output = '';
		foreach ( $style as $property => $value ) {
			$output .= $property . ':' . $value . ';';
		}

		return $output;
	}

	protected function build_grid_style_values( array $settings ): array {
		$devices = [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ];
		$style   = [];

		foreach ( $devices as $device => $suffix ) {
			$style[ '--direl-listing-slider-grid-template-columns-' . $device ] = $this->resolve_grid_column_template( $settings, $suffix );
			$style[ '--direl-listing-slider-grid-template-rows-' . $device ]    = $this->resolve_grid_row_template( $settings, $suffix );
			$style[ '--direl-listing-slider-grid-auto-columns-' . $device ]     = $this->sanitize_track_list( $settings[ 'grid_auto_columns' . $suffix ] ?? ( $settings['grid_auto_columns'] ?? 'auto' ), 'auto' );
			$style[ '--direl-listing-slider-grid-auto-rows-' . $device ]        = $this->sanitize_track_list( $settings[ 'grid_auto_rows' . $suffix ] ?? ( $settings['grid_auto_rows'] ?? 'auto' ), 'auto' );
			$style[ '--direl-listing-slider-grid-auto-flow-' . $device ]        = $this->resolve_grid_auto_flow( $settings[ 'grid_auto_flow' . $suffix ] ?? ( $settings['grid_auto_flow'] ?? 'row' ), $settings[ 'grid_density' . $suffix ] ?? ( $settings['grid_density'] ?? 'auto' ) );
			$style[ '--direl-listing-slider-grid-row-gap-' . $device ]          = $this->slider_value_to_css( $settings[ 'grid_row_gap' . $suffix ] ?? ( $settings['grid_row_gap'] ?? [] ), '16px' );
			$style[ '--direl-listing-slider-grid-column-gap-' . $device ]       = $this->slider_value_to_css( $settings[ 'grid_column_gap' . $suffix ] ?? ( $settings['grid_column_gap'] ?? [] ), '16px' );
			$style[ '--direl-listing-slider-grid-justify-items-' . $device ]    = $this->sanitize_grid_alignment( $settings[ 'grid_justify_items' . $suffix ] ?? ( $settings['grid_justify_items'] ?? 'stretch' ), 'stretch' );
			$style[ '--direl-listing-slider-grid-align-items-' . $device ]      = $this->sanitize_grid_alignment( $settings[ 'grid_align_items' . $suffix ] ?? ( $settings['grid_align_items'] ?? 'stretch' ), 'stretch' );
			$style[ '--direl-listing-slider-grid-justify-content-' . $device ]  = $this->sanitize_grid_content_alignment( $settings[ 'grid_justify_content' . $suffix ] ?? ( $settings['grid_justify_content'] ?? 'normal' ), 'normal' );
			$style[ '--direl-listing-slider-grid-align-content-' . $device ]    = $this->sanitize_grid_content_alignment( $settings[ 'grid_align_content' . $suffix ] ?? ( $settings['grid_align_content'] ?? 'normal' ), 'normal' );
		}

		return $style;
	}

	protected function build_flex_style_values( array $settings ): array {
		$devices = [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ];
		$style   = [
			'--direl-listing-slider-flex-item-grow' => 'yes' === ( $settings['flex_item_grow'] ?? 'yes' ) ? '1' : '0',
		];

		foreach ( $devices as $device => $suffix ) {
			$style[ '--direl-listing-slider-flex-direction-' . $device ]       = $this->sanitize_choice( $settings[ 'flex_direction' . $suffix ] ?? ( $settings['flex_direction'] ?? 'row' ), [ 'row', 'row-reverse', 'column', 'column-reverse' ], 'row' );
			$style[ '--direl-listing-slider-flex-wrap-' . $device ]            = $this->sanitize_choice( $settings[ 'flex_wrap' . $suffix ] ?? ( $settings['flex_wrap'] ?? 'wrap' ), [ 'nowrap', 'wrap', 'wrap-reverse' ], 'wrap' );
			$style[ '--direl-listing-slider-flex-justify-content-' . $device ] = $this->sanitize_flex_alignment( $settings[ 'flex_justify_content' . $suffix ] ?? ( $settings['flex_justify_content'] ?? 'flex-start' ), [ 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ], 'flex-start' );
			$style[ '--direl-listing-slider-flex-align-items-' . $device ]     = $this->sanitize_flex_alignment( $settings[ 'flex_align_items' . $suffix ] ?? ( $settings['flex_align_items'] ?? 'stretch' ), [ 'stretch', 'flex-start', 'center', 'flex-end', 'baseline' ], 'stretch' );
			$style[ '--direl-listing-slider-flex-align-content-' . $device ]   = $this->sanitize_flex_alignment( $settings[ 'flex_align_content' . $suffix ] ?? ( $settings['flex_align_content'] ?? 'stretch' ), [ 'stretch', 'flex-start', 'center', 'flex-end', 'space-between', 'space-around', 'space-evenly' ], 'stretch' );
			$style[ '--direl-listing-slider-flex-item-basis-' . $device ]      = $this->slider_value_to_css( $settings[ 'flex_item_basis' . $suffix ] ?? ( $settings['flex_item_basis'] ?? [] ), '240px' );
			$style[ '--direl-listing-slider-grid-row-gap-' . $device ]         = $this->slider_value_to_css( $settings[ 'grid_row_gap' . $suffix ] ?? ( $settings['grid_row_gap'] ?? [] ), '16px' );
			$style[ '--direl-listing-slider-grid-column-gap-' . $device ]      = $this->slider_value_to_css( $settings[ 'grid_column_gap' . $suffix ] ?? ( $settings['grid_column_gap'] ?? [] ), '16px' );
		}

		return $style;
	}

	protected function build_masonry_style_values( array $settings ): array {
		$devices = [ 'desktop' => '', 'tablet' => '_tablet', 'mobile' => '_mobile' ];
		$style   = [
			'--direl-listing-slider-masonry-object-fit'      => $this->sanitize_choice( $settings['masonry_object_fit'] ?? 'cover', array_keys( $this->get_object_fit_options() ), 'cover' ),
			'--direl-listing-slider-masonry-object-position' => $this->sanitize_choice( $settings['masonry_object_position'] ?? 'center center', array_keys( $this->get_object_position_options() ), 'center center' ),
			'--direl-listing-slider-masonry-aspect-ratio'    => 'ratio' === ( $settings['masonry_height_mode'] ?? 'natural' ) ? $this->sanitize_aspect_ratio( $settings['masonry_item_aspect_ratio'] ?? '4 / 3', '4 / 3' ) : 'auto',
		];

		foreach ( $devices as $device => $suffix ) {
			$style[ '--direl-listing-slider-masonry-column-width-' . $device ]     = $this->slider_value_to_css( $settings[ 'masonry_column_width' . $suffix ] ?? ( $settings['masonry_column_width'] ?? [] ), '220px' );
			$style[ '--direl-listing-slider-masonry-row-height-' . $device ]       = $this->slider_value_to_css( $settings[ 'masonry_row_height' . $suffix ] ?? ( $settings['masonry_row_height'] ?? [] ), '8px' );
			$style[ '--direl-listing-slider-masonry-item-span-' . $device ]        = (string) $this->normalize_int( $settings[ 'masonry_item_column_span' . $suffix ] ?? ( $settings['masonry_item_column_span'] ?? 1 ), 1, 6 );
			$style[ '--direl-listing-slider-masonry-min-height-' . $device ]       = $this->slider_value_to_css( $settings[ 'masonry_item_min_height' . $suffix ] ?? ( $settings['masonry_item_min_height'] ?? [] ), '0px' );
			$style[ '--direl-listing-slider-masonry-max-height-' . $device ]       = $this->slider_value_to_css( $settings[ 'masonry_item_max_height' . $suffix ] ?? ( $settings['masonry_item_max_height'] ?? [] ), 'none' );
			$style[ '--direl-listing-slider-masonry-item-height-' . $device ]      = 'fixed' === ( $settings['masonry_height_mode'] ?? 'natural' ) ? $this->slider_value_to_css( $settings[ 'masonry_item_height' . $suffix ] ?? ( $settings['masonry_item_height'] ?? [] ), '260px' ) : 'auto';
			$style[ '--direl-listing-slider-grid-row-gap-' . $device ]             = $this->slider_value_to_css( $settings[ 'grid_row_gap' . $suffix ] ?? ( $settings['grid_row_gap'] ?? [] ), '16px' );
			$style[ '--direl-listing-slider-grid-column-gap-' . $device ]          = $this->slider_value_to_css( $settings[ 'grid_column_gap' . $suffix ] ?? ( $settings['grid_column_gap'] ?? [] ), '16px' );
		}

		return $style;
	}

	protected function resolve_grid_column_template( array $settings, string $suffix ): string {
		$mode  = $this->sanitize_choice( $settings['grid_columns_mode'] ?? 'equal', [ 'equal', 'equal_minimum', 'equal_fixed', 'auto', 'manual' ], 'equal' );
		$count = $this->normalize_int( $settings[ 'grid_columns' . $suffix ] ?? ( $settings['grid_columns'] ?? 3 ), 1, 12 );

		if ( 'manual' === $mode ) {
			return $this->sanitize_track_list( $settings[ 'grid_columns_custom' . $suffix ] ?? ( $settings['grid_columns_custom'] ?? '' ), 'repeat(' . $count . ', minmax(0, 1fr))' );
		}

		if ( 'equal_minimum' === $mode ) {
			$min_width = $this->slider_value_to_css( $settings[ 'grid_column_min_width' . $suffix ] ?? ( $settings['grid_column_min_width'] ?? [] ), '220px' );
			return 'repeat(auto-fill,minmax(min(100%,' . $min_width . '),1fr))';
		}

		if ( 'equal_fixed' === $mode ) {
			$width = $this->slider_value_to_css( $settings[ 'grid_column_width' . $suffix ] ?? ( $settings['grid_column_width'] ?? [] ), '240px' );
			return 'repeat(auto-fit,minmax(min(100%,' . $width . '),' . $width . '))';
		}

		if ( 'auto' === $mode ) {
			return 'repeat(' . $count . ',auto)';
		}

		return 'repeat(' . $count . ',minmax(0,1fr))';
	}

	protected function resolve_grid_row_template( array $settings, string $suffix ): string {
		$mode  = $this->sanitize_choice( $settings['grid_rows_mode'] ?? 'auto', [ 'auto', 'equal', 'minimum', 'fixed', 'manual' ], 'auto' );
		$count = $this->normalize_int( $settings[ 'grid_rows' . $suffix ] ?? ( $settings['grid_rows'] ?? 2 ), 1, 12 );

		if ( 'manual' === $mode ) {
			return $this->sanitize_track_list( $settings[ 'grid_rows_custom' . $suffix ] ?? ( $settings['grid_rows_custom'] ?? '' ), 'repeat(' . $count . ', auto)' );
		}

		if ( 'equal' === $mode ) {
			return 'repeat(' . $count . ',minmax(0,1fr))';
		}

		if ( 'minimum' === $mode ) {
			return 'repeat(' . $count . ',minmax(' . $this->slider_value_to_css( $settings[ 'grid_row_min_height' . $suffix ] ?? ( $settings['grid_row_min_height'] ?? [] ), '160px' ) . ',auto))';
		}

		if ( 'fixed' === $mode ) {
			return 'repeat(' . $count . ',' . $this->slider_value_to_css( $settings[ 'grid_row_height' . $suffix ] ?? ( $settings['grid_row_height'] ?? [] ), '180px' ) . ')';
		}

		return 'repeat(' . $count . ',auto)';
	}

	protected function build_grid_placement_css( array $settings ): string {
		$scope_class = 'elementor-element-' . sanitize_html_class( (string) $this->get_id() );
		$rules       = is_array( $settings['grid_item_span_rules'] ?? null ) ? $settings['grid_item_span_rules'] : [];
		$css         = '';

		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}

			$property = $this->resolve_grid_rule_property( $rule['rule'] ?? 'column_span' );
			if ( '' === $property ) {
				continue;
			}

			$value = $this->resolve_grid_rule_value( $rule['value'] ?? 1, (string) ( $rule['rule'] ?? 'column_span' ) );
			if ( '' === $value ) {
				continue;
			}

			$selector = $this->resolve_grid_rule_selector( $rule );
			if ( '' === $selector ) {
				continue;
			}

			$css .= '.' . $scope_class . ' ' . $selector . '{' . $property . ':' . $value . ';}';
		}

		return $css;
	}

	protected function resolve_grid_rule_selector( array $rule ): string {
		$target = $this->sanitize_choice( $rule['target'] ?? 'nth', [ 'first', 'last', 'nth', 'custom' ], 'nth' );

		if ( 'first' === $target ) {
			return '.directorist-elementor-listing-card-images-slider__grid-item--first';
		}

		if ( 'last' === $target ) {
			return '.directorist-elementor-listing-card-images-slider__grid-item--last';
		}

		if ( 'custom' === $target ) {
			$expression = $this->sanitize_nth_expression( $rule['nth'] ?? '' );
			return '' === $expression ? '' : '.directorist-elementor-listing-card-images-slider__grid-item:nth-child(' . $expression . ')';
		}

		$nth = $this->normalize_int( $rule['nth'] ?? 1, 1, 99 );
		return '.directorist-elementor-listing-card-images-slider__grid-item--' . $nth;
	}

	protected function resolve_grid_rule_property( $value ): string {
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

	protected function resolve_grid_rule_value( $value, string $rule ): string {
		$rule = str_replace( '-', '_', sanitize_key( $rule ) );
		if ( in_array( $rule, [ 'column_span', 'row_span' ], true ) ) {
			$span = $this->normalize_int( $value, 1, 12 );
			return $span > 1 ? 'span ' . $span : '';
		}

		if ( 'auto' === strtolower( trim( (string) $value ) ) ) {
			return 'auto';
		}

		$line = (int) $value;

		return 0 === $line ? '' : (string) max( -99, min( 99, $line ) );
	}

	protected function get_grid_placement_repeater(): Repeater {
		$repeater = new Repeater();

		$repeater->add_control(
			'target',
			[
				'label'   => __( 'Target', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'nth',
				'options' => [
					'first'  => __( 'First Image', 'directorist-elementor' ),
					'last'   => __( 'Last Image', 'directorist-elementor' ),
					'nth'    => __( 'Image Number', 'directorist-elementor' ),
					'custom' => __( 'nth-child Expression', 'directorist-elementor' ),
				],
			]
		);

		$repeater->add_control(
			'nth',
			[
				'label'       => __( 'Image', 'directorist-elementor' ),
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

	protected function enqueue_slider_assets(): void {
		if ( wp_style_is( 'directorist-swiper-style', 'registered' ) ) {
			wp_enqueue_style( 'directorist-swiper-style' );
		}

		if ( wp_script_is( 'directorist-swiper', 'registered' ) ) {
			wp_enqueue_script( 'directorist-swiper' );
		}

		if ( wp_script_is( 'directorist-listing-slider', 'registered' ) ) {
			wp_enqueue_script( 'directorist-listing-slider' );
		}

		if ( wp_script_is( 'directorist-elementor-v4-taxonomy-slider', 'registered' ) ) {
			wp_enqueue_script( 'directorist-elementor-v4-taxonomy-slider' );
		}
	}

	protected function render_elementor_icon( $icon ): string {
		if ( ! is_array( $icon ) || empty( $icon['value'] ) || ! class_exists( Icons_Manager::class ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
		$output = ob_get_clean();

		return is_string( $output ) ? $output : '';
	}

	protected function get_alignment_choose_options( string $axis ): array {
		return [
			'start'   => [
				'title' => __( 'Start', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-left' : 'eicon-v-align-top',
			],
			'center'  => [
				'title' => __( 'Center', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-center' : 'eicon-v-align-middle',
			],
			'end'     => [
				'title' => __( 'End', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-right' : 'eicon-v-align-bottom',
			],
			'stretch' => [
				'title' => __( 'Stretch', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-stretch' : 'eicon-v-align-stretch',
			],
		];
	}

	protected function get_content_alignment_choose_options( string $axis ): array {
		return [
			'normal'        => [
				'title' => __( 'Normal', 'directorist-elementor' ),
				'icon'  => 'eicon-ban',
			],
			'start'         => [
				'title' => __( 'Start', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-left' : 'eicon-v-align-top',
			],
			'center'        => [
				'title' => __( 'Center', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-center' : 'eicon-v-align-middle',
			],
			'end'           => [
				'title' => __( 'End', 'directorist-elementor' ),
				'icon'  => 'horizontal' === $axis ? 'eicon-h-align-right' : 'eicon-v-align-bottom',
			],
			'space-between' => [
				'title' => __( 'Between', 'directorist-elementor' ),
				'icon'  => 'eicon-justify-space-between-h',
			],
			'space-around'  => [
				'title' => __( 'Around', 'directorist-elementor' ),
				'icon'  => 'eicon-justify-space-around-h',
			],
			'space-evenly'  => [
				'title' => __( 'Evenly', 'directorist-elementor' ),
				'icon'  => 'eicon-justify-space-evenly-h',
			],
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

	protected function get_object_fit_options(): array {
		return [
			'cover'      => __( 'Cover', 'directorist-elementor' ),
			'contain'    => __( 'Contain', 'directorist-elementor' ),
			'fill'       => __( 'Fill', 'directorist-elementor' ),
			'none'       => __( 'None', 'directorist-elementor' ),
			'scale-down' => __( 'Scale Down', 'directorist-elementor' ),
		];
	}

	protected function get_object_position_options(): array {
		return [
			'center center' => __( 'Center Center', 'directorist-elementor' ),
			'center top'    => __( 'Top Center', 'directorist-elementor' ),
			'center bottom' => __( 'Bottom Center', 'directorist-elementor' ),
			'left center'   => __( 'Left Center', 'directorist-elementor' ),
			'right center'  => __( 'Right Center', 'directorist-elementor' ),
		];
	}

	protected function normalize_int( $value, int $min, int $max ): int {
		return max( $min, min( $max, absint( $value ) ) );
	}

	protected function sanitize_choice( $value, array $allowed, string $fallback ): string {
		$value = trim( (string) $value );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	protected function sanitize_grid_alignment( $value, string $fallback ): string {
		$value = str_replace( [ '_', 'flex-start', 'flex-end' ], [ '-', 'start', 'end' ], trim( (string) $value ) );

		return in_array( $value, [ 'normal', 'stretch', 'start', 'center', 'end' ], true ) ? $value : $fallback;
	}

	protected function sanitize_grid_content_alignment( $value, string $fallback ): string {
		$value = str_replace( [ '_', 'flex-start', 'flex-end' ], [ '-', 'start', 'end' ], trim( (string) $value ) );

		return in_array( $value, [ 'normal', 'stretch', 'start', 'center', 'end', 'space-between', 'space-around', 'space-evenly' ], true ) ? $value : $fallback;
	}

	protected function sanitize_flex_alignment( $value, array $allowed, string $fallback ): string {
		$value = str_replace( [ '_', 'start', 'end' ], [ '-', 'flex-start', 'flex-end' ], trim( (string) $value ) );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	protected function resolve_grid_auto_flow( $direction, $density ): string {
		$direction = $this->sanitize_choice( $direction, [ 'row', 'column' ], 'row' );
		$density   = $this->sanitize_choice( $density, [ 'auto', 'dense' ], 'auto' );

		return 'dense' === $density ? $direction . ' dense' : $direction;
	}

	protected function sanitize_track_list( $value, string $fallback ): string {
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

	protected function sanitize_aspect_ratio( $value, string $fallback ): string {
		$value = trim( wp_strip_all_tags( (string) $value ) );

		if ( '' === $value || 'auto' === strtolower( $value ) ) {
			return 'auto';
		}

		return preg_match( '/^\d+(?:\.\d+)?(?:\s*\/\s*\d+(?:\.\d+)?)?$/', $value ) ? $value : $fallback;
	}

	protected function sanitize_nth_expression( $value ): string {
		$value = trim( (string) $value );
		$lower = strtolower( $value );

		if ( in_array( $lower, [ 'odd', 'even' ], true ) ) {
			return $lower;
		}

		if ( preg_match( '/^\d{1,2}$/', $value ) ) {
			return (string) $this->normalize_int( $value, 1, 99 );
		}

		return preg_match( '/^[0-9n+\-\s]+$/i', $value ) ? $value : '';
	}

	protected function slider_value_to_css( $value, string $fallback, bool $allow_negative = false ): string {
		if ( is_array( $value ) ) {
			$size = $value['size'] ?? null;
			$unit = isset( $value['unit'] ) && '' !== $value['unit'] ? (string) $value['unit'] : 'px';

			if ( is_numeric( $size ) ) {
				$number = (float) $size;
				if ( ! $allow_negative ) {
					$number = max( 0, $number );
				}
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
			$number = (float) $value;
			if ( ! $allow_negative ) {
				$number = max( 0, $number );
			}
			$number_text = 0.0 === fmod( $number, 1.0 ) ? (string) (int) $number : rtrim( rtrim( (string) $number, '0' ), '.' );
			return $number_text . 'px';
		}

		$sign = $allow_negative ? '-?' : '';

		return preg_match( '/^' . $sign . '\d+(?:\.\d+)?(?:px|%|em|rem|vw|vh)$/i', $value ) ? $value : $fallback;
	}
}
