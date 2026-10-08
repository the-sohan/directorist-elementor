<?php
/**
 * Author profile composition render helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\ElementorV4\Bridge\DirectoristBridge;
use DirectoristElementor\ElementorV4\Context\EditorContext;
use DirectoristElementor\Services\ExtensionStatusService;
use DirectoristElementor\Traits\Singleton;
use Elementor\Icons_Manager;

class AuthorProfileRenderService {
	use Singleton;

	/**
	 * Cached author payloads.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected array $prepared_authors = [];

	/**
	 * Prepare author display data for a listing.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $display_email Email visibility mode.
	 * @return array<string,mixed>
	 */
	public function prepare( int $listing_id, string $display_email = 'public' ): array {
		$listing_id     = absint( $listing_id );
		$display_email  = $this->normalize_email_visibility( $display_email );
		$cache_key      = $listing_id . ':' . $display_email . ':' . ( is_user_logged_in() ? '1' : '0' );

		if ( isset( $this->prepared_authors[ $cache_key ] ) ) {
			return $this->prepared_authors[ $cache_key ];
		}

		$bridge = DirectoristBridge::get_instance();
		$data   = (array) $bridge->with_listing_post_context(
			$listing_id,
			function() use ( $bridge, $listing_id, $display_email ): array {
				return $this->prepare_in_listing_context( $bridge, $listing_id, $display_email );
			}
		);

		$this->prepared_authors[ $cache_key ] = $data;

		return $data;
	}

	/**
	 * Prepare author display data directly from a user id.
	 *
	 * @param int    $author_id Author user id.
	 * @param string $display_email Email visibility mode.
	 * @param int    $listing_id Optional listing id when rendering from a listing context.
	 * @return array<string,mixed>
	 */
	public function prepare_for_author( int $author_id, string $display_email = 'public', int $listing_id = 0 ): array {
		$author_id     = absint( $author_id );
		$display_email = $this->normalize_email_visibility( $display_email );
		$listing_id    = absint( $listing_id );
		$cache_key     = 'author:' . $author_id . ':' . $display_email . ':' . $listing_id . ':' . ( is_user_logged_in() ? '1' : '0' );

		if ( isset( $this->prepared_authors[ $cache_key ] ) ) {
			return $this->prepared_authors[ $cache_key ];
		}

		$user = $author_id > 0 ? get_userdata( $author_id ) : false;

		if ( ! $user instanceof \WP_User ) {
			return [];
		}

		$show_email = true;
		if ( 'hidden' === $display_email ) {
			$show_email = false;
		} elseif ( 'logged_in' === $display_email ) {
			$show_email = is_user_logged_in();
		}

		$profile_url = '';
		if ( class_exists( 'ATBDP_Permalink' ) && method_exists( 'ATBDP_Permalink', 'get_user_profile_page_link' ) ) {
			$profile_url = (string) \ATBDP_Permalink::get_user_profile_page_link( $author_id );
		} else {
			$profile_url = get_author_posts_url( $author_id );
		}

		$pro_pic_id = get_user_meta( $author_id, 'pro_pic', true );
		$pro_pic    = $pro_pic_id ? wp_get_attachment_image_src( absint( $pro_pic_id ), 'thumbnail' ) : false;
		$avatar_url = ! empty( $pro_pic[0] ) ? (string) $pro_pic[0] : get_avatar_url( $author_id, [ 'size' => 96 ] );

		$user_registered = (string) get_the_author_meta( 'user_registered', $author_id );
		$membership      = '';
		if ( '' !== $user_registered ) {
			$membership = sprintf(
				/* translators: %s: membership duration. */
				__( 'Member since %s ago', 'directorist-elementor' ),
				human_time_diff( strtotime( $user_registered ), current_time( 'timestamp' ) )
			);
		}

		$bridge            = DirectoristBridge::get_instance();
		$listing_post_type = $bridge->get_listing_post_type();
		$listing_count     = count_user_posts( $author_id, $listing_post_type, true );
		$review_enabled    = function_exists( 'get_directorist_option' ) ? (bool) get_directorist_option( 'enable_review', 1 ) : true;
		$rating            = '0';
		$review_count      = 0;

		if ( $review_enabled && function_exists( 'directorist_get_listing_rating' ) ) {
			$author_listing_ids = get_posts(
				[
					'post_type'              => $listing_post_type,
					'post_status'            => 'publish',
					'author'                 => $author_id,
					'fields'                 => 'ids',
					'posts_per_page'         => -1,
					'orderby'                => 'post_date',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$rating_sum = 0;
			foreach ( $author_listing_ids as $author_listing_id ) {
				$average = (float) directorist_get_listing_rating( absint( $author_listing_id ) );
				if ( $average <= 0 ) {
					continue;
				}

				$rating_sum += $average;
				$review_count++;
			}

			if ( $review_count > 0 ) {
				$rating = number_format_i18n( $rating_sum / $review_count, 1 );
			}
		}

		$live_chat_available = $listing_id > 0
			&& ExtensionStatusService::get_instance()->is_extension_active( 'live_chat' )
			&& $bridge->is_preset_widget_allowed( $listing_id, 'live_chat' );

		$data = [
			'author_id'           => $author_id,
			'name'                => sanitize_text_field( $user->display_name ),
			'avatar'              => esc_url_raw( $avatar_url ),
			'profile_url'         => esc_url_raw( $profile_url ),
			'membership'          => sanitize_text_field( $membership ),
			'address'             => sanitize_text_field( (string) get_user_meta( $author_id, 'address', true ) ),
			'phone'               => sanitize_text_field( (string) get_user_meta( $author_id, 'atbdp_phone', true ) ),
			'email'               => $show_email ? sanitize_email( $user->user_email ) : '',
			'website'             => esc_url_raw( (string) get_the_author_meta( 'user_url', $author_id ) ),
			'facebook'            => esc_url_raw( (string) get_user_meta( $author_id, 'atbdp_facebook', true ) ),
			'twitter'             => esc_url_raw( (string) get_user_meta( $author_id, 'atbdp_twitter', true ) ),
			'linkedin'            => esc_url_raw( (string) get_user_meta( $author_id, 'atbdp_linkedin', true ) ),
			'youtube'             => esc_url_raw( (string) get_user_meta( $author_id, 'atbdp_youtube', true ) ),
			'bio'                 => trim( (string) get_the_author_meta( 'description', $author_id ) ),
			'listing_count'       => absint( $listing_count ),
			'rating'              => sanitize_text_field( $rating ),
			'review_count'        => absint( $review_count ),
			'review_enabled'      => $review_enabled,
			'live_chat_available' => $live_chat_available,
			'show_email'          => $show_email,
			'display_email'       => $display_email,
			'listing_id'          => $listing_id,
		];

		$this->prepared_authors[ $cache_key ] = $data;

		return $data;
	}

	/**
	 * Render one author profile field.
	 *
	 * @param string $field Field key.
	 * @param array  $author Author data.
	 * @param array  $settings Widget settings.
	 * @return string
	 */
	public function render_field( string $field, array $author, array $settings = [] ): string {
		switch ( $field ) {
			case 'avatar':
				return $this->render_avatar( $author, $settings );

			case 'name':
				return $this->render_name( $author, $settings );

			case 'membership':
				return $this->render_membership( $author );

			case 'bio':
				return $this->render_bio( $author, $settings );

			case 'listing-count':
				return $this->render_listing_count( $author, $settings );

			case 'rating':
				return $this->render_rating( $author, $settings );

			case 'contact-address':
			case 'contact-phone':
			case 'contact-email':
			case 'contact-website':
				return $this->render_contact_field( $field, $author, $settings );

			case 'social-links':
				return $this->render_social_links( $author, $settings );

			case 'button':
				return $this->render_profile_button( $author, $settings );

			case 'message-button':
				return $this->render_message_button( $author, $settings );
		}

		return '';
	}

	/**
	 * Normalize email visibility.
	 *
	 * @param string $visibility Raw mode.
	 * @return string
	 */
	public function normalize_email_visibility( string $visibility ): string {
		$visibility = sanitize_key( $visibility );

		return in_array( $visibility, [ 'public', 'logged_in', 'hidden' ], true ) ? $visibility : 'public';
	}

	/**
	 * Prepare data while the global post points at the listing.
	 *
	 * @param DirectoristBridge $bridge Bridge.
	 * @param int               $listing_id Listing id.
	 * @param string            $display_email Email visibility mode.
	 * @return array<string,mixed>
	 */
	protected function prepare_in_listing_context( DirectoristBridge $bridge, int $listing_id, string $display_email ): array {
		$author_id = $listing_id > 0 ? absint( (int) get_post_field( 'post_author', $listing_id ) ) : 0;
		$listing   = $bridge->get_single_listing_instance( $listing_id );

		$author_info = static function( string $key ) use ( $listing ): string {
			if ( ! is_object( $listing ) || ! method_exists( $listing, 'author_info' ) ) {
				return '';
			}

			$value = $listing->author_info( $key );

			return is_scalar( $value ) ? trim( (string) $value ) : '';
		};

		$user = $author_id > 0 ? get_userdata( $author_id ) : false;
		$name = $author_info( 'name' );
		if ( '' === $name && $user instanceof \WP_User ) {
			$name = $user->display_name;
		}

		$email = $author_info( 'email' );
		if ( '' === $email && $user instanceof \WP_User ) {
			$email = $user->user_email;
		}

		$show_email = true;
		if ( 'hidden' === $display_email ) {
			$show_email = false;
		} elseif ( 'logged_in' === $display_email ) {
			$show_email = is_user_logged_in();
		}

		$profile_url = '';
		if ( $author_id > 0 && class_exists( 'ATBDP_Permalink' ) && method_exists( 'ATBDP_Permalink', 'get_user_profile_page_link' ) ) {
			$profile_url = (string) \ATBDP_Permalink::get_user_profile_page_link( $author_id );
		} elseif ( $author_id > 0 ) {
			$profile_url = get_author_posts_url( $author_id );
		}

		$pro_pic_id = $author_id > 0 ? get_user_meta( $author_id, 'pro_pic', true ) : 0;
		$pro_pic    = $pro_pic_id ? wp_get_attachment_image_src( absint( $pro_pic_id ), 'thumbnail' ) : false;
		$avatar_url = ! empty( $pro_pic[0] ) ? (string) $pro_pic[0] : '';
		if ( '' === $avatar_url && $author_id > 0 ) {
			$avatar_url = get_avatar_url( $author_id, [ 'size' => 96 ] );
		}

		$member_since = $author_info( 'member_since' );
		$membership   = '' !== $member_since
			? sprintf(
				/* translators: %s: membership duration. */
				__( 'Member since %s ago', 'directorist-elementor' ),
				$member_since
			)
			: '';

		$listing_post_type = $bridge->get_listing_post_type();
		$listing_count     = $author_id > 0 ? count_user_posts( $author_id, $listing_post_type, true ) : 0;
		$review_enabled    = function_exists( 'get_directorist_option' ) ? (bool) get_directorist_option( 'enable_review', 1 ) : true;
		$rating            = '0';
		$review_count      = 0;

		if ( $author_id > 0 && $review_enabled && function_exists( 'directorist_get_listing_rating' ) ) {
			$author_listing_ids = get_posts(
				[
					'post_type'              => $listing_post_type,
					'post_status'            => 'publish',
					'author'                 => $author_id,
					'fields'                 => 'ids',
					'posts_per_page'         => -1,
					'orderby'                => 'post_date',
					'order'                  => 'ASC',
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				]
			);

			$rating_sum = 0;
			foreach ( $author_listing_ids as $author_listing_id ) {
				$average = (float) directorist_get_listing_rating( absint( $author_listing_id ) );
				if ( $average <= 0 ) {
					continue;
				}

				$rating_sum += $average;
				$review_count++;
			}

			if ( $review_count > 0 ) {
				$rating = number_format_i18n( $rating_sum / $review_count, 1 );
			}
		}

		$live_chat_available = $listing_id > 0
			&& ExtensionStatusService::get_instance()->is_extension_active( 'live_chat' )
			&& $bridge->is_preset_widget_allowed( $listing_id, 'live_chat' );

		$bio = $author_id > 0 ? get_the_author_meta( 'description', $author_id ) : '';

		return [
			'author_id'           => $author_id,
			'name'                => sanitize_text_field( $name ),
			'avatar'              => esc_url_raw( $avatar_url ),
			'profile_url'         => esc_url_raw( $profile_url ),
			'membership'          => sanitize_text_field( $membership ),
			'address'             => sanitize_text_field( $author_info( 'address' ) ),
			'phone'               => sanitize_text_field( $author_info( 'phone' ) ),
			'email'               => $show_email ? sanitize_email( $email ) : '',
			'website'             => esc_url_raw( $author_info( 'website' ) ),
			'facebook'            => esc_url_raw( $author_info( 'facebook' ) ),
			'twitter'             => esc_url_raw( $author_info( 'twitter' ) ),
			'linkedin'            => esc_url_raw( $author_info( 'linkedin' ) ),
			'youtube'             => esc_url_raw( $author_info( 'youtube' ) ),
			'bio'                 => is_scalar( $bio ) ? trim( (string) $bio ) : '',
			'listing_count'       => absint( $listing_count ),
			'rating'              => sanitize_text_field( $rating ),
			'review_count'        => absint( $review_count ),
			'review_enabled'      => $review_enabled,
			'live_chat_available' => $live_chat_available,
			'show_email'          => $show_email,
			'display_email'       => $display_email,
			'listing_id'          => $listing_id,
		];
	}

	/**
	 * Render avatar.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_avatar( array $author, array $settings ): string {
		$avatar_url = esc_url_raw( (string) ( $author['avatar'] ?? '' ) );
		if ( '' === $avatar_url ) {
			return '';
		}

		$name = (string) ( $author['name'] ?? __( 'Author', 'directorist-elementor' ) );
		$html = sprintf(
			'<img class="directorist-elementor-author-profile-avatar__image" src="%1$s" alt="%2$s" loading="lazy" />',
			esc_url( $avatar_url ),
			esc_attr( $name )
		);

		if ( $this->is_enabled( $settings['link'] ?? '' ) && ! empty( $author['profile_url'] ) ) {
			$html = sprintf(
				'<a class="directorist-elementor-author-profile-avatar__link" href="%1$s">%2$s</a>',
				esc_url( (string) $author['profile_url'] ),
				$html
			);
		}

		return sprintf(
			'<figure class="directorist-single-author-avatar-inner directorist-elementor-author-profile-avatar">%s</figure>',
			$html
		);
	}

	/**
	 * Render name.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_name( array $author, array $settings ): string {
		$name = trim( (string) ( $author['name'] ?? '' ) );
		if ( '' === $name ) {
			return '';
		}

		$tag = $this->sanitize_tag( (string) ( $settings['html_tag'] ?? 'h4' ), [ 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' ], 'h4' );
		$text = esc_html( $name );

		if ( $this->is_enabled( $settings['link'] ?? 'yes' ) && ! empty( $author['profile_url'] ) ) {
			$text = sprintf(
				'<a class="directorist-elementor-author-profile-name__link" href="%1$s">%2$s</a>',
				esc_url( (string) $author['profile_url'] ),
				$text
			);
		}

		return sprintf(
			'<%1$s class="directorist-single-author-name directorist-elementor-author-profile-name">%2$s</%1$s>',
			tag_escape( $tag ),
			$text
		);
	}

	/**
	 * Render membership text.
	 *
	 * @param array $author Author data.
	 * @return string
	 */
	protected function render_membership( array $author ): string {
		$membership = trim( (string) ( $author['membership'] ?? '' ) );

		return '' === $membership
			? ''
			: sprintf( '<span class="directorist-single-author-membership directorist-elementor-author-profile-membership">%s</span>', esc_html( $membership ) );
	}

	/**
	 * Render bio.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_bio( array $author, array $settings ): string {
		$bio = trim( (string) ( $author['bio'] ?? '' ) );
		if ( '' === $bio ) {
			if ( ! $this->is_enabled( $settings['show_empty_message'] ?? '' ) ) {
				return '';
			}

			$bio = __( 'Nothing to show!', 'directorist-elementor' );
		}

		return sprintf(
			'<div class="directorist-author-about__content directorist-elementor-author-profile-bio">%s</div>',
			wp_kses_post( wpautop( $bio ) )
		);
	}

	/**
	 * Render listing count.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_listing_count( array $author, array $settings ): string {
		$count = absint( $author['listing_count'] ?? 0 );
		$icon  = $this->is_enabled( $settings['show_icon'] ?? 'yes' )
			? $this->render_icon( $settings['field_icon'] ?? [ 'value' => 'fas fa-list-ol', 'library' => 'fa-solid' ], 'directorist-elementor-author-profile-listing-count__icon' )
			: '';
		$text  = sprintf(
			/* translators: %s: listing count. */
			_nx( '<span>%s</span> Listing', '<span>%s</span> Listings', $count, 'author listing count', 'directorist-elementor' ),
			esc_html( number_format_i18n( $count ) )
		);

		return sprintf(
			'<div class="directorist-author-meta-list__item directorist-info-meta directorist-elementor-author-profile-listing-count">%1$s<span class="directorist-listing-count">%2$s</span></div>',
			$icon,
			wp_kses_post( $text )
		);
	}

	/**
	 * Render rating.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_rating( array $author, array $settings ): string {
		if ( empty( $author['review_enabled'] ) ) {
			return '';
		}

		$review_count = absint( $author['review_count'] ?? 0 );
		if ( $review_count <= 0 && ! $this->is_enabled( $settings['show_empty'] ?? 'yes' ) ) {
			return '';
		}

		$icon = $this->is_enabled( $settings['show_icon'] ?? 'yes' )
			? $this->render_icon( $settings['field_icon'] ?? [ 'value' => 'fas fa-star', 'library' => 'fa-solid' ], 'directorist-elementor-author-profile-rating__icon' )
			: '';
		$value = $this->is_enabled( $settings['show_rating_value'] ?? 'yes' )
			? sprintf( '<span>%s</span>', esc_html( (string) ( $author['rating'] ?? '0' ) ) )
			: '';
		$count = $this->is_enabled( $settings['show_review_count'] ?? 'yes' )
			? sprintf(
				/* translators: %s: review count. */
				_nx( '%s Review', '%s Reviews', $review_count, 'author review count', 'directorist-elementor' ),
				esc_html( number_format_i18n( $review_count ) )
			)
			: '';

		if ( '' === $icon && '' === $value && '' === $count ) {
			return '';
		}

		return sprintf(
			'<div class="directorist-author-meta-list__item directorist-info-meta directorist-elementor-author-profile-rating">%1$s<span class="directorist-review-count">%2$s%3$s</span></div>',
			$icon,
			$value,
			'' !== $count ? ' ' . esc_html( $count ) : ''
		);
	}

	/**
	 * Render contact field.
	 *
	 * @param string $field Field key.
	 * @param array  $author Author data.
	 * @param array  $settings Widget settings.
	 * @return string
	 */
	protected function render_contact_field( string $field, array $author, array $settings ): string {
		$config = $this->get_contact_config( $field );
		if ( empty( $config ) ) {
			return '';
		}

		$value = trim( (string) ( $author[ $config['attr'] ] ?? '' ) );
		if ( '' === $value ) {
			return '';
		}

		$tag        = $this->sanitize_tag( (string) ( $settings['html_tag'] ?? 'div' ), [ 'div', 'span', 'p' ], 'div' );
		$link       = $this->is_enabled( $settings['link_to_value'] ?? ( empty( $config['link'] ) ? '' : 'yes' ) )
			? $this->contact_link( (string) $config['link'], $value )
			: '';
		$show_icon  = $this->is_enabled( $settings['show_icon'] ?? 'yes' );
		$show_label = $this->is_enabled( $settings['show_label'] ?? '' );
		$label      = trim( (string) ( $settings['label_text'] ?? $config['label'] ) );
		$icon       = $show_icon
			? $this->render_icon( $settings['field_icon'] ?? $config['icon'], 'directorist-elementor-listing-card-field__icon directorist-elementor-author-profile-contact__icon' )
			: '';
		$label_html = $show_label && '' !== $label
			? sprintf( '<span class="directorist-elementor-listing-card-field__label directorist-elementor-author-profile-contact__label">%s</span>', esc_html( $label ) )
			: '';
		$value_html = esc_html( $value );

		if ( '' !== $link ) {
			$value_html = sprintf(
				'<a class="directorist-elementor-listing-card-field__link directorist-elementor-author-profile-contact__link" href="%1$s"%2$s>%3$s</a>',
				esc_url( $link ),
				'website' === $config['link'] ? ' target="_blank" rel="noopener noreferrer"' : '',
				esc_html( $value )
			);
		}

		return sprintf(
			'<div class="directorist-elementor-listing-card-field directorist-elementor-author-profile-contact directorist-elementor-author-profile-contact--%1$s">%2$s%3$s<%4$s class="directorist-elementor-listing-card-field__value directorist-elementor-author-profile-contact__value">%5$s</%4$s></div>',
			esc_attr( (string) $config['class'] ),
			$icon,
			$label_html,
			tag_escape( $tag ),
			$value_html
		);
	}

	/**
	 * Render social links.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_social_links( array $author, array $settings ): string {
		$target   = $this->is_enabled( $settings['open_in_new_tab'] ?? 'yes' ) ? ' target="_blank" rel="noopener noreferrer"' : '';
		$networks = [
			'facebook' => [ 'lab la-facebook', __( 'Facebook', 'directorist-elementor' ) ],
			'twitter'  => [ 'lab la-twitter', __( 'Twitter', 'directorist-elementor' ) ],
			'linkedin' => [ 'lab la-linkedin', __( 'LinkedIn', 'directorist-elementor' ) ],
			'youtube'  => [ 'lab la-youtube', __( 'YouTube', 'directorist-elementor' ) ],
		];
		$items    = [];

		foreach ( $networks as $key => $network ) {
			$url = esc_url_raw( (string) ( $author[ $key ] ?? '' ) );
			if ( '' === $url ) {
				continue;
			}

			$items[] = sprintf(
				'<li class="directorist-author-social-item directorist-author-social__item directorist-elementor-author-profile-social-links__item directorist-elementor-author-profile-social-links__item--%1$s"><a class="directorist-elementor-author-profile-social-links__link" href="%2$s" aria-label="%3$s"%4$s>%5$s</a></li>',
				esc_attr( $key ),
				esc_url( $url ),
				esc_attr( (string) $network[1] ),
				$target,
				$this->render_line_awesome_icon( (string) $network[0], 'directorist-elementor-author-profile-social-links__icon' )
			);
		}

		return empty( $items )
			? ''
			: '<ul class="directorist-author-social directorist-elementor-author-profile-social-links">' . implode( '', $items ) . '</ul>';
	}

	/**
	 * Render profile button.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_profile_button( array $author, array $settings ): string {
		$url = esc_url_raw( (string) ( $author['profile_url'] ?? '' ) );
		if ( '' === $url ) {
			return '';
		}

		$text   = trim( (string) ( $settings['text'] ?? __( 'View Profile', 'directorist-elementor' ) ) );
		$target = $this->is_enabled( $settings['open_in_new_tab'] ?? '' ) ? ' target="_blank" rel="noopener noreferrer"' : '';

		return sprintf(
			'<div class="directorist-elementor-author-profile-button-wrap"><a class="directorist-btn directorist-btn-light directorist-btn-md diretorist-view-profile-btn directorist-elementor-author-profile-button" href="%1$s"%2$s>%3$s</a></div>',
			esc_url( $url ),
			$target,
			esc_html( '' !== $text ? $text : __( 'View Profile', 'directorist-elementor' ) )
		);
	}

	/**
	 * Render message button through Directorist Live Chat.
	 *
	 * @param array $author Author data.
	 * @param array $settings Widget settings.
	 * @return string
	 */
	protected function render_message_button( array $author, array $settings ): string {
		$listing_id = absint( $author['listing_id'] ?? 0 );
		if ( $listing_id <= 0 ) {
			return '';
		}

		if ( ! ExtensionStatusService::get_instance()->is_extension_active( 'live_chat' ) ) {
			return EditorContext::get_instance()->is_editor_request()
				? $this->render_notice( __( 'Directorist Live Chat must be installed and active to render the message button.', 'directorist-elementor' ) )
				: '';
		}

		if ( empty( $author['live_chat_available'] ) ) {
			return EditorContext::get_instance()->is_editor_request()
				? $this->render_notice( __( 'Live Chat is not enabled for the current preview listing.', 'directorist-elementor' ) )
				: '';
		}

		$bridge = DirectoristBridge::get_instance();
		$bridge->ensure_single_listing_assets( 'single/section-live_chat' );

		if ( function_exists( 'Directorist_Live_Chat' ) ) {
			$chat = \Directorist_Live_Chat();
			if ( is_object( $chat ) && method_exists( $chat, 'load_needed_scripts' ) ) {
				$chat->load_needed_scripts( '' );
			}
		}

		$section_data = wp_parse_args(
			$bridge->get_single_listing_section_data( $listing_id, 'live_chat' ),
			[
				'type'        => 'other_widgets',
				'widget_name' => 'live_chat',
				'label'       => __( 'Live Chat', 'directorist-elementor' ),
			]
		);
		$content      = $bridge->render_single_listing_section( $listing_id, $section_data );

		if ( '' === trim( $content ) ) {
			return EditorContext::get_instance()->is_editor_request()
				? $this->render_notice( __( 'No live chat button is available for the current preview listing.', 'directorist-elementor' ) )
				: '';
		}

		return sprintf(
			'<div class="directorist-elementor-author-profile-message-button">%s</div>',
			$this->add_message_button_class( $content )
		);
	}

	/**
	 * Contact render config.
	 *
	 * @param string $field Field key.
	 * @return array<string,mixed>
	 */
	protected function get_contact_config( string $field ): array {
		$configs = [
			'contact-address' => [
				'attr'  => 'address',
				'label' => __( 'Address:', 'directorist-elementor' ),
				'icon'  => [ 'value' => 'fas fa-address-card', 'library' => 'fa-solid' ],
				'class' => 'address',
				'link'  => '',
			],
			'contact-phone'   => [
				'attr'  => 'phone',
				'label' => __( 'Phone:', 'directorist-elementor' ),
				'icon'  => [ 'value' => 'fas fa-phone-alt', 'library' => 'fa-solid' ],
				'class' => 'phone',
				'link'  => 'phone',
			],
			'contact-email'   => [
				'attr'  => 'email',
				'label' => __( 'Email:', 'directorist-elementor' ),
				'icon'  => [ 'value' => 'fas fa-envelope', 'library' => 'fa-solid' ],
				'class' => 'email',
				'link'  => 'email',
			],
			'contact-website' => [
				'attr'  => 'website',
				'label' => __( 'Website:', 'directorist-elementor' ),
				'icon'  => [ 'value' => 'fas fa-globe', 'library' => 'fa-solid' ],
				'class' => 'website',
				'link'  => 'website',
			],
		];

		return $configs[ $field ] ?? [];
	}

	/**
	 * Build contact href.
	 *
	 * @param string $type Link type.
	 * @param string $value Raw value.
	 * @return string
	 */
	protected function contact_link( string $type, string $value ): string {
		if ( 'phone' === $type ) {
			$phone = $value;
			if ( class_exists( '\Directorist\Helper' ) && method_exists( '\Directorist\Helper', 'formatted_tel' ) ) {
				$phone = \Directorist\Helper::formatted_tel( $value, false );
			} else {
				$phone = preg_replace( '/[^0-9\+]/', '', $value );
			}

			return '' !== (string) $phone ? 'tel:' . $phone : '';
		}

		if ( 'email' === $type ) {
			$email = sanitize_email( $value );

			return '' !== $email ? 'mailto:' . $email : '';
		}

		if ( 'website' === $type ) {
			$url = esc_url_raw( $value );
			if ( '' === $url ) {
				$url = esc_url_raw( 'https://' . ltrim( $value, '/' ) );
			}

			return $url;
		}

		return '';
	}

	/**
	 * Build icon markup from Elementor icon settings.
	 *
	 * @param mixed  $icon_settings Icon setting.
	 * @param string $class Extra class.
	 * @return string
	 */
	protected function render_icon( $icon_settings, string $class = '' ): string {
		if ( is_string( $icon_settings ) && '' !== trim( $icon_settings ) ) {
			return $this->render_line_awesome_icon( $icon_settings, $class );
		}

		if ( empty( $icon_settings ) || ! is_array( $icon_settings ) ) {
			return '';
		}

		ob_start();
		Icons_Manager::render_icon(
			$icon_settings,
			[
				'aria-hidden' => 'true',
				'class'       => $class,
			]
		);

		return (string) ob_get_clean();
	}

	/**
	 * Render a class-based icon.
	 *
	 * @param string $icon_class Icon class.
	 * @param string $class Extra class.
	 * @return string
	 */
	protected function render_line_awesome_icon( string $icon_class, string $class = '' ): string {
		$classes = trim( $icon_class . ' ' . $class );

		return '' === $classes ? '' : '<i class="' . esc_attr( $classes ) . '" aria-hidden="true"></i>';
	}

	/**
	 * Add target class to Live Chat button output.
	 *
	 * @param string $content HTML.
	 * @return string
	 */
	protected function add_message_button_class( string $content ): string {
		$target_class = 'directorist-elementor-author-profile-message-button__control';

		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			while ( $processor->next_tag( 'button' ) ) {
				$processor->add_class( $target_class );
			}

			return $processor->get_updated_html();
		}

		return preg_replace_callback(
			'/<button\b([^>]*)>/i',
			static function( array $matches ) use ( $target_class ): string {
				$attrs = $matches[1];
				if ( preg_match( "/\sclass=([\"\'])(.*?)\\1/i", $attrs ) ) {
					$attrs = preg_replace_callback(
						"/\sclass=([\"\'])(.*?)\\1/i",
						static function( array $class_matches ) use ( $target_class ): string {
							$classes   = preg_split( '/\s+/', trim( (string) $class_matches[2] ) );
							$classes   = is_array( $classes ) ? $classes : [];
							$classes[] = $target_class;

							return ' class=' . $class_matches[1] . implode( ' ', array_unique( array_filter( $classes ) ) ) . $class_matches[1];
						},
						$attrs,
						1
					);
				} else {
					$attrs .= ' class="' . esc_attr( $target_class ) . '"';
				}

				return '<button' . $attrs . '>';
			},
			$content
		) ?: $content;
	}

	/**
	 * Render editor notice.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	protected function render_notice( string $message ): string {
		return sprintf(
			'<div class="directorist-elementor-placeholder directorist-elementor-author-profile-notice"><p>%s</p></div>',
			esc_html( $message )
		);
	}

	/**
	 * Determine switcher truthiness.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	protected function is_enabled( $value ): bool {
		return true === $value || 1 === $value || '1' === $value || 'yes' === $value || 'true' === $value;
	}

	/**
	 * Sanitize HTML tag.
	 *
	 * @param string $tag Raw tag.
	 * @param array  $allowed Allowed tags.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	protected function sanitize_tag( string $tag, array $allowed, string $fallback ): string {
		$tag = strtolower( $tag );

		return in_array( $tag, $allowed, true ) ? $tag : $fallback;
	}
}
