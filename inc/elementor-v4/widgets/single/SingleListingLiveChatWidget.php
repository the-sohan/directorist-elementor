<?php
/**
 * Single listing live chat widget.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Widgets\Single;

use DirectoristElementor\ElementorV4\Widgets\Base\AbstractSingleExtensionSectionTemplateWidget;

class SingleListingLiveChatWidget extends AbstractSingleExtensionSectionTemplateWidget {

	public function get_name(): string {
		return 'directorist_single_listing_live_chat';
	}

	public function get_title(): string {
		return __( 'Live Chat', 'directorist-elementor' );
	}

	public function get_icon(): string {
		return 'eicon-comments';
	}

	public function get_keywords(): array {
		return array_merge( parent::get_keywords(), [ 'single', 'live', 'chat' ] );
	}

	protected function get_extension_slug(): string {
		return 'live_chat';
	}

	protected function get_section_widget_name(): string {
		return 'live_chat';
	}

	protected function get_default_section_icon(): string {
		return 'las la-comment';
	}

	protected function get_section_wrapper_selector(): string {
		return '.directorist-chat-wrapper';
	}

	protected function get_section_title_selector(): string {
		return '.directorist-start-chat button, .directorist-start-chat-btn';
	}

	protected function get_section_body_selector(): string {
		return '.directorist-client-chat-content-area';
	}

	protected function get_section_header_selector(): string {
		return '.directorist-chat-wrapper .directorist-start-chat';
	}

	protected function get_section_icon_selector(): string {
		return '.directorist-chat-wrapper .directorist-icon-mask, .directorist-chat-wrapper i';
	}

	protected function register_additional_extension_section_style_controls( string $section_id ): void {
		$this->register_extension_button_style_section(
			$section_id . '_live_chat_start_button',
			__( 'Start Chat Button', 'directorist-elementor' ),
			'.directorist-start-chat button, .directorist-start-chat-btn',
			'live_chat_start_button'
		);

		$this->register_extension_box_style_section(
			$section_id . '_live_chat_notice',
			__( 'Login / Notice', 'directorist-elementor' ),
			'.directorist-start-chat .dcl_login_notice, .directorist-chat-wrapper .directorist-atbdp-no-chat',
			'live_chat_notice'
		);

		$this->register_extension_box_style_section(
			$section_id . '_live_chat_panel',
			__( 'Chat Panel', 'directorist-elementor' ),
			'.directorist-client-chat-content-area',
			'live_chat_panel'
		);

		$this->register_extension_button_style_section(
			$section_id . '_live_chat_submit_button',
			__( 'Message Submit Button', 'directorist-elementor' ),
			'.directorist-client-chat-content-area #ChatForm button[type="submit"]',
			'live_chat_submit_button'
		);
	}

	protected function render_extension_widget( int $listing_id ): void {
		if ( function_exists( 'Directorist_Live_Chat' ) ) {
			$chat_instance = Directorist_Live_Chat();

			if ( is_object( $chat_instance ) && method_exists( $chat_instance, 'load_needed_scripts' ) ) {
				$chat_instance->load_needed_scripts( '' );
			}
		}

		parent::render_extension_widget( $listing_id );
	}
}
