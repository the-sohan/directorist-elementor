<?php
/**
 * Author profile bio widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

class AuthorProfileBioWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_bio';
	}

	public function get_title(): string {
		return __( 'Author Bio', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-editor-paragraph';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_bio_content',
			[
				'label' => __( 'Bio', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_empty_message',
			[
				'label'        => __( 'Show Empty Message', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_bio_style',
			__( 'Bio', 'directorist-elementor' ),
			'.directorist-elementor-author-profile-bio'
		);
	}

	protected function render(): void {
		$this->render_author_field(
			'bio',
			__( 'The current preview author has no biography.', 'directorist-elementor' )
		);
	}
}
