<?php
/**
 * Author profile phone widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

class AuthorProfileContactPhoneWidget extends AbstractAuthorProfileContactWidget {

	public function get_name(): string {
		return 'directorist_author_profile_contact_phone';
	}

	public function get_title(): string {
		return __( 'Author Phone', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'directorist-eicon directorist-eicon--phone';
	}

	protected function get_contact_field_key(): string {
		return 'contact-phone';
	}

	protected function get_default_icon(): array {
		return [
			'value'   => 'fas fa-phone-alt',
			'library' => 'fa-solid',
		];
	}

	protected function get_default_label(): string {
		return __( 'Phone:', 'directorist-elementor' );
	}
}
