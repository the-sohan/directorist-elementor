<?php
/**
 * Author profile email widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

class AuthorProfileContactEmailWidget extends AbstractAuthorProfileContactWidget {

	public function get_name(): string {
		return 'directorist_author_profile_contact_email';
	}

	public function get_title(): string {
		return __( 'Author Email', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-mail';
	}

	protected function get_contact_field_key(): string {
		return 'contact-email';
	}

	protected function get_default_icon(): array {
		return [
			'value'   => 'fas fa-envelope',
			'library' => 'fa-solid',
		];
	}

	protected function get_default_label(): string {
		return __( 'Email:', 'directorist-elementor' );
	}

	protected function register_widget_controls(): void {
		parent::register_widget_controls();

		$this->start_controls_section(
			'section_email_visibility',
			[
				'label' => __( 'Email Visibility', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'display_email',
			[
				'label'   => __( 'Display Email', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'public',
				'options' => [
					'public'    => __( 'Public', 'directorist-elementor' ),
					'logged_in' => __( 'Logged In Users', 'directorist-elementor' ),
					'hidden'    => __( 'Hidden', 'directorist-elementor' ),
				],
			]
		);

		$this->end_controls_section();
	}
}
