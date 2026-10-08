<?php
/**
 * Composable single listing author profile element.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\CategoryRegistrar;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Render\AuthorProfileRenderService;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Includes\Elements\Container;

class SingleListingAuthorProfileWidget extends Container {

	/**
	 * Cached raw child payloads.
	 *
	 * @var array<int,array<string,mixed>>|null
	 */
	protected $render_child_raw_elements = null;

	public static function get_type() {
		return 'directorist_single_listing_author_profile';
	}

	public function get_name() {
		return static::get_type();
	}

	public function get_title() {
		return __( 'Author Profile', 'directorist-elementor' );
	}

	public function get_icon() {
		return 'eicon-person';
	}

	public function get_keywords(): array {
		return [ 'directorist', 'directory', 'listing', 'single', 'author', 'profile', 'composer' ];
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
		return $this->get_default_author_field_elements();
	}

	protected function get_default_repeater_title_setting_key() {
		return '_title';
	}

	protected function get_default_children_title() {
		return __( 'Author Fields', 'directorist-elementor' );
	}

	protected function get_default_children_placeholder_selector() {
		return '.directorist-elementor-author-profile__storage';
	}

	protected function get_default_children_container_placeholder_selector() {
		return '.directorist-elementor-author-profile__template-canvas';
	}

	protected function get_initial_config() {
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
		$config['target_container'] = [ '.directorist-elementor-author-profile__storage' ];
		$config['node'] = 'div';
		$config['is_interlaced'] = true;
		$config['directorist_default_author_elements'] = $this->get_default_author_field_elements();

		return $config;
	}

	protected function register_controls() {
		$this->register_widget_controls();
		parent::register_controls();
	}

	protected function register_widget_controls(): void {
		$directory_type_id = $this->resolve_document_directory_type_id();

		$this->start_controls_section(
			'section_author_profile_content',
			[
				'label' => __( 'Author Profile', 'directorist-elementor' ),
			]
		);

		if ( ! $this->is_author_profile_document_context() ) {
			$this->add_control(
				'preview_listing_id',
				[
					'label'       => __( 'Preview Listing', 'directorist-elementor' ),
					'type'        => Controls_Manager::SELECT2,
					'label_block' => true,
					'default'     => '',
					'options'     => [ '' => __( 'Document Preview Listing', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_listing_options( 100, $directory_type_id ),
					'description' => __( 'Editor-only preview listing for single listing templates.', 'directorist-elementor' ),
				]
			);
		}

		$this->add_control(
			'preview_author_id',
			[
				'label'       => __( 'Preview Author', 'directorist-elementor' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'default'     => '',
				'options'     => [ '' => __( 'Document Preview Author', 'directorist-elementor' ) ] + DirectoristBridge::get_instance()->get_recent_author_options( 100, $directory_type_id ),
				'description' => __( 'Editor-only preview author for Directorist author profile templates.', 'directorist-elementor' ),
			]
		);

		$this->add_control(
			'show_header',
			[
				'label'        => __( 'Show Section Header', 'directorist-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => __( 'Show', 'directorist-elementor' ),
				'label_off'    => __( 'Hide', 'directorist-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'separator'    => 'before',
			]
		);

		$this->add_control(
			'section_title',
			[
				'label'       => __( 'Section Title', 'directorist-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Author Profile', 'directorist-elementor' ),
				'label_block' => true,
				'condition'   => [
					'show_header' => 'yes',
				],
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

		$this->register_parent_style_controls();
	}

	/**
	 * Register parent style controls.
	 *
	 * @return void
	 */
	protected function register_parent_style_controls(): void {
		$this->start_controls_section(
			'section_author_profile_card_style',
			[
				'label' => __( 'Profile Card', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			[
				'name'     => 'card_background',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__card',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__card',
			]
		);

		$this->add_responsive_control(
			'card_border_radius',
			[
				'label'      => __( 'Border Radius', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile__card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__card',
			]
		);

		$this->add_responsive_control(
			'card_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile__card' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_author_profile_header_style',
			[
				'label'     => __( 'Header', 'directorist-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => [
					'show_header' => 'yes',
				],
			]
		);

		$this->add_control(
			'header_color',
			[
				'label'     => __( 'Title Color', 'directorist-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .directorist-elementor-author-profile__title' => 'color: {{VALUE}};',
				],
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'header_typography',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__title',
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'header_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__header',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'header_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__header',
			]
		);

		$this->add_responsive_control(
			'header_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile__header' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_author_profile_body_style',
			[
				'label' => __( 'Body', 'directorist-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			[
				'name'     => 'body_border',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__body',
			]
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			[
				'name'     => 'body_shadow',
				'selector' => '{{WRAPPER}} .directorist-elementor-author-profile__body',
			]
		);

		$this->add_responsive_control(
			'body_padding',
			[
				'label'      => __( 'Padding', 'directorist-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', 'em', 'rem', '%' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile__body' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
			]
		);

		$this->add_responsive_control(
			'inner_gap',
			[
				'label'      => __( 'Field Gap', 'directorist-elementor' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem' ],
				'selectors'  => [
					'{{WRAPPER}} .directorist-elementor-author-profile__inner' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget.
	 *
	 * @return void
	 */
	protected function print_content() {
		$settings      = $this->get_settings_for_display();
		$display_email = AuthorProfileRenderService::get_instance()->normalize_email_visibility( (string) ( $settings['display_email'] ?? 'public' ) );
		$context       = $this->resolve_author_profile_render_context( $display_email );
		$listing_id    = absint( $context['listing_id'] ?? 0 );
		$author_id     = absint( $context['author_id'] ?? 0 );
		$author        = is_array( $context['author'] ?? null ) ? (array) $context['author'] : [];

		if ( $author_id <= 0 || empty( $author ) ) {
			if ( $this->is_editor_context() ) {
				$placeholder_message = $this->is_author_profile_document_context()
					? __( 'Select a preview author or place this element in a Directorist author profile template.', 'directorist-elementor' )
					: __( 'Select a preview author, select a preview listing, or place this element in a Directorist author profile or single listing template.', 'directorist-elementor' );

				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Author Profile', 'directorist-elementor' ),
						$placeholder_message
					)
				);
			}

			return;
		}

		DirectoristBridge::get_instance()->ensure_single_listing_assets( 'single/section-author_info' );

		$instance_id = InstanceState::get_instance()->normalize_instance_id( 'direl-author-profile-' . $this->get_id() );
		$attributes  = InstanceState::get_instance()->build_widget_root_attributes(
			$instance_id,
			'author-profile',
			[
				'class'           => [
					'directorist-elementor-author-profile',
					$this->is_editor_context() ? 'directorist-elementor-author-profile--editor' : '',
				],
				'data-listing-id' => $listing_id,
				'data-author-id'  => $author_id,
			]
		);
		$children = array_values( $this->get_children() );

		echo '<div ' . $this->format_html_attributes( $attributes ) . '>';
		echo '<section class="directorist-card directorist-card-author-info directorist-elementor-author-profile__card">';

		if ( 'yes' === (string) ( $settings['show_header'] ?? 'yes' ) ) {
			$title = trim( (string) ( $settings['section_title'] ?? __( 'Author Profile', 'directorist-elementor' ) ) );
			echo '<header class="directorist-card__header directorist-elementor-author-profile__header">';
			echo '<h3 class="directorist-card__header__title directorist-elementor-author-profile__title">' . esc_html( '' !== $title ? $title : __( 'Author Profile', 'directorist-elementor' ) ) . '</h3>';
			echo '</header>';
		}

		echo '<div class="directorist-card__body directorist-elementor-author-profile__body">';
		echo '<div class="directorist-single-author-info directorist-elementor-author-profile__inner">';

		$has_listing_context = $listing_id > 0;

		if ( $has_listing_context ) {
			$directory_type_id = DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id );
			RenderContext::get_instance()->push_listing_context( $listing_id, 0, $directory_type_id );
		}

		RenderContext::get_instance()->push_author_profile_context(
			$author,
			$listing_id,
			[
				'display_email' => $display_email,
				'author_id'      => $author_id,
			]
		);

		try {
			$rendered = $this->render_author_child( $children );

			if ( '' !== trim( $rendered ) ) {
				echo $rendered; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Elementor child output is already rendered markup.
			} elseif ( $this->is_editor_context() ) {
				echo wp_kses_post(
					$this->render_placeholder(
						__( 'Empty Author Profile', 'directorist-elementor' ),
						__( 'Add author field widgets into the author profile canvas.', 'directorist-elementor' )
					)
				);
			}
		} finally {
			RenderContext::get_instance()->pop_author_profile_context();

			if ( $has_listing_context ) {
				RenderContext::get_instance()->pop_listing_context();
			}
		}

		echo '</div>';
		echo '</div>';
		echo '</section>';

		if ( $this->is_editor_context() ) {
			echo '<div class="directorist-elementor-author-profile__storage" aria-hidden="true"></div>';
		}

		echo '</div>';
	}

	/**
	 * Editor template.
	 *
	 * @return void
	 */
	protected function content_template() {
		?>
		<#
		const showHeader = 'yes' === ( settings.show_header || 'yes' );
		const title = settings.section_title || '<?php echo esc_js( __( 'Author Profile', 'directorist-elementor' ) ); ?>';
		#>
		<div class="directorist-elementor-author-profile directorist-elementor-author-profile--editor">
			<div class="directorist-elementor-author-profile__preview-surface">
				<div class="directorist-elementor-placeholder directorist-elementor-author-profile__preview-loading">
					<p class="directorist-elementor-placeholder__title"><?php echo esc_html__( 'Loading Preview', 'directorist-elementor' ); ?></p>
					<p><?php echo esc_html__( 'Rendering the author profile preview from the current element settings.', 'directorist-elementor' ); ?></p>
				</div>
			</div>
			<section class="directorist-card directorist-card-author-info directorist-elementor-author-profile__card directorist-elementor-author-profile__storage" aria-hidden="true">
				<# if ( showHeader ) { #>
					<header class="directorist-card__header directorist-elementor-author-profile__header">
						<h3 class="directorist-card__header__title directorist-elementor-author-profile__title">{{{ title }}}</h3>
					</header>
				<# } #>
				<div class="directorist-card__body directorist-elementor-author-profile__body">
					<div class="directorist-single-author-info directorist-elementor-author-profile__inner directorist-elementor-author-profile__template-canvas"></div>
				</div>
			</section>
		</div>
		<?php
	}

	/**
	 * Render child composition.
	 *
	 * @param array<int,mixed> $children Child elements.
	 * @return string
	 */
	protected function render_author_child( array $children ): string {
		$raw_children = $this->expand_legacy_author_wrapper_raw_children( $this->get_render_child_raw_elements() );

		if ( ! empty( $raw_children ) ) {
			$rendered = '';

			foreach ( $raw_children as $raw_child ) {
				if ( ! is_array( $raw_child ) || ! $this->has_raw_composition_content( $raw_child ) ) {
					continue;
				}

				$fresh_element = ElementTreeRenderService::get_instance()->create_element_instance( $raw_child );

				if ( ! $fresh_element || ! method_exists( $fresh_element, 'print_element' ) ) {
					continue;
				}

				ob_start();
				$fresh_element->print_element();
				$rendered .= trim( (string) ob_get_clean() );
			}

			if ( '' !== trim( $rendered ) ) {
				return trim( $rendered );
			}
		}

		$rendered = '';

		foreach ( $children as $child ) {
			if ( ! is_object( $child ) || ! $this->has_composition_content( $child ) || ! method_exists( $child, 'print_element' ) ) {
				continue;
			}

			ob_start();
			$child->print_element();
			$rendered .= trim( (string) ob_get_clean() );
		}

		return trim( $rendered );
	}

	/**
	 * Unwrap the legacy default container used by the first Elementor pass.
	 *
	 * @param array<int,array<string,mixed>> $raw_children Raw child elements.
	 * @return array<int,array<string,mixed>>
	 */
	protected function expand_legacy_author_wrapper_raw_children( array $raw_children ): array {
		if ( 1 !== count( $raw_children ) ) {
			return $raw_children;
		}

		$first_child = $raw_children[0] ?? null;

		if ( ! is_array( $first_child ) || ! $this->is_legacy_author_fields_wrapper_raw( $first_child ) ) {
			return $raw_children;
		}

		$expanded = [];

		foreach ( (array) ( $first_child['elements'] ?? [] ) as $child ) {
			if ( is_array( $child ) ) {
				$expanded[] = $child;
			}
		}

		return $expanded;
	}

	/**
	 * Check whether raw element is the old generated author fields wrapper.
	 *
	 * @param array<string,mixed> $element_data Raw element data.
	 * @return bool
	 */
	protected function is_legacy_author_fields_wrapper_raw( array $element_data ): bool {
		if ( 'container' !== (string) ( $element_data['elType'] ?? '' ) ) {
			return false;
		}

		$settings = (array) ( $element_data['settings'] ?? [] );

		return 'Author Profile Fields' === (string) ( $settings['_title'] ?? '' );
	}

	/**
	 * Get default field widgets.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_default_author_field_elements(): array {
		return [
			$this->get_default_child_widget( 'directorist_author_profile_avatar', __( 'Author Avatar', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_name', __( 'Author Name', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_membership', __( 'Author Membership', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_rating', __( 'Author Rating', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_listing_count', __( 'Author Listing Count', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_bio', __( 'Author Bio', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_contact_address', __( 'Author Address', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_contact_phone', __( 'Author Phone', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_contact_email', __( 'Author Email', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_contact_website', __( 'Author Website', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_social_links', __( 'Author Social Links', 'directorist-elementor' ) ),
			$this->get_default_child_widget( 'directorist_author_profile_button', __( 'Author Profile Button', 'directorist-elementor' ) ),
		];
	}

	/**
	 * Resolve author profile render context.
	 *
	 * @param string $display_email Email visibility mode.
	 * @return array{author_id:int,listing_id:int,author:array<string,mixed>}
	 */
	protected function resolve_author_profile_render_context( string $display_email ): array {
		$bridge    = DirectoristBridge::get_instance();
		$author_id = $bridge->get_current_author_profile_user_id();

		if ( $author_id > 0 && ( $bridge->is_author_profile_request() || wp_doing_ajax() ) ) {
			return [
				'author_id'  => $author_id,
				'listing_id' => 0,
				'author'     => AuthorProfileRenderService::get_instance()->prepare_for_author( $author_id, $display_email ),
			];
		}

		if ( $this->is_editor_context() && $this->is_author_profile_document_context() ) {
			$settings          = $this->get_settings_for_display();
			$preview_author_id = absint( $settings['preview_author_id'] ?? 0 );

			if ( $preview_author_id <= 0 ) {
				$preview_author_id = $bridge->get_default_preview_author_id( $this->resolve_document_directory_type_id() );
			}

			if ( $preview_author_id > 0 ) {
				return [
					'author_id'  => $preview_author_id,
					'listing_id' => 0,
					'author'     => AuthorProfileRenderService::get_instance()->prepare_for_author( $preview_author_id, $display_email ),
				];
			}
		}

		$listing_id = $this->resolve_listing_id();

		if ( $listing_id <= 0 ) {
			return [
				'author_id'  => 0,
				'listing_id' => 0,
				'author'     => [],
			];
		}

		$author = AuthorProfileRenderService::get_instance()->prepare( $listing_id, $display_email );

		return [
			'author_id'  => absint( $author['author_id'] ?? 0 ),
			'listing_id' => $listing_id,
			'author'     => $author,
		];
	}

	/**
	 * Check whether the current Elementor editor document is an author archive.
	 *
	 * @return bool
	 */
	protected function is_author_profile_document_context(): bool {
		$document_type = '';

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_name' ) ) {
				$document_type = (string) $current_document->get_name();
			}
		}

		if ( 'directorist-listing-author-archive' === $document_type ) {
			return true;
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return false;
		}

		return 'directorist-listing-author-archive' === (string) get_post_meta( $editor_post_id, '_elementor_template_type', true );
	}

	/**
	 * Build default child widget payload.
	 *
	 * @param string $widget_type Widget type.
	 * @param string $title Title.
	 * @return array<string,mixed>
	 */
	protected function get_default_child_widget( string $widget_type, string $title ): array {
		return [
			'elType'          => 'widget',
			'widgetType'      => $widget_type,
			'settings'        => [
				'_title' => $title,
			],
			'elements'        => [],
			'editor_settings' => [
				'title' => $title,
			],
		];
	}

	/**
	 * Resolve listing id.
	 *
	 * @return int
	 */
	protected function resolve_listing_id(): int {
		$selected_listing_id = $this->resolve_editor_selected_listing_id();

		if ( $selected_listing_id > 0 ) {
			return $selected_listing_id;
		}

		$document_preview_listing_id = $this->resolve_single_listing_fallback_id();

		if ( $document_preview_listing_id > 0 ) {
			return $document_preview_listing_id;
		}

		if ( $this->is_editor_context() ) {
			return DirectoristBridge::get_instance()->get_default_preview_listing_id( $this->resolve_document_directory_type_id() );
		}

		return 0;
	}

	/**
	 * Resolve selected editor preview listing id.
	 *
	 * @return int
	 */
	protected function resolve_editor_selected_listing_id(): int {
		if ( ! $this->is_editor_context() ) {
			return 0;
		}

		$settings   = $this->get_settings_for_display();
		$listing_id = absint( $settings['preview_listing_id'] ?? 0 );

		if ( $listing_id <= 0 || ! $this->is_listing_in_document_directory_type( $listing_id ) ) {
			return 0;
		}

		return $listing_id;
	}

	/**
	 * Resolve directory type ID from the current Elementor document type.
	 *
	 * @return int
	 */
	protected function resolve_document_directory_type_id(): int {
		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$current_document = \Elementor\Plugin::$instance->documents->get_current();
			if ( $current_document && method_exists( $current_document, 'get_name' ) ) {
				$directory_type_id = $this->parse_directory_type_id_from_document_type( (string) $current_document->get_name() );
				if ( $directory_type_id > 0 ) {
					return $directory_type_id;
				}
			}
		}

		$editor_post_id = $this->resolve_editor_document_post_id_from_request();
		if ( $editor_post_id <= 0 ) {
			return 0;
		}

		return $this->parse_directory_type_id_from_document_type(
			(string) get_post_meta( $editor_post_id, '_elementor_template_type', true )
		);
	}

	/**
	 * Parse Directorist directory id from document type.
	 *
	 * @param string $document_type Document type.
	 * @return int
	 */
	protected function parse_directory_type_id_from_document_type( string $document_type ): int {
		$prefix = 'directorist-single-listing-directory-';

		if ( 0 !== strpos( $document_type, $prefix ) ) {
			return 0;
		}

		return absint( substr( $document_type, strlen( $prefix ) ) );
	}

	/**
	 * Resolve editor document id from request.
	 *
	 * @return int
	 */
	protected function resolve_editor_document_post_id_from_request(): int {
		foreach ( [ 'editor_post_id', 'post_id', 'post' ] as $request_key ) {
			if ( empty( $_REQUEST[ $request_key ] ) ) {
				continue;
			}

			$post_id = absint( wp_unslash( $_REQUEST[ $request_key ] ) );
			if ( $post_id > 0 ) {
				return $post_id;
			}
		}

		return 0;
	}

	/**
	 * Check whether a listing belongs to the active document directory.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	protected function is_listing_in_document_directory_type( int $listing_id ): bool {
		$directory_type_id = $this->resolve_document_directory_type_id();

		return $directory_type_id <= 0 || DirectoristBridge::get_instance()->get_listing_directory_type_id( $listing_id ) === $directory_type_id;
	}

	/**
	 * Resolve single listing fallback id.
	 *
	 * @return int
	 */
	protected function resolve_single_listing_fallback_id(): int {
		$post_type = DirectoristBridge::get_instance()->get_listing_post_type();

		if ( is_singular( $post_type ) ) {
			return absint( get_queried_object_id() );
		}

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return 0;
		}

		$current_document = \Elementor\Plugin::$instance->documents->get_current();

		if ( ! $current_document || ! method_exists( $current_document, 'get_settings' ) ) {
			return 0;
		}

		$preview_type = (string) $current_document->get_settings( 'preview_type' );
		if ( 'single/' . $post_type !== $preview_type ) {
			return 0;
		}

		$preview_id = absint( $current_document->get_settings( 'preview_id' ) );

		return $this->is_listing_in_document_directory_type( $preview_id ) ? $preview_id : 0;
	}

	/**
	 * Get cached raw child payloads.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function get_render_child_raw_elements(): array {
		if ( is_array( $this->render_child_raw_elements ) ) {
			return $this->render_child_raw_elements;
		}

		$raw_data = $this->get_current_widget_raw_data();

		if ( null === $raw_data ) {
			$raw_data = $this->get_raw_data();
		}

		$raw_children = array_values( (array) ( $raw_data['elements'] ?? [] ) );
		$normalized   = [];

		foreach ( $raw_children as $raw_child ) {
			if ( is_array( $raw_child ) ) {
				$normalized[] = $raw_child;
			}
		}

		$this->render_child_raw_elements = $normalized;

		return $this->render_child_raw_elements;
	}

	/**
	 * Resolve current widget raw data.
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
	 * Recursively find raw element by id.
	 *
	 * @param array  $elements Elements.
	 * @param string $target_id Target id.
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

			$found = $this->find_raw_element_by_id( (array) ( $element['elements'] ?? [] ), $target_id );

			if ( is_array( $found ) ) {
				return $found;
			}
		}

		return null;
	}

	/**
	 * Check raw composition content.
	 *
	 * @param array $element_data Element data.
	 * @return bool
	 */
	protected function has_raw_composition_content( array $element_data ): bool {
		if ( 'container' !== (string) ( $element_data['elType'] ?? '' ) ) {
			return true;
		}

		foreach ( (array) ( $element_data['elements'] ?? [] ) as $child ) {
			if ( is_array( $child ) && $this->has_raw_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check live composition content.
	 *
	 * @param mixed $element Element instance.
	 * @return bool
	 */
	protected function has_composition_content( $element ): bool {
		if ( ! is_object( $element ) ) {
			return false;
		}

		if ( ! $this->is_container_element( $element ) ) {
			return true;
		}

		if ( ! method_exists( $element, 'get_children' ) ) {
			return false;
		}

		foreach ( (array) $element->get_children() as $child ) {
			if ( $this->has_composition_content( $child ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether element is container.
	 *
	 * @param mixed $element Element instance.
	 * @return bool
	 */
	protected function is_container_element( $element ): bool {
		if ( ! is_object( $element ) || ! method_exists( $element, 'get_data' ) ) {
			return false;
		}

		$data = (array) $element->get_data();

		return 'container' === (string) ( $data['elType'] ?? '' );
	}

	/**
	 * Check editor context.
	 *
	 * @return bool
	 */
	protected function is_editor_context(): bool {
		return EditorContext::get_instance()->is_editor_request();
	}

	/**
	 * Format HTML attributes.
	 *
	 * @param array $attributes Attributes.
	 * @return string
	 */
	protected function format_html_attributes( array $attributes ): string {
		$pairs = [];

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ' ', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf( '%1$s="%2$s"', esc_attr( $attribute_name ), esc_attr( (string) $attribute_value ) );
		}

		return implode( ' ', $pairs );
	}

	/**
	 * Render placeholder.
	 *
	 * @param string $title Title.
	 * @param string $description Description.
	 * @param string $meta Meta.
	 * @return string
	 */
	protected function render_placeholder( string $title, string $description, string $meta = '' ): string {
		$meta_markup = '' !== $meta ? sprintf( '<p class="directorist-elementor-placeholder__meta">%s</p>', esc_html( $meta ) ) : '';

		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%1$s</p><p>%2$s</p>%3$s</div>',
			esc_html( $title ),
			esc_html( $description ),
			$meta_markup
		);
	}
}
