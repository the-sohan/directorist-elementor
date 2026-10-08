<?php
/**
 * Author profile membership widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

class AuthorProfileMembershipWidget extends AbstractAuthorProfileFieldWidget {

	public function get_name(): string {
		return 'directorist_author_profile_membership';
	}

	public function get_title(): string {
		return __( 'Author Membership', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-calendar';
	}

	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_membership_content',
			[
				'label' => __( 'Membership', 'directorist-elementor' ),
			]
		);

		$this->end_controls_section();

		$this->register_text_style_controls(
			'section_membership_style',
			__( 'Membership', 'directorist-elementor' ),
			'.directorist-elementor-author-profile-membership'
		);
	}

	protected function render(): void {
		$this->render_author_field(
			'membership',
			__( 'Membership text is unavailable for the current preview author.', 'directorist-elementor' )
		);
	}
}
