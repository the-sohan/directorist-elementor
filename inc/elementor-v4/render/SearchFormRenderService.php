<?php
/**
 * Composable search form rendering helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use Directorist\Directorist_Listing_Search_Form;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\Traits\Singleton;

class SearchFormRenderService {
	use Singleton;

	/**
	 * Get mapped search field widget configs.
	 *
	 * @return array<int,array{widget_type:string,widgets:array<int,string>,group:string,design_type:string,title:string}>
	 */
	public function get_field_widget_configs(): array {
		return [
			[ 'widget_type' => 'directorist_listing_card_address', 'widgets' => [ 'address' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Address', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_category', 'widgets' => [ 'category' ], 'group' => 'preset', 'design_type' => 'taxonomy-select', 'title' => __( 'Category', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_email', 'widgets' => [ 'email' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Email', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_fax', 'widgets' => [ 'fax' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Fax', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_location', 'widgets' => [ 'location' ], 'group' => 'preset', 'design_type' => 'taxonomy-select', 'title' => __( 'Location', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_phone', 'widgets' => [ 'phone' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Phone', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_phone_two', 'widgets' => [ 'phone2' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Phone 2', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_pricing', 'widgets' => [ 'pricing', 'price', 'price_range', 'dcar-pricing-slider' ], 'group' => 'preset', 'design_type' => 'pricing-dropdown', 'title' => __( 'Pricing', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_title', 'widgets' => [ 'title' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Title', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_video', 'widgets' => [ 'video', 'videourl' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Video', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_website', 'widgets' => [ 'website' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Website', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_zip_code', 'widgets' => [ 'zip' ], 'group' => 'preset', 'design_type' => 'text-input', 'title' => __( 'Zip Code', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_button', 'widgets' => [ 'button' ], 'group' => 'custom', 'design_type' => 'button-field', 'title' => __( 'Custom Button', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_checkbox', 'widgets' => [ 'checkbox' ], 'group' => 'custom', 'design_type' => 'choice-dropdown', 'title' => __( 'Custom Checkbox', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_color', 'widgets' => [ 'color', 'color_picker' ], 'group' => 'custom', 'design_type' => 'color-field', 'title' => __( 'Custom Color', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_date', 'widgets' => [ 'date' ], 'group' => 'custom', 'design_type' => 'date-input', 'title' => __( 'Custom Date', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_file', 'widgets' => [ 'file', 'file_upload' ], 'group' => 'custom', 'design_type' => 'file-field', 'title' => __( 'Custom File', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_number', 'widgets' => [ 'number' ], 'group' => 'custom', 'design_type' => 'number-input', 'title' => __( 'Custom Number', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_radio', 'widgets' => [ 'radio' ], 'group' => 'custom', 'design_type' => 'choice-dropdown', 'title' => __( 'Custom Radio', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_select', 'widgets' => [ 'select' ], 'group' => 'custom', 'design_type' => 'select-field', 'title' => __( 'Custom Select', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_text', 'widgets' => [ 'text' ], 'group' => 'custom', 'design_type' => 'text-input', 'title' => __( 'Custom Text', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_textarea', 'widgets' => [ 'textarea' ], 'group' => 'custom', 'design_type' => 'textarea', 'title' => __( 'Custom Textarea', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_time', 'widgets' => [ 'time' ], 'group' => 'custom', 'design_type' => 'time-input', 'title' => __( 'Custom Time', 'directorist-elementor' ) ],
			[ 'widget_type' => 'directorist_listing_card_custom_url', 'widgets' => [ 'url' ], 'group' => 'custom', 'design_type' => 'text-input', 'title' => __( 'Custom URL', 'directorist-elementor' ) ],
		];
	}

	/**
	 * Get mapped widget types.
	 *
	 * @return array<int,string>
	 */
	public function get_field_widget_types(): array {
		return array_values(
			array_unique(
				array_map(
					static fn( array $config ): string => $config['widget_type'],
					$this->get_field_widget_configs()
				)
			)
		);
	}

	/**
	 * Get config for one widget type.
	 *
	 * @param string $widget_type Elementor widget type.
	 * @return array<string,mixed>|null
	 */
	public function get_field_widget_config( string $widget_type ): ?array {
		foreach ( $this->get_field_widget_configs() as $config ) {
			if ( $widget_type === $config['widget_type'] ) {
				return $config;
			}
		}

		return null;
	}

	/**
	 * Check whether a widget type can render a search field.
	 *
	 * @param string $widget_type Elementor widget type.
	 * @return bool
	 */
	public function is_search_field_widget( string $widget_type ): bool {
		return null !== $this->get_field_widget_config( $widget_type );
	}

	/**
	 * Resolve normalized Search Bar fields for a directory.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return array<string,array<string,mixed>>
	 */
	public function get_search_fields_for_directory( int $directory_type_id ): array {
		if ( $directory_type_id <= 0 || ! class_exists( Directorist_Listing_Search_Form::class ) ) {
			return [];
		}

		$search_form = new Directorist_Listing_Search_Form( 'search_result', $directory_type_id, [] );
		$fields      = $search_form->get_basic_fields();

		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $fields as $field_key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$search_field_key = sanitize_key( (string) $field_key );

			if ( '' === $search_field_key ) {
				continue;
			}

			$widget_name = $this->get_raw_field_widget_name( $field );
			$config      = $this->get_config_for_field( $field );

			if ( null === $config ) {
				continue;
			}

			$field_key_value = sanitize_key( (string) ( $field['field_key'] ?? $search_field_key ) );

			$normalized[ $search_field_key ] = [
				'search_field_key' => $search_field_key,
				'field_key'        => '' !== $field_key_value ? $field_key_value : $search_field_key,
				'label'            => sanitize_text_field( (string) ( $field['label'] ?? $config['title'] ) ),
				'type'             => sanitize_key( (string) ( $field['type'] ?? '' ) ),
				'widget_name'      => $widget_name,
				'widget_group'     => $config['group'],
				'widget_type'      => $config['widget_type'],
				'design_type'      => $this->resolve_design_type( $field, $config ),
			];
		}

		return $normalized;
	}

	/**
	 * Get editor map of search fields by directory.
	 *
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	public function get_search_fields_by_directory(): array {
		$fields = [];

		foreach ( DirectoristBridge::get_instance()->get_directory_options() as $directory_type_id => $directory_label ) {
			unset( $directory_label );

			$directory_type_id = absint( $directory_type_id );
			if ( $directory_type_id <= 0 ) {
				continue;
			}

			$fields[ (string) $directory_type_id ] = $this->get_search_fields_for_directory( $directory_type_id );
		}

		return $fields;
	}

	/**
	 * Build default child elements for a search composition.
	 *
	 * @param int  $directory_type_id Directory type id.
	 * @param bool $include_submit Include submit button widget.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_default_search_child_elements( int $directory_type_id = 0, bool $include_submit = true ): array {
		if ( $directory_type_id <= 0 ) {
			$directory_ids = array_keys( DirectoristBridge::get_instance()->get_directory_options() );
			$directory_type_id = ! empty( $directory_ids[0] ) ? absint( $directory_ids[0] ) : 0;
		}

		$children = [];

		foreach ( $this->get_search_fields_for_directory( $directory_type_id ) as $field ) {
			$settings = [
				'_title'                         => (string) $field['label'],
				'directorist_search_context'     => 'yes',
				'directorist_search_field_key'   => (string) $field['search_field_key'],
				'directorist_search_field_label' => (string) $field['label'],
				'directorist_search_widget_name' => (string) $field['widget_name'],
				'directorist_search_design_type' => (string) $field['design_type'],
				'directory_type_id'              => $directory_type_id,
			];

			if ( 'custom' === (string) $field['widget_group'] ) {
				$settings['custom_field'] = DirectoristBridge::get_instance()->build_custom_field_selection_key(
					$directory_type_id,
					(string) $field['field_key']
				);
			}

			$children[] = $this->get_default_child_widget(
				(string) $field['widget_type'],
				(string) $field['label'],
				$settings
			);
		}

		$children[] = $this->get_default_child_widget(
			'directorist_search_more_filters_button',
			__( 'More Filters Button', 'directorist-elementor' ),
			[
				'directory_type_id' => $directory_type_id,
			]
		);

		if ( $include_submit ) {
			$children[] = $this->get_default_child_widget(
				'directorist_search_submit_button',
				__( 'Search Button', 'directorist-elementor' ),
				[
					'directory_type_id' => $directory_type_id,
				]
			);
		}

		return $children;
	}

	/**
	 * Build a default child widget payload.
	 *
	 * @param string              $widget_type Widget type.
	 * @param string              $title Widget title.
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,mixed>
	 */
	protected function get_default_child_widget( string $widget_type, string $title, array $settings = [] ): array {
		$settings = array_merge(
			[
				'_title' => $title,
			],
			$settings
		);

		return [
			'elType'          => 'widget',
			'widgetType'      => $widget_type,
			'settings'        => $settings,
			'elements'        => [],
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Render a mapped field widget as a core search field when search context exists.
	 *
	 * @param object $widget Elementor widget instance.
	 * @return bool
	 */
	public function maybe_render_search_field_widget( object $widget ): bool {
		if ( ! method_exists( $widget, 'get_name' ) || ! method_exists( $widget, 'get_settings_for_display' ) ) {
			return false;
		}

		$widget_type = sanitize_key( (string) $widget->get_name() );
		$config      = $this->get_field_widget_config( $widget_type );

		if ( null === $config ) {
			return false;
		}

		$context = RenderContext::get_instance()->current_search_form_context();
		if ( empty( $context ) ) {
			return false;
		}

		$settings = (array) $widget->get_settings_for_display();
		$field    = $this->find_field_for_widget( $widget_type, $settings, $context );

		if ( empty( $field ) ) {
			if ( ! empty( $context['is_editor'] ) ) {
				printf(
					'<div class="directorist-elementor-search-field-notice">%s</div>',
					esc_html__( 'This search field is not configured for the current directory type.', 'directorist-elementor' )
				);
			}

			return true;
		}

		$search_form = $context['searchform'] ?? null;
		if ( ! is_object( $search_form ) || ! method_exists( $search_form, 'field_template' ) ) {
			return true;
		}

		ob_start();
		$search_form->field_template( $field );
		$markup = (string) ob_get_clean();

		echo $this->wrap_field_markup( $markup, $widget_type, $field, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Directorist core renders field markup.

		return true;
	}

	/**
	 * Find the current core field for a mapped Elementor widget.
	 *
	 * @param string              $widget_type Widget type.
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<string,mixed> $context Search context.
	 * @return array<string,mixed>
	 */
	protected function find_field_for_widget( string $widget_type, array $settings, array $context ): array {
		$config = $this->get_field_widget_config( $widget_type );

		if ( null === $config ) {
			return [];
		}

		$search_form = $context['searchform'] ?? null;
		if ( ! is_object( $search_form ) || ! method_exists( $search_form, 'get_basic_fields' ) ) {
			return [];
		}

		$fields = $search_form->get_basic_fields();
		if ( empty( $fields ) || ! is_array( $fields ) ) {
			return [];
		}

		$search_field_key = sanitize_key( (string) ( $settings['directorist_search_field_key'] ?? '' ) );
		if ( '' !== $search_field_key && isset( $fields[ $search_field_key ] ) && is_array( $fields[ $search_field_key ] ) ) {
			return $fields[ $search_field_key ];
		}

		$custom_field = sanitize_text_field( (string) ( $settings['custom_field'] ?? '' ) );
		$custom_parts = array_pad( explode( '|', $custom_field, 2 ), 2, '' );
		$meta_key     = sanitize_key( (string) ( $custom_parts[1] ?: $custom_parts[0] ) );

		foreach ( $fields as $field_key => $field ) {
			if ( ! is_array( $field ) ) {
				continue;
			}

			$widget_name = $this->get_raw_field_widget_name( $field );
			if ( ! in_array( $widget_name, $config['widgets'], true ) ) {
				continue;
			}

			if ( 'custom' === (string) $config['group'] ) {
				$field_storage_key = sanitize_key( (string) $field_key );
				$field_meta_key    = sanitize_key( (string) ( $field['field_key'] ?? $field_storage_key ) );

				if ( '' !== $meta_key && ! in_array( $meta_key, [ $field_storage_key, $field_meta_key ], true ) ) {
					continue;
				}
			}

			return $field;
		}

		return [];
	}

	/**
	 * Wrap rendered core field markup.
	 *
	 * @param string              $markup Core field markup.
	 * @param string              $widget_type Widget type.
	 * @param array<string,mixed> $field Core field data.
	 * @param array<string,mixed> $settings Widget settings.
	 * @return string
	 */
	protected function wrap_field_markup( string $markup, string $widget_type, array $field, array $settings ): string {
		if ( '' === trim( $markup ) ) {
			return '';
		}

		$design_type = sanitize_html_class( $this->resolve_design_type( $field, $this->get_field_widget_config( $widget_type ) ?: [] ) );
		$classes     = [
			'directorist-elementor-search-field',
			'directorist-elementor-search-field--' . $design_type,
			'directorist-elementor-search-field--' . sanitize_html_class( $widget_type ),
		];

		if ( ! empty( $settings['directorist_search_field_key'] ) ) {
			$classes[] = 'directorist-elementor-search-field--key-' . sanitize_html_class( (string) $settings['directorist_search_field_key'] );
		}

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			$markup
		);
	}

	/**
	 * Get raw core field widget name.
	 *
	 * @param array<string,mixed> $field Core field.
	 * @return string
	 */
	protected function get_raw_field_widget_name( array $field ): string {
		$widget_name = sanitize_key( (string) ( $field['widget_name'] ?? '' ) );

		return '' !== $widget_name ? $widget_name : sanitize_key( (string) ( $field['type'] ?? '' ) );
	}

	/**
	 * Get mapped config for core field data.
	 *
	 * @param array<string,mixed> $field Core field.
	 * @return array<string,mixed>|null
	 */
	protected function get_config_for_field( array $field ): ?array {
		$widget_name = $this->get_raw_field_widget_name( $field );

		foreach ( $this->get_field_widget_configs() as $config ) {
			if ( in_array( $widget_name, $config['widgets'], true ) ) {
				return $config;
			}
		}

		return null;
	}

	/**
	 * Resolve the search design family for a core field.
	 *
	 * @param array<string,mixed> $field Core field.
	 * @param array<string,mixed> $config Widget config.
	 * @return string
	 */
	protected function resolve_design_type( array $field, array $config ): string {
		$widget_name = $this->get_raw_field_widget_name( $field );
		$field_type  = sanitize_key( (string) ( $field['type'] ?? '' ) );

		if ( 'number' === $widget_name ) {
			if ( 'range' === $field_type ) {
				return 'number-range';
			}

			if ( 'dropdown' === $field_type ) {
				return 'select-field';
			}

			if ( 'radio' === $field_type ) {
				return 'choice-inline';
			}

			return 'number-input';
		}

		if ( in_array( $widget_name, [ 'checkbox', 'radio' ], true ) ) {
			return 'choice-dropdown';
		}

		if ( in_array( $widget_name, [ 'pricing', 'price', 'price_range', 'dcar-pricing-slider' ], true ) ) {
			return 'pricing-dropdown';
		}

		$design_type = sanitize_key( (string) ( $config['design_type'] ?? '' ) );

		return '' !== $design_type ? $design_type : 'text-input';
	}
}
