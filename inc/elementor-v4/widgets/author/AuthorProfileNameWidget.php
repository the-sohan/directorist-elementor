<?php
/**
 * Author profile name widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

class AuthorProfileNameWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_name';
	}

	public function get_title(): string {
		return __( 'Author Name', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-user-circle-o';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_name_content',
			[
				'label' => __( 'Name', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'link',
			[
				'label'        => __( 'Link to Profile', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'h4',
				'options' => [
					'h2'   => 'H2',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
					'p'    => 'p',
					'div'  => 'div',
					'span' => 'span',
				],
			]
		);

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_name_style',
			__( 'Name', 'directorist-elementor' ),
			'.directorist-elementor-author-profile-name',
			'.directorist-elementor-author-profile-name__link'
		);
	}

	protected function render(): void {
		$this->render_author_field(
			'name',
			__( 'The current preview author has no display name.', 'directorist-elementor' )
		);
	}
}
