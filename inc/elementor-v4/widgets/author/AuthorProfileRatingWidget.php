<?php
/**
 * Author profile rating widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

class AuthorProfileRatingWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_rating';
	}

	public function get_title(): string {
		return __( 'Author Rating', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-star';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_rating_content',
			[
				'label' => __( 'Rating', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_empty',
			[
				'label'        => __( 'Show When Empty', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
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
					'value'   => 'fas fa-star',
					'library' => 'fa-solid',
				],
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_rating_value',
			[
				'label'        => __( 'Show Rating Value', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'show_review_count',
			[
				'label'        => __( 'Show Review Count', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->end_controls_section();

		$this->register_icon_text_style_controls(
			'section_rating_style',
			__( 'Rating', 'directorist-elementor' ),
			'.directorist-elementor-author-profile-rating'
		);
	}

	protected function render(): void {
		$this->render_author_field(
			'rating',
			__( 'Rating is unavailable for the current preview author.', 'directorist-elementor' )
		);
	}
}
