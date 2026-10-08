<?php
/**
 * Elementor package adapter.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;
use WP_Error;

class ElementorTemplateAdapter implements BuilderAdapterInterface {
	use Singleton;

	private const SOURCE_UID_META         = '_directorist_import_source_uid';
	private const PACKAGE_ID_META         = '_directorist_import_package_id';
	private const PACKAGE_VERSION_META    = '_directorist_import_package_version';
	private const LAST_IMPORTED_META      = '_directorist_import_last_imported_at';
	private const ELEMENTOR_DATA_META     = '_elementor_data';
	private const ELEMENTOR_CACHE_META    = '_elementor_element_cache';
	private const ELEMENTOR_TEMPLATE_META = '_elementor_template_type';

	/**
	 * Get builder key.
	 *
	 * @return string
	 */
	public function get_builder_key(): string {
		return 'elementor';
	}

	/**
	 * Check whether the package can be imported.
	 *
	 * @param array<string,mixed> $manifest Package manifest.
	 * @return bool
	 */
	public function can_import_package( array $manifest ): bool {
		return 'elementor' === sanitize_key( (string) ( $manifest['builder'] ?? '' ) );
	}

	/**
	 * Run adapter-level preflight checks.
	 *
	 * @param array<string,mixed> $manifest Package manifest.
	 * @return array<string,mixed>
	 */
	public function preflight( array $manifest ): array {
		$errors = [];

		if ( ! did_action( 'elementor/loaded' ) && ! did_action( 'elementor/init' ) ) {
			$errors[] = __( 'Elementor is not loaded.', 'directorist-elementor' );
		}

		$requirements = isset( $manifest['requirements'] ) && is_array( $manifest['requirements'] ) ? $manifest['requirements'] : [];
		$elementor_min = (string) ( $requirements['min_elementor_version'] ?? '' );

		if ( '' !== $elementor_min && defined( 'ELEMENTOR_VERSION' ) && version_compare( (string) ELEMENTOR_VERSION, $elementor_min, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: Required Elementor version. 2: Current Elementor version. */
				__( 'This package requires Elementor %1$s or higher. Current version: %2$s.', 'directorist-elementor' ),
				$elementor_min,
				(string) ELEMENTOR_VERSION
			);
		}

		return [
			'passed' => empty( $errors ),
			'errors' => $errors,
		];
	}

	/**
	 * Import Elementor items from package.
	 *
	 * @param ImportSession       $session Import session.
	 * @param string              $package_dir Extracted package directory.
	 * @param array<string,mixed> $manifest Package manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>
	 */
	public function import_builder_items( ImportSession $session, string $package_dir, array $manifest, array $args = [] ): array {
		$this->import_media( $session, $package_dir );

		$elementor_file = trailingslashit( $package_dir ) . 'builder/elementor.ndjson';
		$records        = $this->read_ndjson_file( $elementor_file );

		if ( is_wp_error( $records ) ) {
			$session->log( 'warning', $records->get_error_message() );

			return [
				'imported' => 0,
				'skipped'  => 0,
				'errors'   => [ $records->get_error_message() ],
			];
		}

		$imported = 0;
		$skipped  = 0;
		$errors   = [];

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$result = $this->import_elementor_record( $session, $record, $manifest, $args );

			if ( is_wp_error( $result ) ) {
				$errors[] = $result->get_error_message();
				$session->log( 'error', $result->get_error_message(), [ 'record' => $record['uid'] ?? '' ] );
				continue;
			}

			if ( ! empty( $result['skipped'] ) ) {
				$skipped++;
				continue;
			}

			$imported++;
		}

		$this->remap_imported_record_urls( $session );
		$this->clear_caches( $session );

		return [
			'imported' => $imported,
			'skipped'  => $skipped,
			'errors'   => $errors,
		];
	}

	/**
	 * Clear Elementor caches for imported posts.
	 *
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	public function clear_caches( ImportSession $session ): void {
		$post_ids = [];

		foreach ( [ $session->get_created(), $session->get_updated() ] as $group ) {
			foreach ( [ 'elementor_library', 'page', 'post' ] as $type ) {
				if ( empty( $group[ $type ] ) || ! is_array( $group[ $type ] ) ) {
					continue;
				}

				$post_ids = array_merge( $post_ids, array_map( 'absint', $group[ $type ] ) );
			}
		}

		$post_ids = array_values( array_unique( array_filter( $post_ids ) ) );

		foreach ( $post_ids as $post_id ) {
			delete_post_meta( $post_id, self::ELEMENTOR_CACHE_META );

			if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
				$css_file = new \Elementor\Core\Files\CSS\Post( $post_id );

				if ( method_exists( $css_file, 'delete' ) ) {
					$css_file->delete();
				}
			}
		}

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && is_object( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
			\Elementor\Plugin::$instance->files_manager->clear_cache();
		}
	}

	/**
	 * Import media declared in media/manifest.ndjson.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $package_dir Extracted package directory.
	 * @return void
	 */
	protected function import_media( ImportSession $session, string $package_dir ): void {
		$media_file = trailingslashit( $package_dir ) . 'media/manifest.ndjson';

		if ( ! file_exists( $media_file ) ) {
			return;
		}

		$records = $this->read_ndjson_file( $media_file );

		if ( is_wp_error( $records ) ) {
			$session->log( 'warning', $records->get_error_message() );
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( $records as $record ) {
			if ( ! is_array( $record ) ) {
				continue;
			}

			$uid       = sanitize_text_field( (string) ( $record['uid'] ?? '' ) );
			$file_path = sanitize_text_field( (string) ( $record['file'] ?? '' ) );

			if ( '' === $uid || '' === $file_path || str_contains( $file_path, '..' ) ) {
				continue;
			}

			$source_file = trailingslashit( $package_dir ) . 'media/files/' . ltrim( $file_path, '/' );

			if ( ! file_exists( $source_file ) ) {
				$source_file = trailingslashit( $package_dir ) . ltrim( $file_path, '/' );
			}

			if ( ! file_exists( $source_file ) || ! is_readable( $source_file ) ) {
				$session->log( 'warning', __( 'A media file declared in the package could not be read.', 'directorist-elementor' ), [ 'file' => $file_path ] );
				continue;
			}

			$existing_id = $this->find_existing_by_source_uid( $uid, 'attachment' );

			if ( $existing_id > 0 ) {
				$attachment_id = $existing_id;
				$session->record_updated( 'attachment', $attachment_id );
			} else {
				$attachment_id = $this->create_attachment_from_file( $source_file, $record );

				if ( $attachment_id <= 0 ) {
					continue;
				}

				$session->record_created( 'attachment', $attachment_id );
			}

			$this->store_source_meta( $attachment_id, $uid, $session );

			$source_id = isset( $record['source_id'] ) ? (string) absint( $record['source_id'] ) : '';

			if ( '' !== $source_id && '0' !== $source_id ) {
				$session->map_id( 'media_source_id', $source_id, $attachment_id );
			}

			$session->map_id( 'media_uid', $uid, $attachment_id );

			$local_url  = wp_get_attachment_url( $attachment_id );
			$source_url = esc_url_raw( (string) ( $record['source_url'] ?? $record['url'] ?? '' ) );

			if ( $local_url && $source_url ) {
				$session->map_url( $source_url, $local_url );
			}
		}
	}

	/**
	 * Create an attachment from a package file.
	 *
	 * @param string              $source_file Source file.
	 * @param array<string,mixed> $record Media record.
	 * @return int
	 */
	protected function create_attachment_from_file( string $source_file, array $record ): int {
		$filename = basename( $source_file );
		$bits     = file_get_contents( $source_file );

		if ( false === $bits ) {
			return 0;
		}

		$upload = wp_upload_bits( $filename, null, $bits );

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => sanitize_mime_type( (string) ( $record['mime_type'] ?? $upload['type'] ?? 'application/octet-stream' ) ),
				'post_title'     => sanitize_text_field( (string) ( $record['title'] ?? pathinfo( $filename, PATHINFO_FILENAME ) ) ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || $attachment_id <= 0 ) {
			return 0;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );

		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}

		if ( ! empty( $record['alt'] ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $record['alt'] ) );
		}

		return $attachment_id;
	}

	/**
	 * Import one Elementor record.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $record Elementor record.
	 * @param array<string,mixed> $manifest Package manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function import_elementor_record( ImportSession $session, array $record, array $manifest, array $args ) {
		$uid = sanitize_text_field( (string) ( $record['uid'] ?? $record['source_uid'] ?? '' ) );

		if ( '' === $uid ) {
			return new WP_Error( 'directorist_elementor_record_uid_missing', __( 'An Elementor package record is missing its source UID.', 'directorist-elementor' ) );
		}

		$post_type = sanitize_key( (string) ( $record['post_type'] ?? 'elementor_library' ) );

		if ( ! in_array( $post_type, [ 'elementor_library', 'page', 'post' ], true ) ) {
			return new WP_Error( 'directorist_elementor_record_post_type_unsupported', __( 'The Elementor package contains an unsupported post type.', 'directorist-elementor' ) );
		}

		$conflict_behavior = sanitize_key( (string) ( $args['conflict_behavior'] ?? 'update' ) );
		$existing_id       = $this->find_existing_by_source_uid( $uid, $post_type );

		if ( $existing_id > 0 && 'skip' === $conflict_behavior ) {
			$session->log( 'info', __( 'Skipped existing imported item.', 'directorist-elementor' ), [ 'post_id' => $existing_id, 'uid' => $uid ] );

			return [
				'post_id'  => $existing_id,
				'skipped'  => true,
				'updated'  => false,
				'created'  => false,
			];
		}

		$post_id = ( $existing_id > 0 && in_array( $conflict_behavior, [ 'update', 'replace' ], true ) ) ? $existing_id : 0;

		$post_status = ! empty( $args['activate_templates'] ) ? sanitize_key( (string) ( $record['post_status'] ?? 'publish' ) ) : 'draft';

		if ( 'page' === $post_type || 'post' === $post_type ) {
			$post_status = sanitize_key( (string) ( $record['post_status'] ?? 'draft' ) );
		}

		$post_name = sanitize_title( (string) ( $record['post_name'] ?? $record['slug'] ?? '' ) );
		$post_name = $this->get_unique_import_post_slug( $post_name, $post_type, $post_id );
		$post_data = [
			'ID'           => $post_id,
			'post_type'    => $post_type,
			'post_title'   => sanitize_text_field( (string) ( $record['post_title'] ?? $record['title'] ?? __( 'Directorist Imported Template', 'directorist-elementor' ) ) ),
			'post_name'    => $post_name,
			'post_status'  => $post_status,
			'post_content' => $this->replace_dynamic_values( (string) ( $record['post_content'] ?? '' ), $session ),
			'post_excerpt' => sanitize_textarea_field( (string) ( $record['post_excerpt'] ?? '' ) ),
		];

		if ( $post_id > 0 ) {
			$post_id = wp_update_post( wp_slash( $post_data ), true );
		} else {
			unset( $post_data['ID'] );
			$post_id = wp_insert_post( wp_slash( $post_data ), true );
		}

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		$post_id = absint( $post_id );

		if ( $existing_id > 0 && $post_id === $existing_id ) {
			$session->record_updated( $post_type, $post_id );
		} else {
			$session->record_created( $post_type, $post_id );
		}

		$session->map_id( 'post_uid', $uid, $post_id );
		$this->store_source_meta( $post_id, $uid, $session );
		$this->store_elementor_meta( $post_id, $record, $session );

		$source_url = esc_url_raw( (string) ( $record['source_url'] ?? $record['url'] ?? '' ) );
		$target_url = get_permalink( $post_id );

		if ( '' !== $source_url && is_string( $target_url ) && '' !== $target_url ) {
			$session->map_url( $source_url, $target_url );
			$session->map_url( untrailingslashit( $source_url ), untrailingslashit( $target_url ) );

			$manifest_site  = $manifest['source_site_url'] ?? $manifest['site'] ?? '';
			$source_site_url = is_array( $manifest_site )
				? esc_url_raw( (string) ( $manifest_site['url'] ?? '' ) )
				: esc_url_raw( (string) $manifest_site );
			$source_site_url = untrailingslashit( $source_site_url );
			$target_site_url = untrailingslashit( home_url( '/' ) );

			if ( '' !== $source_site_url && str_starts_with( $source_url, $source_site_url ) ) {
				$provisional_url = $target_site_url . substr( $source_url, strlen( $source_site_url ) );

				$session->map_url( $provisional_url, $target_url );
				$session->map_url( untrailingslashit( $provisional_url ), untrailingslashit( $target_url ) );
			}
		}

		$session->log( 'info', __( 'Imported Elementor item.', 'directorist-elementor' ), [ 'post_id' => $post_id, 'uid' => $uid ] );

		return [
			'post_id' => $post_id,
			'skipped' => false,
			'updated' => $existing_id > 0 && $post_id === $existing_id,
			'created' => ! ( $existing_id > 0 && $post_id === $existing_id ),
		];
	}

	/**
	 * Reserve a unique slug even when the imported post remains a draft.
	 *
	 * WordPress does not enforce unique slugs for every non-published status, so
	 * relying on wp_insert_post() alone can leave imported pages ambiguous.
	 *
	 * @param string $post_name Requested post slug.
	 * @param string $post_type Post type.
	 * @param int    $post_id Existing post ID during an update.
	 * @return string
	 */
	protected function get_unique_import_post_slug( string $post_name, string $post_type, int $post_id = 0 ): string {
		if ( '' === $post_name ) {
			return '';
		}

		return wp_unique_post_slug( $post_name, $post_id, 'publish', $post_type, 0 );
	}

	/**
	 * Remap record-to-record links after every imported URL is known.
	 *
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	protected function remap_imported_record_urls( ImportSession $session ): void {
		if ( empty( $session->get_url_map() ) ) {
			return;
		}

		$post_ids = [];

		foreach ( [ $session->get_created(), $session->get_updated() ] as $group ) {
			foreach ( [ 'elementor_library', 'page', 'post' ] as $post_type ) {
				if ( empty( $group[ $post_type ] ) || ! is_array( $group[ $post_type ] ) ) {
					continue;
				}

				$post_ids = array_merge( $post_ids, array_map( 'absint', $group[ $post_type ] ) );
			}
		}

		foreach ( array_unique( array_filter( $post_ids ) ) as $post_id ) {
			$post = get_post( $post_id );

			if ( ! $post ) {
				continue;
			}

			$post_content = $this->replace_dynamic_values( (string) $post->post_content, $session );
			$post_excerpt = $this->replace_dynamic_values( (string) $post->post_excerpt, $session );

			if ( $post_content !== $post->post_content || $post_excerpt !== $post->post_excerpt ) {
				wp_update_post(
					wp_slash(
						[
							'ID'           => $post_id,
							'post_content' => $post_content,
							'post_excerpt' => $post_excerpt,
						]
					)
				);
			}

			$elementor_data = get_post_meta( $post_id, self::ELEMENTOR_DATA_META, true );

			if ( is_string( $elementor_data ) && '' !== $elementor_data ) {
				$decoded_data  = json_decode( $elementor_data, true );
				$remapped_data = is_array( $decoded_data )
					? wp_json_encode( $this->replace_dynamic_values( $decoded_data, $session ) )
					: $this->replace_dynamic_values( $elementor_data, $session );

				if ( $remapped_data !== $elementor_data ) {
					update_post_meta( $post_id, self::ELEMENTOR_DATA_META, wp_slash( $remapped_data ) );
				}
			}

			$page_settings     = get_post_meta( $post_id, '_elementor_page_settings', true );
			$remapped_settings = $this->replace_dynamic_values( $page_settings, $session );

			if ( $remapped_settings !== $page_settings ) {
				update_post_meta( $post_id, '_elementor_page_settings', $remapped_settings );
			}

			delete_post_meta( $post_id, self::ELEMENTOR_CACHE_META );
		}
	}

	/**
	 * Store Elementor post meta.
	 *
	 * @param int                 $post_id Post id.
	 * @param array<string,mixed> $record Elementor record.
	 * @param ImportSession       $session Import session.
	 * @return void
	 */
	protected function store_elementor_meta( int $post_id, array $record, ImportSession $session ): void {
		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );

		$template_type = sanitize_key( (string) ( $record['template_type'] ?? $record['_elementor_template_type'] ?? '' ) );

		if ( '' !== $template_type ) {
			update_post_meta( $post_id, self::ELEMENTOR_TEMPLATE_META, $template_type );
		}

		$elementor_data = $record['elementor_data'] ?? $record['_elementor_data'] ?? [];
		$elementor_data = $this->normalize_elementor_data( $elementor_data );
		$elementor_data = $this->replace_dynamic_values( $elementor_data, $session );

		update_post_meta( $post_id, self::ELEMENTOR_DATA_META, wp_slash( wp_json_encode( $elementor_data ) ) );

		$page_settings = $record['page_settings'] ?? $record['_elementor_page_settings'] ?? [];
		$page_settings = is_array( $page_settings ) ? $this->replace_dynamic_values( $page_settings, $session ) : [];

		update_post_meta( $post_id, '_elementor_page_settings', $page_settings );

		if ( array_key_exists( 'conditions', $record ) || array_key_exists( '_elementor_conditions', $record ) ) {
			$conditions = $record['conditions'] ?? $record['_elementor_conditions'] ?? [];
			update_post_meta( $post_id, '_elementor_conditions', is_array( $conditions ) ? $conditions : [] );
		}

		if ( ! empty( $record['wp_page_template'] ) || ! empty( $record['_wp_page_template'] ) ) {
			update_post_meta( $post_id, '_wp_page_template', sanitize_text_field( (string) ( $record['wp_page_template'] ?? $record['_wp_page_template'] ) ) );
		}

		delete_post_meta( $post_id, self::ELEMENTOR_CACHE_META );
	}

	/**
	 * Normalize Elementor data into an array.
	 *
	 * @param mixed $elementor_data Elementor data.
	 * @return array<int|string,mixed>
	 */
	protected function normalize_elementor_data( $elementor_data ): array {
		if ( is_string( $elementor_data ) ) {
			$decoded = json_decode( $elementor_data, true );
			return is_array( $decoded ) ? $decoded : [];
		}

		return is_array( $elementor_data ) ? $elementor_data : [];
	}

	/**
	 * Replace source URLs and media IDs in imported payload.
	 *
	 * @param mixed         $value Value.
	 * @param ImportSession $session Import session.
	 * @return mixed
	 */
	protected function replace_dynamic_values( $value, ImportSession $session ) {
		if ( is_string( $value ) ) {
			$url_map = $session->get_url_map();

			return ! empty( $url_map ) ? str_replace( array_keys( $url_map ), array_values( $url_map ), $value ) : $value;
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			if ( 'id' === (string) $key && is_numeric( $child ) ) {
				$mapped_id = $session->get_mapped_id( 'media_source_id', (string) absint( $child ) );

				if ( null !== $mapped_id ) {
					$value[ $key ] = $mapped_id;
					continue;
				}
			}

			$value[ $key ] = $this->replace_dynamic_values( $child, $session );
		}

		return $value;
	}

	/**
	 * Store source tracking meta.
	 *
	 * @param int           $post_id Post id.
	 * @param string        $uid Source UID.
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	protected function store_source_meta( int $post_id, string $uid, ImportSession $session ): void {
		update_post_meta( $post_id, self::SOURCE_UID_META, $uid );
		update_post_meta( $post_id, self::PACKAGE_ID_META, $session->get_package_id() );
		update_post_meta( $post_id, self::PACKAGE_VERSION_META, $session->get_package_version() );
		update_post_meta( $post_id, self::LAST_IMPORTED_META, current_time( 'mysql' ) );
	}

	/**
	 * Find previously imported object by source UID.
	 *
	 * @param string $uid Source UID.
	 * @param string $post_type Post type.
	 * @return int
	 */
	protected function find_existing_by_source_uid( string $uid, string $post_type ): int {
		$ids = get_posts(
			[
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::SOURCE_UID_META,
				'meta_value'     => $uid,
			]
		);

		return ! empty( $ids ) ? absint( $ids[0] ) : 0;
	}

	/**
	 * Read NDJSON file.
	 *
	 * @param string $file File path.
	 * @return array<int,mixed>|WP_Error
	 */
	protected function read_ndjson_file( string $file ) {
		if ( ! file_exists( $file ) ) {
			return new WP_Error(
				'directorist_elementor_ndjson_missing',
				sprintf(
					/* translators: %s: File path. */
					__( 'The package data file is missing: %s', 'directorist-elementor' ),
					basename( $file )
				)
			);
		}

		$lines = file( $file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

		if ( false === $lines ) {
			return new WP_Error( 'directorist_elementor_ndjson_unreadable', __( 'Could not read package data.', 'directorist-elementor' ) );
		}

		$records = [];

		foreach ( $lines as $line_number => $line ) {
			$record = json_decode( $line, true );

			if ( ! is_array( $record ) ) {
				return new WP_Error(
					'directorist_elementor_ndjson_invalid',
					sprintf(
						/* translators: %d: Line number. */
						__( 'The package data file contains invalid JSON on line %d.', 'directorist-elementor' ),
						$line_number + 1
					)
				);
			}

			$records[] = $record;
		}

		return $records;
	}
}
