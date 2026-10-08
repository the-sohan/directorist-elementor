<?php
/**
 * Shared renderer for All Categories / All Locations composition output.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\ElementorV4\Context\RenderContext;
use DirectoristElementor\Traits\Singleton;

class TaxonomyCompositionRenderService {
	use Singleton;

	/**
	 * Build the taxonomy widget root attributes.
	 *
	 * @param string              $shortcode Shortcode tag.
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @param array<string,mixed> $slider_settings Slider settings.
	 * @param array<string,mixed> $visibility_settings Visibility settings.
	 * @param array<string,mixed> $tab_settings Directory tab settings.
	 * @param array<string,mixed> $template_raw Raw template child payload.
	 * @param string              $scope Taxonomy scope.
	 * @return array<string,mixed>
	 */
	public function build_wrapper_attributes(
		string $shortcode,
		array $attributes,
		array $slider_settings = [],
		array $visibility_settings = [],
		array $tab_settings = [],
		array $template_raw = [],
		string $scope = ''
	): array {
		$view         = $this->sanitize_taxonomy_view( $attributes['view'] ?? 'grid' );
		$display_mode = ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' ) ? 'slider' : 'pagination';
		$classes      = [
			'directorist-elementor-taxonomy-widget',
			'directorist-elementor-taxonomy-view-' . $view,
			'directorist-elementor-taxonomy-mode-' . $display_mode,
		];
		$style   = $this->build_taxonomy_responsive_style( $attributes );

		if ( ! empty( $visibility_settings ) ) {
			if ( empty( $visibility_settings['show_image'] ) ) {
				$classes[] = 'directorist-gbi-taxonomy-hide-image';
			}
			if ( empty( $visibility_settings['show_icon'] ) ) {
				$classes[] = 'directorist-gbi-taxonomy-hide-icon';
			}
			if ( ! empty( $visibility_settings['show_description'] ) ) {
				$classes[] = 'directorist-gbi-taxonomy-show-description';
			}
		}

		if ( ! empty( $template_raw ) ) {
			$classes[] = 'directorist-elementor-taxonomy-widget--composition';
		}

		$attrs = [
			'class'          => implode( ' ', $classes ),
			'style'          => $style,
			'data-shortcode' => sanitize_key( $shortcode ),
			'data-atts'      => wp_json_encode( $this->filter_shortcode_attributes( $attributes ) ),
		];

		if ( ! empty( $tab_settings ) ) {
			$attrs['data-show-all-tab'] = ! empty( $tab_settings['show_all_directory_tab'] ) ? 'true' : 'false';
			$all_tab_icon = $tab_settings['all_tab_icon'] ?? [];
			if ( is_array( $all_tab_icon ) && ! empty( $all_tab_icon['value'] ) ) {
				$attrs['data-all-tab-icon'] = wp_json_encode( $all_tab_icon );
			}
		}

		if ( ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' ) ) {
			$attrs['data-slider'] = wp_json_encode( $slider_settings );
		}

		if ( ! empty( $template_raw ) ) {
			$attrs['data-composition-scope'] = sanitize_key( $scope );
			$attrs['data-template']          = wp_json_encode( $template_raw );
		}

		return $attrs;
	}

	/**
	 * Render the legacy shortcode content used when no composition exists.
	 *
	 * @param string              $shortcode Shortcode tag.
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @param array<string,mixed> $slider_settings Slider settings.
	 * @param array<string,mixed> $tab_settings Directory tab settings.
	 * @return string
	 */
	public function render_legacy_content( string $shortcode, array $attributes, array $slider_settings = [], array $tab_settings = [] ): string {
		$is_slider = ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' );

		if ( $is_slider ) {
			$attributes['view'] = 'grid';
			foreach ( [ 'cat_per_page', 'loc_per_page' ] as $per_page_key ) {
				if ( array_key_exists( $per_page_key, $attributes ) ) {
					$attributes[ $per_page_key ] = 9999;
				}
			}
		}

		$attributes = $this->filter_shortcode_attributes( $attributes );

		$pairs = [];
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || '' === $attribute_value || false === $attribute_value ) {
				continue;
			}

			if ( is_bool( $attribute_value ) ) {
				$attribute_value = $attribute_value ? 'yes' : 'no';
			}

			if ( is_array( $attribute_value ) ) {
				$attribute_value = implode( ',', array_filter( array_map( 'strval', $attribute_value ) ) );
			}

			$pairs[] = sprintf(
				'%s="%s"',
				sanitize_key( (string) $attribute_name ),
				esc_attr( (string) $attribute_value )
			);
		}

		$output = $this->with_imported_term_order( $shortcode, $attributes, static fn() => do_shortcode(
			sprintf(
				'[%1$s%2$s]',
				sanitize_key( $shortcode ),
				empty( $pairs ) ? '' : ' ' . implode( ' ', $pairs )
			)
		) );

		if ( ! empty( $tab_settings ) ) {
			$output = $this->process_directory_tabs( $output, $tab_settings );
		}

		$output = $this->remove_taxonomy_wrapper_row_class( $output );

		if ( $is_slider ) {
			$output = $this->convert_to_slider( $output, $slider_settings, $shortcode );
		}

		return $output;
	}

	/**
	 * Render composed taxonomy content.
	 *
	 * @param string              $shortcode Shortcode tag.
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @param array<string,mixed> $slider_settings Slider settings.
	 * @param array<string,mixed> $tab_settings Directory tab settings.
	 * @param array<string,mixed> $template_raw Raw template child payload.
	 * @param string              $scope Taxonomy scope.
	 * @return string
	 */
	public function render_composed_content(
		string $shortcode,
		array $attributes,
		array $slider_settings,
		array $tab_settings,
		array $template_raw,
		string $scope
	): string {
		if ( empty( $template_raw ) || ! class_exists( '\\Directorist\\Directorist_Listing_Taxonomy' ) ) {
			return $this->render_legacy_content( $shortcode, $attributes, $slider_settings, $tab_settings );
		}

		$scope     = 'location' === $scope ? 'location' : 'category';
		$is_slider = ! empty( $slider_settings ) && 'slider' === ( $slider_settings['display_mode'] ?? '' );

		if ( 'yes' === (string) ( $attributes['logged_in_user_only'] ?? '' ) && ! is_user_logged_in() ) {
			return function_exists( 'ATBDP' ) ? ATBDP()->helper->guard( [ 'type' => 'auth' ] ) : '';
		}

		if ( ! empty( $attributes['redirect_page_url'] ) ) {
			$validated_url = wp_validate_redirect( (string) $attributes['redirect_page_url'], '' );
			if ( is_string( $validated_url ) && '' !== $validated_url ) {
				return '<script>window.location="' . esc_js( $validated_url ) . '"</script>';
			}
		}

		if ( $is_slider ) {
			$attributes['view'] = 'grid';
			foreach ( [ 'cat_per_page', 'loc_per_page' ] as $per_page_key ) {
				if ( array_key_exists( $per_page_key, $attributes ) ) {
					$attributes[ $per_page_key ] = 9999;
				}
			}
		}

		$taxonomy_attributes = $this->filter_shortcode_attributes( $attributes );
		[ $taxonomy, $items ] = $this->with_imported_term_order( $shortcode, $attributes, static function () use ( $taxonomy_attributes, $scope ) {
			$taxonomy = new \Directorist\Directorist_Listing_Taxonomy( $taxonomy_attributes, $scope );
			return [ $taxonomy, array_values( (array) $taxonomy->tax_data() ) ];
		} );

		ob_start();

		$taxonomy->atts['type']           = $scope;
		$taxonomy->atts['directory_type'] = isset( $_GET['directory_type'] ) && ! empty( $_GET['directory_type'] )
			? sanitize_text_field( wp_unslash( $_GET['directory_type'] ) )
			: '';

		echo '<div id="directorist" class="atbd_wrapper directorist-w-100">';
		printf( '<div class="%s">', esc_attr( $this->get_container_fluid_class() ) );
		printf(
			'<div class="%1$s directorist-elementor-taxonomy-composition directorist-elementor-taxonomy-composition--%2$s" data-attrs="%3$s">',
			esc_attr( $this->get_archive_class( $scope ) ),
			esc_attr( $scope ),
			esc_attr( wp_json_encode( $taxonomy->atts ) )
		);

		do_action( 'category' === $scope ? 'atbdp_before_all_categories_loop' : 'atbdp_before_all_locations_loop', $taxonomy );

		if ( empty( $items ) ) {
			printf( '<p>%s</p>', esc_html__( 'No Results found!', 'directorist-elementor' ) );
		} elseif ( $is_slider ) {
			$this->render_slider_items( $items, $taxonomy, $template_raw, $scope, $slider_settings );
		} else {
			$this->render_grid_items( $items, $taxonomy, $template_raw, $scope );
		}

		echo '</div></div></div>';

		do_action( 'category' === $scope ? 'atbdp_after_all_categories_loop' : 'atbdp_after_all_locations_loop' );

		$output = (string) ob_get_clean();

		if ( ! empty( $tab_settings ) ) {
			$output = $this->process_directory_tabs( $output, $tab_settings );
		}

		return $output;
	}

	/**
	 * Render grid/pagination composition items.
	 *
	 * @param array<int,array<string,mixed>>          $items Items.
	 * @param \Directorist\Directorist_Listing_Taxonomy $taxonomy Taxonomy model.
	 * @param array<string,mixed>                    $template_raw Raw template.
	 * @param string                                 $scope Scope.
	 * @return void
	 */
	protected function render_grid_items( array $items, \Directorist\Directorist_Listing_Taxonomy $taxonomy, array $template_raw, string $scope ): void {
		$wrapper_class = 'category' === $scope ? 'taxonomy-category-wrapper' : 'taxonomy-location-wrapper';
		$filter_name   = 'category' === $scope ? 'directorist_taxonomy_category_wrapper' : 'directorist_taxonomy_location_wrapper';

		printf( '<div class="%s">', esc_attr( apply_filters( $filter_name, $wrapper_class ) ) );

		foreach ( $items as $index => $item ) {
			$item_classes = [
				$this->get_column_class( (int) $taxonomy->columns ),
				'directorist-elementor-taxonomy-grid-item',
				'directorist-elementor-taxonomy-grid-item--' . ( $index + 1 ),
				0 === $index ? 'directorist-elementor-taxonomy-grid-item--first' : '',
				$index + 1 === count( $items ) ? 'directorist-elementor-taxonomy-grid-item--last' : '',
			];
			printf( '<div class="%s">', esc_attr( implode( ' ', array_filter( $item_classes ) ) ) );
			$this->render_taxonomy_card( $item, $taxonomy, $template_raw, $scope, $index );
			echo '</div>';
		}

		ob_start();
		$taxonomy->pagination();
		echo (string) ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		echo '</div>';
	}

	/**
	 * Render slider composition items.
	 *
	 * @param array<int,array<string,mixed>>          $items Items.
	 * @param \Directorist\Directorist_Listing_Taxonomy $taxonomy Taxonomy model.
	 * @param array<string,mixed>                    $template_raw Raw template.
	 * @param string                                 $scope Scope.
	 * @param array<string,mixed>                    $settings Slider settings.
	 * @return void
	 */
	protected function render_slider_items( array $items, \Directorist\Directorist_Listing_Taxonomy $taxonomy, array $template_raw, string $scope, array $settings ): void {
		echo '<div ' . $this->format_html_attributes( $this->build_slider_attributes( $settings, $scope, count( $items ) ) ) . '>';
		echo '<div class="swiper-wrapper directorist-gbi-taxonomy-slider__wrapper">';

		foreach ( $items as $index => $item ) {
			echo '<div class="swiper-slide">';
			$this->render_taxonomy_card( $item, $taxonomy, $template_raw, $scope, $index );
			echo '</div>';
		}

		echo '</div>';

		if ( ! empty( $settings['show_arrow_navigation'] ) ) {
			echo '<div class="directorist-swiper__navigation directorist-gbi-taxonomy-slider__navigation">';
			echo '<div class="directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--prev"><span aria-hidden="true">&#8249;</span></div>';
			echo '<div class="directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--next"><span aria-hidden="true">&#8250;</span></div>';
			echo '</div>';
		}

		if ( ! empty( $settings['show_dot_navigation'] ) ) {
			echo '<div class="directorist-swiper__pagination directorist-swiper__pagination--listing directorist-gbi-taxonomy-slider__pagination"></div>';
		}

		echo '</div>';
	}

	/**
	 * Render a single composed card.
	 *
	 * @param array<string,mixed>                    $item Item payload.
	 * @param \Directorist\Directorist_Listing_Taxonomy $taxonomy Taxonomy model.
	 * @param array<string,mixed>                    $template_raw Raw template.
	 * @param string                                 $scope Scope.
	 * @param int                                    $index Item index.
	 * @return void
	 */
	protected function render_taxonomy_card( array $item, \Directorist\Directorist_Listing_Taxonomy $taxonomy, array $template_raw, string $scope, int $index ): void {
		$item       = $this->enrich_item_context( $item, $scope );
		$background = $this->resolve_background_config( $template_raw, $scope, $item );
		$classes    = [
			'directorist-elementor-taxonomy-card',
			'directorist-elementor-taxonomy-card--' . $scope,
		];
		$styles     = [];

		if ( ! empty( $background['url'] ) ) {
			$classes[] = 'directorist-elementor-taxonomy-card--has-background';
			$styles[]  = 'background-image:url(' . esc_url_raw( (string) $background['url'] ) . ')';
			$styles[]  = 'background-size:' . sanitize_key( (string) ( $background['size'] ?? 'cover' ) );
			$styles[]  = 'background-position:' . sanitize_text_field( (string) ( $background['position'] ?? 'center center' ) );
			$styles[]  = '--direl-taxonomy-background-overlay:' . sanitize_hex_color( (string) ( $background['overlay_color'] ?? '#000000' ) );
			$styles[]  = '--direl-taxonomy-background-opacity:' . max( 0, min( 1, (float) ( $background['overlay_opacity'] ?? 0.45 ) ) );
			$styles[]  = 'min-height:' . max( 80, absint( $background['min_height'] ?? 220 ) ) . 'px';
		}

		$attrs = [
			'class' => implode( ' ', $classes ),
			'style' => implode( ';', $styles ),
		];

		echo '<article ' . $this->format_html_attributes( $attrs ) . '>';

		if ( ! empty( $background['url'] ) ) {
			echo '<span aria-hidden="true" class="wp-block-cover__background has-background-dim directorist-elementor-taxonomy-card__background-dim"></span>';
		}

		echo '<div class="directorist-elementor-taxonomy-card__content">';

		RenderContext::get_instance()->push_taxonomy_context(
			$scope,
			$item,
			$index,
			[
				'show_count' => (bool) $taxonomy->show_count,
				'taxonomy'   => $taxonomy,
			]
		);

		try {
			$element = ElementTreeRenderService::get_instance()->create_element_instance( $template_raw );
			if ( $element && method_exists( $element, 'print_element' ) ) {
				$element->print_element();
			}
		} finally {
			RenderContext::get_instance()->pop_taxonomy_context();
		}

		echo '</div></article>';
	}

	/**
	 * Enrich Directorist taxonomy item payload for field widgets.
	 *
	 * @param array<string,mixed> $item Item payload.
	 * @param string              $scope Scope.
	 * @return array<string,mixed>
	 */
	protected function enrich_item_context( array $item, string $scope ): array {
		$term = $item['term'] ?? null;
		if ( $term instanceof \WP_Term ) {
			$item['term_id']     = (int) $term->term_id;
			$item['description'] = (string) $term->description;
			$item['image_id']    = absint( get_term_meta( $term->term_id, 'image', true ) );

			if ( empty( $item['img'] ) && ! empty( $item['image_id'] ) ) {
				$image_url = wp_get_attachment_image_url( (int) $item['image_id'], 'large' );
				if ( is_string( $image_url ) ) {
					$item['img'] = $image_url;
				}
			}
		}

		if ( 'location' === $scope && empty( $item['icon_class'] ) ) {
			$item['icon_class'] = 'las la-map-marker-alt';
		}

		return $item;
	}

	/**
	 * Locate the background-image block settings in the raw template.
	 *
	 * @param array<string,mixed> $element Raw element.
	 * @param string              $scope Scope.
	 * @param array<string,mixed> $item Item payload.
	 * @return array<string,mixed>
	 */
	protected function resolve_background_config( array $element, string $scope, array $item ): array {
		$target_widget = 'directorist_' . $scope . '_card_image';
		$stack         = [ $element ];

		while ( ! empty( $stack ) ) {
			$current  = array_shift( $stack );
			$settings = is_array( $current['settings'] ?? null ) ? (array) $current['settings'] : [];

			if (
				'widget' === (string) ( $current['elType'] ?? '' ) &&
				$target_widget === (string) ( $current['widgetType'] ?? '' ) &&
				'background' === (string) ( $settings['render_mode'] ?? '' )
			) {
				$image_url = $this->resolve_item_image_url( $item, (string) ( $settings['image_size'] ?? 'large' ) );
				$height    = is_array( $settings['image_height'] ?? null ) ? (array) $settings['image_height'] : [];
				$opacity   = is_array( $settings['background_overlay_opacity'] ?? null )
					? ( $settings['background_overlay_opacity']['size'] ?? 0.45 )
					: ( $settings['background_overlay_opacity'] ?? 0.45 );

				return [
					'url'             => $image_url,
					'size'            => sanitize_key( (string) ( $settings['object_fit'] ?? 'cover' ) ),
					'position'        => 'center center',
					'overlay_color'   => sanitize_hex_color( (string) ( $settings['background_overlay_color'] ?? '#000000' ) ) ?: '#000000',
					'overlay_opacity' => (float) $opacity,
					'min_height'      => absint( $height['size'] ?? 220 ),
				];
			}

			foreach ( (array) ( $current['elements'] ?? [] ) as $child ) {
				if ( is_array( $child ) ) {
					$stack[] = $child;
				}
			}
		}

		return [];
	}

	/**
	 * Resolve a taxonomy item image URL.
	 *
	 * @param array<string,mixed> $item Item payload.
	 * @param string              $size Image size.
	 * @return string
	 */
	protected function resolve_item_image_url( array $item, string $size ): string {
		$image_id = absint( $item['image_id'] ?? 0 );
		if ( $image_id > 0 ) {
			$size = sanitize_key( $size );
			$size = in_array( $size, [ 'thumbnail', 'medium', 'large', 'full' ], true ) ? $size : 'large';
			$url  = wp_get_attachment_image_url( $image_id, $size );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}

		return isset( $item['img'] ) ? (string) $item['img'] : '';
	}

	/**
	 * Process directory tabs in rendered markup.
	 *
	 * @param string              $content Rendered content.
	 * @param array<string,mixed> $tab_settings Tab settings.
	 * @return string
	 */
	public function process_directory_tabs( string $content, array $tab_settings ): string {
		$show_all_tab = ! empty( $tab_settings['show_all_directory_tab'] );
		$all_tab_icon = $tab_settings['all_tab_icon'] ?? [];

		if ( ! $show_all_tab ) {
			return (string) preg_replace(
				'/<li[^>]*class="[^"]*list-inline-item[^"]*"[^>]*>\s*<a[^>]*href="[^"]*directory_type=all[^"]*"[^>]*>.*?<\/a>\s*<\/li>/si',
				'',
				$content
			);
		}

		$new_icon_html = $this->render_icon_html( is_array( $all_tab_icon ) ? $all_tab_icon : [] );
		if ( '' !== $new_icon_html ) {
			$content = (string) preg_replace_callback(
				'/(<a[^>]*href="[^"]*directory_type=all[^"]*"[^>]*>)\s*(<i[^>]*>(?:<\/i>)?)\s*/si',
				static function ( array $matches ) use ( $new_icon_html ): string {
					return $matches[1] . $new_icon_html . ' ';
				},
				$content
			);
		}

		$content = (string) preg_replace_callback(
			'/(<a[^>]*class="[^"]*directorist-type-nav__link[^"]*"[^>]*)href="([^"]*)"/si',
			static function ( array $matches ): string {
				$attrs = $matches[1];
				$href  = $matches[2];

				if ( false !== strpos( $href, 'directory_type=all' ) && false === strpos( $attrs, 'data-listing_type' ) ) {
					$attrs .= ' data-listing_type="all" ';
				}

				return $attrs . 'href="#"';
			},
			$content
		);

		return (string) preg_replace_callback(
			'/(<a[^>]*)href="([^"]*)"([^>]*class="[^"]*page-numbers[^"]*")/si',
			static function ( array $matches ): string {
				$href = $matches[2];
				$page = 1;
				if ( preg_match( '/[?&](?:paged|page)=(\d+)/', $href, $pm ) ) {
					$page = (int) $pm[1];
				} elseif ( preg_match( '/\/page\/(\d+)/', $href, $pm ) ) {
					$page = (int) $pm[1];
				}
				return $matches[1] . 'href="#" data-page="' . esc_attr( (string) $page ) . '"' . $matches[3];
			},
			$content
		);
	}

	/**
	 * Convert legacy taxonomy output into Swiper markup.
	 *
	 * @param string              $content Content.
	 * @param array<string,mixed> $settings Slider settings.
	 * @param string              $shortcode Shortcode tag.
	 * @return string
	 */
	protected function convert_to_slider( string $content, array $settings, string $shortcode ): string {
		if ( '' === trim( $content ) || ! class_exists( 'DOMDocument' ) ) {
			return $content;
		}

		$previous_libxml_state = libxml_use_internal_errors( true );
		$document              = new \DOMDocument( '1.0', 'UTF-8' );
		$wrapper_id            = 'directorist-taxonomy-slider-root-' . wp_rand( 1000, 999999 );
		$load_options          = ( defined( 'LIBXML_HTML_NOIMPLIED' ) ? LIBXML_HTML_NOIMPLIED : 0 )
			| ( defined( 'LIBXML_HTML_NODEFDTD' ) ? LIBXML_HTML_NODEFDTD : 0 );
		$loaded               = $document->loadHTML(
			sprintf( '<?xml encoding="utf-8" ?><div id="%1$s">%2$s</div>', esc_attr( $wrapper_id ), $content ),
			$load_options
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_state );

		if ( ! $loaded ) {
			return $content;
		}

		$xpath  = new \DOMXPath( $document );
		$tracks = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " taxonomy-category-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " taxonomy-location-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " atbdp-no-margin ")]'
		);

		if ( ! ( $tracks instanceof \DOMNodeList ) || 0 === $tracks->length ) {
			return $content;
		}

		$scope = 'directorist_all_categories' === $shortcode ? 'categories' : 'locations';

		foreach ( iterator_to_array( $tracks ) as $track ) {
			if ( ! ( $track instanceof \DOMElement ) || ! $track->parentNode ) {
				continue;
			}

			$this->remove_dom_element_css_class( $track, 'directorist-row' );

			$slide_nodes = [];
			$fixed_nodes = [];
			foreach ( iterator_to_array( $track->childNodes ) as $child_node ) {
				if ( ! ( $child_node instanceof \DOMElement ) ) {
					continue;
				}

				$class = trim( (string) $child_node->getAttribute( 'class' ) );
				if ( 1 === preg_match( '/(^|\s)(directorist-col-[^\s]+|col-[^\s]+)/', $class ) && false === strpos( ' ' . $class . ' ', ' directorist-col-12 ' ) ) {
					$slide_nodes[] = $child_node;
				} else {
					$fixed_nodes[] = $child_node;
				}
			}

			if ( empty( $slide_nodes ) ) {
				continue;
			}

			$slider_node = $document->createElement( 'div' );
			foreach ( $this->build_slider_attributes( $settings, $scope, count( $slide_nodes ) ) as $attr_name => $attr_value ) {
				$slider_node->setAttribute( $attr_name, (string) $attr_value );
			}

			$swiper_wrapper = $document->createElement( 'div' );
			$swiper_wrapper->setAttribute( 'class', 'swiper-wrapper directorist-gbi-taxonomy-slider__wrapper' );

			foreach ( $slide_nodes as $slide_node ) {
				$class = trim( (string) $slide_node->getAttribute( 'class' ) );
				$slide_node->setAttribute( 'class', trim( $class . ' swiper-slide' ) );
				$swiper_wrapper->appendChild( $slide_node );
			}

			$slider_node->appendChild( $swiper_wrapper );

			if ( ! empty( $settings['show_arrow_navigation'] ) ) {
				$navigation_node = $document->createElement( 'div' );
				$navigation_node->setAttribute( 'class', 'directorist-swiper__navigation directorist-gbi-taxonomy-slider__navigation' );

				$prev_node = $document->createElement( 'div' );
				$prev_node->setAttribute( 'class', 'directorist-swiper__nav directorist-swiper__nav--prev directorist-swiper__nav--prev-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--prev' );
				$prev_node->appendChild( $document->createElement( 'span' ) );
				$prev_node->firstChild->appendChild( $document->createTextNode( "\xE2\x80\xB9" ) );

				$next_node = $document->createElement( 'div' );
				$next_node->setAttribute( 'class', 'directorist-swiper__nav directorist-swiper__nav--next directorist-swiper__nav--next-listing directorist-gbi-taxonomy-slider__arrow directorist-gbi-taxonomy-slider__arrow--next' );
				$next_node->appendChild( $document->createElement( 'span' ) );
				$next_node->firstChild->appendChild( $document->createTextNode( "\xE2\x80\xBA" ) );

				$navigation_node->appendChild( $prev_node );
				$navigation_node->appendChild( $next_node );
				$slider_node->appendChild( $navigation_node );
			}

			if ( ! empty( $settings['show_dot_navigation'] ) ) {
				$pagination_node = $document->createElement( 'div' );
				$pagination_node->setAttribute( 'class', 'directorist-swiper__pagination directorist-swiper__pagination--listing directorist-gbi-taxonomy-slider__pagination' );
				$slider_node->appendChild( $pagination_node );
			}

			$track->parentNode->replaceChild( $slider_node, $track );

			foreach ( $fixed_nodes as $fixed_node ) {
				$slider_node->parentNode->insertBefore( $fixed_node, $slider_node );
			}
		}

		$root_wrapper = $document->getElementById( $wrapper_id );
		if ( ! ( $root_wrapper instanceof \DOMElement ) ) {
			return $content;
		}

		$updated = '';
		foreach ( $root_wrapper->childNodes as $child_node ) {
			$updated .= $document->saveHTML( $child_node );
		}

		return '' !== trim( $updated ) ? $updated : $content;
	}

	/**
	 * Build slider data attributes.
	 *
	 * @param array<string,mixed> $settings Slider settings.
	 * @param string              $scope Scope.
	 * @param int                 $count Slide count.
	 * @return array<string,string>
	 */
	protected function build_slider_attributes( array $settings, string $scope, int $count ): array {
		$slides_desktop = max( 1, min( 12, (int) ( $settings['slides_per_view'] ?? 3 ) ) );
		$slides_tablet  = max( 1, min( 12, (int) ( $settings['slides_per_view_tablet'] ?? 2 ) ) );
		$slides_mobile  = max( 1, min( 12, (int) ( $settings['slides_per_view_mobile'] ?? 1 ) ) );
		$effect         = sanitize_key( (string) ( $settings['slider_effect'] ?? 'slide' ) );
		$grid_rows      = max( 1, min( 6, (int) ( $settings['slider_grid_rows'] ?? 2 ) ) );
		$speed          = max( 100, (int) ( $settings['transition_speed'] ?? 500 ) );
		$delay          = max( 100, (int) ( $settings['autoplay_delay'] ?? 3000 ) );
		$autoplay       = ! empty( $settings['slider_autoplay'] );
		$pause          = isset( $settings['pause_on_hover'] ) ? ! empty( $settings['pause_on_hover'] ) : true;
		$show_arrows    = ! empty( $settings['show_arrow_navigation'] );
		$show_dots      = ! empty( $settings['show_dot_navigation'] );
		$breakpoints    = wp_json_encode(
			[
				0    => [ 'slidesPerView' => $slides_mobile, 'spaceBetween' => 10 ],
				768  => [ 'slidesPerView' => $slides_tablet, 'spaceBetween' => 14 ],
				1200 => [ 'slidesPerView' => $slides_desktop, 'spaceBetween' => 16 ],
			]
		);

		return [
			'class'                   => 'directorist-swiper directorist-swiper-listing directorist-gbi-taxonomy-slider directorist-gbi-taxonomy-slider--' . sanitize_html_class( $scope ),
			'data-gbi-slides-per-view' => (string) $slides_desktop,
			'data-gbi-space-between'  => '16',
			'data-gbi-show-arrows'    => $show_arrows ? 'true' : 'false',
			'data-gbi-show-dots'      => $show_dots ? 'true' : 'false',
			'data-gbi-transition-speed' => (string) $speed,
			'data-gbi-autoplay'       => $autoplay ? 'true' : 'false',
			'data-gbi-effect'         => $effect,
			'data-gbi-grid-rows'      => (string) $grid_rows,
			'data-gbi-pause-on-hover' => $pause ? 'true' : 'false',
			'data-gbi-delay'          => (string) $delay,
			'data-gbi-breakpoints'    => is_string( $breakpoints ) ? $breakpoints : '',
			'data-sw-items'           => (string) $slides_desktop,
			'data-sw-margin'          => '16',
			'data-sw-loop'            => $count > $slides_desktop ? 'true' : 'false',
			'data-sw-perslide'        => '1',
			'data-sw-speed'           => (string) $speed,
			'data-sw-delay'           => (string) $delay,
			'data-sw-autoplay'        => $autoplay ? 'true' : 'false',
			'data-sw-effect'          => $effect,
			'data-sw-grid-rows'       => (string) $grid_rows,
			'data-sw-pause-on-hover'  => $pause ? 'true' : 'false',
			'data-sw-responsive'      => is_string( $breakpoints ) ? $breakpoints : '',
		];
	}

	/**
	 * Build responsive CSS vars for the taxonomy root.
	 *
	 * @param array<string,mixed> $attributes Shortcode attributes.
	 * @return string
	 */
	protected function build_taxonomy_responsive_style( array $attributes ): string {
		$columns_desktop = $this->normalize_taxonomy_columns( $attributes['columns'] ?? 3 );
		$columns_tablet  = $this->resolve_responsive_taxonomy_columns( $attributes, 'columns_tablet', $columns_desktop );
		$columns_mobile  = $this->resolve_responsive_taxonomy_columns( $attributes, 'columns_mobile', $columns_tablet );
		$layout_style    = is_string( $attributes['_direl_layout_style'] ?? null ) ? (string) $attributes['_direl_layout_style'] : '';

		return sprintf(
			'--direl-taxonomy-columns:%1$d;--direl-taxonomy-columns-tablet:%2$d;--direl-taxonomy-columns-mobile:%3$d;%4$s',
			$columns_desktop,
			$columns_tablet,
			$columns_mobile,
			$layout_style
		);
	}

	/**
	 * Build a scoped placement style tag.
	 *
	 * @param array<string,mixed> $attributes Widget attributes.
	 * @return string
	 */
	public function build_taxonomy_placement_style_tag( array $attributes ): string {
		$css = is_string( $attributes['_direl_placement_style'] ?? null ) ? trim( (string) $attributes['_direl_placement_style'] ) : '';

		return '' === $css ? '' : '<style>' . $css . '</style>';
	}

	/** Preserve imported ID order without freezing the query or its pagination. */
	protected function with_imported_term_order( string $shortcode, array $attributes, callable $render ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', explode( ',', (string) ( $attributes['imported_term_order'] ?? '' ) ) ) ) ) );
		if ( empty( $ids ) || ! in_array( $attributes['orderby'] ?? 'id', [ 'id', 'term_id' ], true ) ) {
			return $render();
		}
		$taxonomy = 'directorist_all_locations' === $shortcode ? 'at_biz_dir-location' : 'at_biz_dir-category';
		$cases = [];
		foreach ( $ids as $rank => $id ) {
			$cases[] = 'WHEN ' . $id . ' THEN ' . $rank;
		}
		$sql = 'CASE t.term_id ' . implode( ' ', $cases ) . ' ELSE ' . count( $ids ) . ' + t.term_id END';
		$filter = static function ( $orderby, $args, $taxonomies ) use ( $taxonomy, $sql ) {
			return [ $taxonomy ] === array_values( (array) $taxonomies ) && in_array( $args['orderby'] ?? '', [ 'id', 'term_id' ], true ) ? $sql : $orderby;
		};
		add_filter( 'get_terms_orderby', $filter, 10, 3 );
		try {
			return $render();
		} finally {
			remove_filter( 'get_terms_orderby', $filter, 10 );
		}
	}

	/**
	 * Filter Elementor-only attributes before Directorist shortcode/model usage.
	 *
	 * @param array<string,mixed> $attributes Attributes.
	 * @return array<string,mixed>
	 */
	protected function filter_shortcode_attributes( array $attributes ): array {
		$filtered = [];

		foreach ( $attributes as $key => $value ) {
			if ( $this->is_private_attribute( (string) $key ) ) {
				continue;
			}

			$filtered[ $key ] = $value;
		}

		if ( 'flex' === ( $filtered['view'] ?? '' ) ) {
			$filtered['view'] = 'grid';
		}

		return $filtered;
	}

	protected function is_private_attribute( string $attribute_name ): bool {
		return 0 === strpos( $attribute_name, '_' ) || 0 === strpos( $attribute_name, 'direl_' );
	}

	protected function sanitize_taxonomy_view( $value ): string {
		$value = sanitize_key( (string) $value );

		return in_array( $value, [ 'grid', 'flex', 'list' ], true ) ? $value : 'grid';
	}

	protected function normalize_taxonomy_columns( $value ): int {
		return max( 1, min( 12, absint( $value ) ) );
	}

	protected function resolve_responsive_taxonomy_columns( array $attributes, string $attribute_key, int $fallback ): int {
		if ( ! array_key_exists( $attribute_key, $attributes ) ) {
			return $fallback;
		}

		$value = $attributes[ $attribute_key ];
		if ( is_string( $value ) && '' === trim( $value ) ) {
			return $fallback;
		}

		$normalized = $this->normalize_taxonomy_columns( $value );

		return $normalized > 0 ? $normalized : $fallback;
	}

	protected function render_icon_html( array $icon ): string {
		if ( empty( $icon['value'] ) || ! class_exists( '\\Elementor\\Icons_Manager' ) ) {
			return '';
		}

		ob_start();
		\Elementor\Icons_Manager::render_icon(
			$icon,
			[ 'aria-hidden' => 'true', 'style' => 'width:1em;height:1em;vertical-align:middle;fill:currentColor;' ]
		);

		$output = ob_get_clean();

		return is_string( $output ) && '' !== trim( $output ) ? $output : '';
	}

	protected function get_archive_class( string $scope ): string {
		return 'category' === $scope ? 'directorist-categories' : 'directorist-location directorist-location--grid directorist-location--grid-one';
	}

	protected function get_container_fluid_class(): string {
		return (string) apply_filters( 'directorist_container_fluid', 'directorist-container-fluid' );
	}

	protected function get_column_class( int $columns ): string {
		$columns = max( 1, min( 6, $columns ) );
		$span    = 5 === $columns ? '2-5' : (string) max( 1, (int) floor( 12 / $columns ) );
		$class   = 'directorist-col-' . $span;

		return (string) apply_filters( 'directorist_column', $class, $span );
	}

	protected function remove_taxonomy_wrapper_row_class( string $content ): string {
		if ( '' === trim( $content ) || ! class_exists( '\\DOMDocument' ) ) {
			return $content;
		}

		$previous_libxml_state = libxml_use_internal_errors( true );
		$document             = new \DOMDocument( '1.0', 'UTF-8' );
		$wrapper_id           = 'directorist-taxonomy-row-cleanup-' . wp_rand( 1000, 999999 );
		$load_options         = ( defined( 'LIBXML_HTML_NOIMPLIED' ) ? LIBXML_HTML_NOIMPLIED : 0 )
			| ( defined( 'LIBXML_HTML_NODEFDTD' ) ? LIBXML_HTML_NODEFDTD : 0 );

		$loaded = $document->loadHTML(
			sprintf( '<?xml encoding="utf-8" ?><div id="%1$s">%2$s</div>', esc_attr( $wrapper_id ), $content ),
			$load_options
		);

		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_state );

		if ( ! $loaded ) {
			return $content;
		}

		$xpath  = new \DOMXPath( $document );
		$tracks = $xpath->query(
			'//*[contains(concat(" ", normalize-space(@class), " "), " taxonomy-category-wrapper ") or contains(concat(" ", normalize-space(@class), " "), " taxonomy-location-wrapper ")]'
		);

		if ( ! ( $tracks instanceof \DOMNodeList ) || 0 === $tracks->length ) {
			return $content;
		}

		foreach ( $tracks as $track ) {
			if ( $track instanceof \DOMElement ) {
				$this->remove_dom_element_css_class( $track, 'directorist-row' );
			}
		}

		$root_wrapper = $document->getElementById( $wrapper_id );
		if ( ! ( $root_wrapper instanceof \DOMElement ) ) {
			return $content;
		}

		$updated = '';
		foreach ( $root_wrapper->childNodes as $child_node ) {
			$updated .= $document->saveHTML( $child_node );
		}

		return '' !== trim( $updated ) ? $updated : $content;
	}

	protected function remove_dom_element_css_class( \DOMElement $element, string $class_name ): void {
		$classes = preg_split( '/\s+/', trim( (string) $element->getAttribute( 'class' ) ) );
		$classes = is_array( $classes ) ? array_filter( $classes, static fn( $class ): bool => $class_name !== $class ) : [];

		$element->setAttribute( 'class', implode( ' ', $classes ) );
	}

	protected function format_html_attributes( array $attributes ): string {
		$pairs = [];
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			if ( null === $attribute_value || false === $attribute_value || '' === $attribute_value ) {
				continue;
			}
			$pairs[] = sprintf( '%1$s="%2$s"', esc_attr( $attribute_name ), esc_attr( (string) $attribute_value ) );
		}
		return implode( ' ', $pairs );
	}
}
