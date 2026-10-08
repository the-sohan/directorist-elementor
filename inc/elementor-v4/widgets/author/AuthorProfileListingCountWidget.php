<?php
/**
 * Author profile listing count widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

class AuthorProfileListingCountWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_listing_count';
	}

	public function get_title(): string {
		return __( 'Author Listing Count', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-number-field';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_listing_count_content',
			[
				'label' => __( 'Listing Count', 'directorist-elementor' ),
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
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => [
					'value'   => 'fas fa-list-ol',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->end_controls_section();

		$this->register_icon_text_style_controls(
			'section_listing_count_style',
			__( 'Listing Count', 'directorist-elementor' ),
			'.directorist-elementor-author-profile-listing-count'
		);
	}

	protected function render(): void {
		$this->render_author_field(
			'listing-count',
			__( 'Listing count is unavailable for the current preview author.', 'directorist-elementor' )
		);
	}
}
