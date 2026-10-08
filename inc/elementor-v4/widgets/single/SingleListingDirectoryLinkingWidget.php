<?php
/**
 * Directory linking single listing widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionSectionTemplateWidget;
use Elementor\Controls_Manager;

class SingleListingDirectoryLinkingWidget extends AbstractSingleExtensionSectionTemplateWidget {

	/**
	 * Directory Linking follows Gutenberg's preset-field grouping.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_PRESET;
	}

	/**
	 * Get widget slug.
	 *
	 * @return string
	 */
	public function get_name(): string {
		return 'directorist_single_listing_directory_linking';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title(): string {
		return __( 'Directory Linking', 'directorist-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon(): string {
		return 'eicon-link';
	}

	/**
	 * Get required extension slug.
	 *
	 * @return string
	 */
	protected function get_extension_slug(): string {
		return 'directory_linking';
	}

	/**
	 * Unused section widget key for this custom render widget.
	 *
	 * @return string
	 */
	protected function get_section_widget_name(): string {
		return 'directory_linking';
	}

	/**
	 * Get default section label.
	 *
	 * @return string
	 */
	protected function get_default_section_label(): string {
		return __( 'Directory Linking', 'directorist-elementor' );
	}

	/**
	 * Get default section icon.
	 *
	 * @return string
	 */
	protected function get_default_section_icon(): string {
		return 'la la-link';
	}

	/**
	 * Get wrapper selector.
	 *
	 * @return string
	 */
	protected function get_section_wrapper_selector(): string {
		return '.directorist-elementor-extension--directory-linking';
	}

	/**
	 * Get title selector.
	 *
	 * @return string
	 */
	protected function get_section_title_selector(): string {
		return '.directorist-elementor-extension__title';
	}

	/**
	 * Get body selector.
	 *
	 * @return string
	 */
	protected function get_section_body_selector(): string {
		return '.directorist-elementor-extension__content';
	}

	protected function get_section_header_selector(): string {
		return '.directorist-elementor-extension--directory-linking .directorist-elementor-extension__header';
	}

	protected function get_section_icon_selector(): string {
		return '.directorist-elementor-extension--directory-linking .directorist-elementor-extension__icon, .directorist-elementor-extension--directory-linking .directorist-elementor-extension__icon i';
	}

	/**
	 * Register extra controls.
	 *
	 * @return void
	 */
	protected function register_extension_section_controls(): void {
		$directory_options = [ 0 => __( 'Auto Detect', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_directory_options();

		$this->add_control(
			'linked_directory_type_id',
			[
				'label'   => __( 'Linked Directory', 'directorist-elementor' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 0,
				'options' => $directory_options,
			]
		);

		$this->add_control(
			'posts_per_page',
			[
				'label'   => __( 'Posts Per Page', 'directorist-elementor' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 8,
				'min'     => 1,
			]
		);

		$this->add_control(
			'display_image',
			[
				'label'        => __( 'Show Image', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'display_title',
			[
				'label'        => __( 'Show Title', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'display_category',
			[
				'label'        => __( 'Show Category', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'display_rating',
			[
				'label'        => __( 'Show Rating', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'display_see_post',
			[
				'label'        => __( 'Show See Post Button', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'display_navigation',
			[
				'label'        => __( 'Show Navigation', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
			]
		);

		$this->add_control(
			'linking_view_all_text',
			[
				'label'   => __( 'View All Label', 'directorist-elementor' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'View all listings', 'directorist-elementor' ),
			]
		);
	}

	/**
	 * Get placeholder message.
	 *
	 * @return string
	 */
	protected function get_section_placeholder_message(): string {
		return __( 'No linked listings were found for the current preview listing.', 'directorist-elementor' );
	}

	protected function register_additional_extension_section_style_controls( string $section_id ): void {
		$this->register_extension_box_style_section(
			$section_id . '_linking_cards',
			__( 'Cards Wrapper', 'directorist-elementor' ),
			'.directorist-linking-content__cards',
			'linking_cards'
		);

		$this->register_extension_box_style_section(
			$section_id . '_linking_card',
			__( 'Linked Listing Card', 'directorist-elementor' ),
			'.directorist-linking-card',
			'linking_card'
		);

		$this->register_extension_box_style_section(
			$section_id . '_linking_image',
			__( 'Card Image', 'directorist-elementor' ),
			'.directorist-linking-card__img, .directorist-linking-card__img img',
			'linking_image'
		);

		$this->register_extension_text_style_section(
			$section_id . '_linking_card_title',
			__( 'Card Title', 'directorist-elementor' ),
			'.directorist-linking-card__title, .directorist-linking-card__title a',
			'linking_card_title'
		);

		$this->register_extension_text_style_section(
			$section_id . '_linking_category',
			__( 'Card Category', 'directorist-elementor' ),
			'.directorist-linking-card__category, .directorist-linking-card__category a',
			'linking_category'
		);

		$this->register_extension_text_style_section(
			$section_id . '_linking_rating',
			__( 'Card Rating', 'directorist-elementor' ),
			'.directorist-linking-card__reviews, .directorist-linking-card__reviews span',
			'linking_rating'
		);

		$this->register_extension_button_style_section(
			$section_id . '_linking_view_all',
			__( 'View All Link', 'directorist-elementor' ),
			'.directorist-linking-content__all-link',
			'linking_view_all'
		);

		$this->register_extension_button_style_section(
			$section_id . '_linking_nav',
			__( 'Slider Navigation', 'directorist-elementor' ),
			'.directorist-linking-content__slider-nav',
			'linking_nav'
		);
	}

	/**
	 * Render widget output.
	 *
	 * @param int $listing_id Listing id.
	 * @return void
	 */
	protected function render_extension_widget( int $listing_id ): void {
		$settings      = $this->get_settings_for_display();
		$section_title = trim( (string) ( $settings['section_title'] ?? '' ) );
		$section_icon  = trim( (string) ( $settings['section_icon'] ?? '' ) );
		$content       = DirectoristBridge::get_instance()->render_directory_linking_content( $listing_id, $settings );

		if ( '' === $content ) {
			if ( $this->is_editor_context() ) {
				$this->render_single_section_placeholder( $this->get_section_placeholder_message() );
			}

			return;
		}

		echo '<div class="directorist-elementor-extension directorist-elementor-extension--directory-linking">';

		if ( '' !== $section_title || '' !== $section_icon ) {
			echo '<header class="directorist-elementor-extension__header">';
			echo '<h3 class="directorist-elementor-extension__title">';

			if ( '' !== $section_icon ) {
				printf(
					'<span class="directorist-elementor-extension__icon"><i class="%s" aria-hidden="true"></i></span>',
					esc_attr( $section_icon )
				);
			}

			if ( '' !== $section_title ) {
				printf(
					'<span class="directorist-elementor-extension__title-text">%s</span>',
					esc_html( $section_title )
				);
			}

			echo '</h3>';
			echo '</header>';
		}

		echo '<div class="directorist-elementor-extension__content">';
		echo wp_kses_post( $content );
		echo '</div>';
		echo '</div>';
	}
}
