<?php
/**
 * Package validation helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Core\Plugin;
use DirectoristElementor\Traits\Singleton;
use WP_Error;
use ZipArchive;

class PackageVerifier {
	use Singleton;

	private const SUPPORTED_SCHEMA_MAJOR = 1;

	/**
	 * Verify archive path safety before extraction.
	 *
	 * @param string $zip_file Zip file path.
	 * @return true|WP_Error
	 */
	public function verify_archive_paths( string $zip_file ) {
		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error( 'directorist_elementor_zip_missing', __( 'The PHP ZipArchive extension is required to import Directorist Elementor website packages.', 'directorist-elementor' ) );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $zip_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		for ( $i = 0; $i < $zip->numFiles; $i++ ) {
			$name = (string) $zip->getNameIndex( $i );

			if ( ! $this->is_safe_archive_path( $name ) ) {
				$zip->close();

				return new WP_Error(
					'directorist_elementor_zip_unsafe_path',
					sprintf(
						/* translators: %s: Archive path. */
						__( 'The package contains an unsafe path: %s', 'directorist-elementor' ),
						$name
					)
				);
			}

			$stats = $zip->statIndex( $i );

			if ( is_array( $stats ) && ! empty( $stats['name'] ) && $this->is_php_file( (string) $stats['name'] ) ) {
				$zip->close();

				return new WP_Error( 'directorist_elementor_zip_php_file', __( 'Elementor template packages cannot contain PHP files.', 'directorist-elementor' ) );
			}
		}

		$zip->close();

		return true;
	}

	/**
	 * Read and validate manifest from extracted package.
	 *
	 * @param string $package_dir Extracted package directory.
	 * @return array<string,mixed>|WP_Error
	 */
	public function read_manifest( string $package_dir ) {
		$manifest_file = trailingslashit( $package_dir ) . 'manifest.json';

		if ( ! file_exists( $manifest_file ) ) {
			return new WP_Error( 'directorist_elementor_manifest_missing', __( 'The package manifest is missing.', 'directorist-elementor' ) );
		}

		$manifest = json_decode( (string) file_get_contents( $manifest_file ), true );

		if ( ! is_array( $manifest ) ) {
			return new WP_Error( 'directorist_elementor_manifest_invalid', __( 'The package manifest is not valid JSON.', 'directorist-elementor' ) );
		}

		$validation = $this->validate_manifest( $manifest );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		return $manifest;
	}

	/**
	 * Validate manifest compatibility.
	 *
	 * @param array<string,mixed> $manifest Manifest.
	 * @return true|WP_Error
	 */
	public function validate_manifest( array $manifest ) {
		$schema_version = (string) ( $manifest['schema_version'] ?? '' );

		if ( '' === $schema_version ) {
			return new WP_Error( 'directorist_elementor_manifest_schema_missing', __( 'The package manifest does not declare a schema version.', 'directorist-elementor' ) );
		}

		$schema_major = (int) strtok( $schema_version, '.' );

		if ( self::SUPPORTED_SCHEMA_MAJOR !== $schema_major ) {
			return new WP_Error( 'directorist_elementor_manifest_schema_unsupported', __( 'The package schema version is not supported by this importer.', 'directorist-elementor' ) );
		}

		if ( 'published' !== (string) ( $manifest['release_status'] ?? '' ) && ! (bool) apply_filters( 'directorist_elementor/template_import/allow_unpublished_packages', false, $manifest ) ) {
			return new WP_Error( 'directorist_elementor_manifest_not_published', __( 'Only published Directorist Elementor website packages can be imported.', 'directorist-elementor' ) );
		}

		if ( 'elementor' !== sanitize_key( (string) ( $manifest['builder'] ?? '' ) ) ) {
			return new WP_Error( 'directorist_elementor_manifest_wrong_builder', __( 'This package is not an Elementor package.', 'directorist-elementor' ) );
		}

		if ( empty( $manifest['package_id'] ) ) {
			return new WP_Error( 'directorist_elementor_manifest_package_missing', __( 'The package manifest does not include a package id.', 'directorist-elementor' ) );
		}

		$requirements = isset( $manifest['requirements'] ) && is_array( $manifest['requirements'] ) ? $manifest['requirements'] : [];

		$plugin_requirement = (string) ( $requirements['min_plugin_version'] ?? '' );

		$current_plugin_version = $this->get_plugin_version();

		if ( '' !== $plugin_requirement && version_compare( $current_plugin_version, $plugin_requirement, '<' ) ) {
			return new WP_Error(
				'directorist_elementor_manifest_plugin_version',
				sprintf(
					/* translators: 1: Required version. 2: Current version. */
					__( 'This package requires Directorist Elementor %1$s or higher. Current version: %2$s.', 'directorist-elementor' ),
					$plugin_requirement,
					$current_plugin_version
				)
			);
		}

		$wp_requirement = (string) ( $requirements['min_wp_version'] ?? '' );

		if ( '' !== $wp_requirement && version_compare( get_bloginfo( 'version' ), $wp_requirement, '<' ) ) {
			return new WP_Error(
				'directorist_elementor_manifest_wp_version',
				sprintf(
					/* translators: 1: Required version. 2: Current version. */
					__( 'This package requires WordPress %1$s or higher. Current version: %2$s.', 'directorist-elementor' ),
					$wp_requirement,
					get_bloginfo( 'version' )
				)
			);
		}

		$directorist_requirement = (string) ( $requirements['min_directorist_version'] ?? '' );

		if ( '' !== $directorist_requirement && defined( 'ATBDP_VERSION' ) && version_compare( (string) ATBDP_VERSION, $directorist_requirement, '<' ) ) {
			return new WP_Error(
				'directorist_elementor_manifest_directorist_version',
				sprintf(
					/* translators: 1: Required version. 2: Current version. */
					__( 'This package requires Directorist %1$s or higher. Current version: %2$s.', 'directorist-elementor' ),
					$directorist_requirement,
					(string) ATBDP_VERSION
				)
			);
		}

		return $this->verify_elementor_compatibility( $manifest );
	}

	/**
	 * Reject newer builder data before native import creates any content.
	 *
	 * @param array<string,mixed> $manifest Package manifest.
	 * @return true|WP_Error
	 */
	public function verify_elementor_compatibility( array $manifest ) {
		$required = (string) ( $manifest['requirements']['min_elementor_version'] ?? '' );
		$exported = (string) ( $manifest['elementor_version'] ?? '' );
		if ( '' !== $exported && ( '' === $required || version_compare( $exported, $required, '>' ) ) ) {
			$required = $exported;
		}
		$current = defined( 'ELEMENTOR_VERSION' ) ? (string) ELEMENTOR_VERSION : '0';
		if ( '' !== $required && version_compare( $current, $required, '<' ) ) {
			return new WP_Error(
				'directorist_elementor_builder_version',
				sprintf(
					/* translators: 1: Required Elementor version, 2: installed version. */
					__( 'This package requires Elementor %1$s or higher. Current version: %2$s. Update Elementor before importing; no content has been imported.', 'directorist-elementor' ),
					$required,
					$current
				),
				[ 'status' => 400 ]
			);
		}
		return true;
	}

	/**
	 * Verify package file checksums declared in the manifest.
	 *
	 * @param string              $package_dir Extracted package directory.
	 * @param array<string,mixed> $manifest Manifest.
	 * @return true|WP_Error
	 */
	public function verify_manifest_file_checksums( string $package_dir, array $manifest ) {
		$checksums = $manifest['checksums']['files'] ?? [];

		if ( empty( $checksums ) || ! is_array( $checksums ) ) {
			return true;
		}

		foreach ( $checksums as $relative_path => $expected_hash ) {
			$relative_path = (string) $relative_path;

			if ( ! $this->is_safe_archive_path( $relative_path ) ) {
				return new WP_Error( 'directorist_elementor_manifest_unsafe_file', __( 'The package manifest contains an unsafe file path.', 'directorist-elementor' ) );
			}

			$file = trailingslashit( $package_dir ) . $relative_path;

			if ( ! file_exists( $file ) ) {
				return new WP_Error(
					'directorist_elementor_manifest_file_missing',
					sprintf(
						/* translators: %s: File path. */
						__( 'The package file declared in the manifest is missing: %s', 'directorist-elementor' ),
						$relative_path
					)
				);
			}

			if ( hash_file( 'sha256', $file ) !== strtolower( (string) $expected_hash ) ) {
				return new WP_Error(
					'directorist_elementor_manifest_checksum_mismatch',
					sprintf(
						/* translators: %s: File path. */
						__( 'The package file checksum does not match: %s', 'directorist-elementor' ),
						$relative_path
					)
				);
			}
		}

		return true;
	}

	/**
	 * Check whether archive path is safe.
	 *
	 * @param string $path Archive path.
	 * @return bool
	 */
	protected function is_safe_archive_path( string $path ): bool {
		$path = trim( $path );

		if ( '' === $path || str_starts_with( $path, '/' ) || str_contains( $path, '\\' ) || str_contains( $path, "\0" ) ) {
			return false;
		}

		$parts = explode( '/', $path );

		foreach ( $parts as $part ) {
			if ( '..' === $part ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if a path is a PHP file.
	 *
	 * @param string $path File path.
	 * @return bool
	 */
	protected function is_php_file( string $path ): bool {
		return (bool) preg_match( '/\.php[0-9s]?$/i', $path );
	}

	/**
	 * Resolve the public plugin version from the plugin header.
	 *
	 * @return string
	 */
	protected function get_plugin_version(): string {
		if ( ! function_exists( 'get_file_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_file_data(
			Plugin::$plugin_path . 'directorist-elementor.php',
			[
				'Version' => 'Version',
			],
			'plugin'
		);

		return ! empty( $data['Version'] ) ? (string) $data['Version'] : Plugin::$version;
	}
}
