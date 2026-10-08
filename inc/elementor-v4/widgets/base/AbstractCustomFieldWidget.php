<?php
/**
 * Base custom field widget for listing-card composition.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Base;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use Elementor\Controls_Manager;

abstract class AbstractCustomFieldWidget extends AbstractIconTextFieldWidget {

	/**
	 * Custom fields are available both as listing-card fields and contextual search fields.
	 *
	 * @return array<int,string>
	 */
	public function get_categories(): array {
		$categories = [ CategoryRegistrar::CATEGORY_CUSTOM ];

		if ( SearchFormRenderService::get_instance()->is_search_field_widget( $this->get_name() ) ) {
			$categories[] = CategoryRegistrar::CATEGORY_SEARCH_FIELDS;
		}

		return $categories;
	}

	/**
	 * Custom field widgets belong to the custom field category.
	 *
	 * @return string
	 */
	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_CUSTOM;
	}

	/**
	 * Custom field widgets should not inherit preset keywords.
	 *
	 * @return array<int,string>
	 */
	public function get_keywords(): array {
		return [ 'directorist', 'directory', 'listing', 'card', 'custom' ];
	}

	/**
	 * Get the Directorist custom widget type handled by the widget.
	 *
	 * @return string
	 */
	abstract protected function get_custom_field_widget_name(): string;

	/**
	 * Get all Directorist custom widget types handled by this Elementor widget.
	 *
	 * @return array<int,string>
	 */
	protected function get_custom_field_widget_names(): array {
		return [ $this->get_custom_field_widget_name() ];
	}

	/**
	 * Register shared content controls for custom field widgets.
	 *
	 * @param string $section_id Elementor section id.
	 * @param string $label Section label.
	 * @param array  $args Optional control defaults.
	 * @return void
	 */
	protected function register_custom_field_content_controls( string $section_id, string $label, array $args = [] ): void {
		$defaults = [
			'default_icon'       => [
				'value'   => 'fas fa-circle',
				'library' => 'fa-solid',
			],
			'default_tag'        => 'div',
			'default_show_icon'  => 'yes',
			'default_show_label' => '',
			'default_label_text' => '',
			'supports_link'      => false,
		];
		$args     = array_merge( $defaults, $args );
		$options  = $this->get_custom_field_control_options();
		$notice   = $this->get_custom_field_unavailable_notice(
			__( 'No matching custom fields were found across your directory types yet.', 'directorist-elementor' )
		);

		$this->start_controls_section(
			$section_id,
			[
				'label' => $label,
			]
		);

		$this->register_custom_field_preview_listing_control();

		if ( empty( $options ) ) {
			$this->add_control(
				'custom_field_notice',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => $notice,
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				]
			);
		} else {
			$this->add_control(
				'custom_field',
				[
					'label'       => __( 'Custom Field', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'default'     => '',
					'options'     => $options,
					'label_block' => true,
					'description' => __( 'Choose which custom field this widget should render.', 'directorist-elementor' ),
				]
			);
		}

		$this->add_control(
			'show_icon',
			[
				'label'        => __( 'Show Icon', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Yes', 'directorist-elementor' ),
				'label_off'    => __( 'No', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => $args['default_show_icon'],
			]
		);

		$this->add_control(
			'field_icon',
			[
				'label'       => __( 'Icon', 'directorist-elementor' ),
				'type'        => Controls_Manager::ICONS,
				'default'     => $args['default_icon'],
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
				'default'      => $args['default_show_label'],
			]
		);

		$this->add_control(
			'label_text',
			[
				'label'       => __( 'Label Text', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => $args['default_label_text'],
				'placeholder' => __( 'Defaults to the selected field label', 'directorist-elementor' ),
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
				'default' => $args['default_tag'],
				'options' => [
					'div'  => 'div',
					'span' => 'span',
					'p'    => 'p',
					'h3'   => 'H3',
					'h4'   => 'H4',
					'h5'   => 'H5',
					'h6'   => 'H6',
				],
			]
		);

		if ( ! empty( $args['supports_link'] ) ) {
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
	}

	/**
	 * Register the editor-only preview listing control for custom fields.
	 *
	 * @return void
	 */
	protected function register_custom_field_preview_listing_control(): void {
		if ( ! $this->is_single_listing_template_context() ) {
			return;
		}

		$this->add_control(
			'preview_listing_id',
			[
				'label'       => __( 'Preview Listing', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_listing_options( 100, $this->resolve_document_directory_type_id() ),
				'description' => __( 'Editor-only preview listing for single listing templates.', 'directorist-elementor' ),
			]
		);
	}

	/**
	 * Resolve listing id, honoring the custom-field editor preview selector.
	 *
	 * @return int
	 */
	protected function get_current_listing_id(): int {
		$preview_listing_id = $this->resolve_editor_selected_preview_listing_id();

		if ( $preview_listing_id > 0 ) {
			return $preview_listing_id;
		}

		return parent::get_current_listing_id();
	}

	/**
	 * Resolve selected editor preview listing id for custom fields.
	 *
	 * @return int
	 */
	protected function resolve_editor_selected_preview_listing_id(): int {
		if ( ! $this->is_editor_context() || ! empty( $this->get_loop_context() ) || ! $this->is_single_listing_template_context() ) {
			return 0;
		}

		$settings   = $this->get_settings_for_display();
		$listing_id = absint( $settings['preview_listing_id'] ?? 0 );

		if ( $listing_id <= 0 ) {
			return 0;
		}

		return $this->is_listing_in_document_directory_type( $listing_id ) ? $listing_id : 0;
	}

	/**
	 * Check whether the current editor document is a Directorist single listing template.
	 *
	 * @return bool
	 */
	protected function is_single_listing_template_context(): bool {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();

			if ( $current_document ) {
				if ( method_exists( $current_document, 'get_name' ) ) {
					$document_type = (string) $current_document->get_name();
					if ( 0 === strpos( $document_type, 'directorist-single-listing-directory-' ) ) {
						return true;
					}
				}
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return false;
		}

		$template_type = (string) get_post_meta( $editor_post_id, '_elementor_template_type', true );

		return 0 === strpos( $template_type, 'directorist-single-listing-directory-' );
	}

	/**
	 * Get the selected custom field definition.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_selected_custom_field_definition(): array {
		$settings = $this->get_settings_for_display();

		$selection = (string) ( $settings['custom_field'] ?? '' );
		$bridge    = DirectoristBridge::get_instance();

		foreach ( $this->get_custom_field_widget_names() as $widget_name ) {
			$field_definition = $bridge->resolve_custom_field_definition( $selection, $widget_name );

			if ( ! empty( $field_definition ) ) {
				return $field_definition;
			}
		}

		return [];
	}

	/**
	 * Get merged field dropdown options for this custom widget.
	 *
	 * @return array<string,string>
	 */
	protected function get_custom_field_control_options(): array {
		$options           = [];
		$bridge            = DirectoristBridge::get_instance();
		$directory_type_id = $this->resolve_custom_field_control_directory_type_id();

		foreach ( $this->get_custom_field_widget_names() as $widget_name ) {
			$options = array_replace(
				$options,
				$bridge->get_custom_field_control_options( $widget_name, $directory_type_id )
			);
		}

		return $options;
	}

	/**
	 * Resolve the directory scope for custom field controls.
	 *
	 * @return int
	 */
	protected function resolve_custom_field_control_directory_type_id(): int {
		$settings          = $this->get_raw_control_settings();
		$directory_type_id = absint( $settings['active_directory_type_id'] ?? $settings['directory_type_id'] ?? 0 );

		if ( $directory_type_id > 0 ) {
			return $directory_type_id;
		}

		$directory_type_id = absint(
			$this->get_listing_context()['directory_type_id']
			?? $this->get_loop_context()['active_directory']
			?? 0
		);

		if ( $directory_type_id > 0 ) {
			return $directory_type_id;
		}

		return $this->resolve_document_directory_type_id();
	}

	/**
	 * Read widget settings without forcing Elementor display-settings parsing.
	 *
	 * Elementor requests widget-type control config without a concrete element
	 * settings payload. Calling get_settings_for_display() in that path can pass
	 * null into Elementor's sanitizer, so controls must read raw data defensively.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_raw_control_settings(): array {
		if ( method_exists( $this, 'get_raw_data' ) ) {
			try {
				$raw_data = $this->get_raw_data();
			} catch ( \Throwable $throwable ) {
				$raw_data = [];
			}

			if ( is_array( $raw_data['settings'] ?? null ) ) {
				return (array) $raw_data['settings'];
			}
		}

		return [];
	}

	/**
	 * Get the empty-options notice for custom field controls.
	 *
	 * @param string $fallback_notice Fallback notice for global/unscoped contexts.
	 * @return string
	 */
	protected function get_custom_field_unavailable_notice( string $fallback_notice ): string {
		if ( $this->resolve_custom_field_control_directory_type_id() > 0 ) {
			return esc_html__(
				'This custom field widget is not available for the current directory type because no matching field widget is configured in the submission form fields.',
				'directorist-elementor'
			);
		}

		return esc_html( $fallback_notice );
	}

	/**
	 * Resolve the label text to render for the selected field.
	 *
	 * @param array<string,mixed> $field_definition Selected field definition.
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function get_custom_field_label_text( array $field_definition, array $settings ): string {
		if ( 'yes' !== ( $settings['show_label'] ?? '' ) ) {
			return '';
		}

		$custom_label = trim( (string) ( $settings['label_text'] ?? '' ) );

		if ( '' !== $custom_label ) {
			return $custom_label;
		}

		return trim( (string) ( $field_definition['label'] ?? '' ) );
	}

	/**
	 * Get the base CSS class used by the custom widget.
	 *
	 * @return string
	 */
	protected function get_custom_field_base_class(): string {
		$slug = str_replace( 'directorist_listing_card_', '', $this->get_name() );

		return 'directorist-elementor-listing-card-' . str_replace( '_', '-', sanitize_key( $slug ) );
	}

	/**
	 * Get the base selector for style controls.
	 *
	 * @return string
	 */
	protected function get_custom_field_base_selector(): string {
		return '.' . $this->get_custom_field_base_class();
	}

	/**
	 * Render a standard custom-field placeholder.
	 *
	 * @param string $description Placeholder description.
	 * @return void
	 */
	protected function render_custom_field_context_placeholder( string $description ): void {
		if ( ! $this->is_editor_context() ) {
			return;
		}

		echo wp_kses_post(
			$this->render_placeholder(
				$this->get_title(),
				$description
			)
		);
	}
}
