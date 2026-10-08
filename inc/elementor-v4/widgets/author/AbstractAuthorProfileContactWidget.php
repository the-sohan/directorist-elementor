<?php
/**
 * Base author profile contact field widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Author;

use Elementor\Controls_Manager;

abstract class AbstractAuthorProfileContactWidget extends AbstractAuthorProfileFieldWidget {

	/**
	 * Contact field key.
	 *
	 * @return string
	 */
	abstract protected function get_contact_field_key(): string;

	/**
	 * Default icon setting.
	 *
	 * @return array<string,string>
	 */
	abstract protected function get_default_icon(): array;

	/**
	 * Default label.
	 *
	 * @return string
	 */
	abstract protected function get_default_label(): string;

	/**
	 * Whether the field supports a value link.
	 *
	 * @return bool
	 */
	protected function supports_value_link(): bool {
		return true;
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_widget_controls(): void {
		$this->start_controls_section(
			'section_contact_content',
			[
				'label' => $this->get_title(),
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
				'default'     => $this->get_default_icon(),
				'skin'        => 'inline',
				'label_block' => false,
				'condition'   => [
					'show_icon' => 'yes',
				],
			]
		);

		$this->add_control(
			'show_label',
			[
				'label'        => __( 'Show Label', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$this->add_control(
			'label_text',
			[
				'label'       => __( 'Label Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $this->get_default_label(),
				'label_block' => true,
				'condition'   => [
					'show_label' => 'yes',
				],
			]
		);

		$this->add_control(
			'html_tag',
			[
				'label'   => __( 'HTML Tag', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'div',
				'options' => [
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
				],
			]
		);

		if ( $this->supports_value_link() ) {
			$this->add_control(
				'link_to_value',
				[
					'label'        => __( 'Link Value', 'directorist-elementor' ),
					'type'         => Controls_Manager::SWITCHER,
					'label_on'     => __( 'Yes', 'directorist-elementor' ),
					'label_off'    => __( 'No', 'directorist-elementor' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			);
		}

		$this->end_controls_section();

		$this->register_icon_text_style_controls(
			'section_contact_style',
			$this->get_title(),
			'.directorist-elementor-author-profile-contact--' . str_replace( 'contact-', '', $this->get_contact_field_key() )
		);
	}

	/**
	 * Render.
	 *
	 * @return void
	 */
	protected function render(): void {
		$this->render_author_field(
			$this->get_contact_field_key(),
			__( 'This author contact field is empty for the current preview author.', 'directorist-elementor' )
		);
	}
}
