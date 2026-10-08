<?php
/**
 * Author profile website widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

class AuthorProfileContactWebsiteWidget extends AbstractAuthorProfileContactWidget {

	public function get_name(): string {
		return 'directorist_author_profile_contact_website';
	}

	public function get_title(): string {
		return __( 'Author Website', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-editor-link';
	}

	protected function get_contact_field_key(): string {
		return 'contact-website';
	}

	protected function get_default_icon(): array {
		return [
			'value'   => 'fas fa-globe',
			'library' => 'fa-solid',
		];
	}

	protected function get_default_label(): string {
		return __( 'Website:', 'directorist-elementor' );
	}
}
