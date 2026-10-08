<?php
/**
 * Base composable search widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Loop;

use Directorist\Directorist_Listing_Search_Form;
use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\ElementTreeStyleService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\ElementorV4\Render\SearchFormRenderService;
use Elementor\Controls_Manager;
use Elementor\Includes\Elements\Container;

abstract class AbstractSearchCompositionWidget extends Container {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	protected function get_directorist_category_slug(): string {
		return CategoryRegistrar::CATEGORY_ARCHIVE;
	}

	public function get_categories(): array {
		return [ $this->get_directorist_category_slug() ];
	}

	public function get_keywords(): array {
		return [ 'directorist', 'directory', 'listing', 'search' ];
	}

	public function get_panel_presets() {
		return [];
	}

	protected function is_dynamic_content(): bool {
		return true;
	}

	public function has_widget_inner_wrapper(): bool {
		return false;
	}

	protected function get_default_children_elements() {
		return SearchFormRenderService::get_instance()->get_default_search_child_elements(
			0,
			$this->default_template_includes_submit_button()
		);
	}

	protected function default_template_includes_submit_button(): bool {
		return true;
	}

	protected function get_default_children_title() {
		return __( 'Search Fields', 'directorist-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-search-composition__storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-search-composition__canvas';
	}

	protected function get_default_repeater_title_setting_key() {
		return '_title';
	}

	protected function get_initial_config(): array {
		$config           = parent::get_initial_config();
		$default_children = $this->get_default_children_elements();

		$config['show_in_panel'] = true;
		$config['categories'] = [ CategoryRegistrar::CATEGORY_ARCHIVE ];
		$config['title'] = $this->get_title();
		$config['icon'] = $this->get_icon();
		$config['include_in_widgets_config'] = true;
		$config['default_children'] = $default_children;
		$config['defaults'] = array_merge(
			is_array( $config['defaults'] ?? null ) ? $config['defaults'] : [],
			[
				'elements'                             => $default_children,
				'elements_title'                       => $this->get_default_children_title(),
				'elements_placeholder_selector'        => $this->get_default_children_placeholder_selector(),
				'child_container_placeholder_selector' => $this->get_default_children_container_placeholder_selector(),
				'repeater_title_setting'               => $this->get_default_repeater_title_setting_key(),
			]
		);
		$config['support_improved_repeaters'] = true;
		$config['target_container'] = [ $this->get_default_children_placeholder_selector() ];
		$config['node'] = 'div';
		$config['is_interlaced'] = true;
		$config['directorist_default_search_elements'] = $default_children;

		return $config;
	}

	protected function register_controls() {
		$this->register_widget_controls();

		if ( $this->should_register_template_state_section() ) {
			$this->register_hidden_search_template_state_controls();
		}

		$this->register_directorist_advanced_controls();
	}

	/**
	 * Register hidden search template state without exposing an empty Content tab.
	 *
	 * @return void
	 */
	protected function register_hidden_search_template_state_controls(): void {
		$this->start_controls_section(
			'section_search_template_state',
			[
				'label'     => __( 'Template State', 'directorist-elementor' ),
				'tab'       => $this->get_search_style_controls_tab(),
				'condition' => [
					'directorist_template_state_visible' => 'yes',
				],
			]
		);

		$this->register_template_state_controls();

		$this->end_controls_section();
	}

	/**
	 * Register the hidden controls used by per-directory search composition.
	 *
	 * @return void
	 */
	protected function register_template_state_controls(): void {
		$this->add_control(
			'active_search_template_key',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '',
			]
		);

		$this->add_control(
			'scoped_search_templates',
			[
				'type'    => Controls_Manager::HIDDEN,
				'default' => '{}',
			]
		);
	}

	/**
	 * Whether to create a standalone hidden Template State section.
	 *
	 * @return bool
	 */
	protected function should_register_template_state_section(): bool {
		return true;
	}

	protected function register_widget_controls(): void {
	}

	/**
	 * Get the Elementor top-level tab used by search design controls.
	 *
	 * @return string
	 */
	protected function get_search_style_controls_tab(): string {
		return Controls_Manager::TAB_STYLE;
	}

	/**
	 * Register safe wrapper controls without inheriting Container layout/design controls.
	 *
	 * @return void
	 */
	protected function register_directorist_advanced_controls(): void {
		$this->start_controls_section(
			'section_directorist_advanced',
			[
				'label' => __( 'Advanced', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'_element_id',
			[
				'label'          => __( 'CSS ID', 'elementor' ),
				'type'           => Controls_Manager::TEXT,
				'default'        => '',
				'title'          => __( 'Add your custom id WITHOUT the Pound key. e.g: my-id', 'elementor' ),
				'style_transfer' => false,
				'classes'        => 'elementor-control-direction-ltr',
				'dynamic'        => [
					'active' => true,
				],
				'ai'             => [
					'active' => false,
				],
			]
		);

		$this->add_control(
			'_css_classes',
			[
				'label'        => __( 'CSS Classes', 'elementor' ),
				'type'         => Controls_Manager::TEXT,
				'title'        => __( 'Add your custom class WITHOUT the dot. e.g: my-class', 'elementor' ),
				'classes'      => 'elementor-control-direction-ltr',
				'prefix_class' => '',
				'dynamic'      => [
					'active' => true,
				],
				'ai'           => [
					'active' => false,
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'_section_responsive',
			[
				'label' => __( 'Responsive', 'elementor' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$this->add_control(
			'responsive_description',
			[
				'raw'             => sprintf(
					/* translators: 1: Link open tag, 2: Link close tag. */
					__( 'Responsive visibility will take effect only on %1$s preview mode %2$s or live page, and not while editing in Elementor.', 'elementor' ),
					'<a href="javascript: $e.run( \'panel/close\' )">',
					'</a>'
				),
				'type'            => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
			]
		);

		$this->add_hidden_device_controls();

		$this->end_controls_section();
	}

	/**
	 * Print element content.
	 *
	 * Elementor containers render frontend output through print_content(), while
	 * direct preview calls use render(). Keep both paths on the same renderer.
	 *
	 * @return void
	 */
	protected function print_content() {
		$this->render();
	}

	protected function is_editor_context(): bool {
		return EditorContext::get_instance()->is_editor_request();
	}

	protected function format_html_attributes( array $attributes ): string {
		$pairs = [];

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ' ', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf(
				'%1$s="%2$s"',
				esc_attr( $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		return implode( ' ', $pairs );
	}

	protected function render_placeholder( string $title, string $description, string $meta = '' ): string {
		$meta_markup = '' !== $meta
			? sprintf( '<p class="directorist-elementor-placeholder__meta">%s</p>', esc_html( $meta ) )
			: '';

		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%1$s</p><p>%2$s</p>%3$s</div>',
			esc_html( $title ),
			esc_html( $description ),
			$meta_markup
		);
	}

	public function render_directorist_preview_content(): string {
		ob_start();
		$this->render();

		return trim( (string) ob_get_clean() );
	}

	/**
	 * Get current loop context.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_loop_context(): array {
		return RenderContext::get_instance()->current_loop_context();
	}

	/**
	 * Get current loop controller.
	 *
	 * @return object|null
	 */
	protected function get_loop_controller() {
		$loop_context = $this->get_loop_context();
		$controller   = $loop_context['controller'] ?? null;

		return is_object( $controller ) ? $controller : null;
	}

	/**
	 * Get mutable loop controller.
	 *
	 * @return object|null
	 */
	protected function get_cloned_loop_controller() {
		$controller = $this->get_loop_controller();

		return is_object( $controller ) ? clone $controller : null;
	}

	/**
	 * Execute callback within active loop context.
	 *
	 * @param callable $callback Render callback.
	 * @return bool
	 */
	protected function with_active_loop_context( callable $callback ): bool {
		if ( ! empty( $this->get_loop_context() ) ) {
			$callback();

			return true;
		}

		if ( ! $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return false;
		}

		$loop_element = $this->resolve_ancestor_loop_element();

		if ( ! is_array( $loop_element ) ) {
			return false;
		}

			$loop_settings = is_array( $loop_element['settings'] ?? null ) ? (array) $loop_element['settings'] : [];
			if ( 'directorist_homepage_search_loop' === sanitize_key( (string) ( $loop_element['elType'] ?? '' ) ) ) {
				$loop_settings['query_mode'] = 'default';
				$loop_settings['query_type'] = 'regular';
				$loop_settings['directorist_elementor_source'] = 'homepage-search-loop';
			}

		$instance_id = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_element['id'] ?? 'preview' ) )
		);
		$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
			$loop_settings,
			$instance_id,
			true,
			null
		);
		$runtime_state['utility_state'] = LoopRenderService::get_instance()->extract_utility_state_from_elements(
			is_array( $loop_element['elements'] ?? null ) ? (array) $loop_element['elements'] : []
		);

		LoopRenderService::get_instance()->push_loop_context( $runtime_state );

		try {
			$callback();
		} finally {
			RenderContext::get_instance()->pop_loop_context();
		}

		return true;
	}

	/**
	 * Render composed search markup.
	 *
	 * @param array<int,string>   $classes Wrapper classes.
	 * @param array<string,mixed> $attributes Wrapper attributes.
	 * @param bool                $suppress_submit Whether submit widgets should render.
	 * @return bool
	 */
	protected function render_search_composition( array $classes = [], array $attributes = [], bool $suppress_submit = false ): bool {
		$controller = $this->get_cloned_loop_controller();

		if ( ! $controller || ! class_exists( Directorist_Listing_Search_Form::class ) ) {
			return false;
		}

		$search_form = $this->create_search_form( $controller );
		if ( ! $search_form ) {
			return false;
		}

		$directory_type_id = $this->get_controller_directory_type_id( $controller );
		$children          = $this->get_render_child_raw_elements_for_directory( $directory_type_id );
		if ( empty( $children ) ) {
			if ( $this->should_render_empty_search_composition() ) {
				$attributes = $this->build_loop_utility_attributes( $classes, $attributes );
				$context    = [
					'controller'          => $controller,
					'searchform'          => $search_form,
					'directory_type_id'   => $directory_type_id,
					'directory_type_ids'  => $this->get_context_directory_type_ids(),
					'query_id'            => absint( $this->get_loop_context()['query_args']['query_id'] ?? 0 ),
					'suppress_submit'     => $suppress_submit,
					'is_editor'           => $this->is_editor_context(),
				];

				echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
				RenderContext::get_instance()->push_search_form_context( $context );

				try {
					$this->render_before_search_form();

					if ( $this->is_editor_context() ) {
						echo wp_kses_post(
							$this->render_placeholder(
								$this->get_title(),
								__( 'Add Directorist search field widgets inside this search composition.', 'directorist-elementor' )
							)
						);
					}
				} finally {
					RenderContext::get_instance()->pop_search_form_context();
				}

				echo '</div>';

				return true;
			}

			if ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						$this->get_title(),
						__( 'Add Directorist search field widgets inside this search composition.', 'directorist-elementor' )
					)
				);
			}
			return true;
		}

		$attributes = $this->build_loop_utility_attributes( $classes, $attributes );
		$action_url = $this->get_search_action_url();
		$context    = [
			'controller'          => $controller,
			'searchform'          => $search_form,
			'directory_type_id'   => $directory_type_id,
			'directory_type_ids'  => $this->get_context_directory_type_ids(),
			'query_id'            => absint( $this->get_loop_context()['query_args']['query_id'] ?? 0 ),
			'suppress_submit'     => $suppress_submit,
			'is_editor'           => $this->is_editor_context(),
		];

		echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
		RenderContext::get_instance()->push_search_form_context( $context );

		try {
			$this->render_before_search_form();
			echo '<div class="directorist-elementor-listings-search__form directorist-elementor-listings-archive-search-form directorist-elementor-search-composition__form">';
			echo ElementTreeStyleService::get_instance()->render_style_tag( $children, 0, 'search-' . $this->get_id() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor's native CSS compiler produces scoped declarations.
			echo '<form action="' . esc_url( $action_url ) . '" class="directorist-search-form directorist-basic-search">';
			echo '<div class="directorist-search-form__box">';
			echo '<div class="directorist-search-form-top directorist-flex directorist-align-center directorist-search-form-inline directorist-search-form__top directorist-elementor-search-composition__body">';
			echo '<input type="hidden" name="directory_type" value="' . esc_attr( $this->get_controller_directory_type_slug( $controller ) ) . '">';
			$this->render_raw_children( $this->filter_search_field_children( $children ) );
			echo '</div>';
			$this->render_search_form_box_extras( $search_form, $suppress_submit );
			echo '</div>';
			$this->render_after_search_form_box( $search_form, $suppress_submit );
			echo '</form></div>';
			if ( $this->is_editor_context() ) {
				$this->render_editor_storage();
			}
		} finally {
			RenderContext::get_instance()->pop_search_form_context();
		}

		echo '</div>';

		return true;
	}

	/**
	 * Render optional markup before the search form.
	 *
	 * @return void
	 */
	protected function render_before_search_form(): void {
	}

	/**
	 * Render optional markup inside the search form box after composed fields.
	 *
	 * @param Directorist_Listing_Search_Form $search_form Core search form model.
	 * @param bool                            $suppress_submit Whether submit widgets are suppressed.
	 * @return void
	 */
	protected function render_search_form_box_extras( Directorist_Listing_Search_Form $search_form, bool $suppress_submit ): void {
		unset( $search_form, $suppress_submit );
	}

	/**
	 * Render optional markup after the search form box but before closing form.
	 *
	 * @param Directorist_Listing_Search_Form $search_form Core search form model.
	 * @param bool                            $suppress_submit Whether submit widgets are suppressed.
	 * @return void
	 */
	protected function render_after_search_form_box( Directorist_Listing_Search_Form $search_form, bool $suppress_submit ): void {
		unset( $suppress_submit );

		if ( ! $this->has_advanced_filters( $search_form ) || ! method_exists( $search_form, 'advanced_search_form_fields_template' ) ) {
			return;
		}

		echo '<div class="directorist-search-modal directorist-search-modal--advanced">';
		$search_form->advanced_search_form_fields_template();
		echo '</div>';
	}

	/**
	 * Check whether the current search form has advanced filter fields.
	 *
	 * @param Directorist_Listing_Search_Form $search_form Core search form model.
	 * @return bool
	 */
	protected function has_advanced_filters( Directorist_Listing_Search_Form $search_form ): bool {
		if ( empty( $search_form->has_more_filters_button ) || ! method_exists( $search_form, 'get_advance_fields' ) ) {
			return false;
		}

		$fields = $search_form->get_advance_fields();

		return ! empty( $fields ) && is_array( $fields );
	}

	/**
	 * Determine whether an empty search composition should still render its wrapper.
	 *
	 * @return bool
	 */
	protected function should_render_empty_search_composition(): bool {
		return false;
	}

	/**
	 * Render hidden editor storage target for nested children.
	 *
	 * @return void
	 */
	protected function render_editor_storage(): void {
		echo '<div class="directorist-elementor-search-composition__storage" aria-hidden="true">';
		echo '<div class="directorist-elementor-search-composition__template">';
		echo '<div class="directorist-elementor-search-composition__canvas"></div>';
		echo '</div></div>';
	}

	/**
	 * Backbone editor template.
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<div class="directorist-elementor-listings-search directorist-elementor-search-composition directorist-elementor-search-composition--editor">
			<div class="directorist-elementor-search-composition__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-search-composition__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Search', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the search composition for the current directory context.', 'directorist-elementor' ); ?></p>
				</div>
			</div>
			<div class="directorist-elementor-search-composition__storage" aria-hidden="true">
				<div class="directorist-elementor-search-composition__template">
					<div class="directorist-elementor-search-composition__canvas"></div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Create the core search form model.
	 *
	 * @param object $controller Loop controller.
	 * @return Directorist_Listing_Search_Form|null
	 */
	protected function create_search_form( object $controller ): ?Directorist_Listing_Search_Form {
		if ( ! class_exists( Directorist_Listing_Search_Form::class ) ) {
			return null;
		}

		$search_field_atts = array_filter(
			(array) ( $controller->atts ?? [] ),
			static function( $key ): bool {
				return 0 === strpos( (string) $key, 'filter_' );
			},
			ARRAY_FILTER_USE_KEY
		);

		return new Directorist_Listing_Search_Form(
			'search_result',
			$this->get_controller_directory_type_id( $controller ),
			$search_field_atts
		);
	}

	/**
	 * Get search action URL.
	 *
	 * @return string
	 */
	protected function get_search_action_url(): string {
		if ( function_exists( 'atbdp_search_result_page_link' ) ) {
			ob_start();
			atbdp_search_result_page_link();
			$url = trim( (string) ob_get_clean() );

			if ( '' !== $url ) {
				return $url;
			}
		}

		return home_url( '/' );
	}

	/**
	 * Build loop utility wrapper attributes.
	 *
	 * @param array<int,string>   $classes Classes.
	 * @param array<string,mixed> $attributes Attributes.
	 * @return array<string,mixed>
	 */
	protected function build_loop_utility_attributes( array $classes = [], array $attributes = [] ): array {
		$loop_context = $this->get_loop_context();
		$data_atts    = is_array( $loop_context['data_atts'] ?? null ) ? (array) $loop_context['data_atts'] : [];
		$classes      = array_values( array_unique( array_merge( [ 'directorist-contents-wrap' ], $classes ) ) );

		return array_merge(
			[
				'class'     => array_merge( [ 'directorist-elementor-loop-utility' ], $classes ),
				'data-atts' => ! empty( $data_atts ) ? wp_json_encode( $data_atts ) : null,
			],
			$attributes
		);
	}

	/**
	 * Render a standard loop-context placeholder.
	 *
	 * @param string $title Placeholder title.
	 * @param string $description Placeholder description.
	 * @return void
	 */
	protected function render_loop_context_placeholder( string $title, string $description ): void {
		if ( $this->is_editor_context() ) {
			echo wp_kses_post( $this->render_placeholder( $title, $description ) );
		}
	}

	/**
	 * Filter stale directory type controls out of composed search fields.
	 *
	 * @param array<int,array<string,mixed>> $children Raw children.
	 * @return array<int,array<string,mixed>>
	 */
	protected function filter_search_field_children( array $children ): array {
		$fields = [];

		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			if ( 'directorist_search_directory_types' === sanitize_key( (string) ( $child['widgetType'] ?? '' ) ) ) {
				continue;
			}

			$fields[] = $child;
		}

		return $fields;
	}

	/**
	 * Render raw child element payloads.
	 *
	 * @param array<int,array<string,mixed>> $children Raw children.
	 * @return void
	 */
	protected function render_raw_children( array $children ): void {
		foreach ( $children as $child ) {
			if ( ! is_array( $child ) ) {
				continue;
			}

			echo ElementTreeRenderService::get_instance()->render_element( $child ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor renders child element output.
		}
	}

	/**
	 * Get raw child payloads.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements(): array {
		return $this->get_render_child_raw_elements_for_directory( $this->get_active_search_directory_type_id() );
	}

	/**
	 * Get raw child payloads for the active directory branch.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements_for_directory( int $directory_type_id ): array {
		if ( is_array( $this->render_child_raw_elements ) ) {
			return $this->render_child_raw_elements;
		}

		$raw_data = $this->get_current_widget_raw_data();
		if ( null === $raw_data && method_exists( $this, 'get_raw_data' ) ) {
			$raw_data = $this->get_raw_data();
		}

		$settings          = is_array( $raw_data['settings'] ?? null ) ? (array) $raw_data['settings'] : $this->get_settings_for_display();
		$fallback_elements = is_array( $raw_data['elements'] ?? null ) ? (array) $raw_data['elements'] : [];
		$normalized        = $this->resolve_search_children_from_payload( $settings, $fallback_elements, $directory_type_id );

		$this->render_child_raw_elements = $normalized;

		return $this->render_child_raw_elements;
	}

	/**
	 * Resolve search children from scoped template settings with raw children fallback.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @param array<int,mixed>    $fallback_elements Raw fallback children.
	 * @param int                 $directory_type_id Directory type id.
	 * @return array<int,array<string,mixed>>
	 */
	protected function resolve_search_children_from_payload( array $settings, array $fallback_elements, int $directory_type_id ): array {
		$templates = $this->get_scoped_search_templates( $settings );

		if ( ! empty( $templates ) ) {
			$scope_key = $this->build_search_template_key( $directory_type_id );
			$template  = $templates[ $scope_key ] ?? null;

			return is_array( $template )
				? $this->normalize_search_child_elements( (array) ( $template['elements'] ?? [] ) )
				: [];
		}

		return $this->normalize_search_child_elements( $fallback_elements );
	}

	/**
	 * Build a scoped storage key for a directory search template.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return string
	 */
	protected function build_search_template_key( int $directory_type_id ): string {
		return 'dir-' . absint( $directory_type_id );
	}

	/**
	 * Decode scoped search templates from widget settings.
	 *
	 * @param array<string,mixed> $settings Widget settings.
	 * @return array<string,array<string,mixed>>
	 */
	protected function get_scoped_search_templates( array $settings ): array {
		$raw = $settings['scoped_search_templates'] ?? '{}';

		if ( is_string( $raw ) ) {
			$decoded = json_decode( html_entity_decode( $raw, ENT_QUOTES, 'UTF-8' ), true );
		} else {
			$decoded = $raw;
		}

		if ( ! is_array( $decoded ) ) {
			return [];
		}

		$templates = [];

		foreach ( $decoded as $key => $template ) {
			if ( ! is_string( $key ) || ! is_array( $template ) || ! preg_match( '/^dir-\d+$/', $key ) ) {
				continue;
			}

			$scope_key = sanitize_key( $key );
			$templates[ $scope_key ] = [
				'directory_type_id' => absint( $template['directory_type_id'] ?? 0 ),
				'label'             => sanitize_text_field( (string) ( $template['label'] ?? '' ) ),
				'elements'          => $this->normalize_search_child_elements( (array) ( $template['elements'] ?? [] ) ),
			];
		}

		return $templates;
	}

	/**
	 * Normalize raw search child elements for rendering.
	 *
	 * @param array<int,mixed> $elements Raw children.
	 * @return array<int,array<string,mixed>>
	 */
	protected function normalize_search_child_elements( array $elements ): array {
		$normalized = [];

		foreach ( array_values( $elements ) as $raw_child ) {
			if ( ! is_array( $raw_child ) || ! empty( $raw_child['hidden'] ) ) {
				continue;
			}

			if ( 'directorist_search_directory_types' === sanitize_key( (string) ( $raw_child['widgetType'] ?? '' ) ) ) {
				continue;
			}

			$normalized[] = $raw_child;
		}

		return $normalized;
	}

	/**
	 * Resolve the current search directory id from loop context.
	 *
	 * @return int
	 */
	protected function get_active_search_directory_type_id(): int {
		$controller = $this->get_loop_controller();

		if ( is_object( $controller ) ) {
			$directory_type_id = $this->get_controller_directory_type_id( $controller );

			if ( $directory_type_id > 0 ) {
				return $directory_type_id;
			}
		}

		$loop_context = $this->get_loop_context();
		$active       = absint( $loop_context['active_directory'] ?? $loop_context['default_directory_type_id'] ?? 0 );

		if ( $active > 0 ) {
			return $active;
		}

		$settings = $this->get_settings_for_display();

		return absint( $settings['default_directory_type_id'] ?? 0 );
	}

	/**
	 * Get current widget raw data on frontend.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function get_current_widget_raw_data(): ?array {
		if ( $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();
		if ( ! $current_document || ! method_exists( $current_document, 'get_elements_data' ) ) {
			return null;
		}

		$element_data = $this->find_raw_element_by_id(
			(array) $current_document->get_elements_data(),
			(string) $this->get_id()
		);

		return is_array( $element_data ) ? $element_data : null;
	}

	/**
	 * Find raw element payload by id.
	 *
	 * @param array<int,mixed> $elements Elements.
	 * @param string           $target_id Target id.
	 * @return array<string,mixed>|null
	 */
	protected function find_raw_element_by_id( array $elements, string $target_id ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $element;
			}

			$children = (array) ( $element['elements'] ?? [] );
			if ( empty( $children ) ) {
				continue;
			}

			$found = $this->find_raw_element_by_id( $children, $target_id );
			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Resolve ancestor loop element from current document.
	 *
	 * @return array<string,mixed>|null
	 */
	protected function resolve_ancestor_loop_element(): ?array {
		if ( ! $this->is_editor_context() || ! class_exists( '\\Elementor\\Plugin' ) ) {
			return null;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();
		if ( ! $current_document || ! method_exists( $current_document, 'get_elements_data' ) ) {
			return null;
		}

		return $this->find_ancestor_loop_element(
			(array) $current_document->get_elements_data(),
			(string) $this->get_id()
		);
	}

	/**
	 * Find nearest loop ancestor for a widget id.
	 *
	 * @param array<int,array<string,mixed>> $elements Elements.
	 * @param string                         $target_id Target id.
	 * @param array<string,mixed>|null       $current_loop Current loop.
	 * @return array<string,mixed>|null
	 */
	protected function find_ancestor_loop_element( array $elements, string $target_id, ?array $current_loop = null ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$next_loop = $current_loop;
			if ( in_array( sanitize_key( (string) ( $element['elType'] ?? '' ) ), [ 'directorist_listings_loop', 'directorist_homepage_search_loop' ], true ) ) {
				$next_loop = $element;
			}

			if ( $target_id === (string) ( $element['id'] ?? '' ) ) {
				return $next_loop;
			}

			$children = (array) ( $element['elements'] ?? [] );
			if ( empty( $children ) ) {
				continue;
			}

			$found = $this->find_ancestor_loop_element( $children, $target_id, $next_loop );
			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Normalize id lists.
	 *
	 * @param mixed $value Raw value.
	 * @return array<int,int>
	 */
	protected function normalize_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Resolve controller directory id.
	 *
	 * @param object $controller Controller.
	 * @return int
	 */
	protected function get_controller_directory_type_id( object $controller ): int {
		$directory_type_id = absint( $controller->current_listing_type ?? 0 );

		if ( $directory_type_id <= 0 ) {
			$directory_type_id = absint( $controller->directory_type_id ?? 0 );
		}

		if ( $directory_type_id <= 0 ) {
			$directory_type_id = absint( $controller->atts['default_directory_type'] ?? $controller->atts['default_directory_type_id'] ?? $controller->atts['directory_type_id'] ?? 0 );
		}

		return $directory_type_id;
	}

	/**
	 * Resolve controller directory slug.
	 *
	 * @param object $controller Controller.
	 * @return string
	 */
	protected function get_controller_directory_type_slug( object $controller ): string {
		if ( method_exists( $controller, 'get_directory_type_slug' ) ) {
			return (string) $controller->get_directory_type_slug();
		}

		return sanitize_title( (string) ( $controller->atts['directory_type'] ?? '' ) );
	}

	/**
	 * Resolve current loop directory ids.
	 *
	 * @return array<int,int>
	 */
	protected function get_context_directory_type_ids(): array {
		$loop_context = $this->get_loop_context();
		$ids          = $this->normalize_id_list( $loop_context['directory_type_ids'] ?? [] );

		if ( ! empty( $ids ) ) {
			return $ids;
		}

		$active = absint( $loop_context['active_directory'] ?? 0 );

		return $active > 0 ? [ $active ] : [];
	}
}
