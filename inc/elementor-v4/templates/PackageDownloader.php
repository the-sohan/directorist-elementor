<?php
/**
 * Package downloader.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;
use WP_Error;

class PackageDownloader {
	use Singleton;

	/**
	 * Download a package for a catalog item.
	 *
	 * @param array<string,mixed> $item Catalog item.
	 * @return string|WP_Error
	 */
	public function download( array $item ) {
		$package = isset( $item['package'] ) && is_array( $item['package'] ) ? $item['package'] : [];

		$download_link_url = esc_url_raw( (string) ( $item['download_link_url'] ?? $package['download_link_url'] ?? '' ) );

		if ( '' !== $download_link_url ) {
			$link = $this->fetch_download_link( $download_link_url );

			if ( is_wp_error( $link ) ) {
				return $link;
			}

			$package = array_merge(
				$package,
				[
					'download_url'    => (string) ( $link['download_link'] ?? $link['download_url'] ?? '' ),
					'checksum_sha256' => (string) ( $link['checksum_sha256'] ?? '' ),
					'size'            => absint( $link['package_size'] ?? $link['size'] ?? 0 ),
				]
			);
		}

		$url     = esc_url_raw( (string) ( $package['download_url'] ?? $item['package_url'] ?? $item['download_url'] ?? '' ) );

		if ( '' === $url ) {
			return new WP_Error( 'directorist_elementor_package_url_missing', __( 'The selected item does not include a package download URL.', 'directorist-elementor' ) );
		}

		$valid_url = CatalogClient::get_instance()->validate_remote_url( $url );

		if ( is_wp_error( $valid_url ) ) {
			return $valid_url;
		}

		$expected_hash = strtolower( (string) ( $package['checksum_sha256'] ?? $item['checksum_sha256'] ?? $item['package_checksum_sha256'] ?? '' ) );

		if ( '' === $expected_hash && (bool) apply_filters( 'directorist_elementor/template_import/require_package_checksum', true, $item ) ) {
			return new WP_Error( 'directorist_elementor_package_checksum_missing', __( 'The package checksum is missing, so the package cannot be verified.', 'directorist-elementor' ) );
		}

		if ( '' !== $expected_hash ) {
			$url = add_query_arg( 'checksum', $expected_hash, $url );
		}

		$expected_size = absint( $package['size'] ?? $item['package_size'] ?? 0 );
		$max_size      = (int) apply_filters( 'directorist_elementor/template_import/max_package_size', 100 * MB_IN_BYTES, $item );

		if ( $expected_size > 0 && $expected_size > $max_size ) {
			return new WP_Error( 'directorist_elementor_package_too_large', __( 'The package is larger than the allowed import size.', 'directorist-elementor' ) );
		}

		$tmp_file = $this->download_to_temp_file( $url, $max_size, $expected_size );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		clearstatcache( true, $tmp_file );
		$actual_size = filesize( $tmp_file );

		if ( false !== $actual_size && $actual_size > $max_size ) {
			@unlink( $tmp_file );

			return new WP_Error( 'directorist_elementor_package_too_large_after_download', __( 'The downloaded package is larger than the allowed import size.', 'directorist-elementor' ) );
		}
		if ( $expected_size > 0 && (int) $actual_size !== $expected_size ) {
			@unlink( $tmp_file );
			return new WP_Error( 'directorist_elementor_package_incomplete', __( 'The Elementor package download did not complete.', 'directorist-elementor' ) );
		}

		if ( '' !== $expected_hash && hash_file( 'sha256', $tmp_file ) !== $expected_hash ) {
			@unlink( $tmp_file );

			return new WP_Error( 'directorist_elementor_package_checksum_mismatch', __( 'The downloaded package checksum does not match the catalog checksum.', 'directorist-elementor' ) );
		}

		return $tmp_file;
	}

	/**
	 * Download a trusted package URL into a temporary file.
	 *
	 * The core download_url() helper uses wp_safe_remote_get(), which rejects
	 * local development hosts such as templates.test before our importer can
	 * validate the plugin-controlled trusted catalog source. The URL is already
	 * validated by CatalogClient::validate_remote_url(), so use a streamed
	 * wp_remote_get().
	 *
	 * @param string $url Package URL.
	 * @param int    $max_size Maximum allowed package size in bytes.
	 * @param int    $expected_size Published package size in bytes.
	 * @return string|WP_Error
	 */
	protected function download_to_temp_file( string $url, int $max_size, int $expected_size = 0 ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$work_dir = trailingslashit( WP_CONTENT_DIR ) . 'upgrade/';
		if ( ! wp_mkdir_p( $work_dir ) || ! is_writable( $work_dir ) ) {
			return new WP_Error( 'directorist_elementor_package_temp_failed', __( 'The wp-content/upgrade directory is not writable for the Elementor package download.', 'directorist-elementor' ) );
		}
		$free_bytes = @disk_free_space( $work_dir );
		if ( $expected_size > 0 && false !== $free_bytes && $free_bytes < $expected_size + 10 * MB_IN_BYTES ) {
			return new WP_Error( 'directorist_elementor_package_space_insufficient', __( 'There is not enough free space in wp-content/upgrade to download the Elementor package.', 'directorist-elementor' ) );
		}

		$tmp_file = wp_tempnam( $url, $work_dir );

		if ( ! $tmp_file || ! is_file( $tmp_file ) ) {
			return new WP_Error( 'directorist_elementor_package_temp_failed', __( 'Could not create a temporary file for the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$response = wp_remote_get(
			$url,
			[
				'timeout'             => 60,
				'stream'              => true,
				'filename'            => $tmp_file,
				'limit_response_size' => $max_size + 1,
			]
		);

		if ( is_wp_error( $response ) ) {
			@unlink( $tmp_file );

			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			@unlink( $tmp_file );

			return new WP_Error(
				'directorist_elementor_package_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'The Directorist Elementor website package returned HTTP %d.', 'directorist-elementor' ),
					$status_code
				)
			);
		}

		return $tmp_file;
	}

	/**
	 * Resolve an Elementor Kit Library style download-link endpoint.
	 *
	 * @param string $url Download-link endpoint URL.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function fetch_download_link( string $url ) {
		$valid_url = CatalogClient::get_instance()->validate_remote_url( $url );

		if ( is_wp_error( $valid_url ) ) {
			return $valid_url;
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

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code < 200 || $status_code >= 300 ) {
			return new WP_Error(
				'directorist_elementor_download_link_http_error',
				sprintf(
					/* translators: %d: HTTP status code. */
					__( 'The Directorist template download link returned HTTP %d.', 'directorist-elementor' ),
					$status_code
				)
			);
		}

		$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'directorist_elementor_download_link_invalid_json', __( 'The Directorist template download link response is not valid JSON.', 'directorist-elementor' ) );
		}

		return $data;
	}
}
