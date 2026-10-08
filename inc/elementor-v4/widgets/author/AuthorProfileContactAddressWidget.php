<?php
/**
 * Author profile address widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

class AuthorProfileContactAddressWidget extends AbstractAuthorProfileContactWidget {

	public function get_name(): string {
		return 'directorist_author_profile_contact_address';
	}

	public function get_title(): string {
		return __( 'Author Address', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-google-maps';
	}

	protected function get_contact_field_key(): string {
		return 'contact-address';
	}

	protected function get_default_icon(): array {
		return [
			'value'   => 'fas fa-address-card',
			'library' => 'fa-solid',
		];
	}

	protected function get_default_label(): string {
		return __( 'Address:', 'directorist-elementor' );
	}

	protected function supports_value_link(): bool {
		return false;
	}
}
