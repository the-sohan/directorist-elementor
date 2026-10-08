<?php
/**
 * Remote catalog client for Directorist Elementor imports.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;
use WP_Error;

class CatalogClient {
	use Singleton;

	public const DEFAULT_CATALOG_URL = 'https://live.directorist.com/wp-json/directorist-elementor-template-source/v1/kits/plugin-version/{elementor_version}';

	private const TRANSIENT_PREFIX = 'directorist_elementor_template_catalog_';
	private const CACHE_TTL        = HOUR_IN_SECONDS;

	/**
	 * Get the configured catalog URL.
	 *
	 * @return string
	 */
	public function get_catalog_url(): string {
		$url = self::DEFAULT_CATALOG_URL;

		if ( defined( 'DIRECTORIST_ELEMENTOR_TEMPLATE_CATALOG_URL' ) ) {
			$url = (string) DIRECTORIST_ELEMENTOR_TEMPLATE_CATALOG_URL;
		}

		$url = $this->replace_url_tokens( $url );
		$url = (string) apply_filters( 'directorist_elementor/template_import/catalog_url', $url );
		$url = $this->replace_url_tokens( $url );

		return esc_url_raw( trim( $url ) );
	}

	/**
	 * Get plugin-controlled trusted hosts.
	 *
	 * @return array<int,string>
	 */
	public function get_trusted_hosts(): array {
		$hosts        = [];
		$catalog_host = wp_parse_url( $this->get_catalog_url(), PHP_URL_HOST );

		if ( is_string( $catalog_host ) && '' !== $catalog_host ) {
			$hosts[] = strtolower( $catalog_host );
		}

		$hosts = array_values( array_unique( array_filter( $hosts ) ) );

		/**
		 * Filter trusted remote hosts for catalog details and package downloads.
		 *
		 * This is intentionally code-level configuration, not a dashboard setting.
		 * Use it when the catalog JSON is hosted on one domain and packages/details
		 * are hosted on another trusted Directorist or GitHub release domain.
		 *
		 * @param array<int,string> $hosts Trusted hosts.
		 */
		return (array) apply_filters( 'directorist_elementor/template_import/trusted_hosts', $hosts );
	}

	/**
	 * Fetch catalog items.
	 *
	 * @param bool $refresh Force remote refresh.
	 * @return array<string,mixed>|WP_Error
	 */
	public function fetch_catalog( bool $refresh = false ) {
		$url = $this->get_catalog_url();

		if ( '' === $url ) {
			return new WP_Error(
				'directorist_elementor_catalog_missing_url',
				__( 'Directorist template catalog URL is not configured.', 'directorist-elementor' )
			);
		}

		$valid_url = $this->validate_remote_url( $url );

		if ( is_wp_error( $valid_url ) ) {
			return $valid_url;
		}

		$cache_key = $this->get_cache_key( 'catalog:' . $url );

		if ( $refresh ) {
			delete_transient( $cache_key );
		}

		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout' => 20,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		$catalog = $this->decode_response( $response );

		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}

		$catalog = $this->normalize_catalog( $catalog );
		set_transient( $cache_key, $catalog, (int) apply_filters( 'directorist_elementor/template_import/catalog_cache_ttl', self::CACHE_TTL ) );

		return $catalog;
	}

	/**
	 * Fetch one catalog item detail.
	 *
	 * @param string $item_id Catalog item id.
	 * @param bool   $refresh Force remote refresh.
	 * @return array<string,mixed>|WP_Error
	 */
	public function fetch_item( string $item_id, bool $refresh = false ) {
		$item_id = sanitize_text_field( $item_id );
		$catalog = $this->fetch_catalog( $refresh );

		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}

		$item = $this->find_catalog_item( $catalog, $item_id );

		if ( ! $item ) {
			return new WP_Error(
				'directorist_elementor_catalog_item_missing',
				__( 'The selected Directorist template item was not found in the catalog.', 'directorist-elementor' )
			);
		}

		$detail_url = isset( $item['detail_url'] ) ? esc_url_raw( (string) $item['detail_url'] ) : '';

		if ( '' === $detail_url ) {
			$detail_url = $this->get_item_endpoint_url( $item_id, 'manifest' );
		}

		if ( '' === $detail_url ) {
			return $item;
		}

		$valid_url = $this->validate_remote_url( $detail_url );

		if ( is_wp_error( $valid_url ) ) {
			return $valid_url;
		}

		$cache_key = $this->get_cache_key( 'item:' . $detail_url );

		if ( $refresh ) {
			delete_transient( $cache_key );
		}

		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );

			if ( is_array( $cached ) ) {
				return array_merge( $item, $cached );
			}
		}

		$response = wp_remote_get(
			$detail_url,
			[
				'timeout' => 20,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		$detail = $this->decode_response( $response );

		if ( is_wp_error( $detail ) ) {
			return $item;
		}

		$detail = $this->normalize_item( array_merge( $item, $detail ) );
		set_transient( $cache_key, $detail, (int) apply_filters( 'directorist_elementor/template_import/catalog_cache_ttl', self::CACHE_TTL ) );

		return $detail;
	}

	/**
	 * Fetch related catalog items.
	 *
	 * @param string $item_id Catalog item id.
	 * @param bool   $refresh Force remote refresh.
	 * @return array<int,array<string,mixed>>|WP_Error
	 */
	public function fetch_related( string $item_id, bool $refresh = false ) {
		$item = $this->fetch_item( $item_id, $refresh );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		if ( ! empty( $item['related_items'] ) && is_array( $item['related_items'] ) ) {
			return array_values( array_map( [ $this, 'normalize_item' ], $item['related_items'] ) );
		}

		$related_url = isset( $item['related_url'] ) ? esc_url_raw( (string) $item['related_url'] ) : '';

		if ( '' === $related_url ) {
			$related_url = $this->get_item_endpoint_url( $item_id, 'related' );
		}

		if ( '' === $related_url ) {
			return [];
		}

		$valid_url = $this->validate_remote_url( $related_url );

		if ( is_wp_error( $valid_url ) ) {
			return $valid_url;
		}

		$cache_key = $this->get_cache_key( 'related:' . $related_url );

		if ( $refresh ) {
			delete_transient( $cache_key );
		}

		if ( ! $refresh ) {
			$cached = get_transient( $cache_key );

			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		$response = wp_remote_get(
			$related_url,
			[
				'timeout' => 20,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		$related = $this->decode_response( $response );

		if ( is_wp_error( $related ) ) {
			return [];
		}

		$items = isset( $related['items'] ) && is_array( $related['items'] ) ? $related['items'] : $related;
		$items = is_array( $items ) ? array_values( array_map( [ $this, 'normalize_item' ], $items ) ) : [];

		set_transient( $cache_key, $items, (int) apply_filters( 'directorist_elementor/template_import/catalog_cache_ttl', self::CACHE_TTL ) );

		return $items;
	}

	/**
	 * Validate remote URL against protocol and plugin-controlled trusted hosts.
	 *
	 * @param string $url URL.
	 * @return true|WP_Error
	 */
	public function validate_remote_url( string $url ) {
		$parts = wp_parse_url( $url );

		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return new WP_Error( 'directorist_elementor_invalid_remote_url', __( 'The remote URL is invalid.', 'directorist-elementor' ) );
		}

		if ( ! in_array( strtolower( (string) $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
			return new WP_Error( 'directorist_elementor_invalid_remote_scheme', __( 'Only HTTP and HTTPS remote URLs are supported.', 'directorist-elementor' ) );
		}

		$host = strtolower( (string) $parts['host'] );

		foreach ( $this->get_trusted_hosts() as $trusted_host ) {
			$trusted_host = strtolower( trim( (string) $trusted_host ) );

			if ( '' === $trusted_host ) {
				continue;
			}

			if ( $host === $trusted_host ) {
				return true;
			}

			if ( str_starts_with( $trusted_host, '*.' ) && str_ends_with( $host, substr( $trusted_host, 1 ) ) ) {
				return true;
			}
		}

		return new WP_Error(
			'directorist_elementor_remote_host_not_allowed',
			sprintf(
				/* translators: %s: Remote host. */
				__( 'The remote host %s is not trusted for Directorist template imports.', 'directorist-elementor' ),
				$host
			)
		);
	}

	/**
	 * Decode a JSON response.
	 *
	 * @param mixed $response HTTP response.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function decode_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );

		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error(
				'directorist_elementor_catalog_http_error',
				sprintf(
					/* translators: %d: HTTP status. */
					__( 'The Directorist template catalog returned HTTP %d.', 'directorist-elementor' ),
					$status
				)
			);
		}

		$body = (string) wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error(
				'directorist_elementor_catalog_invalid_json',
				__( 'The Directorist template catalog response is not valid JSON. Check the source site for PHP warnings or output before the JSON payload.', 'directorist-elementor' )
			);
		}

		return $data;
	}

	/**
	 * Normalize catalog payload.
	 *
	 * @param array<string,mixed> $catalog Catalog payload.
	 * @return array<string,mixed>
	 */
	protected function normalize_catalog( array $catalog ): array {
		$is_list_payload = array_is_list( $catalog );

		if ( isset( $catalog['items'] ) && is_array( $catalog['items'] ) ) {
			$items = $catalog['items'];
		} elseif ( isset( $catalog['data'] ) && is_array( $catalog['data'] ) ) {
			$items = $catalog['data'];
		} else {
			$items = $catalog;
		}

		if ( ! is_array( $items ) ) {
			$items = [];
		}

		$items = array_values( array_map( [ $this, 'normalize_item' ], $items ) );

		if ( $is_list_payload ) {
			return [
				'items' => $items,
			];
		}

		$catalog['items'] = $items;

		return $catalog;
	}

	/**
	 * Normalize one catalog item.
	 *
	 * @param mixed $item Raw item.
	 * @return array<string,mixed>
	 */
	protected function normalize_item( $item ): array {
		$item = is_array( $item ) ? $item : [];

		$id = isset( $item['id'] ) ? (string) $item['id'] : (string) ( $item['_id'] ?? $item['package_id'] ?? $item['name'] ?? '' );

		$item['id']           = sanitize_text_field( $id );
		$item['package_id']   = sanitize_text_field( (string) ( $item['package_id'] ?? $id ) );
		$item['title']        = sanitize_text_field( (string) ( $item['title'] ?? $item['name'] ?? $id ) );
		$item['builder']      = sanitize_key( (string) ( $item['builder'] ?? 'elementor' ) );
		$item['package_type'] = sanitize_key( (string) ( $item['package_type'] ?? $item['type'] ?? 'template' ) );
		$item['import_format'] = sanitize_key( (string) ( $item['import_format'] ?? ( ! empty( $item['download_link_url'] ) || ! empty( $item['elementor_version'] ) ? 'elementor_kit' : '' ) ) );

		if ( empty( $item['thumbnail'] ) && ! empty( $item['thumbnail_url'] ) ) {
			$item['thumbnail'] = $item['thumbnail_url'];
		}

		foreach ( [ 'thumbnail', 'thumbnail_url', 'preview_url', 'demo_url', 'dashboard_preview_url', 'detail_url', 'download_link_url', 'related_url', 'package_url', 'download_url' ] as $url_key ) {
			if ( isset( $item[ $url_key ] ) ) {
				$item[ $url_key ] = esc_url_raw( (string) $item[ $url_key ] );
			}
		}

		$item = $this->normalize_item_taxonomies( $item );

		return $item;
	}

	/**
	 * Find catalog item by id.
	 *
	 * @param array<string,mixed> $catalog Catalog.
	 * @param string              $item_id Item id.
	 * @return array<string,mixed>|null
	 */
	protected function find_catalog_item( array $catalog, string $item_id ): ?array {
		$items = isset( $catalog['items'] ) && is_array( $catalog['items'] ) ? $catalog['items'] : [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			if ( (string) ( $item['id'] ?? '' ) === $item_id || (string) ( $item['package_id'] ?? '' ) === $item_id ) {
				return $this->normalize_item( $item );
			}
		}

		return null;
	}

	/**
	 * Resolve API base URL from configured catalog URL.
	 *
	 * @return string
	 */
	protected function get_api_base_url(): string {
		$catalog_url = $this->get_catalog_url();

		if ( '' === $catalog_url ) {
			return '';
		}

		if ( ! preg_match( '#/catalog(?:\?.*)?$#', $catalog_url ) ) {
			if ( preg_match( '#/kits/plugin-version/[^/?]+(?:\?.*)?$#', $catalog_url ) ) {
				$base = preg_replace( '#/kits/plugin-version/[^/?]+(?:\?.*)?$#', '', $catalog_url );

				return is_string( $base ) ? esc_url_raw( $base ) : '';
			}

			return '';
		}

		$base = preg_replace( '#/catalog(?:\?.*)?$#', '', $catalog_url );

		return is_string( $base ) ? esc_url_raw( $base ) : '';
	}

	/**
	 * Build an item endpoint URL from the configured catalog URL.
	 *
	 * @param string $item_id Item id.
	 * @param string $endpoint Endpoint slug.
	 * @return string
	 */
	protected function get_item_endpoint_url( string $item_id, string $endpoint ): string {
		$base_url = $this->get_api_base_url();

		if ( '' === $base_url ) {
			return '';
		}

		$catalog_url = $this->get_catalog_url();
		$item_id     = rawurlencode( $item_id );
		$endpoint    = trim( sanitize_key( $endpoint ), '/' );

		if ( preg_match( '#/kits/plugin-version/[^/?]+(?:\?.*)?$#', $catalog_url ) ) {
			return trailingslashit( $base_url ) . 'kits/' . $item_id . '/' . $endpoint;
		}

		return trailingslashit( $base_url ) . 'items/' . $item_id . ( '' !== $endpoint ? '/' . $endpoint : '' );
	}

	/**
	 * Get transient cache key.
	 *
	 * @param string $seed Cache seed.
	 * @return string
	 */
	protected function get_cache_key( string $seed ): string {
		return self::TRANSIENT_PREFIX . md5( $seed );
	}

	/**
	 * Replace supported URL tokens.
	 *
	 * @param string $url URL.
	 * @return string
	 */
	protected function replace_url_tokens( string $url ): string {
		return strtr(
			$url,
			[
				'{elementor_version}' => defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : 'unknown',
				'{plugin_version}'    => defined( 'DIRECTORIST_ELEMENTOR_VERSION' ) ? (string) DIRECTORIST_ELEMENTOR_VERSION : 'unknown',
			]
		);
	}

	/**
	 * Normalize Elementor Kit Library taxonomy arrays into simple categories/tags.
	 *
	 * @param array<string,mixed> $item Item.
	 * @return array<string,mixed>
	 */
	protected function normalize_item_taxonomies( array $item ): array {
		if ( empty( $item['taxonomies'] ) || ! is_array( $item['taxonomies'] ) ) {
			return $item;
		}

		$categories = isset( $item['categories'] ) && is_array( $item['categories'] ) ? $item['categories'] : [];
		$tags       = isset( $item['tags'] ) && is_array( $item['tags'] ) ? $item['tags'] : [];

		foreach ( $item['taxonomies'] as $taxonomy ) {
			if ( ! is_array( $taxonomy ) ) {
				continue;
			}

			$name = sanitize_text_field( (string) ( $taxonomy['name'] ?? $taxonomy['label'] ?? '' ) );
			$type = sanitize_key( (string) ( $taxonomy['type'] ?? '' ) );

			if ( '' === $name ) {
				continue;
			}

			if ( 'categories' === $type || 'category' === $type ) {
				$categories[] = $name;
			} elseif ( 'tags' === $type || 'tag' === $type ) {
				$tags[] = $name;
			}
		}

		$item['categories'] = array_values( array_unique( array_filter( $categories ) ) );
		$item['tags']       = array_values( array_unique( array_filter( $tags ) ) );

		return $item;
	}
}
