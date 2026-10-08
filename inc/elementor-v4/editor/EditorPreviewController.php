<?php
/**
 * Authenticated editor preview AJAX controller.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Editor;

use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\ElementorV4\Context\InstanceState;
use DirectoristElementor\ElementorV4\Render\ElementTreeRenderService;
use DirectoristElementor\ElementorV4\Render\LoopRenderService;
use DirectoristElementor\Traits\Singleton;
use Elementor\Plugin as ElementorPlugin;

class EditorPreviewController {
	use Singleton;

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	protected function __construct() {
		add_action( 'wp_ajax_directorist_elementor_v4_preview_loop', [ $this, 'handle_loop_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_card_template', [ $this, 'handle_card_template_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_loop_utility', [ $this, 'handle_loop_utility_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_single_map', [ $this, 'handle_single_map_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_related_listings', [ $this, 'handle_related_listings_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_taxonomy_archive', [ $this, 'handle_taxonomy_archive_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_pricing_plans', [ $this, 'handle_pricing_plans_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_author_profile', [ $this, 'handle_author_profile_preview' ] );
		add_action( 'wp_ajax_directorist_elementor_v4_preview_search_composition', [ $this, 'handle_search_composition_preview' ] );
	}

	/**
	 * Render server-backed loop state for the editor.
	 *
	 * @return void
	 */
	public function handle_loop_preview(): void {
		$this->authorize_preview_request();

		$payload       = $this->get_payload();
		$loop_payload  = is_array( $payload['loop'] ?? null ) ? (array) $payload['loop'] : [];
		$loop_settings = is_array( $loop_payload['settings'] ?? null ) ? (array) $loop_payload['settings'] : [];
		$loop_settings = $this->normalize_loop_preview_settings( $loop_settings, $loop_payload );
		$instance_id   = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_payload['id'] ?? 'preview' ) )
		);

		$session = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );
		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
				$loop_settings,
				$instance_id,
				true,
				null
			);

			wp_send_json_success(
				[
					'html'    => LoopRenderService::get_instance()->render_editor_state_markup( $runtime_state ),
					'context' => [
						'activeDirectoryId' => (int) ( $runtime_state['active_directory'] ?? 0 ),
						'activeView'        => (string) ( $runtime_state['active_view'] ?? 'grid' ),
						'listingIds'        => array_values( array_map( 'absint', (array) ( $runtime_state['listing_ids'] ?? [] ) ) ),
						'total'             => (int) ( $runtime_state['total'] ?? 0 ),
					],
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render the active card-template preview for the current loop scope.
	 *
	 * @return void
	 */
	public function handle_card_template_preview(): void {
		$this->authorize_preview_request();

		$payload       = $this->get_payload();
		$loop_payload  = is_array( $payload['loop'] ?? null ) ? (array) $payload['loop'] : [];
		$card_payload  = is_array( $payload['card'] ?? null ) ? (array) $payload['card'] : [];
		$loop_settings = is_array( $loop_payload['settings'] ?? null ) ? (array) $loop_payload['settings'] : [];
		$loop_settings = $this->normalize_loop_preview_settings( $loop_settings, $loop_payload );
		$instance_id   = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_payload['id'] ?? 'preview' ) )
		);
		$session       = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );
		$has_loop      = ! empty( $loop_settings );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			if ( $has_loop ) {
				$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
					$loop_settings,
					$instance_id,
					true,
					null
				);
				$runtime_state['utility_state'] = LoopRenderService::get_instance()->extract_utility_state_from_elements(
					is_array( $loop_payload['elements'] ?? null ) ? (array) $loop_payload['elements'] : []
				);
				LoopRenderService::get_instance()->push_loop_context( $runtime_state );
			}

			$html = ElementTreeRenderService::get_instance()->render_element( $card_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the listing card preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			if ( $has_loop ) {
				RenderContext::get_instance()->pop_loop_context();
			}

			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render a loop utility widget against the live editor loop scope.
	 *
	 * Elementor's built-in widget remote render only receives the child widget
	 * payload, so it cannot see unsaved parent loop state such as the active
	 * directory tab. This endpoint accepts the live loop payload explicitly.
	 *
	 * @return void
	 */
	public function handle_loop_utility_preview(): void {
		$this->authorize_preview_request();

		$payload       = $this->get_payload();
		$loop_payload  = is_array( $payload['loop'] ?? null ) ? (array) $payload['loop'] : [];
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$loop_settings = is_array( $loop_payload['settings'] ?? null ) ? (array) $loop_payload['settings'] : [];
		$loop_settings = $this->normalize_loop_preview_settings( $loop_settings, $loop_payload );
		$instance_id   = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_payload['id'] ?? 'preview' ) )
		);
		$session       = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );
		$has_loop      = ! empty( $loop_settings );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			if ( $has_loop ) {
				$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
					$loop_settings,
					$instance_id,
					true,
					null
				);
				$runtime_state['utility_state'] = LoopRenderService::get_instance()->extract_utility_state_from_elements(
					is_array( $loop_payload['elements'] ?? null ) ? (array) $loop_payload['elements'] : []
				);
				LoopRenderService::get_instance()->push_loop_context( $runtime_state );
			}

			$html = ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the loop utility preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			if ( $has_loop ) {
				RenderContext::get_instance()->pop_loop_context();
			}

			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render a composable search widget preview against optional live loop scope.
	 *
	 * Listings Search needs the parent loop scope. Standalone Homepage Search can
	 * render without one because the widget resolves the result-template contract
	 * and builds a loop state from its own directory settings.
	 *
	 * @return void
	 */
	public function handle_search_composition_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$loop_payload   = is_array( $payload['loop'] ?? null ) ? (array) $payload['loop'] : [];
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$loop_settings  = is_array( $loop_payload['settings'] ?? null ) ? (array) $loop_payload['settings'] : [];
		$loop_settings  = $this->normalize_loop_preview_settings( $loop_settings, $loop_payload );
		$instance_id    = InstanceState::get_instance()->normalize_instance_id(
			'direl-loop-' . sanitize_key( (string) ( $loop_payload['id'] ?? 'preview' ) )
		);
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );
		$has_loop       = ! empty( $loop_settings );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			if ( $has_loop ) {
				$runtime_state = LoopRenderService::get_instance()->build_runtime_state(
					$loop_settings,
					$instance_id,
					true,
					null
				);
				LoopRenderService::get_instance()->push_loop_context( $runtime_state );
			}

			$html = ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the search composition preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			if ( $has_loop ) {
				RenderContext::get_instance()->pop_loop_context();
			}

			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render a related-listings widget against the active single-listing preview context.
	 *
	 * @return void
	 */
	public function handle_related_listings_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$html = ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the related listings preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render a pricing plans widget preview from the live editor tree.
	 *
	 * @return void
	 */
	public function handle_pricing_plans_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$html = 'directorist_pricing_plans' === (string) ( $widget_payload['elType'] ?? '' )
				? ElementTreeRenderService::get_instance()->render_element( $widget_payload )
				: ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the pricing plans preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render an author profile element preview from the live editor tree.
	 *
	 * @return void
	 */
	public function handle_author_profile_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$html = 'directorist_single_listing_author_profile' === (string) ( $widget_payload['elType'] ?? '' )
				? ElementTreeRenderService::get_instance()->render_element( $widget_payload )
				: ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the author profile preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render an All Categories / All Locations widget preview.
	 *
	 * @return void
	 */
	public function handle_taxonomy_archive_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$html = ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the taxonomy archive preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Render a single-listing map widget against the active single-listing preview context.
	 *
	 * @return void
	 */
	public function handle_single_map_preview(): void {
		$this->authorize_preview_request();

		$payload        = $this->get_payload();
		$widget_payload = is_array( $payload['widget'] ?? null ) ? (array) $payload['widget'] : [];
		$session        = $this->begin_editor_render_session( absint( $payload['editor_post_id'] ?? 0 ) );

		EditorContext::get_instance()->set_forced_editor_request( true );

		try {
			$html = ElementTreeRenderService::get_instance()->render_widget_content( $widget_payload );

			if ( '' === $html ) {
				wp_send_json_error(
					[
						'message' => __( 'Unable to render the single listing map preview.', 'directorist-elementor' ),
					],
					500
				);
			}

			wp_send_json_success(
				[
					'html' => $html,
				]
			);
		} finally {
			EditorContext::get_instance()->set_forced_editor_request( false );
			$this->end_editor_render_session( $session );
		}
	}

	/**
	 * Authorize the preview request.
	 *
	 * @return void
	 */
	protected function authorize_preview_request(): void {
		check_ajax_referer( 'directorist_elementor_v4_preview', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error(
				[
					'message' => __( 'You are not allowed to render editor previews.', 'directorist-elementor' ),
				],
				403
			);
		}
	}

	/**
	 * Parse the JSON payload from the AJAX request.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_payload(): array {
		$payload = wp_unslash( (string) ( $_POST['payload'] ?? '' ) );

		if ( '' === $payload ) {
			return [];
		}

		$decoded = json_decode( $payload, true );

		return is_array( $decoded ) ? $decoded : [];
	}

	/**
	 * Normalize loop preview settings that are sent as raw editor payload.
	 *
	 * @param array<string,mixed> $settings Loop settings.
	 * @param array<string,mixed> $loop_payload Raw loop payload.
	 * @return array<string,mixed>
	 */
	protected function normalize_loop_preview_settings( array $settings, array $loop_payload ): array {
		$element_type = sanitize_key( (string) ( $loop_payload['elType'] ?? $loop_payload['widgetType'] ?? '' ) );

		if ( 'directorist_homepage_search_loop' !== $element_type ) {
			return $settings;
		}

		$settings['query_mode'] = 'default';
		$settings['query_type'] = 'regular';
		$settings['directorist_elementor_source'] = 'homepage-search-loop';
		$settings['editor_notice'] = __( 'Compose this result template with Directory Types, Homepage Search, header, filters, pagination, and card template.', 'directorist-elementor' );

		return $settings;
	}

	/**
	 * Switch Elementor into a temporary editor render session.
	 *
	 * @param int $editor_post_id Editor document id.
	 * @return array<string,mixed>
	 */
	protected function begin_editor_render_session( int $editor_post_id ): array {
		$session = [
			'editor_post_id' => $editor_post_id,
			'document'       => null,
			'edit_mode'      => null,
			'previous_post'  => $GLOBALS['post'] ?? null,
		];

		if ( ! class_exists( '\\Elementor\\Plugin' ) ) {
			return $session;
		}

		if ( isset( ElementorPlugin::$instance->editor ) && method_exists( ElementorPlugin::$instance->editor, 'is_edit_mode' ) ) {
			$session['edit_mode'] = ElementorPlugin::$instance->editor->is_edit_mode();
			ElementorPlugin::$instance->editor->set_edit_mode( true );
		}

		if ( $editor_post_id > 0 ) {
			$document = ElementorPlugin::$instance->documents->get_with_permissions( $editor_post_id );

			if ( $document ) {
				ElementorPlugin::$instance->documents->switch_to_document( $document );
				$session['document'] = $document;
			}

			$post = get_post( $editor_post_id );

			if ( $post instanceof \WP_Post ) {
				$GLOBALS['post'] = $post;
				setup_postdata( $post );
			}
		}

		return $session;
	}

	/**
	 * Restore the previous Elementor editor render session.
	 *
	 * @param array<string,mixed> $session Session state.
	 * @return void
	 */
	protected function end_editor_render_session( array $session ): void {
		if ( class_exists( '\\Elementor\\Plugin' ) && null !== ( $session['edit_mode'] ?? null ) ) {
			ElementorPlugin::$instance->editor->set_edit_mode( (bool) $session['edit_mode'] );
		}

		if ( isset( $session['previous_post'] ) ) {
			$GLOBALS['post'] = $session['previous_post'];
			wp_reset_postdata();
		}
	}
}
