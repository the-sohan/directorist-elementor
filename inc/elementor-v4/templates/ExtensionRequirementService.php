<?php
/**
 * Directorist template extension requirement helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;
use WP_Error;

class ExtensionRequirementService {
	use Singleton;

	/**
	 * Cached installed plugin headers.
	 *
	 * @var array<string,array<string,string>>|null
	 */
	protected ?array $installed_plugins = null;

	/**
	 * Add local install/active status to a catalog payload.
	 *
	 * @param array<string,mixed> $catalog Catalog payload.
	 * @return array<string,mixed>
	 */
	public function annotate_catalog( array $catalog ): array {
		if ( isset( $catalog['items'] ) && is_array( $catalog['items'] ) ) {
			$catalog['items'] = array_values(
				array_map(
					function ( $item ): array {
						return $this->annotate_item( is_array( $item ) ? $item : [] );
					},
					$catalog['items']
				)
			);
		}

		return $catalog;
	}

	/**
	 * Add local install/active status to a catalog item.
	 *
	 * @param array<string,mixed> $item Catalog item.
	 * @return array<string,mixed>
	 */
	public function annotate_item( array $item ): array {
		$requirements = $this->normalize_requirements_from_item( $item );
		$items        = array_map( [ $this, 'resolve_requirement_status' ], $requirements );
		$missing      = array_values(
			array_filter(
				$items,
				static function ( array $requirement ): bool {
					return 'missing' === $requirement['status'];
				}
			)
		);
		$inactive     = array_values(
			array_filter(
				$items,
				static function ( array $requirement ): bool {
					return 'inactive' === $requirement['status'];
				}
			)
		);

		$item['required_extensions']   = $requirements;
		$item['extension_requirements'] = [
			'items'     => $items,
			'missing'   => $missing,
			'inactive'  => $inactive,
			'satisfied' => empty( $missing ) && empty( $inactive ),
		];

		return $item;
	}

	/**
	 * Verify a catalog item can be imported.
	 *
	 * Extension requirements are advisory. Imports may proceed when an extension
	 * is missing or inactive, but the annotated catalog payload still exposes the
	 * requirement status so the UI can warn users before import.
	 *
	 * @param array<string,mixed> $item Catalog item.
	 * @return true
	 */
	public function verify_item( array $item ) {
		$item = $this->annotate_item( $item );

		return $this->verify_annotated_requirements( $item['extension_requirements'] );
	}

	/**
	 * Verify a downloaded manifest can be imported.
	 *
	 * @param array<string,mixed> $manifest Manifest.
	 * @return true
	 */
	public function verify_manifest( array $manifest ) {
		$item = [
			'required_extensions' => isset( $manifest['directorist']['required_extensions'] ) && is_array( $manifest['directorist']['required_extensions'] )
				? $manifest['directorist']['required_extensions']
				: [],
		];

		return $this->verify_item( $item );
	}

	/**
	 * Normalize requirements from a catalog item.
	 *
	 * @param array<string,mixed> $item Catalog item.
	 * @return array<int,array<string,string>>
	 */
	protected function normalize_requirements_from_item( array $item ): array {
		$requirements = [];
		$raw          = [];

		if ( isset( $item['required_extensions'] ) && is_array( $item['required_extensions'] ) ) {
			$raw = $item['required_extensions'];
		} elseif ( isset( $item['directorist']['required_extensions'] ) && is_array( $item['directorist']['required_extensions'] ) ) {
			$raw = $item['directorist']['required_extensions'];
		}

		foreach ( $raw as $requirement ) {
			$normalized = $this->normalize_requirement( $requirement );

			if ( empty( $normalized['slug'] ) ) {
				continue;
			}

			$requirements[ $normalized['slug'] ] = $normalized;
		}

		return array_values( $requirements );
	}

	/**
	 * Normalize one requirement.
	 *
	 * @param mixed $requirement Requirement data.
	 * @return array<string,string>
	 */
	protected function normalize_requirement( $requirement ): array {
		if ( is_array( $requirement ) ) {
			$slug        = sanitize_key( (string) ( $requirement['slug'] ?? $requirement['id'] ?? $requirement['plugin'] ?? $requirement['name'] ?? '' ) );
			$name        = sanitize_text_field( (string) ( $requirement['name'] ?? $requirement['label'] ?? $slug ) );
			$plugin      = sanitize_text_field( (string) ( $requirement['plugin'] ?? '' ) );
			$type        = sanitize_key( (string) ( $requirement['type'] ?? 'plugin' ) );
			$install_url = esc_url_raw( (string) ( $requirement['install_url'] ?? $requirement['url'] ?? '' ) );
			$sources     = array_values( array_unique( array_filter( array_map( 'sanitize_key', (array) ( $requirement['sources'] ?? [] ) ) ) ) );
			$evidence    = array_values( array_unique( array_filter( array_map( 'sanitize_text_field', (array) ( $requirement['evidence'] ?? [] ) ) ) ) );
		} else {
			$slug        = sanitize_key( (string) $requirement );
			$name        = sanitize_text_field( ucwords( str_replace( '-', ' ', $slug ) ) );
			$plugin      = '';
			$type        = 'plugin';
			$install_url = '';
			$sources     = [];
			$evidence    = [];
		}

		if ( '' === $slug ) {
			return [];
		}

		return [
			'slug'        => $slug,
			'name'        => '' !== $name ? $name : $slug,
			'plugin'      => $plugin,
			'type'        => '' !== $type ? $type : 'plugin',
			'install_url' => $install_url,
			'sources'     => $sources,
			'evidence'    => $evidence,
		];
	}

	/**
	 * Resolve one requirement against installed plugins.
	 *
	 * @param array<string,string> $requirement Requirement.
	 * @return array<string,mixed>
	 */
	protected function resolve_requirement_status( array $requirement ): array {
		$plugin_file = $this->resolve_plugin_file( $requirement );
		$status      = 'missing';

		if ( '' !== $plugin_file ) {
			$status = $this->is_plugin_active( $plugin_file ) ? 'active' : 'inactive';
		}

		$requirement['plugin']       = $plugin_file;
		$requirement['status']       = $status;
		$requirement['action_url']   = $this->get_requirement_action_url( $requirement, $status );
		$requirement['can_activate'] = 'inactive' === $status && current_user_can( 'activate_plugins' );

		return $requirement;
	}

	/**
	 * Activate an installed requirement without leaving the import screen.
	 *
	 * @param string $requested_plugin Plugin basename supplied by the annotated requirement.
	 * @param string $slug Requirement slug.
	 * @return array<string,mixed>|WP_Error
	 */
	public function activate_requirement( string $requested_plugin, string $slug ) {
		$requested_plugin = sanitize_text_field( wp_unslash( $requested_plugin ) );
		$slug             = sanitize_key( $slug );
		$plugin_file      = $this->resolve_plugin_file(
			[
				'plugin' => $requested_plugin,
				'slug'   => $slug,
			]
		);
		$plugins          = $this->get_installed_plugins();

		if (
			'' === $plugin_file ||
			0 !== validate_file( $plugin_file ) ||
			! isset( $plugins[ $plugin_file ] )
		) {
			return new WP_Error(
				'directorist_elementor_requirement_plugin_unavailable',
				__( 'The required plugin is not installed or could not be identified.', 'directorist-elementor' ),
				[ 'status' => 404 ]
			);
		}

		if ( ! $this->is_plugin_active( $plugin_file ) ) {
			if ( ! function_exists( 'activate_plugin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			$activated = activate_plugin( $plugin_file, '', false, false );

			if ( is_wp_error( $activated ) ) {
				return new WP_Error(
					'directorist_elementor_requirement_activation_failed',
					$activated->get_error_message(),
					[ 'status' => 500 ]
				);
			}
		}

		return [
			'plugin'       => $plugin_file,
			'slug'         => $slug,
			'status'       => 'active',
			'action_url'   => '',
			'can_activate' => false,
		];
	}

	/**
	 * Resolve a plugin basename from a slug or optional plugin file.
	 *
	 * @param array<string,string> $requirement Requirement.
	 * @return string
	 */
	protected function resolve_plugin_file( array $requirement ): string {
		$plugins = $this->get_installed_plugins();
		$plugin  = (string) ( $requirement['plugin'] ?? '' );
		$slug    = sanitize_key( (string) ( $requirement['slug'] ?? '' ) );
		if ( 'elementor-pro' === $slug ) {
			$candidates = array_values( array_unique( array_filter( [ $plugin, 'elementor-pro/elementor-pro.php', 'pro-elements/pro-elements.php' ] ) ) );
			foreach ( $candidates as $candidate ) {
				if ( isset( $plugins[ $candidate ] ) && $this->is_plugin_active( $candidate ) ) {
					return $candidate;
				}
			}
			foreach ( $candidates as $candidate ) {
				if ( isset( $plugins[ $candidate ] ) ) {
					return $candidate;
				}
			}
		}

		if ( '' !== $plugin && isset( $plugins[ $plugin ] ) ) {
			return $plugin;
		}

		foreach ( $plugins as $file => $data ) {
			$directory = '.' !== dirname( $file ) ? sanitize_key( dirname( $file ) ) : sanitize_key( basename( $file, '.php' ) );
			$file_slug = sanitize_key( basename( $file, '.php' ) );
			$name_slug = sanitize_title( (string) ( $data['Name'] ?? '' ) );

			if ( $slug === $directory || $slug === $file_slug || $slug === $name_slug ) {
				return $file;
			}
		}

		return '';
	}

	/**
	 * Get installed plugin headers.
	 *
	 * @return array<string,array<string,string>>
	 */
	protected function get_installed_plugins(): array {
		if ( null !== $this->installed_plugins ) {
			return $this->installed_plugins;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$this->installed_plugins = get_plugins();

		return $this->installed_plugins;
	}

	/**
	 * Check active or network-active plugin state.
	 *
	 * @param string $plugin_file Plugin basename.
	 * @return bool
	 */
	protected function is_plugin_active( string $plugin_file ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $plugin_file ) || ( is_multisite() && is_plugin_active_for_network( $plugin_file ) );
	}

	/**
	 * Get an action URL for missing or inactive requirements.
	 *
	 * @param array<string,string> $requirement Requirement.
	 * @param string               $status Status.
	 * @return string
	 */
	protected function get_requirement_action_url( array $requirement, string $status ): string {
		if ( in_array( $status, [ 'active', 'inactive' ], true ) ) {
			return '';
		}

		if ( ! empty( $requirement['install_url'] ) ) {
			return esc_url_raw( (string) $requirement['install_url'] );
		}

		$query = ! empty( $requirement['name'] ) ? (string) $requirement['name'] : (string) $requirement['slug'];

		return self_admin_url( 'plugin-install.php?tab=search&type=term&s=' . rawurlencode( $query ) );
	}

	/**
	 * Keep extension requirements advisory during import.
	 *
	 * @param array<string,mixed> $extension_requirements Annotated requirements.
	 * @return true
	 */
	protected function verify_annotated_requirements( array $extension_requirements ) {
		return true;
	}
}
