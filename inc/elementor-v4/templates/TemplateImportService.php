<?php
/**
 * Template import orchestration service.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\ElementorV4\FeatureDependencyManager;
use DirectoristElementor\Traits\Singleton;
use WP_Error;
use ZipArchive;

class TemplateImportService {
	use Singleton;

	private const DEFERRED_THEME_BUILDER_REFRESH_OPTION = 'directorist_elementor_deferred_theme_builder_refresh';
	private const COMPONENT_META_VALUES_KEY              = '__directorist_meta_values';

	/**
	 * Pricing plan IDs imported from the active package.
	 *
	 * @var array<int,int>
	 */
	protected array $active_pricing_plan_id_map = [];
	protected array $active_term_slug_map = [];
	protected array $active_author_id_map = [];

	/**
	 * Conflict result for source posts in the active import.
	 *
	 * @var array<string,array<string,string>>
	 */
	protected array $active_import_actions = [];
	protected string $active_component_package_file = '';

	/**
	 * Elementor posts restored and remapped directly from package JSON.
	 *
	 * @var array<int,bool>
	 */
	protected array $active_normalized_elementor_post_ids = [];
	protected ?ImportJob $import_job = null;

	protected function __construct() {
		ImportJob::register_hooks();
	}

	public function begin_import_job( array $item, array $args, string $file = '' ): array|WP_Error {
		if ( ! FeatureDependencyManager::get_instance()->are_active() ) {
			if ( '' !== $file ) {
				wp_delete_file( $file );
			}
			return $this->required_features_error();
		}
		return ImportJob::create( [ 'item' => $item, 'options' => $args, 'file' => $file ] )->response();
	}

	public function step_import_job( string $id ) {
		$job = ImportJob::load( $id );
		if ( is_wp_error( $job ) ) {
			return $job;
		}
		$this->prepare_import_execution();
		$this->import_job = $job;
		try {
			return $job->execute( function () use ( $job ) {
				$payload = $job->payload();
				return '' !== $payload['file']
					? $this->import_downloaded_package( $payload['file'], $payload['options'] )
					: $this->import_item( $payload['item'], $payload['options'] );
			} );
		} finally {
			$this->import_job = null;
		}
	}

	protected function import_phase( string $name, callable $callback, ?ImportSession $session = null, bool $isolated = false ) {
		if ( ! $this->import_job ) {
			return $callback();
		}
		$cached = $this->import_job->has_phase( $name );
		$phase = $this->import_job->phase( $name, function () use ( $callback, $session ) {
			$before = $session ? $session->to_array() : [];
			$result = $callback();
			return is_wp_error( $result ) ? $result : [
				'value' => $result,
				'session' => $session ? $session->delta_since( $before ) : null,
				'plans' => $this->active_pricing_plan_id_map,
				'actions' => $this->active_import_actions,
				'normalized' => $this->active_normalized_elementor_post_ids,
			];
		}, $isolated );
		if ( $cached && $session && is_array( $phase['session'] ) ) {
			$session->apply_delta( $phase['session'] );
		}
		$this->active_pricing_plan_id_map = $phase['plans'];
		$this->active_import_actions = $phase['actions'];
		$this->active_normalized_elementor_post_ids = $phase['normalized'];
		return $phase['value'];
	}

	/**
	 * Import a selected catalog item.
	 *
	 * @param array<string,mixed> $item Catalog item detail.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>|WP_Error
	 */
	public function import_item( array $item, array $args = [] ) {
		if ( ! FeatureDependencyManager::get_instance()->are_active() ) {
			return $this->required_features_error();
		}
		$this->prepare_import_execution();
		$downloaded_file = $this->import_phase( 'download', fn() => PackageDownloader::get_instance()->download( $item ) );

		if ( is_wp_error( $downloaded_file ) ) {
			return $downloaded_file;
		}

		$args['catalog_item'] = $item;

		return $this->import_downloaded_package( $downloaded_file, $args );
	}

	/**
	 * Import an already downloaded package.
	 *
	 * @param string              $package_file Zip package file.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>|WP_Error
	 */
	public function import_downloaded_package( string $package_file, array $args = [] ) {
		$this->prepare_import_execution();
		$package_dir = '';

		try {
			if ( ! FeatureDependencyManager::get_instance()->are_active() ) {
				return $this->required_features_error();
			}
			$verification = PackageVerifier::get_instance()->verify_archive_paths( $package_file );

			if ( is_wp_error( $verification ) ) {
				return $verification;
			}

			$zip_manifest = $this->import_phase( 'manifest', fn() => $this->read_zip_manifest( $package_file ) );

			if ( is_wp_error( $zip_manifest ) ) {
				return $zip_manifest;
			}

			$compatibility = PackageVerifier::get_instance()->verify_elementor_compatibility( $zip_manifest );
			if ( is_wp_error( $compatibility ) ) {
				return $compatibility;
			}

			if ( $this->is_elementor_kit_manifest( $zip_manifest, $args ) ) {
				return $this->import_elementor_kit_package( $package_file, $zip_manifest, $args );
			}

			$package_dir = $this->create_temp_directory();

			if ( is_wp_error( $package_dir ) ) {
				return $package_dir;
			}

			$unzipped = $this->unzip_package( $package_file, $package_dir );

			if ( is_wp_error( $unzipped ) ) {
				return $unzipped;
			}

			$manifest = PackageVerifier::get_instance()->read_manifest( $package_dir );

			if ( is_wp_error( $manifest ) ) {
				return $manifest;
			}

			$requirements = ExtensionRequirementService::get_instance()->verify_manifest( $manifest );

			if ( is_wp_error( $requirements ) ) {
				return $requirements;
			}

			$file_check = PackageVerifier::get_instance()->verify_manifest_file_checksums( $package_dir, $manifest );

			if ( is_wp_error( $file_check ) ) {
				return $file_check;
			}

			$adapter = ElementorTemplateAdapter::get_instance();

			if ( ! $adapter->can_import_package( $manifest ) ) {
				return new WP_Error( 'directorist_elementor_import_wrong_adapter', __( 'No compatible importer is available for this package.', 'directorist-elementor' ) );
			}

			$preflight = $adapter->preflight( $manifest );

			if ( empty( $preflight['passed'] ) ) {
				return new WP_Error(
					'directorist_elementor_import_preflight_failed',
					implode( ' ', array_map( 'sanitize_text_field', (array) ( $preflight['errors'] ?? [] ) ) )
				);
			}

			$session = new ImportSession(
				sanitize_text_field( (string) ( $manifest['package_id'] ?? '' ) ),
				sanitize_text_field( (string) ( $manifest['package_version'] ?? '1.0.0' ) )
			);

			$result = $adapter->import_builder_items( $session, $package_dir, $manifest, $args );

			$status = empty( $result['errors'] ) ? 'success' : 'partial';
			ImportSessionLogger::get_instance()->store( $session, $status, __( 'Directorist Elementor template import completed.', 'directorist-elementor' ) );

			return [
				'status'  => $status,
				'message' => __( 'Directorist Elementor template import completed.', 'directorist-elementor' ),
				'result'  => $result,
				'session' => $session->to_array(),
				'report'  => $this->build_import_report( $session, $result, $args ),
			];
		} finally {
			if ( ! $this->import_job || ! $this->import_job->did_yield() ) {
				$this->cleanup_file( $package_file );

				if ( '' !== $package_dir ) {
					$this->cleanup_directory( $package_dir );
				}
			}
		}
	}

	/** Give imports a clear failure instead of storing empty nested compositions. */
	private function required_features_error(): WP_Error {
		return new WP_Error(
			'directorist_elementor_nested_features_inactive',
			__( 'Elementor Container and Nested Elements must be active to import Directorist Elementor layouts.', 'directorist-elementor' ),
			[ 'status' => 409 ]
		);
	}

	protected function finalize_native_import_post( array $imported_post, ImportSession $session, array $manifest, string $package_file, array $media_id_map, array $term_id_map, array $post_date_map, array $template_meta, array $document_meta, bool $activate_templates, string $conflict_behavior ): array {
		$stats = [ 'processed' => 0, 'skipped' => 0, 'replaced' => 0 ];
		$post_id   = absint( $imported_post['post_id'] ?? 0 );
		$post_type = sanitize_key( (string) ( $imported_post['post_type'] ?? get_post_type( $post_id ) ) );
		$source_id = sanitize_text_field( (string) ( $imported_post['source_id'] ?? '' ) );

		if ( $post_id <= 0 || '' === $post_type || ! get_post( $post_id ) ) {
			return $stats;
		}

		$meta           = $this->resolve_directorist_document_meta( $document_meta, $template_meta, $post_type, $source_id );
		$source_uid     = sanitize_text_field( (string) ( $meta['source_uid'] ?? $session->get_package_id() . ':' . $post_type . ':' . $source_id ) );
		$native_post_id = $post_id;
		$this->restore_imported_post_date( $post_id, $post_type, $source_id, $post_date_map );
		$this->remap_imported_post_media_meta( $post_id, $media_id_map );
		$source_slug    = $this->get_imported_post_slug( $post_type, $source_id, $manifest );
		$source_content = (string) get_post_field( 'post_content', $post_id );
		$preferred_id   = in_array( $post_type, [ 'page', 'post' ], true ) && 'duplicate' !== $conflict_behavior ? $this->find_pristine_destination_post( $post_type, $source_slug, $source_content ) : 0;
		if ( $preferred_id <= 0 && in_array( $post_type, [ 'page', 'post' ], true ) && 'duplicate' !== $conflict_behavior && '' === $source_slug ) {
			$native_slug  = sanitize_title( (string) get_post_field( 'post_name', $post_id ) );
			$base_slug    = preg_replace( '/-[0-9]+$/', '', $native_slug );
			$preferred_id = is_string( $base_slug ) ? $this->find_pristine_destination_post( $post_type, $base_slug, $source_content ) : 0;
		}
		if ( $preferred_id <= 0 && 'duplicate' !== $conflict_behavior && in_array( $post_type, [ 'page', 'post', 'at_biz_dir' ], true ) ) {
			$existing_by_uid = $this->find_existing_imported_post_id( $source_uid, $post_id );
			if ( $existing_by_uid > 0 ) {
				$preferred_id = $existing_by_uid;
			} else {
				$preferred_id = $this->find_matching_destination_content_post( $post_type, $source_slug, (string) get_post_field( 'post_title', $post_id ), $source_uid, $post_id );
				if ( $preferred_id < 0 ) {
					wp_delete_post( $post_id, true );
					$session->log( 'warning', __( 'An imported content item matched multiple existing resources and was skipped.', 'directorist-elementor' ), [ 'post_type' => $post_type, 'source_id' => $source_id, 'slug' => $source_slug ] );
					$stats['skipped'] = 1;
					return $stats;
				}
			}
		}
		$conflict       = $this->resolve_imported_post_conflict( $post_id, $source_uid, $post_type, $source_id, $conflict_behavior, $preferred_id );
		$post_id        = absint( $conflict['post_id'] ?? $post_id );
		$action         = sanitize_key( (string) ( $conflict['action'] ?? 'created' ) );
		$this->active_import_actions[ $post_type ][ $source_id ] = $action;

		if ( $post_id <= 0 ) {
			return $stats;
		}

		if ( 'skipped' !== $action ) {
			$this->restore_imported_post_slug( $post_id, $post_type, $source_id, $manifest );
			$this->ensure_unique_imported_post_slug( $post_id );
		}

		if ( 'skipped' === $action ) {
			$stats['skipped'] = 1;
		} elseif ( 'updated' === $action ) {
			$session->record_updated( $post_type, $post_id );
		} else {
			$session->record_created( $post_type, $post_id );

			if ( 'replaced' === $action ) {
				$stats['replaced'] = 1;
			}
		}

		if ( '' !== $source_id ) {
			$this->map_imported_post_id( $session, $post_type, $source_id, $post_id );
		}
		$this->map_imported_post_url( $session, $manifest, $post_type, $source_id, $post_id );

		$this->map_native_imported_post_id( $session, $post_type, $native_post_id, $post_id );
		$session->map_id( 'post_uid', $source_uid, $post_id );

		update_post_meta( $post_id, '_directorist_import_source_uid', $source_uid );
		update_post_meta( $post_id, '_directorist_import_package_id', $session->get_package_id() );
		update_post_meta( $post_id, '_directorist_import_package_version', $session->get_package_version() );
		update_post_meta( $post_id, '_directorist_import_last_imported_at', current_time( 'mysql' ) );

		if ( 'at_biz_dir' === $post_type ) {
			$this->remap_directorist_listing_directory_type( $post_id, $term_id_map );
		}

		if ( 'elementor_library' === $post_type ) {
			if ( $activate_templates && ! empty( $meta['conditions'] ) && is_array( $meta['conditions'] ) ) {
				update_post_meta( $post_id, '_elementor_conditions', $this->remap_elementor_template_conditions( $meta['conditions'], $session, $term_id_map ) );
			}

			$this->ensure_imported_template_data( $post_id, $source_id, $manifest, $package_file, $term_id_map, $session );
		} else {
			$this->ensure_imported_content_data( $post_id, $post_type, $source_id, $manifest, $package_file, $term_id_map, $session );
		}

		$this->clear_elementor_cache_for_post( $post_id );
		$session->log( 'info', __( 'Imported Elementor kit item.', 'directorist-elementor' ), [ 'post_id' => $post_id, 'post_type' => $post_type, 'source_id' => $source_id, 'action' => $action ] );
		$stats['processed'] = 1;
		return $stats;
	}

	protected function prepare_import_execution(): void {
		wp_raise_memory_limit( 'admin' );
		$this->ensure_elementor_notes_schema();
		if ( $this->import_job && function_exists( 'ignore_user_abort' ) ) {
			ignore_user_abort( true );
		}
		if ( function_exists( 'set_time_limit' ) ) {
			set_time_limit( 0 );
		}
	}

	/** Initialize Elementor Pro's per-site Notes tables before document hooks run. */
	protected function ensure_elementor_notes_schema(): void {
		if ( ! class_exists( '\ElementorPro\Modules\Notes\Database\Notes_Database_Updater' ) ) {
			return;
		}
		global $wpdb;
		$tables = [ $wpdb->prefix . 'e_notes', $wpdb->prefix . 'e_notes_users_relations' ];
		$missing = false;
		foreach ( $tables as $table ) {
			if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
				$missing = true;
				break;
			}
		}
		if ( ! $missing ) {
			return;
		}
		delete_option( 'elementor_notes_db_version' );
		( new \ElementorPro\Modules\Notes\Database\Notes_Database_Updater() )->up();
	}

	/**
	 * Import an Elementor-native kit package through Elementor's importer.
	 *
	 * @param string              $package_file Zip package file.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function import_elementor_kit_package( string $package_file, array $manifest, array $args = [] ) {
		if ( ! class_exists( '\Elementor\App\Modules\ImportExport\Processes\Import' ) ) {
			return new WP_Error( 'directorist_elementor_kit_importer_missing', __( 'Elementor kit importer is not available.', 'directorist-elementor' ) );
		}

		$requirements = ExtensionRequirementService::get_instance()->verify_manifest( $manifest );

		if ( is_wp_error( $requirements ) ) {
			return $requirements;
		}

		$directorist       = isset( $manifest['directorist'] ) && is_array( $manifest['directorist'] ) ? $manifest['directorist'] : [];
		$catalog_item      = isset( $args['catalog_item'] ) && is_array( $args['catalog_item'] ) ? $args['catalog_item'] : [];
		$package_id        = sanitize_text_field( (string) ( $directorist['package_id'] ?? $manifest['name'] ?? $catalog_item['id'] ?? '' ) );
		$package_version   = sanitize_text_field( (string) ( $manifest['package_version'] ?? $catalog_item['version'] ?? '1.0.0' ) );
		$import_args       = $this->normalize_elementor_kit_import_args( $manifest, $args );
		$homepage_snapshot = $this->import_phase( 'homepage-snapshot', fn() => $this->capture_homepage_settings() );
		$import_args['directory_default_id'] = $this->import_phase( 'directory-default-snapshot', fn() => function_exists( 'directorist_get_default_directory' ) ? absint( directorist_get_default_directory() ) : 0 );
		$session            = new ImportSession( $package_id, $package_version );
		$term_slug_map      = [];
		$existing_term_map  = [];

		$native_manifest   = $this->prepare_elementor_native_import_manifest( $manifest );
		$manifest_replaced = $this->import_phase( 'native-manifest', fn() => $this->replace_zip_manifest( $package_file, $native_manifest ) );

		if ( is_wp_error( $manifest_replaced ) ) {
			return $manifest_replaced;
		}

		$site_settings_prepared = $this->import_phase(
			'native-site-settings-file',
			fn() => $this->prepare_elementor_native_site_settings_file( $package_file, $import_args )
		);

		if ( is_wp_error( $site_settings_prepared ) ) {
			return $site_settings_prepared;
		}

		$taxonomy_phase = $this->import_phase( 'native-taxonomy-files', function () use ( $package_file, $native_manifest, $session, $import_args, &$term_slug_map, &$existing_term_map ) {
			$result = $this->prepare_elementor_native_taxonomy_files(
				$package_file,
				$native_manifest,
				$session->get_package_id(),
				$this->normalize_conflict_behavior( (string) ( $import_args['conflict_behavior'] ?? 'update' ) ),
				$term_slug_map,
				$existing_term_map
			);
			return is_wp_error( $result ) ? $result : [ 'result' => $result, 'slugs' => $term_slug_map, 'existing' => $existing_term_map ];
		} );
		$native_taxonomies_prepared = is_wp_error( $taxonomy_phase ) ? $taxonomy_phase : $taxonomy_phase['result'];
		if ( ! is_wp_error( $taxonomy_phase ) ) {
			$term_slug_map = $taxonomy_phase['slugs'];
			$existing_term_map = $taxonomy_phase['existing'];
			$import_args['term_slug_map'] = $term_slug_map;
		}

		if ( is_wp_error( $native_taxonomies_prepared ) ) {
			return $native_taxonomies_prepared;
		}

		$safe_remote_filter = $this->get_safe_remote_host_filter();
		$native_term_filter = function ( array $terms ) use ( $session, $import_args, &$term_slug_map, &$existing_term_map ): array {
			return $this->prepare_native_import_terms(
				$terms,
				$session->get_package_id(),
				$this->normalize_conflict_behavior( (string) ( $import_args['conflict_behavior'] ?? 'update' ) ),
				$term_slug_map,
				$existing_term_map
			);
		};
		$native_post_term_filter = function ( array $terms ) use ( &$term_slug_map ): array {
			return $this->prepare_native_import_post_terms( $terms, $term_slug_map );
		};
		$native_plan_meta_filter = static fn( $key, $post_id = 0, $post = [] ) => '_plan_id' === $key && 'at_biz_dir' === ( $post['post_type'] ?? get_post_type( $post_id ) ) ? false : $key;

		add_filter( 'http_request_host_is_external', $safe_remote_filter, 10, 3 );
		add_filter( 'wp_import_terms', $native_term_filter );
		add_filter( 'wp_import_post_terms', $native_post_term_filter );
		add_filter( 'import_post_meta_key', $native_plan_meta_filter, 10, 3 );

		$native_directory = '';
		try {
			$include         = $this->get_elementor_kit_import_include( $manifest, $import_args );
			$import_settings = [
				'id'       => $package_id,
				'referrer' => 'kit-library',
				'include'  => $include,
			];

			if ( in_array( 'content', $include, true ) ) {
				$import_settings['selectedCustomPostTypes'] = $this->get_elementor_kit_custom_post_types( $manifest );
			}

			if ( in_array( 'plugins', $include, true ) && ! empty( $manifest['plugins'] ) && is_array( $manifest['plugins'] ) ) {
				$import_settings['plugins'] = $manifest['plugins'];
			}

			if ( $this->import_job ) {
				$native = $this->import_phase( 'native-session', function () use ( $package_file, $import_settings ) {
					$import = new \Elementor\App\Modules\ImportExport\Processes\Import( $package_file, $import_settings );
					$import->register_default_runners();
					$import->init_import_session( true );
					return [ 'id' => $import->get_session_id(), 'runners' => $import->get_runners_name(), 'directory' => $import->get_extracted_directory_path() ];
				} );
				$native_directory = (string) ( $native['directory'] ?? '' );
				$elementor_session_id = $native['id'];
				$raw_result = [];
				foreach ( $native['runners'] as $runner ) {
					$raw_result = $this->import_phase( 'native-' . $runner, function () use ( $elementor_session_id, $runner ) {
						$import = \Elementor\App\Modules\ImportExport\Processes\Import::from_session( $elementor_session_id );
						$import->run_runner( $runner );
						return $import->get_imported_data();
					}, null, true );
				}
			} else {
				$import = new \Elementor\App\Modules\ImportExport\Processes\Import( $package_file, $import_settings );
				$native_directory = (string) $import->get_extracted_directory_path();
				$import->register_default_runners();
				$elementor_session_id = $import->get_session_id();
				$raw_result = $import->run();
			}
			$result                = $this->finalize_elementor_kit_import( $session, $manifest, $raw_result, $package_file, $import_args, $elementor_session_id, $existing_term_map );
		} catch ( ImportJobYield $yield ) {
			throw $yield;
		} catch ( \Throwable $error ) {
			ImportSessionLogger::get_instance()->store( $session, 'failed', $error->getMessage() );

			return new WP_Error( 'directorist_elementor_kit_import_failed', $error->getMessage() );
		} finally {
			if ( '' !== $native_directory && ( ! $this->import_job || ! $this->import_job->did_yield() ) ) {
				$this->cleanup_directory( $native_directory );
			}
			if ( empty( $import_args['set_homepage'] ) ) {
				$this->restore_homepage_settings( $homepage_snapshot );
			}

			remove_filter( 'http_request_host_is_external', $safe_remote_filter, 10 );
			remove_filter( 'wp_import_terms', $native_term_filter );
			remove_filter( 'wp_import_post_terms', $native_post_term_filter );
			remove_filter( 'import_post_meta_key', $native_plan_meta_filter );
		}

		if ( ! empty( $import_args['include_content'] ) ) {
			$this->remove_pristine_wordpress_sample_content();
		}

		$status = empty( $result['errors'] ) ? 'success' : 'partial';

		ImportSessionLogger::get_instance()->store( $session, $status, __( 'Directorist Elementor template import completed.', 'directorist-elementor' ) );

		return [
			'status'  => $status,
			'message' => __( 'Directorist Elementor template import completed.', 'directorist-elementor' ),
			'result'  => $result,
			'session' => $session->to_array(),
			'report'  => $this->build_import_report( $session, $result, $import_args ),
		];
	}

	/**
	 * Remove untouched WordPress starter content before a complete-site import.
	 *
	 * @return void
	 */
	protected function remove_pristine_wordpress_sample_content(): void {
		foreach ( [ [ 'post', 'hello-world' ], [ 'page', 'sample-page' ] ] as [ $post_type, $slug ] ) {
			$post_id = $this->find_pristine_destination_post( $post_type, $slug );
			if ( $post_id > 0 ) {
				wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Build a concise, user-facing record of what an import changed.
	 *
	 * @param ImportSession       $session Completed import session.
	 * @param array<string,mixed> $result Import result.
	 * @param array<string,mixed> $args Normalized import options.
	 * @return array<string,mixed>
	 */
	protected function build_import_report( ImportSession $session, array $result, array $args ): array {
		$created          = $this->build_import_report_groups( $session->get_created() );
		$updated          = $this->build_import_report_groups( $session->get_updated() );
		$conditions_count = 0;

		foreach ( array_merge( $session->get_created()['elementor_library'] ?? [], $session->get_updated()['elementor_library'] ?? [] ) as $post_id ) {
			if ( ! empty( get_post_meta( absint( $post_id ), '_elementor_conditions', true ) ) ) {
				$conditions_count++;
			}
		}

		$homepage_id           = absint( get_option( 'page_on_front' ) );
		$site_settings_on      = ! empty( $args['apply_site_settings'] );
		$site_settings_result  = isset( $result['site_settings'] ) && is_array( $result['site_settings'] ) ? $result['site_settings'] : [];
		$site_settings_applied = $site_settings_on && ! empty( $site_settings_result['applied'] );
		$site_settings_skipped = $site_settings_on && 'preserved' === (string) ( $site_settings_result['status'] ?? '' );
		$conditions_on         = ! empty( $args['apply_template_conditions'] ) || ! empty( $args['activate_templates'] );
		$conditions_stored     = $conditions_on && $conditions_count > 0;
		$theme_builder_active  = class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' );
		$conditions_applied    = $conditions_stored && $theme_builder_active;
		$homepage_on           = ! empty( $args['set_homepage'] );
		$homepage_applied      = $homepage_on
			&& $homepage_id > 0
			&& $session->get_package_id() === (string) get_post_meta( $homepage_id, '_directorist_import_package_id', true );
		$site_settings_detail  = __( 'Existing Elementor site settings were preserved.', 'directorist-elementor' );

		if ( $site_settings_applied ) {
			$site_settings_detail = __( 'Imported colors, typography, and layout settings were applied.', 'directorist-elementor' );
		} elseif ( $site_settings_on && ! empty( $site_settings_result['error'] ) ) {
			$site_settings_detail = __( 'Elementor site settings could not be applied. Review the import errors for details.', 'directorist-elementor' );
		} elseif ( $site_settings_on && ! $site_settings_skipped ) {
			$site_settings_detail = __( 'The package did not contain site settings to apply.', 'directorist-elementor' );
		}

		return [
			'package_id'      => $session->get_package_id(),
			'package_version' => $session->get_package_version(),
			'site_url'        => home_url( '/' ),
			'created'         => $created,
			'updated'         => $updated,
			'warnings'        => array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $result['warnings'] ?? [] ) ) ) ),
			'totals'          => [
				'created'  => array_sum( array_column( $created, 'count' ) ),
				'updated'  => array_sum( array_column( $updated, 'count' ) ),
				'skipped'  => absint( $result['skipped'] ?? 0 ),
				'replaced' => absint( $result['replaced'] ?? 0 ),
			],
			'components'      => isset( $result['components'] ) && is_array( $result['components'] ) ? $result['components'] : [],
			'site_changes'    => array_merge(
				[
				[
					'key'    => 'site_settings',
					'status' => $site_settings_applied ? 'applied' : ( $site_settings_on ? 'unchanged' : 'preserved' ),
					'label'  => __( 'Elementor site settings', 'directorist-elementor' ),
					'detail' => $site_settings_detail,
				],
				[
					'key'    => 'template_conditions',
					'status' => $conditions_applied ? 'applied' : ( $conditions_on ? 'unchanged' : 'preserved' ),
					'label'  => __( 'Template conditions', 'directorist-elementor' ),
					'detail' => $conditions_applied
						? sprintf(
							/* translators: %d: number of templates with active conditions. */
							_n( '%d template condition was activated.', '%d template conditions were activated.', $conditions_count, 'directorist-elementor' ),
							$conditions_count
						)
						: ( $conditions_stored
							? sprintf(
								/* translators: %d: number of templates with stored conditions. */
								_n( '%d template condition was imported but remains inactive until Elementor Pro Theme Builder is available.', '%d template conditions were imported but remain inactive until Elementor Pro Theme Builder is available.', $conditions_count, 'directorist-elementor' ),
								$conditions_count
							)
							: ( $conditions_on
							? __( 'The package did not contain template conditions to activate.', 'directorist-elementor' )
							: __( 'Existing template conditions were preserved.', 'directorist-elementor' ) ) ),
				],
				[
					'key'      => 'homepage',
					'status'   => $homepage_applied ? 'applied' : ( $homepage_on ? 'unchanged' : 'preserved' ),
					'label'    => __( 'Homepage', 'directorist-elementor' ),
					'detail'   => $homepage_applied
						? sprintf(
							/* translators: %s: imported homepage title. */
							__( 'Set to %s.', 'directorist-elementor' ),
							get_the_title( $homepage_id ) ?: __( 'the imported homepage', 'directorist-elementor' )
						)
						: ( $homepage_on
							? __( 'The package did not declare an imported homepage.', 'directorist-elementor' )
							: __( 'The existing homepage setting was preserved.', 'directorist-elementor' ) ),
					'view_url' => $homepage_applied ? get_permalink( $homepage_id ) : '',
				],
				],
				$this->build_component_report_changes( (array) ( $result['components'] ?? [] ) )
			),
		];
	}

	/**
	 * Describe portable Directorist data imported alongside builder content.
	 *
	 * @param array<string,mixed> $components Component import results.
	 * @return array<int,array<string,string>>
	 */
	protected function build_component_report_changes( array $components ): array {
		$changes = [];

		$assigned = [];
		foreach ( (array) ( $components['listings']['plan_assignments'] ?? [] ) as $assignment ) {
			if ( 'assigned' === ( $assignment['status'] ?? '' ) ) {
				$plan_id = absint( $assignment['plan_id'] ?? 0 );
				$assigned[ $plan_id ] = ( $assigned[ $plan_id ] ?? 0 ) + 1;
			}
		}
		foreach ( $assigned as $plan_id => $count ) {
			global $wpdb;
			$title = $wpdb->get_var( $wpdb->prepare( "SELECT title FROM {$wpdb->prefix}directorist_plans WHERE id = %d", $plan_id ) );
			$changes[] = [
				'key' => 'demo_plan_' . $plan_id, 'status' => 'applied',
				'label' => __( 'Demo listing access', 'directorist-elementor' ),
				'detail' => sprintf(
					/* translators: 1: listings assigned, 2: plan title. */
					__( '%1$d demo listings assigned to "%2$s". No orders or subscriptions were created.', 'directorist-elementor' ),
					$count, $title ?: (string) $plan_id
				),
			];
		}


		if ( ! empty( $components['directory_types'] ) && is_array( $components['directory_types'] ) ) {
			$result   = $components['directory_types'];
			$imported = absint( $result['imported'] ?? 0 );
			$updated  = absint( $result['updated'] ?? 0 );
			$skipped  = absint( $result['skipped'] ?? 0 );
			$settings = absint( $result['settings'] ?? 0 );
			$terms    = absint( $result['terms'] ?? 0 );
			$changes[] = [
				'key'    => 'directorist_directory_types',
				'status' => $imported + $updated + $settings + $terms > 0 ? 'applied' : 'unchanged',
				'label'  => __( 'Directory configuration', 'directorist-elementor' ),
				'detail' => sprintf(
					/* translators: 1: created directories, 2: updated directories, 3: restored taxonomy terms, 4: restored settings, 5: skipped directories. */
					__( '%1$d directories created, %2$d updated, %3$d taxonomy terms restored, %4$d settings applied, and %5$d skipped.', 'directorist-elementor' ),
					$imported,
					$updated,
					$terms,
					$settings,
					$skipped
				),
			];
		}

		if ( ! empty( $components['listings'] ) && is_array( $components['listings'] ) ) {
			$result   = $components['listings'];
			$imported = absint( $result['imported'] ?? 0 );
			$updated  = absint( $result['updated'] ?? 0 );
			$skipped  = absint( $result['skipped'] ?? 0 );
			$comments = absint( $result['comments'] ?? 0 );
			$changes[] = [
				'key'    => 'directorist_listings',
				'status' => $imported + $updated + $comments > 0 ? 'applied' : 'unchanged',
				'label'  => __( 'Listing data', 'directorist-elementor' ),
				'detail' => sprintf(
					/* translators: 1: created listings, 2: updated listings, 3: restored comments, 4: skipped listings. */
					__( '%1$d listings created, %2$d updated, %3$d reviews restored, and %4$d skipped.', 'directorist-elementor' ),
					$imported,
					$updated,
					$comments,
					$skipped
				),
			];
		}

		if ( ! empty( $components['site_settings'] ) && is_array( $components['site_settings'] ) ) {
			$result  = $components['site_settings'];
			$applied = ! empty( $result['applied'] );
			$updated = absint( $result['updated'] ?? 0 );
			$changes[] = [
				'key'    => 'directorist_site_settings',
				'status' => $applied ? 'applied' : 'preserved',
				'label'  => __( 'Directorist site settings', 'directorist-elementor' ),
				'detail' => $applied
					? sprintf(
						/* translators: %d: number of Directorist settings changed. */
						__( '%d portable Directorist settings were updated.', 'directorist-elementor' ),
						$updated
					)
					: __( 'Existing Directorist site settings were preserved.', 'directorist-elementor' ),
			];
		}

		if ( ! empty( $components['pricing_plans'] ) && is_array( $components['pricing_plans'] ) ) {
			$result   = $components['pricing_plans'];
			$imported = absint( $result['imported'] ?? 0 );
			$updated  = absint( $result['updated'] ?? 0 );
			$skipped  = absint( $result['skipped'] ?? 0 );
			$replaced = absint( $result['replaced'] ?? 0 );
			$changes[] = [
				'key'    => 'pricing_plans',
				'status' => $imported + $updated + $replaced > 0 ? 'applied' : 'unchanged',
				'label'  => __( 'Pricing plans', 'directorist-elementor' ),
				'detail' => sprintf(
					/* translators: 1: created plans, 2: updated plans, 3: replaced plans, 4: skipped plans. */
					__( '%1$d created, %2$d updated, %3$d replaced, and %4$d skipped.', 'directorist-elementor' ),
					$imported,
					$updated,
					$replaced,
					$skipped
				),
			];
		}

		return $changes;
	}

	/**
	 * Group imported post IDs by post type for the completion report.
	 *
	 * @param array<string,array<int>> $objects Imported object IDs by type.
	 * @return array<int,array<string,mixed>>
	 */
	protected function build_import_report_groups( array $objects ): array {
		$groups = [];

		foreach ( $objects as $post_type => $post_ids ) {
			$post_type_object = get_post_type_object( $post_type );
			$items            = [];

			foreach ( array_values( array_unique( array_map( 'absint', (array) $post_ids ) ) ) as $post_id ) {
				$post = get_post( $post_id );

				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				$items[] = [
					'id'       => $post_id,
					'title'    => get_the_title( $post_id ) ?: __( '(Untitled)', 'directorist-elementor' ),
					'edit_url' => get_edit_post_link( $post_id, 'raw' ) ?: '',
					'view_url' => 'publish' === $post->post_status ? $this->get_import_report_view_url( $post ) : '',
				];
			}

			if ( empty( $items ) ) {
				continue;
			}

			$groups[] = [
				'type'  => sanitize_key( (string) $post_type ),
				'label' => $post_type_object && isset( $post_type_object->labels->name )
					? (string) $post_type_object->labels->name
					: ucwords( str_replace( [ '-', '_' ], ' ', (string) $post_type ) ),
				'count' => count( $items ),
				'items' => $items,
			];
		}

		usort(
			$groups,
			static function ( array $first, array $second ): int {
				return strcasecmp( (string) $first['label'], (string) $second['label'] );
			}
		);

		return $groups;
	}

	/** Build listing report links from uncached imported permalink settings. */
	protected function get_import_report_view_url( \WP_Post $post ): string {
		if ( 'at_biz_dir' !== $post->post_type ) {
			return (string) get_permalink( $post );
		}
		global $wpdb;
		$raw_settings = $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1", 'atbdp_option' ) );
		$settings     = is_string( $raw_settings ) ? (array) maybe_unserialize( $raw_settings ) : [];
		if ( empty( $settings['single_listing_slug_with_directory_type'] ) ) {
			return (string) get_permalink( $post );
		}
		$directory_id = absint( get_post_meta( $post->ID, '_directory_type', true ) );
		$directory    = $directory_id > 0 ? get_term( $directory_id, 'atbdp_listing_types' ) : null;
		if ( ! $directory instanceof \WP_Term || '' === $directory->slug ) {
			return (string) get_permalink( $post );
		}
		$base = sanitize_title( (string) ( $settings['atbdp_listing_slug'] ?? 'directory' ) );
		return home_url( user_trailingslashit( $base . '/' . $directory->slug . '/' . $post->post_name ) );
	}

	/**
	 * Add Directorist tracking metadata and clear Elementor caches after kit import.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $raw_result Raw Elementor import result.
	 * @param string              $package_file Zip package file.
	 * @param array<string,mixed> $args Import args.
	 * @param string              $elementor_session_id Elementor native import session id.
	 * @param array<string,array<int,int>> $existing_term_map Existing package-owned terms reused by this import.
	 * @return array<string,mixed>
	 */
	protected function finalize_elementor_kit_import( ImportSession $session, array $manifest, array $raw_result, string $package_file, array $args = [], string $elementor_session_id = '', array $existing_term_map = [] ): array {
		$this->active_component_package_file = $package_file;
		$this->active_term_slug_map = (array) ( $args['term_slug_map'] ?? [] );
		$imported_posts      = $this->import_phase( 'native-post-map', fn() => $this->collect_elementor_kit_imported_posts( $raw_result, $elementor_session_id ), $session );
		$package_media_urls  = $this->read_elementor_kit_attachment_urls( $package_file );
		$media_id_map        = $this->collect_elementor_kit_media_id_map( $raw_result );
		$media_reconcile     = $this->import_phase( 'native-media', fn() => $this->record_elementor_kit_media( $session, $media_id_map ), $session );
		$media_id_map        = $media_reconcile['media_id_map'];
		$duplicate_media_ids = $media_reconcile['duplicate_ids'];
		$this->record_elementor_kit_media_source_urls( $media_id_map, $package_media_urls );
		$unmapped_media_ids  = array_values(
			array_diff(
				array_map( 'absint', array_keys( $package_media_urls ) ),
				array_map( 'absint', array_keys( $media_id_map ) )
			)
		);
		$failed              = $this->collect_elementor_kit_import_errors( $raw_result );
		$warnings            = array_merge(
			$this->collect_unavailable_content_type_warnings( $manifest, $args ),
			$this->collect_unavailable_extension_warnings( $manifest ),
			$this->collect_nonportable_elementor_css_warnings( $manifest, $package_file )
		);
		$template_meta      = isset( $manifest['directorist']['templates'] ) && is_array( $manifest['directorist']['templates'] ) ? $manifest['directorist']['templates'] : [];
		$document_meta      = isset( $manifest['directorist']['documents'] ) && is_array( $manifest['directorist']['documents'] ) ? $manifest['directorist']['documents'] : [];
		$activate_templates = ! empty( $args['activate_templates'] );
		$conflict_behavior  = $this->normalize_conflict_behavior( (string) ( $args['conflict_behavior'] ?? 'update' ) );
		$term_id_map        = $this->merge_elementor_term_id_maps( $this->collect_elementor_kit_term_id_map( $raw_result ), $existing_term_map );
		$post_date_map      = $this->read_elementor_kit_post_dates( $package_file, $imported_posts );
		$processed          = 0;
		$skipped            = 0;
		$replaced           = 0;

		$component_import = $this->import_phase( 'components', function () use ( $session, $term_id_map, $args, $manifest, $package_file, $conflict_behavior ) {
			$this->record_elementor_kit_term_id_map( $session, $term_id_map );
			$result = ! empty( $args['include_content'] )
				? $this->import_directorist_components( $session, $manifest, $package_file, $term_id_map, $conflict_behavior )
				: $this->get_empty_component_import_result();
			$this->record_elementor_kit_term_ownership( $session, $term_id_map, $conflict_behavior );
			return $result;
		}, $session );
		$failed           = array_merge( $failed, $component_import['errors'] );
		$warnings         = array_merge( $warnings, $component_import['warnings'] );
		$this->active_pricing_plan_id_map = $component_import['pricing_plan_id_map'];
		$this->active_author_id_map = (array) ( $component_import['author_id_map'] ?? [] );

		foreach ( $imported_posts as $imported_post ) {
			$stats = $this->import_phase(
				'kit-post-' . absint( $imported_post['post_id'] ?? 0 ),
				fn() => $this->finalize_native_import_post( $imported_post, $session, $manifest, $package_file, $media_id_map, $term_id_map, $post_date_map, $template_meta, $document_meta, $activate_templates, $conflict_behavior ),
				$session
			);
			$processed += $stats['processed'];
			$skipped += $stats['skipped'];
			$replaced += $stats['replaced'];
		}

		$formgent_restore = $this->import_phase( 'forms', fn() => $this->restore_imported_formgent_forms( $session, $package_file ), $session );
		$failed           = array_merge( $failed, $formgent_restore['errors'] );

		if ( ! empty( $args['include_content'] ) && ! empty( $component_import['payload'] ) ) {
			$content_components = $this->import_phase( 'directory-data', fn() => $this->import_directorist_content_components(
				$session,
				(array) $component_import['payload'],
				$term_id_map,
				$media_id_map,
				$conflict_behavior,
				(string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ),
				! empty( $args['apply_site_settings'] ) || ! empty( $args['include_settings'] )
			), $session );
			if ( empty( $args['apply_site_settings'] ) && empty( $args['include_settings'] ) ) {
				$this->restore_default_directory( absint( $args['directory_default_id'] ?? 0 ) );
			}
			$media_id_map = $content_components['media_id_map'];
			$this->active_author_id_map = (array) ( $content_components['author_id_map'] ?? [] );
			$component_import['results'] = array_merge( $component_import['results'], $content_components['results'] );
			$component_import['errors'] = array_merge( $component_import['errors'], $content_components['errors'] );
			$component_import['warnings'] = array_merge( $component_import['warnings'], $content_components['warnings'] );
			$failed = array_merge( $failed, $content_components['errors'] );
			$warnings = array_merge( $warnings, $content_components['warnings'] );

			$imported_post_map = [];
			foreach ( $imported_posts as $imported_post ) {
				$source_post_id = absint( $imported_post['source_id'] ?? 0 );
				$post_type = sanitize_key( (string) ( $imported_post['post_type'] ?? '' ) );
				$target_post_id = absint( $this->get_mapped_post_id( $session, $post_type, (string) $source_post_id ) );
				if ( $source_post_id > 0 && $target_post_id > 0 ) {
					$imported_post_map[ $source_post_id ] = $target_post_id;
				}
			}
			foreach ( (array) ( $component_import['payload']['listings']['post_authors'] ?? [] ) as $source_post_id => $source_author_id ) {
				$target_post_id = absint( $imported_post_map[ absint( $source_post_id ) ] ?? 0 );
				$target_author_id = absint( $content_components['author_id_map'][ absint( $source_author_id ) ] ?? 0 );
				if ( $target_post_id > 0 && $target_author_id > 0 && (int) get_post_field( 'post_author', $target_post_id ) !== $target_author_id ) {
					wp_update_post( [ 'ID' => $target_post_id, 'post_author' => $target_author_id ] );
				}
			}
		}

		if ( ! empty( $args['include_templates'] ) ) {
			$fallback_templates = $this->import_phase( 'missing-templates', fn() => $this->import_missing_elementor_kit_templates( $session, $manifest, $package_file, $term_id_map, $activate_templates, $conflict_behavior ), $session );
			$processed         += absint( $fallback_templates['imported'] ?? 0 );
			$skipped           += absint( $fallback_templates['skipped'] ?? 0 );
			$replaced          += absint( $fallback_templates['replaced'] ?? 0 );
			$failed             = array_merge( $failed, (array) ( $fallback_templates['errors'] ?? [] ) );
		}

		$kit_shell = $this->import_phase( 'kit-shell', fn() => $this->finalize_elementor_kit_shell( $session, $manifest, $conflict_behavior ), $session );
		$site_settings = $this->import_phase( 'site-settings', fn() => $this->finalize_elementor_kit_site_settings(
			$session,
			$manifest,
			$package_file,
			$kit_shell,
			! empty( $args['apply_site_settings'] )
		), $session );

		if ( ! empty( $site_settings['error'] ) ) {
			$failed[] = (string) $site_settings['error'];
		}

		if ( ! empty( $kit_shell['post_id'] ) ) {
			if ( 'skipped' === $kit_shell['action'] ) {
				$skipped++;
			} elseif ( 'updated' === $kit_shell['action'] ) {
				$session->record_updated( 'elementor_library', absint( $kit_shell['post_id'] ) );
			} else {
				$session->record_created( 'elementor_library', absint( $kit_shell['post_id'] ) );

				if ( 'replaced' === $kit_shell['action'] ) {
					$replaced++;
				}
			}

			$processed++;
		}

		if ( ! empty( $args['include_content'] ) ) {
			$processed += $this->import_phase( 'menus', fn() => $this->repair_imported_nav_menus( $session, $manifest, $package_file, $term_id_map, $elementor_session_id ), $session );
		}
		$this->import_phase( 'theme-builder-documents', function () {
			\DirectoristElementor\ElementorV4\ThemeBuilder\ThemeBuilderSupport::get_instance()->refresh_directory_single_documents();
			return true;
		} );
		$post_ids = $this->import_phase( 'mapped-documents', fn() => $this->get_imported_package_post_ids( $session ), $session );
		foreach ( $post_ids as $post_id ) {
			$failed = array_merge( $failed, $this->import_phase(
				'document-' . $post_id,
				fn() => $this->finalize_imported_elementor_documents( $session, $manifest, $term_id_map, $activate_templates, [ $post_id ] ),
				$session
			) );
		}
		$warnings = array_merge( $warnings, $this->import_phase( 'css-audit', fn() => $this->collect_imported_elementor_css_warnings( $session ), $session ) );

		if ( $activate_templates && ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			$warnings[] = __( 'Template conditions were imported but remain inactive because Elementor Pro Theme Builder is unavailable.', 'directorist-elementor' );
		}

		foreach ( $post_ids as $post_id ) {
			$this->import_phase( 'references-' . $post_id, function () use ( $session, $manifest, $post_id, $unmapped_media_ids ) {
				$this->clear_unmapped_elementor_media_ids( $session, $unmapped_media_ids, [ $post_id ] );
				$this->remap_imported_listing_permalink_strings( $session, [ $post_id ] );
				$this->remap_imported_source_site_urls( $session, $manifest, [ $post_id ] );
				$this->remap_imported_menu_item_css_selectors( $session, [ $post_id ] );
				return true;
			}, $session );
		}
		$this->import_phase( 'final-caches', function () use ( $duplicate_media_ids ) {
			if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->files_manager ) && is_object( \Elementor\Plugin::$instance->files_manager ) && method_exists( \Elementor\Plugin::$instance->files_manager, 'clear_cache' ) ) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
			$this->refresh_elementor_theme_builder_conditions_cache();
			update_option( self::DEFERRED_THEME_BUILDER_REFRESH_OPTION, time(), false );
			$this->delete_reconciled_media_duplicates( $duplicate_media_ids );
			flush_rewrite_rules( false );
			return true;
		}, $session );

		if ( ! empty( $args['set_homepage'] ) ) {
			$this->apply_imported_homepage( $session, $manifest );
		}

		$session_media_ids = array_values(
			array_unique(
				array_merge(
					(array) ( $session->get_created()['attachment'] ?? [] ),
					(array) ( $session->get_updated()['attachment'] ?? [] )
				)
			)
		);

		$result = [
			'imported'       => $processed + count( $session_media_ids ),
			'media_imported' => count( $session_media_ids ),
			'skipped'        => $skipped,
			'replaced'       => $replaced,
			'errors'         => array_values( array_filter( array_map( 'sanitize_text_field', $failed ) ) ),
			'warnings'       => $warnings,
			'components'     => $component_import['results'],
			'site_settings'  => $site_settings,
			'forms_restored' => $formgent_restore['restored'],
			'raw'            => $raw_result,
		];

		$this->active_pricing_plan_id_map = [];
		$this->active_import_actions = [];
		$this->active_normalized_elementor_post_ids = [];
		$this->active_component_package_file = '';
		$this->active_author_id_map = [];

		return $result;
	}

	/**
	 * Get a stable empty result for packages without portable components.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_empty_component_import_result(): array {
		return [
			'results'             => [],
			'pricing_plan_id_map' => [],
			'author_id_map'       => [],
			'errors'              => [],
			'warnings'            => [],
			'payload'             => [],
		];
	}

	/**
	 * Import builder-neutral Directorist components from an Elementor package.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $manifest Package manifest.
	 * @param string                           $package_file Package archive.
	 * @param array<string,array<int,int>>     $term_id_map Imported term ID map.
	 * @param string                           $conflict_behavior Conflict behavior.
	 * @return array<string,mixed>
	 */
	protected function import_directorist_components( ImportSession $session, array $manifest, string $package_file, array $term_id_map, string $conflict_behavior ): array {
		$result   = $this->get_empty_component_import_result();
		$metadata = isset( $manifest['directorist']['components'] ) && is_array( $manifest['directorist']['components'] )
			? $manifest['directorist']['components']
			: [];

		if ( empty( $metadata['items'] ) ) {
			return $result;
		}

		$path = sanitize_text_field( (string) ( $metadata['path'] ?? 'directorist/components.json' ) );

		if ( 'directorist/components.json' !== $path ) {
			$result['errors'][] = __( 'The Directorist component package path is invalid.', 'directorist-elementor' );
			return $result;
		}

		$payload = $this->read_zip_json_file( $package_file, $path );

		if ( is_wp_error( $payload ) ) {
			$result['errors'][] = $payload->get_error_message();
			return $result;
		}

		if ( 'directorist-components-v1' !== (string) ( $payload['schema_version'] ?? '' ) ) {
			$result['errors'][] = __( 'The Directorist component package uses an unsupported schema.', 'directorist-elementor' );
			return $result;
		}

		$result['payload'] = isset( $payload['components'] ) && is_array( $payload['components'] )
			? $payload['components']
			: [];
		$source_site_url = (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' );
		$author_import = $this->import_component_authors(
			$session,
			(array) ( $result['payload']['listings']['authors'] ?? [] ),
			[],
			$source_site_url
		);
		$result['author_id_map'] = $author_import['id_map'];
		$result['results']['authors'] = [ 'imported' => $author_import['imported'], 'updated' => $author_import['updated'] ];
		$result['warnings'] = array_merge( $result['warnings'], $author_import['warnings'] );

		$pricing = isset( $payload['components']['pricing_plans'] ) && is_array( $payload['components']['pricing_plans'] )
			? $payload['components']['pricing_plans']
			: [];

		if ( empty( $pricing ) ) {
			return $result;
		}


		if ( ! class_exists( \DirectoristPricingPlan\App\Models\Plan::class ) ) {
			$result['warnings'][] = __( 'Pricing plans were not imported because Directorist Pricing Plans is unavailable.', 'directorist-elementor' );
			return $result;
		}

		require_once __DIR__ . '/PricingPlanTableImporter.php';
		$pricing_result = ( new PricingPlanTableImporter() )->import(
			$pricing,
			[
				'package_id'       => $session->get_package_id(),
				'conflict_behavior' => $conflict_behavior,
				'term_id_map'      => $term_id_map,
			]
		);

		if ( is_wp_error( $pricing_result ) ) {
			$result['errors'][] = $pricing_result->get_error_message();
			return $result;
		}

		$result['results']['pricing_plans'] = $pricing_result;
		$result['warnings'] = array_merge( $result['warnings'], (array) ( $pricing_result['warnings'] ?? [] ) );

		foreach ( (array) ( $pricing_result['plan_id_map'] ?? [] ) as $source_id => $target_id ) {
			$source_id = absint( $source_id );
			$target_id = absint( $target_id );

			if ( $source_id > 0 && $target_id > 0 ) {
				$result['pricing_plan_id_map'][ $source_id ] = $target_id;
			}
		}

		return $result;
	}

	/**
	 * Import Directorist components that require final post and term mappings.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $components Component payload.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param array<int,int>                   $media_id_map Imported media map.
	 * @param string                           $conflict_behavior Conflict behavior.
	 * @param string                           $source_site_url Source site URL.
	 * @return array<string,mixed>
	 */
	protected function import_directorist_content_components( ImportSession $session, array $components, array $term_id_map, array $media_id_map, string $conflict_behavior, string $source_site_url, bool $apply_site_settings ): array {
		$result = [
			'results'      => [],
			'errors'       => [],
			'warnings'     => [],
			'media_id_map' => $media_id_map,
			'author_id_map'=> [],
		];

		if ( ! empty( $components['directory_types'] ) && is_array( $components['directory_types'] ) ) {
			$directory_result = $this->import_directory_types_component(
				$session,
				$components['directory_types'],
				$term_id_map,
				$result['media_id_map'],
				$conflict_behavior,
				$source_site_url,
				$apply_site_settings,
				(string) ( $this->active_component_package_file ?? '' )
			);
			$result['media_id_map'] = $directory_result['media_id_map'];
			$result['errors'] = array_merge( $result['errors'], $directory_result['errors'] );
			$result['warnings'] = array_merge( $result['warnings'], $directory_result['warnings'] );
			$result['results']['directory_types'] = $directory_result['result'];
		}

		if ( ! empty( $components['listings'] ) && is_array( $components['listings'] ) ) {
			$listings_result = $this->import_listings_component(
				$session,
				$components['listings'],
				$term_id_map,
				$result['media_id_map'],
				$conflict_behavior,
				$source_site_url,
				(string) ( $this->active_component_package_file ?? '' )
			);
			$result['media_id_map'] = $listings_result['media_id_map'];
			$result['author_id_map'] = (array) ( $listings_result['author_id_map'] ?? [] );
			$result['errors'] = array_merge( $result['errors'], $listings_result['errors'] );
			$result['warnings'] = array_merge( $result['warnings'], $listings_result['warnings'] );
			$result['results']['listings'] = $listings_result['result'];
		}

		if ( ! empty( $components['site_settings'] ) && is_array( $components['site_settings'] ) ) {
			$result['results']['site_settings'] = $this->import_directorist_site_settings_component(
				$session,
				$components['site_settings'],
				$term_id_map,
				$source_site_url,
				$apply_site_settings
			);
		}

		return $result;
	}

	/**
	 * Apply portable Directorist site settings after post and term mappings exist.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $component Site-settings component.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param string                           $source_site_url Source site URL.
	 * @param bool                             $apply Whether site settings were selected.
	 * @return array<string,mixed>
	 */
	protected function import_directorist_site_settings_component( ImportSession $session, array $component, array $term_id_map, string $source_site_url, bool $apply ): array {
		$result = [
			'applied' => false,
			'status'  => $apply ? 'unchanged' : 'preserved',
			'updated' => 0,
		];

		if ( ! $apply || 'directorist-site-settings-v1' !== (string) ( $component['schema_version'] ?? '' ) ) {
			return $result;
		}

		$settings     = isset( $component['settings'] ) && is_array( $component['settings'] ) ? $component['settings'] : [];
		$page_id_keys = array_fill_keys( array_map( 'sanitize_key', (array) ( $component['page_id_keys'] ?? [] ) ), true );
		$options      = (array) get_option( 'atbdp_option', [] );
		$updated      = 0;
		$rewrite      = false;

		foreach ( $settings as $source_key => $value ) {
			$source_key = sanitize_key( (string) $source_key );

			if ( $this->is_protected_directorist_site_setting_key( $source_key ) ) {
				continue;
			}

			$key = $this->remap_directorist_site_setting_key( $source_key, $term_id_map );

			if ( '' === $key ) {
				continue;
			}

			if ( isset( $page_id_keys[ sanitize_key( (string) $source_key ) ] ) ) {
				$source_page_id = absint( $value );

				if ( $source_page_id > 0 ) {
					$mapped_page_id = $this->get_mapped_post_id( $session, 'page', (string) $source_page_id );

					if ( ! $mapped_page_id ) {
						continue;
					}

					$value = is_string( $value ) ? (string) $mapped_page_id : $mapped_page_id;
				}
			} elseif ( 'atbdp_default_derectory' === $source_key ) {
				$mapped_directory_id = absint( $term_id_map['atbdp_listing_types'][ absint( $value ) ] ?? 0 );

				if ( $mapped_directory_id <= 0 ) {
					continue;
				}

				$value = is_string( $value ) ? (string) $mapped_directory_id : $mapped_directory_id;
			}

			$value = $this->replace_source_site_url_in_value( $value, $source_site_url );

			if ( array_key_exists( $key, $options ) && $options[ $key ] === $value ) {
				continue;
			}

			$options[ $key ] = $value;
			$updated++;

			if ( in_array( $key, [ 'atbdp_listing_slug', 'category_base', 'location_base', 'tag_base', 'single_listing_slug_with_directory_type' ], true ) ) {
				$rewrite = true;
			}
		}

		if ( $updated > 0 ) {
			update_option( 'atbdp_option', $options );
		}

		$wordpress = isset( $component['wordpress'] ) && is_array( $component['wordpress'] ) ? $component['wordpress'] : [];
		foreach ( [ 'blogname', 'blogdescription' ] as $option_name ) {
			if ( array_key_exists( $option_name, $wordpress ) ) {
				$value = sanitize_text_field( (string) $wordpress[ $option_name ] );
				if ( (string) get_option( $option_name, '' ) !== $value ) {
					update_option( $option_name, $value );
					$updated++;
				}
			}
		}
		if ( array_key_exists( 'permalink_structure', $wordpress ) ) {
			$permalink = (string) $wordpress['permalink_structure'];
			if ( (string) get_option( 'permalink_structure', '' ) !== $permalink ) {
				update_option( 'permalink_structure', $permalink );
				$updated++;
				$rewrite = true;
			}
		}
		if ( is_ssl() ) {
			foreach ( [ 'home', 'siteurl' ] as $option_name ) {
				$current_url = (string) get_option( $option_name, '' );
				if ( '' !== $current_url && 'https' !== wp_parse_url( $current_url, PHP_URL_SCHEME ) ) {
					update_option( $option_name, set_url_scheme( $current_url, 'https' ) );
					$updated++;
				}
			}
		}
		if ( $rewrite ) {
			flush_rewrite_rules( false );
		}
		$this->prune_unowned_installer_page_duplicates( $options );
		if ( $updated > 0 ) {
			$session->log( 'info', __( 'Restored portable Directorist and WordPress site settings.', 'directorist-elementor' ), [ 'settings' => $updated ] );
		}

		$result['applied'] = true;
		$result['status']  = 'applied';
		$result['updated'] = $updated;

		return $result;
	}

	/** Remove shortcode-only installer pages regenerated beside imported owned pages. */
	protected function prune_unowned_installer_page_duplicates( array $options ): void {
		$defaults = [
			'search_listing' => '[directorist_search_listing]', 'search_result_page' => '[directorist_search_result]',
			'add_listing_page' => '[directorist_add_listing]', 'all_listing_page' => '[directorist_all_listing]',
			'single_category_page' => '[directorist_category]', 'single_location_page' => '[directorist_location]',
			'single_tag_page' => '[directorist_tag]', 'author_profile_page' => '[directorist_author_profile]',
			'user_dashboard' => '[directorist_user_dashboard]', 'signin_signup_page' => '[directorist_signin_signup]',
			'booking_confirmation' => '[directorist_booking_confirmation]',
		];
		foreach ( $defaults as $option_key => $shortcode ) {
			$keep_id = absint( $options[ $option_key ] ?? 0 );
			if ( $keep_id <= 0 ) { continue; }
			foreach ( get_posts( [ 'post_type' => 'page', 'post_status' => 'any', 'posts_per_page' => -1, 's' => trim( $shortcode, '[]' ) ] ) as $page ) {
				if ( (int) $page->ID === $keep_id || trim( (string) $page->post_content ) !== $shortcode || '' !== (string) get_post_meta( $page->ID, '_directorist_import_package_id', true ) || $page->post_date !== $page->post_modified ) { continue; }
				wp_delete_post( $page->ID, true );
			}
		}
	}

	/**
	 * Remap directory-scoped option keys such as directory_schema_type_30.
	 *
	 * @param string                           $key Source option key.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @return string
	 */
	protected function remap_directorist_site_setting_key( string $key, array $term_id_map ): string {
		if ( 1 !== preg_match( '/^(directory_schema_type_)(\d+)$/', $key, $matches ) ) {
			return $key;
		}

		$target_id = absint( $term_id_map['atbdp_listing_types'][ absint( $matches[2] ) ] ?? 0 );

		return $target_id > 0 ? $matches[1] . $target_id : '';
	}

	/**
	 * Keep destination credentials and runtime bookkeeping out of site imports.
	 *
	 * @param string $key Directorist option key.
	 * @return bool
	 */
	protected function is_protected_directorist_site_setting_key( string $key ): bool {
		$protected = [
			'admin_email_lists',
			'atbdp_reset_cache',
			'bank_transfer_instruction',
			'directorist_installed_event_key',
			'directorist_updated_event_key',
			'email_from_email',
			'paypal_gateway_email',
			'reg_email',
			'regenerate_pages',
			'shortcode-updated',
		];

		if ( in_array( $key, $protected, true ) ) {
			return true;
		}

		return 1 === preg_match( '/(?:password|api_?key|secret|token|license)/i', $key );
	}

	/**
	 * Directorist queries one _default term; imported settings must not leave two.
	 */
	protected function restore_default_directory( int $directory_id ): void {
		$directory = $directory_id > 0 ? get_term( $directory_id, 'atbdp_listing_types' ) : null;
		if ( ! $directory instanceof \WP_Term ) {
			return;
		}

		$defaults = get_terms( [
			'taxonomy' => 'atbdp_listing_types',
			'hide_empty' => false,
			'meta_key' => '_default',
			'meta_value' => '1',
			'fields' => 'ids',
		] );
		if ( is_wp_error( $defaults ) ) {
			return;
		}
		foreach ( $defaults as $term_id ) {
			if ( (int) $term_id !== $directory_id ) {
				update_term_meta( (int) $term_id, '_default', '0' );
			}
		}
		update_term_meta( $directory_id, '_default', '1' );
	}

	/**
	 * Restore complete directory-builder settings from the package.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $component Directory component.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param array<int,int>                   $media_id_map Imported media map.
	 * @param string                           $conflict_behavior Conflict behavior.
	 * @param string                           $source_site_url Source site URL.
	 * @return array<string,mixed>
	 */
	protected function import_directory_types_component( ImportSession $session, array $component, array $term_id_map, array $media_id_map, string $conflict_behavior, string $source_site_url, bool $apply_site_settings = true, string $package_file = '' ): array {
		$output = [
			'result'       => [
				'imported' => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'settings' => 0,
				'terms'    => 0,
			],
			'errors'       => [],
			'warnings'     => [],
			'media_id_map' => $media_id_map,
			'author_id_map'=> [],
		];

		if ( 'directorist-directory-types-v1' !== (string) ( $component['schema_version'] ?? '' ) ) {
			$output['errors'][] = __( 'The Directorist directory component uses an unsupported schema.', 'directorist-elementor' );
			return $output;
		}

		$output['media_id_map'] = $this->import_component_media(
			$session,
			(array) ( $component['media'] ?? [] ),
			$output['media_id_map'],
			$output['warnings'],
			$package_file
		);
		$default_directory_id = 0;
		foreach ( (array) ( $component['directories'] ?? [] ) as $directory ) {
			if ( ! is_array( $directory ) ) {
				continue;
			}

			$source_id = absint( $directory['source_id'] ?? 0 );
			$target_id = absint( $term_id_map['atbdp_listing_types'][ $source_id ] ?? 0 );

			if ( $source_id <= 0 || $target_id <= 0 || ! get_term( $target_id, 'atbdp_listing_types' ) ) {
				$output['warnings'][] = sprintf(
					/* translators: %d: source directory ID. */
					__( 'Directory settings for source directory %d could not be mapped.', 'directorist-elementor' ),
					$source_id
				);
				continue;
			}

			$owner = (string) get_term_meta( $target_id, '_directorist_import_package_id', true );

			if ( 'skip' === $conflict_behavior && '' !== $owner && $session->get_package_id() !== $owner ) {
				$output['result']['skipped']++;
				continue;
			}

			foreach ( (array) ( $directory['meta'] ?? [] ) as $meta_key => $value ) {
				$meta_key = sanitize_key( (string) $meta_key );

				if ( '' === $meta_key || $this->is_import_bookkeeping_meta_key( $meta_key ) || ( '_default' === $meta_key && ! $apply_site_settings ) ) {
					continue;
				}

				$this->restore_directory_component_term_meta(
					$session,
					'atbdp_listing_types',
					$target_id,
					$meta_key,
					$value,
					$term_id_map,
					$output['media_id_map'],
					$source_site_url
				);
			}

			update_term_meta( $target_id, '_directorist_import_package_id', $session->get_package_id() );
			update_term_meta( $target_id, '_directorist_import_source_id', (string) $source_id );
			$session->map_id( 'term_source_id:atbdp_listing_types', (string) $source_id, $target_id );

			if ( $apply_site_settings && '1' === (string) get_term_meta( $target_id, '_default', true ) ) {
				$default_directory_id = $target_id;
			}

			if ( $session->get_package_id() === $owner ) {
				$output['result']['updated']++;
			} else {
				$output['result']['imported']++;
			}
		}

		$this->restore_default_directory( $default_directory_id );
		foreach ( (array) ( $component['taxonomy_terms'] ?? [] ) as $taxonomy => $terms ) {
			$taxonomy = sanitize_key( (string) $taxonomy );

			if ( empty( $term_id_map[ $taxonomy ] ) || ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				if ( ! is_array( $term ) ) {
					continue;
				}

				$source_id = absint( $term['source_id'] ?? 0 );
				$target_id = absint( $term_id_map[ $taxonomy ][ $source_id ] ?? 0 );

				if ( $source_id <= 0 || $target_id <= 0 || ! get_term( $target_id, $taxonomy ) ) {
					continue;
				}

				foreach ( (array) ( $term['meta'] ?? [] ) as $source_meta_key => $value ) {
					$source_meta_key = sanitize_key( (string) $source_meta_key );

					if ( '' === $source_meta_key || $this->is_import_bookkeeping_meta_key( $source_meta_key ) ) {
						continue;
					}

					$this->restore_directory_component_term_meta(
						$session,
						$taxonomy,
						$target_id,
						$source_meta_key,
						$value,
						$term_id_map,
						$output['media_id_map'],
						$source_site_url
					);
				}

				update_term_meta( $target_id, '_directorist_import_package_id', $session->get_package_id() );
				update_term_meta( $target_id, '_directorist_import_source_id', (string) $source_id );
				$output['result']['terms']++;
			}
		}

		$output['result']['settings'] = $this->import_directory_component_settings(
			$session,
			(array) ( $component['settings'] ?? [] ),
			$term_id_map
		);

		return $output;
	}

	/**
	 * Restore one logical term-meta field, including repeated database rows.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param string                           $taxonomy Taxonomy.
	 * @param int                              $target_id Target term ID.
	 * @param string                           $source_meta_key Source metadata key.
	 * @param mixed                            $value Exported metadata value.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param array<int,int>                   $media_id_map Imported media map.
	 * @param string                           $source_site_url Source site URL.
	 * @return void
	 */
	protected function restore_directory_component_term_meta( ImportSession $session, string $taxonomy, int $target_id, string $source_meta_key, $value, array $term_id_map, array $media_id_map, string $source_site_url ): void {
		$meta_key = $this->remap_directory_component_meta_key( $source_meta_key, $term_id_map );
		$is_multi = is_array( $value ) && array_key_exists( self::COMPONENT_META_VALUES_KEY, $value );
		$values   = $is_multi ? (array) $value[ self::COMPONENT_META_VALUES_KEY ] : [ $value ];
		$restored = [];

		foreach ( $values as $meta_value ) {
			$meta_value = $this->remap_directory_component_meta_value(
				$session,
				$taxonomy,
				$source_meta_key,
				$meta_value,
				$term_id_map,
				$media_id_map
			);
			$restored[] = $this->replace_source_site_url_in_value( $meta_value, $source_site_url );
		}

		if ( ! $is_multi ) {
			update_term_meta( $target_id, $meta_key, wp_slash( $restored[0] ?? '' ) );
			return;
		}

		delete_term_meta( $target_id, $meta_key );

		foreach ( $restored as $meta_value ) {
			add_term_meta( $target_id, $meta_key, wp_slash( $meta_value ) );
		}
	}

	/**
	 * Restore listing metadata, media references, and review metadata.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $component Listings component.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param array<int,int>                   $media_id_map Imported media map.
	 * @param string                           $conflict_behavior Conflict behavior.
	 * @param string                           $source_site_url Source site URL.
	 * @return array<string,mixed>
	 */
	protected function import_listings_component( ImportSession $session, array $component, array $term_id_map, array $media_id_map, string $conflict_behavior, string $source_site_url, string $package_file = '' ): array {
		$output = [
			'result'       => [
				'imported' => 0,
				'updated'  => 0,
				'skipped'  => 0,
				'comments' => 0,
			],
			'errors'       => [],
			'warnings'     => [],
			'media_id_map' => $media_id_map,
		];

		if ( 'directorist-listings-v1' !== (string) ( $component['schema_version'] ?? '' ) ) {
			$output['errors'][] = __( 'The Directorist listings component uses an unsupported schema.', 'directorist-elementor' );
			return $output;
		}

		$output['media_id_map'] = $this->import_component_media(
			$session,
			(array) ( $component['media'] ?? [] ),
			$output['media_id_map'],
			$output['warnings'],
			$package_file
		);
		$author_import = $this->import_component_authors(
			$session,
			(array) ( $component['authors'] ?? [] ),
			$output['media_id_map'],
			$source_site_url
		);
		$author_id_map = $author_import['id_map'];
		$output['author_id_map'] = $author_id_map;
		$output['result']['authors'] = [ 'imported' => $author_import['imported'], 'updated' => $author_import['updated'] ];
		$output['warnings'] = array_merge( $output['warnings'], $author_import['warnings'] );

		foreach ( (array) ( $component['listings'] ?? [] ) as $listing ) {
			if ( ! is_array( $listing ) ) {
				continue;
			}

			$source_id = (string) absint( $listing['source_id'] ?? 0 );
			$target_id = absint( $this->get_mapped_post_id( $session, 'at_biz_dir', $source_id ) );
			$action = (string) ( $this->active_import_actions['at_biz_dir'][ $source_id ] ?? 'updated' );

			if ( $target_id <= 0 || 'at_biz_dir' !== get_post_type( $target_id ) ) {
				$output['warnings'][] = sprintf(
					/* translators: %s: source listing ID. */
					__( 'Listing metadata for source listing %s could not be mapped.', 'directorist-elementor' ),
					$source_id
				);
				continue;
			}

			if ( 'skipped' === $action || ( 'skip' === $conflict_behavior && 'created' !== $action ) ) {
				$output['result']['skipped']++;
				continue;
			}

			$source_author_id = absint( $listing['author_source_id'] ?? 0 );
			$target_author_id = absint( $author_id_map[ $source_author_id ] ?? 0 );
			if ( $target_author_id > 0 && (int) get_post_field( 'post_author', $target_id ) !== $target_author_id ) {
				wp_update_post( [ 'ID' => $target_id, 'post_author' => $target_author_id ] );
			}

			$media_meta_keys = array_values(
				array_filter(
					array_map( 'sanitize_key', (array) ( $listing['media_meta_keys'] ?? [] ) )
				)
			);

			foreach ( (array) ( $listing['meta'] ?? [] ) as $meta_key => $values ) {
				$meta_key = sanitize_key( (string) $meta_key );

				if ( '' === $meta_key || '_plan_id' === $meta_key || $this->is_import_bookkeeping_meta_key( $meta_key ) ) {
					continue;
				}

				$values = is_array( $values ) ? $values : [ $values ];
				delete_post_meta( $target_id, $meta_key );

				foreach ( $values as $value ) {
					if ( '_directory_type' === $meta_key ) {
						$source_directory = absint( $value );
						$value = absint( $term_id_map['atbdp_listing_types'][ $source_directory ] ?? 0 );
						} elseif ( in_array( $meta_key, $media_meta_keys, true ) ) {
							$value = $this->remap_component_media_value( $value, $output['media_id_map'] );
						}

						$value = $this->replace_source_site_url_in_value( $value, $source_site_url );

						if ( in_array( $meta_key, $media_meta_keys, true ) && ( '' === $value || 0 === $value || [] === $value ) ) {
						continue;
					}

					add_post_meta( $target_id, $meta_key, wp_slash( $value ) );
				}
			}

			$output['result']['comments'] += $this->restore_listing_component_comments(
				$session,
					$target_id,
					absint( $source_id ),
					(array) ( $listing['comments'] ?? [] ),
				$source_site_url
				);
			// Recompute after rating comment meta is in place; insert hooks run too early.
			if ( class_exists( '\\Directorist\\Review\\Comment' ) && method_exists( '\\Directorist\\Review\\Comment', 'maybe_clear_transients' ) ) {
				\Directorist\Review\Comment::maybe_clear_transients( $target_id );
			}
			if ( function_exists( 'directorist_user_package_repository' ) || is_array( $listing['pricing_package'] ?? null ) ) {
				require_once __DIR__ . '/PricingPlanTableImporter.php';
				$restored_package = ( new PricingPlanTableImporter() )->ensure_listing_package( $target_id, $listing, $this->active_pricing_plan_id_map, $session->get_package_id() );
				if ( is_wp_error( $restored_package ) ) {
					$level = 'directorist_import_demo_plan_unavailable' === $restored_package->get_error_code() ? 'warnings' : 'errors';
					$output[ $level ][] = sprintf(
						/* translators: 1: listing ID, 2: assignment warning or error. */
						__( 'Listing #%1$d: %2$s', 'directorist-elementor' ), $target_id, $restored_package->get_error_message()
					);
				} else {
					$output['result']['plan_assignments'][] = $restored_package;
				}
			}
			$this->clear_elementor_cache_for_post( $target_id );

			if ( 'created' === $action || 'replaced' === $action ) {
				$output['result']['imported']++;
			} else {
				$output['result']['updated']++;
			}
		}

		return $output;
	}

	/**
	 * Validate a portable WordPress UTC registration date without normalizing it.
	 * Missing, zero and malformed dates retain WordPress's default/current date.
	 */
	private function get_portable_author_registered_date( $value ): string {
		if ( ! is_string( $value ) || ! preg_match( '/\\A([1-9][0-9]{3})-([0-9]{2})-([0-9]{2}) ([0-9]{2}):([0-9]{2}):([0-9]{2})\\z/', $value, $parts ) ) {
			return '';
		}
		if ( ! checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) || (int) $parts[4] > 23 || (int) $parts[5] > 59 || (int) $parts[6] > 59 ) {
			return '';
		}
		return $value;
	}

	/** Import safe package-owned listing authors. */
	protected function import_component_authors( ImportSession $session, array $authors, array $media_id_map, string $source_site_url ): array {
		$result = [ 'id_map' => [], 'imported' => 0, 'updated' => 0, 'warnings' => [] ];
		foreach ( $authors as $key => $author ) {
			if ( ! is_array( $author ) ) {
				continue;
			}
			$source_id = absint( $author['source_id'] ?? $key );
			if ( $source_id <= 0 ) {
				continue;
			}
			$uid = sanitize_key( $session->get_package_id() ) . ':user:' . $source_id;
			$registered = $this->get_portable_author_registered_date( $author['user_registered'] ?? null );
			$ids = get_users( [ 'meta_key' => '_directorist_import_source_uid', 'meta_value' => $uid, 'number' => 1, 'fields' => 'ids' ] );
			$user_id = absint( $ids[0] ?? 0 );
			$created = false;
			if ( $user_id <= 0 ) {
				$base_login = sanitize_user( (string) ( $author['login'] ?? '' ), true );
				$base_login = '' !== $base_login ? $base_login : 'directorist-author-' . $source_id;
				$login = username_exists( $base_login ) ? $base_login . '-' . substr( sha1( $uid ), 0, 8 ) : $base_login;
				$user_data = [
					'user_login'   => $login,
					'user_pass'    => wp_generate_password( 32, true, true ),
					'user_email'   => sanitize_email( $login . '+' . substr( sha1( $uid ), 0, 12 ) . '@example.invalid' ),
					'display_name' => sanitize_text_field( (string) ( $author['display_name'] ?? $base_login ) ),
					'user_url'     => esc_url_raw( (string) $this->replace_source_site_url_in_value( (string) ( $author['user_url'] ?? '' ), $source_site_url ) ),
					'role'         => 'subscriber',
				];
				if ( '' !== $registered ) {
					$user_data['user_registered'] = $registered;
				}
				$user_id = wp_insert_user( $user_data );
				if ( is_wp_error( $user_id ) ) {
					$result['warnings'][] = $user_id->get_error_message();
					continue;
				}
				$user_id = absint( $user_id );
				$created = true;
			} else {
				$user_data = [
					'ID'           => $user_id,
					'display_name' => sanitize_text_field( (string) ( $author['display_name'] ?? '' ) ),
					'user_url'     => esc_url_raw( (string) $this->replace_source_site_url_in_value( (string) ( $author['user_url'] ?? '' ), $source_site_url ) ),
				];
				// Restore dates only for demo users owned by this package, never by login.
				if ( '' !== $registered && sanitize_key( $session->get_package_id() ) === get_user_meta( $user_id, '_directorist_import_package_id', true ) ) {
					$user_data['user_registered'] = $registered;
				}
				wp_update_user( $user_data );
			}
			if ( is_multisite() && ! is_user_member_of_blog( $user_id, get_current_blog_id() ) ) {
				add_user_to_blog( get_current_blog_id(), $user_id, 'subscriber' );
			}
			update_user_meta( $user_id, '_directorist_import_source_uid', $uid );
			update_user_meta( $user_id, '_directorist_import_package_id', sanitize_key( $session->get_package_id() ) );
			foreach ( (array) ( $author['meta'] ?? [] ) as $meta_key => $value ) {
				$meta_key = sanitize_key( (string) $meta_key );
				if ( ! in_array( $meta_key, [ 'nickname', 'first_name', 'last_name', 'description', 'address', 'atbdp_phone', 'atbdp_facebook', 'atbdp_twitter', 'atbdp_linkedin', 'atbdp_youtube', 'pro_pic' ], true ) ) {
					continue;
				}
				if ( 'pro_pic' === $meta_key ) {
					$value = absint( $media_id_map[ absint( $value ) ] ?? 0 );
				}
				update_user_meta( $user_id, $meta_key, is_scalar( $value ) ? (string) $value : '' );
			}
			$session->map_id( 'user', (string) $source_id, $user_id );
			$result['id_map'][ $source_id ] = $user_id;
			$result[ $created ? 'imported' : 'updated' ]++;
		}
		return $result;
	}

	/**
	 * Import media declared by a portable Directorist component.
	 *
	 * @param ImportSession                  $session Import session.
	 * @param array<int|string,mixed>        $items Media descriptors.
	 * @param array<int,int>                 $media_id_map Existing media map.
	 * @param array<int,string>              $warnings Import warnings.
	 * @return array<int,int>
	 */
	protected function import_component_media( ImportSession $session, array $items, array $media_id_map, array &$warnings, string $package_file = '' ): array {
		foreach ( $items as $key => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$source_id = absint( $item['source_id'] ?? $key );
			$source_url = esc_url_raw( (string) ( $item['url'] ?? '' ) );

			if ( $source_id <= 0 || '' === $source_url ) {
				continue;
			}

			$local_id = absint( $media_id_map[ $source_id ] ?? 0 );

			if ( $local_id <= 0 || 'attachment' !== get_post_type( $local_id ) ) {
				$local_id = $this->import_phase(
					'component-packaged-media-' . $source_id . '-' . md5( (string) ( $item['package_path'] ?? '' ) ),
					fn() => $this->import_packaged_media_attachment( $session, $source_id, $source_url, $item, $package_file ),
					$session
				);
			}

			if ( $local_id <= 0 || 'attachment' !== get_post_type( $local_id ) ) {
				$local_id = $this->import_phase( 'component-media-' . $source_id . '-' . md5( $source_url ), fn() => $this->import_remote_media_attachment( $session, $source_id, $source_url ), $session );
			}

			if ( $local_id <= 0 ) {
				$warnings[] = sprintf(
					/* translators: %d: source attachment ID. */
					__( 'Media attachment %d could not be imported from the source package.', 'directorist-elementor' ),
					$source_id
				);
				continue;
			}

			$media_id_map[ $source_id ] = $local_id;

			if ( ! empty( $item['alt'] ) ) {
				update_post_meta( $local_id, '_wp_attachment_image_alt', sanitize_text_field( (string) $item['alt'] ) );
			}
		}

		return $media_id_map;
	}

	/**
	 * Restore the global settings required for imported directory behavior.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $settings Source settings.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @return int
	 */
	protected function import_directory_component_settings( ImportSession $session, array $settings, array $term_id_map ): int {
		$allowed = [
			'enable_multi_directory',
			'atbdp_default_derectory',
			'single_listing_slug_with_directory_type',
			'atbdp_listing_slug',
			'disable_single_listing',
			'single_listing_template',
		];
		$changed = 0;
		$rewrite_changed = false;

		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $settings ) || null === $settings[ $key ] ) {
				continue;
			}

			$value = $settings[ $key ];

			if ( 'atbdp_default_derectory' === $key ) {
				$value = absint( $term_id_map['atbdp_listing_types'][ absint( $value ) ] ?? 0 );

				if ( $value <= 0 ) {
					continue;
				}
			} elseif ( in_array( $key, [ 'enable_multi_directory', 'single_listing_slug_with_directory_type', 'disable_single_listing' ], true ) ) {
				$value = (bool) $value;
			} elseif ( 'atbdp_listing_slug' === $key ) {
				$value = sanitize_title( (string) $value );
			} else {
				$value = sanitize_key( (string) $value );
			}

			$previous = function_exists( 'get_directorist_option' )
				? get_directorist_option( $key, null )
				: ( (array) get_option( 'atbdp_option', [] ) )[ $key ] ?? null;

			if ( $previous === $value ) {
				continue;
			}

			if ( function_exists( 'update_directorist_option' ) ) {
				update_directorist_option( $key, $value );
			} else {
				$options = (array) get_option( 'atbdp_option', [] );
				$options[ $key ] = $value;
				update_option( 'atbdp_option', $options );
			}

			if ( in_array( $key, [ 'enable_multi_directory', 'single_listing_slug_with_directory_type', 'atbdp_listing_slug' ], true ) ) {
				$rewrite_changed = true;
			}

			$changed++;
		}

		if ( $rewrite_changed ) {
			flush_rewrite_rules( false );
		}

		if ( $changed > 0 ) {
			$session->log( 'info', __( 'Restored Directorist directory settings.', 'directorist-elementor' ), [ 'settings' => $changed ] );
		}

		return $changed;
	}

	/**
	 * Remap a directory or taxonomy metadata key containing a directory ID.
	 *
	 * @param string                           $meta_key Source metadata key.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @return string
	 */
	protected function remap_directory_component_meta_key( string $meta_key, array $term_id_map ): string {
		if ( preg_match( '/^_directory_type_(\d+)$/', $meta_key, $matches ) ) {
			$mapped_id = absint( $term_id_map['atbdp_listing_types'][ absint( $matches[1] ) ] ?? 0 );

			if ( $mapped_id > 0 ) {
				return '_directory_type_' . $mapped_id;
			}
		}

		return $meta_key;
	}

	/**
	 * Remap IDs stored inside directory and taxonomy metadata.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param string                           $taxonomy Taxonomy.
	 * @param string                           $meta_key Metadata key.
	 * @param mixed                            $value Metadata value.
	 * @param array<string,array<int,int>>     $term_id_map Imported term map.
	 * @param array<int,int>                   $media_id_map Imported media map.
	 * @return mixed
	 */
	protected function remap_directory_component_meta_value( ImportSession $session, string $taxonomy, string $meta_key, $value, array $term_id_map, array $media_id_map ) {
		if ( '_directory_type' === $meta_key ) {
			$was_array = is_array( $value );
			$values = $was_array ? $value : [ $value ];
			$remapped = [];

			foreach ( $values as $source_id ) {
				$target_id = absint( $term_id_map['atbdp_listing_types'][ absint( $source_id ) ] ?? 0 );

				if ( $target_id > 0 ) {
					$remapped[] = $target_id;
				}
			}

			return $was_array ? array_values( array_unique( $remapped ) ) : ( $remapped[0] ?? '' );
		}

		if ( in_array( $meta_key, [ 'image', 'preview_image' ], true ) ) {
			return $this->remap_component_media_value( $value, $media_id_map );
		}

		if ( 'atbdp_listing_types' === $taxonomy && 'single_listing_page' === $meta_key ) {
			$mapped_id = absint( $this->get_mapped_post_id( $session, 'page', (string) absint( $value ) ) );
			return $mapped_id > 0 ? $mapped_id : '';
		}

		return $value;
	}

	/**
	 * Strictly remap media IDs from component data.
	 *
	 * @param mixed          $value Source value.
	 * @param array<int,int> $media_id_map Media map.
	 * @return mixed
	 */
	protected function remap_component_media_value( $value, array $media_id_map ) {
		if ( is_array( $value ) ) {
			$remapped = [];

			foreach ( $value as $key => $item ) {
				$mapped = $this->remap_component_media_value( $item, $media_id_map );

				if ( '' !== $mapped && 0 !== $mapped && [] !== $mapped ) {
					$remapped[ $key ] = $mapped;
				}
			}

			return $remapped;
		}

		$source_id = absint( $value );

		if ( $source_id <= 0 ) {
			return $value;
		}

		return absint( $media_id_map[ $source_id ] ?? 0 );
	}

	/**
	 * Restore real review metadata and establish stable comment identities.
	 *
	 * @param ImportSession              $session Import session.
	 * @param int                        $listing_id Target listing ID.
	 * @param int                        $source_listing_id Source listing ID.
	 * @param array<int,mixed>           $comments Source comments.
	 * @param string                     $source_site_url Source site URL.
	 * @return int
	 */
	protected function restore_listing_component_comments( ImportSession $session, int $listing_id, int $source_listing_id, array $comments, string $source_site_url ): int {
		$restored = 0;
		$comment_map = [];
		$pending_parents = [];
		$used_comment_ids = [];

		foreach ( $comments as $comment ) {
			if ( ! is_array( $comment ) ) {
				continue;
			}

			$source_id = absint( $comment['source_id'] ?? 0 );

			if ( $source_id <= 0 ) {
				continue;
			}

			$source_uid = $session->get_package_id() . ':comment:' . $source_listing_id . ':' . $source_id;
			$comment_id = $this->find_imported_listing_comment( $listing_id, $source_uid, $comment, $used_comment_ids );
			$data = [
				'comment_post_ID'      => $listing_id,
				'comment_author'       => sanitize_text_field( (string) ( $comment['author'] ?? '' ) ),
				'comment_author_email' => sanitize_email( (string) ( $comment['author_email'] ?? '' ) ),
				'comment_author_url'   => esc_url_raw( (string) ( $comment['author_url'] ?? '' ) ),
				'comment_author_IP'    => sanitize_text_field( (string) ( $comment['author_ip'] ?? '' ) ),
				'comment_date'         => sanitize_text_field( (string) ( $comment['date'] ?? '' ) ),
				'comment_date_gmt'     => sanitize_text_field( (string) ( $comment['date_gmt'] ?? '' ) ),
				'comment_content'      => (string) $this->replace_source_site_url_in_value( (string) ( $comment['content'] ?? '' ), $source_site_url ),
				'comment_approved'     => sanitize_text_field( (string) ( $comment['approved'] ?? '1' ) ),
				'comment_type'         => sanitize_key( (string) ( $comment['type'] ?? 'review' ) ),
				'comment_parent'       => 0,
			];

			if ( $comment_id > 0 ) {
				$data['comment_ID'] = $comment_id;
				wp_update_comment( wp_slash( $data ) );
			} else {
				$comment_id = wp_insert_comment( wp_slash( $data ) );
			}

			$comment_id = absint( $comment_id );

			if ( $comment_id <= 0 ) {
				continue;
			}

			$used_comment_ids[] = $comment_id;
			$comment_map[ $source_id ] = $comment_id;
			$pending_parents[ $source_id ] = absint( $comment['parent_source_id'] ?? 0 );

				foreach ( (array) ( $comment['meta'] ?? [] ) as $meta_key => $values ) {
				$meta_key = sanitize_key( (string) $meta_key );

				if ( '' === $meta_key || $this->is_import_bookkeeping_meta_key( $meta_key ) ) {
					continue;
				}

				delete_comment_meta( $comment_id, $meta_key );

					foreach ( (array) $values as $value ) {
						$value = $this->replace_source_site_url_in_value( $value, $source_site_url );
						add_comment_meta( $comment_id, $meta_key, wp_slash( $value ) );
					}
			}

			update_comment_meta( $comment_id, '_directorist_import_source_uid', $source_uid );
			update_comment_meta( $comment_id, '_directorist_import_package_id', $session->get_package_id() );
			update_comment_meta( $comment_id, '_directorist_import_package_version', $session->get_package_version() );
			if ( array_key_exists( 'advanced_ratings', $comment ) ) {
				$this->restore_advanced_review_ratings( $comment_id, $listing_id, (array) $comment['advanced_ratings'] );
			}
			$restored++;
		}

		foreach ( $pending_parents as $source_id => $source_parent_id ) {
			if ( $source_parent_id <= 0 || empty( $comment_map[ $source_id ] ) || empty( $comment_map[ $source_parent_id ] ) ) {
				continue;
			}

			wp_update_comment(
				[
					'comment_ID'     => $comment_map[ $source_id ],
					'comment_parent' => $comment_map[ $source_parent_id ],
				]
			);
		}

		if ( $restored === count( $comments ) ) {
			$owned = get_comments( [
				'post_id' => $listing_id,
				'status' => 'all',
				'meta_key' => '_directorist_import_package_id',
				'meta_value' => $session->get_package_id(),
				'fields' => 'ids',
			] );
			foreach ( array_diff( array_map( 'intval', $owned ), $used_comment_ids ) as $obsolete_id ) {
				wp_delete_comment( $obsolete_id, true );
			}
			wp_update_comment_count( $listing_id );
		}
		return $restored;
	}

	/** Restore the criteria rows belonging to a remapped review. */
	protected function restore_advanced_review_ratings( int $comment_id, int $listing_id, array $ratings ): void {
		global $wpdb;
		$table = $wpdb->prefix . 'directorist_advanced_reviews';
		if ( $table !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) ) ) {
			return;
		}
		$wpdb->delete( $table, [ 'comment_ID' => $comment_id, 'listing_id' => $listing_id ], [ '%d', '%d' ] );
		foreach ( $ratings as $rating ) {
			if ( ! is_array( $rating ) || '' === (string) ( $rating['criteria_key'] ?? '' ) ) {
				continue;
			}
			$data = [
				'comment_ID' => $comment_id,
				'listing_id' => $listing_id,
				'criteria_key' => sanitize_text_field( (string) $rating['criteria_key'] ),
				'rating' => max( 0, min( 5, (float) ( $rating['rating'] ?? 0 ) ) ),
				'created_at' => sanitize_text_field( (string) ( $rating['created_at'] ?? current_time( 'mysql' ) ) ),
			];
			if ( ! empty( $rating['updated_at'] ) ) {
				$data['updated_at'] = sanitize_text_field( (string) $rating['updated_at'] );
			}
			$wpdb->insert( $table, $data );
		}
	}

	/**
	 * Resolve a target comment by package identity or native WXR signature.
	 *
	 * @param int                    $listing_id Listing ID.
	 * @param string                 $source_uid Stable source UID.
	 * @param array<string,mixed>    $source Source comment.
	 * @param array<int,int>         $used_comment_ids Already matched comments.
	 * @return int
	 */
	protected function find_imported_listing_comment( int $listing_id, string $source_uid, array $source, array $used_comment_ids ): int {
		$matches = get_comments(
			[
				'post_id'    => $listing_id,
				'status'     => 'all',
				'meta_key'   => '_directorist_import_source_uid',
				'meta_value' => $source_uid,
				'number'     => 1,
				'fields'     => 'ids',
			]
		);

		if ( ! empty( $matches[0] ) ) {
			return absint( $matches[0] );
		}

		$comments = get_comments(
			[
				'post_id' => $listing_id,
				'status'  => 'all',
				'type'    => sanitize_key( (string) ( $source['type'] ?? 'review' ) ),
			]
		);

		foreach ( $comments as $comment ) {
			if ( ! $comment instanceof \WP_Comment || in_array( $comment->comment_ID, $used_comment_ids, true ) ) {
				continue;
			}

			if (
				(string) $comment->comment_date_gmt === (string) ( $source['date_gmt'] ?? '' )
				&& (string) $comment->comment_content === (string) ( $source['content'] ?? '' )
				&& (string) $comment->comment_author_email === (string) ( $source['author_email'] ?? '' )
			) {
				return (int) $comment->comment_ID;
			}
		}

		return 0;
	}

	/**
	 * Check whether metadata belongs only to an import session.
	 *
	 * @param string $meta_key Metadata key.
	 * @return bool
	 */
	protected function is_import_bookkeeping_meta_key( string $meta_key ): bool {
		return 0 === strpos( $meta_key, '_directorist_import_' )
			|| '_elementor_import_session_id' === $meta_key;
	}

	/**
	 * Report package content that could not be registered by an inactive plugin.
	 *
	 * Elementor silently skips WXR files for unavailable custom post types. Keep
	 * the import usable, but make that omission explicit in the completion report.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $args Normalized import arguments.
	 * @return array<int,string>
	 */
	protected function collect_unavailable_content_type_warnings( array $manifest, array $args ): array {
		if ( empty( $args['include_content'] ) || empty( $manifest['custom-post-type-title'] ) || ! is_array( $manifest['custom-post-type-title'] ) ) {
			return [];
		}

		$warnings = [];

		foreach ( $manifest['custom-post-type-title'] as $post_type => $details ) {
			$post_type = sanitize_key( (string) $post_type );

			if ( '' === $post_type || post_type_exists( $post_type ) ) {
				continue;
			}

			$label = is_array( $details )
				? sanitize_text_field( (string) ( $details['label'] ?? $details['name'] ?? $post_type ) )
				: sanitize_text_field( (string) $details );

			$warnings[] = sprintf(
				/* translators: %s: unavailable content type label. */
				__( '%s content was not imported because the plugin that registers it is unavailable. Install or activate the required plugin, then run the import again.', 'directorist-elementor' ),
				'' !== $label ? $label : $post_type
			);
		}

		return array_values( array_unique( $warnings ) );
	}

	/**
	 * Report required plugins that cannot provide their imported widgets.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return array<int,string>
	 */
	protected function collect_unavailable_extension_warnings( array $manifest ): array {
		$requirements = isset( $manifest['directorist']['required_extensions'] ) && is_array( $manifest['directorist']['required_extensions'] )
			? $manifest['directorist']['required_extensions']
			: [];

		if ( empty( $requirements ) ) {
			return [];
		}

		$item       = ExtensionRequirementService::get_instance()->annotate_item( [ 'required_extensions' => $requirements ] );
		$annotated  = isset( $item['extension_requirements'] ) && is_array( $item['extension_requirements'] ) ? $item['extension_requirements'] : [];
		$unavailable = array_merge(
			(array) ( $annotated['missing'] ?? [] ),
			(array) ( $annotated['inactive'] ?? [] )
		);
		$warnings = [];

		foreach ( $unavailable as $requirement ) {
			if ( ! is_array( $requirement ) ) {
				continue;
			}

			$name   = sanitize_text_field( (string) ( $requirement['name'] ?? $requirement['slug'] ?? '' ) );
			$status = sanitize_key( (string) ( $requirement['status'] ?? 'missing' ) );

			$warnings[] = sprintf(
				/* translators: 1: required plugin name, 2: plugin status. */
				__( '%1$s is %2$s. Widgets and content provided by that plugin may not render until it is installed and activated, then the import is run again.', 'directorist-elementor' ),
				'' !== $name ? $name : __( 'A required plugin', 'directorist-elementor' ),
				'inactive' === $status ? __( 'inactive', 'directorist-elementor' ) : __( 'not installed', 'directorist-elementor' )
			);
		}

		return array_values( array_unique( $warnings ) );
	}

	/**
	 * Report custom CSS selectors that target element IDs absent from the export.
	 *
	 * Elementor regenerates element IDs while exporting templates. Explicit
	 * `.elementor-element-{id}` selectors are portable only when the referenced
	 * ID exists in the exported content.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $package_file Zip package file.
	 * @return array<int,string>
	 */
	protected function collect_nonportable_elementor_css_warnings( array $manifest, string $package_file ): array {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return [];
		}

		$zip = new \ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return [];
		}

		$documents      = [];
		$global_ids     = [];
		$settings_paths = [];

		for ( $index = 0; $index < $zip->numFiles; $index++ ) {
			$stat = $zip->statIndex( $index );
			$path = is_array( $stat ) ? (string) ( $stat['name'] ?? '' ) : '';

			if (
				'' !== $path
				&& str_ends_with( strtolower( $path ), '.json' )
				&& ( str_starts_with( $path, 'settings/' ) || false !== stripos( basename( $path ), 'site-settings' ) )
			) {
				$settings_paths[] = $path;
				continue;
			}

			if (
				! preg_match( '#^templates/([^/]+)\.json$#', $path, $matches )
				&& ! preg_match( '#^content/[^/]+/([^/]+)\.json$#', $path, $matches )
			) {
				continue;
			}

			$source_id = sanitize_file_name( (string) $matches[1] );
			$raw       = $zip->getFromName( $path );

			if ( ! is_string( $raw ) ) {
				continue;
			}

			$data = json_decode( $raw, true );

			if ( ! is_array( $data ) || ! isset( $data['content'] ) || ! is_array( $data['content'] ) ) {
				continue;
			}

			$element_ids = [];
			$custom_css  = [];
			$this->collect_elementor_css_references( $data['content'], $element_ids, $custom_css );
			if ( ! empty( $data['settings'] ) && is_array( $data['settings'] ) ) {
				$this->collect_elementor_custom_css_values( $data['settings'], $custom_css );
			}
			$global_ids += $element_ids;

			$template = str_starts_with( $path, 'templates/' ) && isset( $manifest['templates'][ $source_id ] )
				? $manifest['templates'][ $source_id ]
				: [];
			$title = is_array( $template )
				? sanitize_text_field( (string) ( $template['title'] ?? $data['title'] ?? $source_id ) )
				: sanitize_text_field( (string) ( $data['title'] ?? $source_id ) );

			$documents[] = [
				'title'      => '' !== $title ? $title : $source_id,
				'custom_css' => $custom_css,
			];
		}

		foreach ( array_unique( $settings_paths ) as $settings_path ) {
			$raw_settings = $zip->getFromName( $settings_path );
			$settings     = is_string( $raw_settings ) ? json_decode( $raw_settings, true ) : null;

			if ( ! is_array( $settings ) ) {
				continue;
			}

			$custom_css = [];
			$this->collect_elementor_custom_css_values( $settings, $custom_css );
			$documents[] = [
				'title'      => sanitize_text_field( basename( $settings_path ) ),
				'custom_css' => $custom_css,
			];
		}

		$warnings = [];

		foreach ( $documents as $document ) {
			if ( empty( $this->get_missing_elementor_css_ids( $global_ids, (array) ( $document['custom_css'] ?? [] ) ) ) ) {
				continue;
			}

			$warnings[] = sprintf(
				/* translators: %s: Elementor document title. */
				__( '%s contains custom CSS element references that could not be mapped safely. The CSS was preserved, but those selectors require review in the source document.', 'directorist-elementor' ),
				sanitize_text_field( (string) ( $document['title'] ?? __( '(Untitled)', 'directorist-elementor' ) ) )
			);
		}

		$zip->close();

		return array_values( array_unique( $warnings ) );
	}

	/**
	 * Collect exported element IDs and custom CSS declarations recursively.
	 *
	 * @param array<int,array<string,mixed>> $elements Elementor elements.
	 * @param array<string,bool>             $element_ids Exported element IDs.
	 * @param array<int,string>              $custom_css Custom CSS declarations.
	 * @return void
	 */
	protected function collect_elementor_css_references( array $elements, array &$element_ids, array &$custom_css ): void {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$element_id = sanitize_key( (string) ( $element['id'] ?? '' ) );

			if ( '' !== $element_id ) {
				$element_ids[ $element_id ] = true;
			}

			$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : [];

			if ( isset( $settings['custom_css'] ) && is_string( $settings['custom_css'] ) && '' !== trim( $settings['custom_css'] ) ) {
				$custom_css[] = $settings['custom_css'];
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$this->collect_elementor_css_references( $element['elements'], $element_ids, $custom_css );
			}
		}
	}

	/**
	 * Collect custom CSS strings from arbitrary Elementor settings.
	 *
	 * @param array<mixed>     $value Settings.
	 * @param array<int,string> $custom_css Custom CSS declarations.
	 * @return void
	 */
	protected function collect_elementor_custom_css_values( array $value, array &$custom_css ): void {
		foreach ( $value as $key => $child ) {
			if ( 'custom_css' === $key && is_string( $child ) && '' !== trim( $child ) ) {
				$custom_css[] = $child;
				continue;
			}

			if ( is_array( $child ) ) {
				$this->collect_elementor_custom_css_values( $child, $custom_css );
			}
		}
	}

	/**
	 * Return custom CSS element IDs absent from the available documents.
	 *
	 * @param array<string,bool> $element_ids Available element IDs.
	 * @param array<int,string>  $custom_css Custom CSS values.
	 * @return array<int,string>
	 */
	protected function get_missing_elementor_css_ids( array $element_ids, array $custom_css ): array {
		$missing_ids = [];

		foreach ( $custom_css as $css ) {
			if ( ! preg_match_all( '/\.elementor-element-([A-Za-z0-9_-]+)/', $css, $matches ) ) {
				continue;
			}

			foreach ( $matches[1] as $referenced_id ) {
				$referenced_id = sanitize_key( (string) $referenced_id );

				if ( '' !== $referenced_id && ! isset( $element_ids[ $referenced_id ] ) ) {
					$missing_ids[ $referenced_id ] = true;
				}
			}
		}

		return array_keys( $missing_ids );
	}

	/**
	 * Validate custom CSS against the final imported Elementor documents.
	 *
	 * @param ImportSession $session Import session.
	 * @return array<int,string>
	 */
	protected function collect_imported_elementor_css_warnings( ImportSession $session ): array {
		$post_ids = get_posts(
			[
				'post_type'      => $this->get_import_lookup_post_types(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_directorist_import_package_id',
				'meta_value'     => $session->get_package_id(),
				'orderby'        => 'ID',
				'order'          => 'ASC',
			]
		);
		$documents  = [];
		$global_ids = [];

		foreach ( $post_ids as $post_id ) {
			$raw_content = get_post_meta( $post_id, '_elementor_data', true );
			$content     = is_string( $raw_content ) ? json_decode( $raw_content, true ) : $raw_content;
			$content     = is_array( $content ) ? $content : [];

			$element_ids = [];
			$custom_css  = [];
			$this->collect_elementor_css_references( $content, $element_ids, $custom_css );
			$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );

			if ( is_array( $page_settings ) ) {
				$this->collect_elementor_custom_css_values( $page_settings, $custom_css );
			}

			$global_ids += $element_ids;

			if ( empty( $custom_css ) ) {
				continue;
			}

			$documents[] = [
				'post_id'    => absint( $post_id ),
				'custom_css' => $custom_css,
			];
		}

		$warnings = [];

		foreach ( $documents as $document ) {
			if ( empty( $this->get_missing_elementor_css_ids( $global_ids, (array) $document['custom_css'] ) ) ) {
				continue;
			}

			$post_id = absint( $document['post_id'] ?? 0 );
			$warnings[] = sprintf(
				/* translators: %s: imported Elementor document title. */
				__( '%s contains custom CSS selectors that do not match its final imported element IDs. The CSS was preserved for review.', 'directorist-elementor' ),
				get_the_title( $post_id ) ?: __( '(Untitled)', 'directorist-elementor' )
			);
		}

		return array_values( array_unique( $warnings ) );
	}

	/**
	 * Import template JSON files when Elementor skips its Pro-only template runner.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $package_file Zip package file.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @param bool                $activate_templates Whether to restore template conditions.
	 * @param string              $conflict_behavior Conflict behavior.
	 * @return array{imported:int,skipped:int,replaced:int,errors:array<int,string>}
	 */
	protected function import_missing_elementor_kit_templates( ImportSession $session, array $manifest, string $package_file, array $term_id_map, bool $activate_templates, string $conflict_behavior ): array {
		$templates = isset( $manifest['templates'] ) && is_array( $manifest['templates'] ) ? $manifest['templates'] : [];
		$documents = isset( $manifest['directorist']['documents'] ) && is_array( $manifest['directorist']['documents'] ) ? $manifest['directorist']['documents'] : [];

		$processed = 0;
		$skipped   = 0;
		$replaced  = 0;
		$errors    = [];

		foreach ( $templates as $source_id => $template ) {
			if ( ! is_array( $template ) ) {
				continue;
			}

			$source_id = sanitize_text_field( (string) $source_id );

			if ( '' === $source_id || $this->get_mapped_post_id( $session, 'elementor_library', $source_id ) ) {
				continue;
			}

			$meta       = isset( $documents[ 'elementor_library:' . $source_id ] ) && is_array( $documents[ 'elementor_library:' . $source_id ] ) ? $documents[ 'elementor_library:' . $source_id ] : [];
			$source_uid = sanitize_text_field( (string) ( $meta['source_uid'] ?? $session->get_package_id() . ':elementor_library:' . $source_id ) );
			$title      = sanitize_text_field( (string) ( $template['title'] ?? $meta['title'] ?? $source_id ) );

			$post_id = wp_insert_post(
				[
					'post_title'  => '' !== $title ? $title : __( 'Imported Elementor Template', 'directorist-elementor' ),
					'post_type'   => 'elementor_library',
					'post_status' => 'publish',
					'post_name'   => sanitize_title( '' !== $title ? $title : $source_id ),
				],
				true
			);

			if ( is_wp_error( $post_id ) ) {
				$errors[] = $post_id->get_error_message();
				continue;
			}

			$post_id        = absint( $post_id );
			$native_post_id = $post_id;
			$this->ensure_imported_template_data( $post_id, $source_id, $manifest, $package_file, $term_id_map, $session );

			if ( $activate_templates && ! empty( $meta['conditions'] ) && is_array( $meta['conditions'] ) ) {
				update_post_meta( $post_id, '_elementor_conditions', $this->remap_elementor_template_conditions( $meta['conditions'], $session, $term_id_map ) );
			}

			$conflict = $this->resolve_imported_post_conflict( $post_id, $source_uid, 'elementor_library', $source_id, $conflict_behavior );
			$post_id  = absint( $conflict['post_id'] ?? $post_id );
			$action   = sanitize_key( (string) ( $conflict['action'] ?? 'created' ) );

			if ( $post_id <= 0 ) {
				continue;
			}

			if ( 'skipped' !== $action ) {
				$this->ensure_unique_imported_post_slug( $post_id );
			}

			$this->ensure_imported_template_data( $post_id, $source_id, $manifest, $package_file, $term_id_map, $session );

			if ( $activate_templates && ! empty( $meta['conditions'] ) && is_array( $meta['conditions'] ) ) {
				update_post_meta( $post_id, '_elementor_conditions', $this->remap_elementor_template_conditions( $meta['conditions'], $session, $term_id_map ) );
			}

			if ( 'skipped' === $action ) {
				$skipped++;
			} elseif ( 'updated' === $action ) {
				$session->record_updated( 'elementor_library', $post_id );
			} else {
				$session->record_created( 'elementor_library', $post_id );

				if ( 'replaced' === $action ) {
					$replaced++;
				}
			}

			if ( '' !== $source_id ) {
				$this->map_imported_post_id( $session, 'elementor_library', $source_id, $post_id );
				$session->map_id( 'fallback_template_source_id', $source_id, $post_id );
			}

			$this->map_native_imported_post_id( $session, 'elementor_library', $native_post_id, $post_id );
			$session->map_id( 'post_uid', $source_uid, $post_id );
			update_post_meta( $post_id, '_directorist_import_source_uid', $source_uid );
			update_post_meta( $post_id, '_directorist_import_package_id', $session->get_package_id() );
			update_post_meta( $post_id, '_directorist_import_package_version', $session->get_package_version() );
			update_post_meta( $post_id, '_directorist_import_last_imported_at', current_time( 'mysql' ) );
			$this->clear_elementor_cache_for_post( $post_id );

			$session->log( 'info', __( 'Imported Elementor template fallback item.', 'directorist-elementor' ), [ 'post_id' => $post_id, 'source_id' => $source_id, 'action' => $action ] );
			$processed++;
		}

		return [
			'imported' => $processed,
			'skipped'  => $skipped,
			'replaced' => $replaced,
			'errors'   => $errors,
		];
	}

	/**
	 * Track and reconcile the native Elementor kit shell post.
	 *
	 * Elementor creates a site-kit library record outside of the normal template
	 * runner result. Tag it so repeat imports can honor update/skip/replace.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $conflict_behavior Conflict behavior.
	 * @return array{post_id:int,action:string}|array{}
	 */
	protected function finalize_elementor_kit_shell( ImportSession $session, array $manifest, string $conflict_behavior ): array {
		$title      = sanitize_text_field( (string) ( $manifest['title'] ?? $manifest['name'] ?? $session->get_package_id() ) );
		$source_uid = $session->get_package_id() . ':elementor_library:site-kit';
		$fresh_id   = $this->find_untracked_elementor_kit_shell_id( $title );

		if ( $fresh_id <= 0 ) {
			return [];
		}

		$existing_id = $this->find_existing_imported_post_id( $source_uid, $fresh_id );

		if ( $existing_id <= 0 && 'duplicate' !== $conflict_behavior ) {
			$existing_id = $this->find_untracked_elementor_kit_shell_id( $title, $fresh_id, 'ASC' );
		}

		if ( $existing_id > 0 && 'skip' === $conflict_behavior ) {
			$this->delete_elementor_kit_shell( $fresh_id, $existing_id );
			$this->mark_elementor_kit_shell_imported( $existing_id, $source_uid, $session );
			$this->cleanup_untracked_elementor_kit_shells( $title, $existing_id );

			return [
				'post_id' => $existing_id,
				'action'  => 'skipped',
			];
		}

		if ( $existing_id > 0 && 'replace' === $conflict_behavior ) {
			$this->delete_elementor_kit_shell( $existing_id, $fresh_id );
			$this->mark_elementor_kit_shell_imported( $fresh_id, $source_uid, $session );
			$this->cleanup_untracked_elementor_kit_shells( $title, $fresh_id );

			return [
				'post_id' => $fresh_id,
				'action'  => 'replaced',
			];
		}

		if ( $existing_id > 0 && 'update' === $conflict_behavior && $this->copy_imported_post_to_existing( $fresh_id, $existing_id ) ) {
			$this->delete_elementor_kit_shell( $fresh_id, $existing_id );
			$this->mark_elementor_kit_shell_imported( $existing_id, $source_uid, $session );
			$this->cleanup_untracked_elementor_kit_shells( $title, $existing_id );

			return [
				'post_id' => $existing_id,
				'action'  => 'updated',
			];
		}

		$this->mark_elementor_kit_shell_imported( $fresh_id, $source_uid, $session );
		$this->cleanup_untracked_elementor_kit_shells( $title, $fresh_id );

		return [
			'post_id' => $fresh_id,
			'action'  => 'created',
		];
	}

	/**
	 * Persist source site settings on the reconciled Elementor kit.
	 *
	 * Elementor's native importer creates a new kit before Directorist resolves
	 * repeat-import conflicts. Persisting the package settings after that
	 * reconciliation guarantees that the final active kit owns the imported
	 * layout, color, and typography values.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $package_file Zip package file.
	 * @param array<string,mixed> $kit_shell Reconciled kit result.
	 * @param bool                $apply Whether site settings were selected.
	 * @return array{applied:bool,status:string,kit_id:int,error?:string}
	 */
	protected function finalize_elementor_kit_site_settings( ImportSession $session, array $manifest, string $package_file, array $kit_shell, bool $apply ): array {
		$kit_id = absint( $kit_shell['post_id'] ?? 0 );
		$action = sanitize_key( (string) ( $kit_shell['action'] ?? '' ) );
		$result = [
			'applied' => false,
			'status'  => $apply ? 'unchanged' : 'preserved',
			'kit_id'  => $kit_id,
		];

		if ( ! $apply || 'skipped' === $action ) {
			if ( 'skipped' === $action ) {
				$result['status'] = 'preserved';
			}

			return $result;
		}

		if ( $kit_id <= 0 || 'elementor_library' !== get_post_type( $kit_id ) ) {
			$result['error'] = __( 'Elementor site settings could not be applied because the imported kit was not found.', 'directorist-elementor' );
			return $result;
		}

		$site_settings = $this->read_zip_json_file( $package_file, 'site-settings.json' );

		if ( is_wp_error( $site_settings ) ) {
			$result['error'] = $site_settings->get_error_message();
			return $result;
		}

		$settings = isset( $site_settings['settings'] ) && is_array( $site_settings['settings'] )
			? $site_settings['settings']
			: [];

		if ( empty( $settings ) ) {
			return $result;
		}

		$settings = $this->remap_imported_elementor_value(
			$settings,
			[],
			(string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ),
			'',
			$session,
			true
		);

		if ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->documents ) ) {
			$kit_document = \Elementor\Plugin::$instance->documents->get( $kit_id );

			if ( $kit_document && method_exists( $kit_document, 'save' ) ) {
				$kit_document->save( [ 'settings' => $settings ] );
			}
		}

		$persisted_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );

		if ( $persisted_settings !== $settings ) {
			update_post_meta( $kit_id, '_elementor_page_settings', $settings );
			$persisted_settings = get_post_meta( $kit_id, '_elementor_page_settings', true );
		}

		if ( ! is_array( $persisted_settings ) || $persisted_settings !== $settings ) {
			$result['error'] = __( 'Elementor site settings could not be saved to the imported kit.', 'directorist-elementor' );
			return $result;
		}

		update_option( 'elementor_active_kit', $kit_id );
		$this->clear_elementor_cache_for_post( $kit_id );

		$result['applied'] = true;
		$result['status']  = 'applied';

		return $result;
	}

	/**
	 * Find an Elementor site-kit shell post created by the native importer.
	 *
	 * @param string $title Exact kit title.
	 * @param int    $exclude_id Post ID to ignore.
	 * @param string $order Sort order.
	 * @return int
	 */
	protected function find_untracked_elementor_kit_shell_id( string $title, int $exclude_id = 0, string $order = 'DESC' ): int {
		$posts = get_posts(
			[
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC' === strtoupper( $order ) ? 'ASC' : 'DESC',
				'post__not_in'   => $exclude_id > 0 ? [ $exclude_id ] : [],
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_elementor_template_type',
						'value' => 'kit',
					],
					[
						'key'     => '_directorist_import_source_uid',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		foreach ( $posts as $post_id ) {
			$post = get_post( absint( $post_id ) );

			if ( $post && $title === $post->post_title ) {
				return absint( $post_id );
			}
		}

		return 0;
	}

	/**
	 * Store Directorist import metadata on the native Elementor kit shell.
	 *
	 * @param int           $post_id Post ID.
	 * @param string        $source_uid Stable source UID.
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	protected function mark_elementor_kit_shell_imported( int $post_id, string $source_uid, ImportSession $session ): void {
		update_post_meta( $post_id, '_directorist_import_source_uid', $source_uid );
		update_post_meta( $post_id, '_directorist_import_package_id', $session->get_package_id() );
		update_post_meta( $post_id, '_directorist_import_package_version', $session->get_package_version() );
		update_post_meta( $post_id, '_directorist_import_last_imported_at', current_time( 'mysql' ) );
		$this->clear_elementor_cache_for_post( $post_id );
	}

	/**
	 * Remove untracked native Elementor kit shells left from older imports.
	 *
	 * @param string $title Exact kit title.
	 * @param int    $keep_id Post ID to keep.
	 * @return void
	 */
	protected function cleanup_untracked_elementor_kit_shells( string $title, int $keep_id ): void {
		$posts = get_posts(
			[
				'post_type'      => 'elementor_library',
				'post_status'    => 'any',
				'posts_per_page' => 50,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'post__not_in'   => $keep_id > 0 ? [ $keep_id ] : [],
				'meta_query'     => [
					'relation' => 'AND',
					[
						'key'   => '_elementor_template_type',
						'value' => 'kit',
					],
					[
						'key'     => '_directorist_import_source_uid',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);

		foreach ( $posts as $post_id ) {
			$post = get_post( absint( $post_id ) );

			if ( $post && $title === $post->post_title ) {
				$this->delete_elementor_kit_shell( absint( $post_id ), $keep_id );
			}
		}
	}

	/**
	 * Delete an imported Elementor kit shell without triggering the UI confirmation.
	 *
	 * @param int $post_id Kit shell post ID.
	 * @param int $fallback_kit_id Kit ID to keep active when needed.
	 * @return void
	 */
	protected function delete_elementor_kit_shell( int $post_id, int $fallback_kit_id = 0 ): void {
		if ( $post_id <= 0 ) {
			return;
		}

		foreach ( [ 'elementor_active_kit', 'elementor_previous_kit' ] as $option_name ) {
			if ( absint( get_option( $option_name ) ) === $post_id && $fallback_kit_id > 0 ) {
				update_option( $option_name, $fallback_kit_id );
			}
		}

		$had_force_delete = array_key_exists( 'force_delete_kit', $_GET );
		$force_delete    = $had_force_delete ? $_GET['force_delete_kit'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$_GET['force_delete_kit'] = '1';
		wp_delete_post( $post_id, true );

		if ( $had_force_delete ) {
			$_GET['force_delete_kit'] = $force_delete;
		} else {
			unset( $_GET['force_delete_kit'] );
		}
	}

	/**
	 * Resolve Directorist source metadata for an imported document.
	 *
	 * @param array<string,mixed> $document_meta Site-kit document metadata.
	 * @param array<string,mixed> $template_meta Legacy template metadata.
	 * @param string              $post_type Imported post type.
	 * @param string              $source_id Source object id.
	 * @return array<string,mixed>
	 */
	protected function resolve_directorist_document_meta( array $document_meta, array $template_meta, string $post_type, string $source_id ): array {
		$key = sanitize_key( $post_type ) . ':' . sanitize_text_field( $source_id );

		if ( isset( $document_meta[ $key ] ) && is_array( $document_meta[ $key ] ) ) {
			return $document_meta[ $key ];
		}

		if ( 'elementor_library' === $post_type && isset( $template_meta[ $source_id ] ) && is_array( $template_meta[ $source_id ] ) ) {
			return $template_meta[ $source_id ];
		}

		return [];
	}

	/**
	 * Normalize import behavior for Elementor-native site kit packages.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return array<string,mixed>
	 */
	protected function normalize_elementor_kit_import_args( array $manifest, array $args ): array {
		$defaults = isset( $manifest['directorist']['import_defaults'] ) && is_array( $manifest['directorist']['import_defaults'] )
			? $manifest['directorist']['import_defaults']
			: [];
		$is_complete_site = 'site' === (string) ( $manifest['directorist']['package_type'] ?? $manifest['package_type'] ?? '' );

		$normalized = $args;
		$normalized['include_templates'] = array_key_exists( 'include_templates', $args ) ? (bool) $args['include_templates'] : (bool) ( $defaults['include_templates'] ?? true );
		$normalized['include_content'] = array_key_exists( 'include_content', $args ) ? (bool) $args['include_content'] : (bool) ( $defaults['include_content'] ?? true );
		$normalized['apply_site_settings'] = array_key_exists( 'apply_site_settings', $args ) ? (bool) $args['apply_site_settings'] : ( $is_complete_site || (bool) ( $defaults['apply_site_settings'] ?? false ) );
		$normalized['apply_template_conditions'] = array_key_exists( 'apply_template_conditions', $args ) ? (bool) $args['apply_template_conditions'] : ( $is_complete_site || (bool) ( $defaults['apply_template_conditions'] ?? false ) );
		$normalized['set_homepage'] = array_key_exists( 'set_homepage', $args ) ? (bool) $args['set_homepage'] : ( $is_complete_site || (bool) ( $defaults['set_homepage'] ?? false ) );

		if ( ! empty( $args['activate_templates'] ) ) {
			$normalized['apply_template_conditions'] = true;
		}

		$normalized['activate_templates'] = ! empty( $normalized['apply_template_conditions'] );

		return $normalized;
	}

	/**
	 * Capture homepage-related options before Elementor's native importer runs.
	 *
	 * @return array<string,mixed>
	 */
	protected function capture_homepage_settings(): array {
		return [
			'show_on_front'  => get_option( 'show_on_front' ),
			'page_on_front'  => get_option( 'page_on_front' ),
			'page_for_posts' => get_option( 'page_for_posts' ),
		];
	}

	/**
	 * Restore homepage-related options after a safe import.
	 *
	 * @param array<string,mixed> $snapshot Captured options.
	 * @return void
	 */
	protected function restore_homepage_settings( array $snapshot ): void {
		foreach ( [ 'show_on_front', 'page_on_front', 'page_for_posts' ] as $option_name ) {
			if ( array_key_exists( $option_name, $snapshot ) ) {
				update_option( $option_name, $snapshot[ $option_name ] );
			}
		}
	}

	/**
	 * Set the imported homepage declared by the Elementor site-kit manifest.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return void
	 */
	protected function apply_imported_homepage( ImportSession $session, array $manifest ): void {
		$pages = isset( $manifest['content']['page'] ) && is_array( $manifest['content']['page'] ) ? $manifest['content']['page'] : [];

		foreach ( $pages as $source_id => $page ) {
			if ( ! is_array( $page ) || empty( $page['show_on_front'] ) ) {
				continue;
			}

			$page_id = $this->get_mapped_post_id( $session, 'page', (string) $source_id );

			if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
				continue;
			}

			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', absint( $page_id ) );
			return;
		}
	}

	/**
	 * Strip Elementor template conditions before safe imports.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return array<string,mixed>
	 */
	protected function strip_elementor_kit_template_conditions( array $manifest ): array {
		if ( ! empty( $manifest['templates'] ) && is_array( $manifest['templates'] ) ) {
			foreach ( $manifest['templates'] as $id => $template ) {
				if ( is_array( $template ) ) {
					unset( $template['conditions'] );
					$manifest['templates'][ $id ] = $template;
				}
			}
		}

		foreach ( [ 'templates', 'documents' ] as $directorist_key ) {
			if ( empty( $manifest['directorist'][ $directorist_key ] ) || ! is_array( $manifest['directorist'][ $directorist_key ] ) ) {
				continue;
			}

			foreach ( $manifest['directorist'][ $directorist_key ] as $id => $item ) {
				if ( is_array( $item ) ) {
					unset( $item['conditions'] );
					$manifest['directorist'][ $directorist_key ][ $id ] = $item;
				}
			}
		}

		return $manifest;
	}

	/**
	 * Prepare a native manifest that Elementor can import before term IDs exist.
	 *
	 * Directorist directory-scoped document types contain a directory term ID.
	 * Elementor cannot register the source-site type on the target site, so defer
	 * those templates to Directorist's fallback importer after taxonomy mappings
	 * have been collected.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return array<string,mixed>
	 */
	protected function prepare_elementor_native_import_manifest( array $manifest ): array {
		$manifest  = $this->strip_elementor_kit_template_conditions( $manifest );
		$templates = isset( $manifest['templates'] ) && is_array( $manifest['templates'] ) ? $manifest['templates'] : [];

		foreach ( $templates as $source_id => $template ) {
			if ( ! is_array( $template ) ) {
				continue;
			}

			$document_type = sanitize_key( (string) ( $template['doc_type'] ?? '' ) );

			if ( 0 === strpos( $document_type, 'directorist-single-listing-directory-' ) ) {
				unset( $manifest['templates'][ $source_id ] );
			}
		}

		return $manifest;
	}

	/**
	 * Elementor 4.3 merges destination custom styles with the imported arrays.
	 * Its site-settings runner assumes both keys exist, while valid exported kits
	 * may omit them. Normalize only the temporary package used for this import.
	 *
	 * @param string              $package_file Temporary Elementor kit archive.
	 * @param array<string,mixed> $args Normalized import options.
	 * @return true|WP_Error
	 */
	protected function prepare_elementor_native_site_settings_file( string $package_file, array $args ) {
		if ( empty( $args['apply_site_settings'] ) ) {
			return true;
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$json = $zip->getFromName( 'site-settings.json' );
		if ( false === $json ) {
			$zip->close();
			return true;
		}

		$site_settings = json_decode( $json, true );
		if ( ! is_array( $site_settings ) || ! isset( $site_settings['settings'] ) || ! is_array( $site_settings['settings'] ) ) {
			$zip->close();
			return new WP_Error( 'directorist_elementor_site_settings_invalid', __( 'The Elementor site settings file is not valid.', 'directorist-elementor' ) );
		}

		$changed = false;
		foreach ( [ 'custom_colors', 'custom_typography' ] as $key ) {
			if ( ! isset( $site_settings['settings'][ $key ] ) || ! is_array( $site_settings['settings'][ $key ] ) ) {
				$site_settings['settings'][ $key ] = [];
				$changed = true;
			}
		}

		$encoded = $changed ? wp_json_encode( $site_settings ) : '';
		if ( $changed && ( ! is_string( $encoded ) || false === $zip->addFromString( 'site-settings.json', $encoded ) ) ) {
			$zip->close();
			return new WP_Error( 'directorist_elementor_site_settings_write_failed', __( 'The Elementor site settings file could not be prepared for import.', 'directorist-elementor' ) );
		}

		$zip->close();
		return true;
	}

	/**
	 * Isolate Elementor's native taxonomy JSON from unrelated destination terms.
	 *
	 * Elementor's taxonomy runner resolves existing terms directly by slug and
	 * does not pass them through the WXR term filters. Rewrite its temporary
	 * package files so every non-shared source term gets a package-owned identity.
	 *
	 * @param string                              $package_file Temporary kit zip.
	 * @param array<string,mixed>                 $manifest Native import manifest.
	 * @param string                              $package_id Import package ID.
	 * @param string                              $conflict_behavior Conflict behavior.
	 * @param array<string,array<string,string>>  $slug_map Source-to-target slug map.
	 * @param array<string,array<int,int>>        $existing_term_map Existing package-owned term IDs.
	 * @return true|WP_Error
	 */
	protected function prepare_elementor_native_taxonomy_files( string $package_file, array $manifest, string $package_id, string $conflict_behavior, array &$slug_map, array &$existing_term_map = [] ) {
		if ( empty( $manifest['taxonomies'] ) || ! is_array( $manifest['taxonomies'] ) ) {
			return true;
		}

		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error( 'directorist_elementor_zip_missing', __( 'The PHP ZipArchive extension is required to import Directorist Elementor website packages.', 'directorist-elementor' ) );
		}

		$taxonomy_names = [];

		foreach ( $manifest['taxonomies'] as $taxonomies ) {
			if ( ! is_array( $taxonomies ) ) {
				continue;
			}

			foreach ( $taxonomies as $taxonomy ) {
				$taxonomy = sanitize_key( (string) $taxonomy );

				if ( '' !== $taxonomy ) {
					$taxonomy_names[ $taxonomy ] = $taxonomy;
				}
			}
		}

		if ( empty( $taxonomy_names ) ) {
			return true;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$reserved = [];

		foreach ( $taxonomy_names as $manifest_taxonomy ) {
			$relative_path = 'taxonomies/' . $manifest_taxonomy . '.json';
			$json          = $zip->getFromName( $relative_path );

			if ( ! is_string( $json ) ) {
				$zip->close();

				return new WP_Error( 'directorist_elementor_taxonomy_file_missing', sprintf( __( 'The taxonomy package file %s is missing.', 'directorist-elementor' ), $relative_path ) );
			}

			$terms = json_decode( $json, true );

			if ( ! is_array( $terms ) ) {
				$zip->close();

				return new WP_Error( 'directorist_elementor_taxonomy_file_invalid', sprintf( __( 'The taxonomy package file %s is not valid JSON.', 'directorist-elementor' ), $relative_path ) );
			}

			foreach ( $terms as &$term ) {
				if ( ! is_array( $term ) ) {
					continue;
				}

				$taxonomy   = sanitize_key( (string) ( $term['taxonomy'] ?? $manifest_taxonomy ) );
				$source_slug = sanitize_title( (string) ( $term['slug'] ?? '' ) );
				$source_id   = absint( $term['term_id'] ?? 0 );

				if ( '' === $taxonomy || '' === $source_slug || ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				$existing_id = $this->find_existing_imported_term_id( $taxonomy, $package_id, $source_id );

				if ( isset( $slug_map[ $taxonomy ][ $source_slug ] ) ) {
					$target_slug = $slug_map[ $taxonomy ][ $source_slug ];
				} elseif ( $existing_id > 0 && 'duplicate' !== $conflict_behavior ) {
					$existing    = get_term( $existing_id, $taxonomy );
					$target_slug = $existing instanceof \WP_Term ? sanitize_title( $existing->slug ) : $source_slug;
				} elseif ( $this->should_share_import_term_by_slug( $taxonomy ) ) {
					$target_slug = $source_slug;
				} else {
					$target_slug = $this->get_unique_import_term_slug( $source_slug, $taxonomy, (array) ( $reserved[ $taxonomy ] ?? [] ) );
				}

				$slug_map[ $taxonomy ][ $source_slug ] = $target_slug;
				$reserved[ $taxonomy ][]               = $target_slug;
				$term['slug']                           = $target_slug;

				if ( $existing_id > 0 && 'duplicate' !== $conflict_behavior && $source_id > 0 ) {
					$existing_term_map[ $taxonomy ][ $source_id ] = $existing_id;
				}
			}
			unset( $term );

			$encoded = wp_json_encode( $terms );

			if ( ! is_string( $encoded ) || false === $zip->addFromString( $relative_path, $encoded ) ) {
				$zip->close();

				return new WP_Error( 'directorist_elementor_taxonomy_file_write_failed', sprintf( __( 'The taxonomy package file %s could not be prepared for import.', 'directorist-elementor' ), $relative_path ) );
			}
		}

		$zip->close();

		return true;
	}

	/**
	 * Isolate native WXR terms from unrelated destination terms with the same slug.
	 *
	 * @param array<int,mixed>               $terms Native importer terms.
	 * @param string                         $package_id Import package ID.
	 * @param string                         $conflict_behavior Conflict behavior.
	 * @param array<string,array<string,string>> $slug_map Source slug to target slug map.
	 * @param array<string,array<int,int>>   $existing_term_map Existing package-owned term IDs.
	 * @return array<int,mixed>
	 */
	protected function prepare_native_import_terms( array $terms, string $package_id, string $conflict_behavior, array &$slug_map, array &$existing_term_map = [] ): array {
		$reserved = [];

		foreach ( $terms as $term ) {
			if ( ! is_array( $term ) ) {
				continue;
			}

			$taxonomy   = sanitize_key( (string) ( $term['term_taxonomy'] ?? '' ) );
			$source_slug = sanitize_title( (string) ( $term['slug'] ?? '' ) );
			$source_id   = absint( $term['term_id'] ?? 0 );

			if ( '' === $taxonomy || '' === $source_slug || ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$shared      = $this->should_share_import_term_by_slug( $taxonomy );
			$existing_id = $this->find_existing_imported_term_id( $taxonomy, $package_id, $source_id );

			if ( isset( $slug_map[ $taxonomy ][ $source_slug ] ) ) {
				$target_slug = $slug_map[ $taxonomy ][ $source_slug ];
				$current_term = get_term_by( 'slug', $target_slug, $taxonomy );

				if ( $source_id > 0 && $current_term instanceof \WP_Term ) {
					$existing_term_map[ $taxonomy ][ $source_id ] = absint( $current_term->term_id );
				}
			} elseif ( $existing_id > 0 && 'duplicate' !== $conflict_behavior ) {
				$existing = get_term( $existing_id, $taxonomy );
				$target_slug = $existing instanceof \WP_Term ? sanitize_title( $existing->slug ) : $source_slug;
				$existing_term_map[ $taxonomy ][ $source_id ] = $existing_id;
			} elseif ( $shared ) {
				$target_slug = $source_slug;
			} else {
				$target_slug = $this->get_unique_import_term_slug( $source_slug, $taxonomy, (array) ( $reserved[ $taxonomy ] ?? [] ) );
			}

			$slug_map[ $taxonomy ][ $source_slug ] = $target_slug;
			$reserved[ $taxonomy ][] = $target_slug;
		}

		foreach ( $terms as &$term ) {
			if ( ! is_array( $term ) ) {
				continue;
			}

			$taxonomy    = sanitize_key( (string) ( $term['term_taxonomy'] ?? '' ) );
			$source_slug = sanitize_title( (string) ( $term['slug'] ?? '' ) );
			$parent_slug = sanitize_title( (string) ( $term['term_parent'] ?? '' ) );

			if ( isset( $slug_map[ $taxonomy ][ $source_slug ] ) ) {
				$term['slug'] = $slug_map[ $taxonomy ][ $source_slug ];
			}

			if ( '' !== $parent_slug && isset( $slug_map[ $taxonomy ][ $parent_slug ] ) ) {
				$term['term_parent'] = $slug_map[ $taxonomy ][ $parent_slug ];
			}
		}
		unset( $term );

		return $terms;
	}

	/**
	 * Keep post-term assignments aligned with slugs rewritten before WXR term import.
	 *
	 * @param array<int,mixed>                    $terms Post term records.
	 * @param array<string,array<string,string>>  $slug_map Source slug to target slug map.
	 * @return array<int,mixed>
	 */
	protected function prepare_native_import_post_terms( array $terms, array $slug_map ): array {
		foreach ( $terms as &$term ) {
			if ( ! is_array( $term ) ) {
				continue;
			}

			$taxonomy = sanitize_key( (string) ( $term['domain'] ?? '' ) );
			$taxonomy = 'tag' === $taxonomy ? 'post_tag' : $taxonomy;
			$slug     = sanitize_title( (string) ( $term['slug'] ?? '' ) );

			if ( isset( $slug_map[ $taxonomy ][ $slug ] ) ) {
				$term['slug'] = $slug_map[ $taxonomy ][ $slug ];
			}
		}
		unset( $term );

		return $terms;
	}

	/**
	 * Find a term previously imported from the same package object.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @param string $package_id Package ID.
	 * @param int    $source_id Source term ID.
	 * @return int
	 */
	protected function find_existing_imported_term_id( string $taxonomy, string $package_id, int $source_id ): int {
		if ( '' === $package_id || $source_id <= 0 ) {
			return 0;
		}

		$matches = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => 1,
				'fields'     => 'ids',
				'meta_query' => [
					'relation' => 'AND',
					[
						'key'   => '_directorist_import_package_id',
						'value' => $package_id,
					],
					[
						'key'   => '_directorist_import_source_id',
						'value' => (string) $source_id,
					],
				],
			]
		);

		return ! is_wp_error( $matches ) && ! empty( $matches[0] ) ? absint( $matches[0] ) : 0;
	}

	/**
	 * Allocate a WordPress-style unique term slug, including same-batch reservations.
	 *
	 * @param string            $slug Desired slug.
	 * @param string            $taxonomy Taxonomy.
	 * @param array<int,string> $reserved Slugs reserved by the current import batch.
	 * @return string
	 */
	protected function get_unique_import_term_slug( string $slug, string $taxonomy, array $reserved = [] ): string {
		$base = sanitize_title( $slug );

		if ( ! term_exists( $base, $taxonomy ) && ! in_array( $base, $reserved, true ) ) {
			return $base;
		}

		$suffix = 2;

		do {
			$candidate = $base . '-' . $suffix;
			$suffix++;
		} while ( term_exists( $candidate, $taxonomy ) || in_array( $candidate, $reserved, true ) );

		return $candidate;
	}

	/**
	 * Whether a taxonomy is a shared builder or WordPress semantic namespace.
	 *
	 * @param string $taxonomy Taxonomy.
	 * @return bool
	 */
	protected function should_share_import_term_by_slug( string $taxonomy ): bool {
		return in_array( $taxonomy, [ 'elementor_library_type', 'wp_template_part_area', 'wp_theme' ], true );
	}

	/**
	 * Normalize conflict behavior values.
	 *
	 * @param string $behavior Requested behavior.
	 * @return string
	 */
	protected function normalize_conflict_behavior( string $behavior ): string {
		$behavior = sanitize_key( $behavior );

		return in_array( $behavior, [ 'skip', 'duplicate', 'update', 'replace' ], true ) ? $behavior : 'update';
	}

	/**
	 * Apply repeat-import behavior after Elementor creates a fresh item.
	 *
	 * Elementor's native kit importer always creates new posts. Directorist
	 * tracks source UIDs so repeated imports can behave predictably.
	 *
	 * @param int    $post_id Imported post id.
	 * @param string $source_uid Stable source UID.
	 * @param string $post_type Post type.
	 * @param string $source_id Source object id.
	 * @param string $behavior Conflict behavior.
	 * @return array{post_id:int,action:string}
	 */
	protected function resolve_imported_post_conflict( int $post_id, string $source_uid, string $post_type, string $source_id, string $behavior, int $preferred_existing_id = 0 ): array {
		if ( 'duplicate' === $behavior || '' === $source_uid ) {
			return [
				'post_id' => $post_id,
				'action'  => 'created',
			];
		}

		$existing_id = $this->find_existing_imported_post_id( $source_uid, $post_id );
		if ( $existing_id <= 0 ) {
			$existing_id = $preferred_existing_id;
		}

		if ( $existing_id <= 0 ) {
			return [
				'post_id' => $post_id,
				'action'  => 'created',
			];
		}

		if ( 'skip' === $behavior ) {
			$this->reassign_native_import_menu_items( $post_id, $existing_id );
			wp_delete_post( $post_id, true );

			return [
				'post_id' => $existing_id,
				'action'  => 'skipped',
			];
		}

		if ( 'replace' === $behavior ) {
			wp_delete_post( $existing_id, true );

			return [
				'post_id' => $post_id,
				'action'  => 'replaced',
			];
		}

		$updated = $this->copy_imported_post_to_existing( $post_id, $existing_id );

		if ( $updated ) {
			$this->reassign_native_import_menu_items( $post_id, $existing_id );
			$this->detach_imported_listing_media_before_delete( $post_id );
			wp_delete_post( $post_id, true );

			return [
				'post_id' => $existing_id,
				'action'  => 'updated',
			];
		}

		return [
			'post_id' => $post_id,
			'action'  => 'created',
		];
	}

	/**
	 * Transfer imported listing media ownership to the persistent listing.
	 *
	 * Directorist deletes a listing's preview and gallery attachments on
	 * before_delete_post. The update path has already copied these references to
	 * the persistent listing, so detach them from the temporary listing first.
	 *
	 * @param int $post_id Temporary imported listing ID.
	 * @return void
	 */
	protected function detach_imported_listing_media_before_delete( int $post_id ): void {
		if ( 'at_biz_dir' !== get_post_type( $post_id ) ) {
			return;
		}

		delete_post_meta( $post_id, '_listing_prv_img' );
		delete_post_meta( $post_id, '_listing_img' );
	}

	/**
	 * WordPress deletes associated menu items when a temporary page is deleted.
	 * Move only this native import's references onto its persistent replacement.
	 */
	protected function reassign_native_import_menu_items( int $temporary_id, int $persistent_id ): void {
		$native_session = (string) get_post_meta( $temporary_id, '_elementor_import_session_id', true );
		if ( '' === $native_session ) {
			return;
		}
		foreach ( wp_get_associated_nav_menu_items( $temporary_id, 'post_type' ) as $item_id ) {
			if ( $native_session === (string) get_post_meta( $item_id, '_elementor_import_session_id', true ) ) {
				update_post_meta( $item_id, '_menu_item_object_id', (string) $persistent_id );
			}
		}
	}

	/**
	 * Find an existing imported object by source UID.
	 *
	 * @param string $source_uid Stable source UID.
	 * @param int    $exclude_id Newly imported post id to ignore.
	 * @return int
	 */
	protected function find_existing_imported_post_id( string $source_uid, int $exclude_id = 0 ): int {
		$posts = get_posts(
			[
				'post_type'      => $this->get_import_lookup_post_types(),
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'DESC',
				'post__not_in'   => $exclude_id > 0 ? [ $exclude_id ] : [],
				'meta_key'       => '_directorist_import_source_uid',
				'meta_value'     => $source_uid,
			]
		);

		return ! empty( $posts[0] ) ? absint( $posts[0] ) : 0;
	}

	/**
	 * Find one untagged copy of an imported page, post or listing. A matching
	 * slug by itself is insufficient on occupied sites.
	 *
	 * @return int Matching ID, zero for no match, or -1 for ambiguity.
	 */
	protected function find_matching_destination_content_post( string $post_type, string $slug, string $title, string $source_uid, int $exclude_id = 0 ): int {
		$slug  = sanitize_title( $slug );
		$title = trim( $title );
		if ( ! in_array( $post_type, [ 'page', 'post', 'at_biz_dir' ], true ) || '' === $slug || '' === $title ) {
			return 0;
		}

		$candidates = get_posts( [
			'post_type'        => $post_type,
			'post_status'      => 'any',
			'name'             => $slug,
			'numberposts'      => 20,
			'suppress_filters' => true,
		] );
		$matches = [];
		foreach ( $candidates as $candidate ) {
			if ( ! $candidate instanceof \WP_Post || $candidate->ID === $exclude_id || $candidate->post_name !== $slug || trim( $candidate->post_title ) !== $title ) {
				continue;
			}
			$claimed_uid = (string) get_post_meta( $candidate->ID, '_directorist_import_source_uid', true );
			if ( '' !== $claimed_uid && $claimed_uid !== $source_uid ) {
				continue;
			}
			$matches[] = (int) $candidate->ID;
		}

		return count( $matches ) > 1 ? -1 : ( $matches[0] ?? 0 );
	}

	/**
	 * Copy imported post data and metadata onto an existing imported object.
	 *
	 * @param int $source_post_id Fresh imported post id.
	 * @param int $target_post_id Existing imported post id.
	 * @return bool
	 */
	protected function copy_imported_post_to_existing( int $source_post_id, int $target_post_id ): bool {
		$source = get_post( $source_post_id );
		$target = get_post( $target_post_id );

		if ( ! $source || ! $target || $source->post_type !== $target->post_type ) {
			return false;
		}

		$updated = wp_update_post(
			[
				'ID'            => $target_post_id,
				'post_title'    => $source->post_title,
				'post_content'  => $source->post_content,
				'post_excerpt'  => $source->post_excerpt,
				'post_status'   => $source->post_status,
				'post_date'     => $source->post_date,
				'post_date_gmt' => $source->post_date_gmt,
				'menu_order'    => $source->menu_order,
			],
			true
		);

		if ( is_wp_error( $updated ) ) {
			return false;
		}

		$this->copy_post_meta( $source_post_id, $target_post_id );
		$this->copy_post_terms( $source_post_id, $target_post_id, $source->post_type );

		return true;
	}

	/**
	 * Copy post metadata from one post to another.
	 *
	 * @param int $source_post_id Source post id.
	 * @param int $target_post_id Target post id.
	 * @return void
	 */
	protected function copy_post_meta( int $source_post_id, int $target_post_id ): void {
		$excluded_keys = [
			'_edit_lock',
			'_edit_last',
			'_elementor_import_session_id',
			'_directorist_import_last_imported_at',
		];
		$meta = get_post_meta( $source_post_id );

		foreach ( [ '_thumbnail_id', '_listing_prv_img', '_listing_img', '_wp_page_template' ] as $media_key ) {
			if ( ! array_key_exists( $media_key, $meta ) ) {
				delete_post_meta( $target_post_id, $media_key );
			}
		}

		foreach ( $meta as $key => $values ) {
			if ( in_array( (string) $key, $excluded_keys, true ) ) {
				continue;
			}

			delete_post_meta( $target_post_id, (string) $key );

			foreach ( (array) $values as $value ) {
				add_post_meta( $target_post_id, (string) $key, wp_slash( maybe_unserialize( $value ) ) );
			}
		}
	}

	/**
	 * Copy taxonomy terms from one post to another.
	 *
	 * @param int    $source_post_id Source post id.
	 * @param int    $target_post_id Target post id.
	 * @param string $post_type Post type.
	 * @return void
	 */
	protected function copy_post_terms( int $source_post_id, int $target_post_id, string $post_type ): void {
		$taxonomies = get_object_taxonomies( $post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$term_ids = wp_get_object_terms( $source_post_id, $taxonomy, [ 'fields' => 'ids' ] );

			if ( is_wp_error( $term_ids ) ) {
				continue;
			}

			wp_set_object_terms( $target_post_id, array_map( 'absint', $term_ids ), $taxonomy, false );
		}
	}

	/**
	 * Determine which native Elementor kit runners should be used.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $args Normalized import args.
	 * @return array<int,string>
	 */
	protected function get_elementor_kit_import_include( array $manifest, array $args = [] ): array {
		$include = [];

		if ( ! empty( $args['include_templates'] ) && ! empty( $manifest['templates'] ) && is_array( $manifest['templates'] ) ) {
			$include[] = 'templates';
		}

		if ( ! empty( $manifest['plugins'] ) && is_array( $manifest['plugins'] ) && current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) ) {
			$include[] = 'plugins';
		}

		if ( ! empty( $args['include_content'] ) && ( ! empty( $manifest['content'] ) || ! empty( $manifest['wp-content'] ) || ! empty( $manifest['taxonomies'] ) ) ) {
			$include[] = 'content';
		}

		if ( ! empty( $args['apply_site_settings'] ) && ( ! empty( $manifest['site-settings'] ) || ! empty( $manifest['settings'] ) ) ) {
			$include[] = 'settings';
		}

		if ( empty( $include ) && ! empty( $args['include_templates'] ) ) {
			$include[] = 'templates';
		}

		return array_values( array_unique( $include ) );
	}

	/**
	 * Get custom post types selected for native Elementor kit import.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return array<int,string>
	 */
	protected function get_elementor_kit_custom_post_types( array $manifest ): array {
		if ( empty( $manifest['custom-post-type-title'] ) || ! is_array( $manifest['custom-post-type-title'] ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'sanitize_key', array_keys( $manifest['custom-post-type-title'] ) ) ) );
	}

	/**
	 * Collect old-to-new taxonomy term IDs from Elementor's import output.
	 *
	 * @param array<string,mixed> $raw_result Elementor import result.
	 * @return array<string,array<int,int>>
	 */
	protected function collect_elementor_kit_term_id_map( array $raw_result ): array {
		$map = [];

		if ( empty( $raw_result['taxonomies'] ) || ! is_array( $raw_result['taxonomies'] ) ) {
			return $map;
		}

		foreach ( $raw_result['taxonomies'] as $post_type_taxonomies ) {
			if ( ! is_array( $post_type_taxonomies ) ) {
				continue;
			}

			foreach ( $post_type_taxonomies as $taxonomy => $terms ) {
				if ( ! is_array( $terms ) ) {
					continue;
				}

				foreach ( $terms as $term ) {
					if ( ! is_array( $term ) || ! isset( $term['old_id'], $term['new_id'] ) ) {
						continue;
					}

					$map[ sanitize_key( (string) $taxonomy ) ][ absint( $term['old_id'] ) ] = absint( $term['new_id'] );
				}
			}
		}

		return $map;
	}

	/**
	 * Merge native term mappings with package-owned terms selected before import.
	 *
	 * The existing map takes precedence because Elementor deliberately creates a
	 * duplicate nav-menu term when it sees an existing slug during kit import.
	 *
	 * @param array<string,array<int,int>> $native_map Native importer mappings.
	 * @param array<string,array<int,int>> $existing_map Existing package-owned mappings.
	 * @return array<string,array<int,int>>
	 */
	protected function merge_elementor_term_id_maps( array $native_map, array $existing_map ): array {
		foreach ( $existing_map as $taxonomy => $terms ) {
			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $source_id => $target_id ) {
				$source_id = absint( $source_id );
				$target_id = absint( $target_id );

				if ( $source_id > 0 && $target_id > 0 ) {
					$native_map[ sanitize_key( (string) $taxonomy ) ][ $source_id ] = $target_id;
				}
			}
		}

		return $native_map;
	}

	/**
	 * Persist taxonomy mappings in the import session for validation and repair.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,array<int,int>>     $term_id_map Term ID map by taxonomy.
	 * @return void
	 */
	protected function record_elementor_kit_term_id_map( ImportSession $session, array $term_id_map ): void {
		foreach ( $term_id_map as $taxonomy => $terms ) {
			if ( ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $source_id => $target_id ) {
				$source_id = absint( $source_id );
				$target_id = absint( $target_id );

				if ( $source_id > 0 && $target_id > 0 ) {
					$session->map_id( 'term_source_id:' . sanitize_key( (string) $taxonomy ), (string) $source_id, $target_id );
				}
			}
		}
	}

	/**
	 * Persist source ownership after component import has classified new terms.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,array<int,int>>     $term_id_map Term ID map by taxonomy.
	 * @param string                           $conflict_behavior Conflict behavior.
	 * @return void
	 */
	protected function record_elementor_kit_term_ownership( ImportSession $session, array $term_id_map, string $conflict_behavior ): void {
		if ( 'duplicate' === $conflict_behavior ) {
			return;
		}

		foreach ( $term_id_map as $taxonomy => $terms ) {
			$taxonomy = sanitize_key( (string) $taxonomy );

			if ( $this->should_share_import_term_by_slug( $taxonomy ) || ! is_array( $terms ) ) {
				continue;
			}

			foreach ( $terms as $source_id => $target_id ) {
				$source_id = absint( $source_id );
				$target_id = absint( $target_id );

				if ( $source_id <= 0 || $target_id <= 0 || ! get_term( $target_id, $taxonomy ) ) {
					continue;
				}

				update_term_meta( $target_id, '_directorist_import_package_id', $session->get_package_id() );
				update_term_meta( $target_id, '_directorist_import_source_id', (string) $source_id );
			}
		}
	}

	/**
	 * Store both legacy and post-type-specific source mappings.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $post_type Imported post type.
	 * @param string        $source_id Source post ID.
	 * @param int           $post_id Imported post ID.
	 * @return void
	 */
	protected function map_imported_post_id( ImportSession $session, string $post_type, string $source_id, int $post_id ): void {
		$post_type = sanitize_key( $post_type );
		$source_id = sanitize_text_field( $source_id );
		$post_id   = absint( $post_id );

		if ( '' === $source_id || $post_id <= 0 ) {
			return;
		}

		$session->map_id( 'post_source_id', $source_id, $post_id );

		if ( '' !== $post_type ) {
			$session->map_id( 'post_source_id:' . $post_type, $source_id, $post_id );
		}
	}

	/**
	 * Map Elementor's temporary imported post to its conflict-resolved target.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $post_type Imported post type.
	 * @param int           $native_post_id Elementor's imported post ID.
	 * @param int           $post_id Conflict-resolved post ID.
	 * @return void
	 */
	protected function map_native_imported_post_id( ImportSession $session, string $post_type, int $native_post_id, int $post_id ): void {
		$post_type      = sanitize_key( $post_type );
		$native_post_id = absint( $native_post_id );
		$post_id        = absint( $post_id );

		if ( '' === $post_type || $native_post_id <= 0 || $post_id <= 0 || $native_post_id === $post_id ) {
			return;
		}

		$session->map_id( 'post_import_id:' . $post_type, (string) $native_post_id, $post_id );
	}

	/**
	 * Resolve a source post ID without allowing cross-post-type collisions.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $post_type Source post type.
	 * @param string        $source_id Source post ID.
	 * @return int|null
	 */
	protected function get_mapped_post_id( ImportSession $session, string $post_type, string $source_id ): ?int {
		$post_type = sanitize_key( $post_type );

		if ( '' !== $post_type ) {
			$mapped_id = $session->get_mapped_id( 'post_source_id:' . $post_type, $source_id );

			if ( null !== $mapped_id ) {
				return $mapped_id;
			}
		}

		return $session->get_mapped_id( 'post_source_id', $source_id );
	}

	/**
	 * Remap Directorist listing directory type meta after taxonomy import.
	 *
	 * @param int                         $post_id Listing id.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @return void
	 */
	protected function remap_directorist_listing_directory_type( int $post_id, array $term_id_map ): void {
		$source_term_id = absint( get_post_meta( $post_id, '_directory_type', true ) );

		if ( $source_term_id <= 0 || empty( $term_id_map['atbdp_listing_types'][ $source_term_id ] ) ) {
			return;
		}

		update_post_meta( $post_id, '_directory_type', absint( $term_id_map['atbdp_listing_types'][ $source_term_id ] ) );
	}

	/**
	 * Rebuild imported nav menu relationships from the Elementor kit WXR files.
	 *
	 * Elementor's native content runner can import custom menu parents and page
	 * menu children through different internal paths. The final menu must follow
	 * the source WXR exactly so Elementor nav-menu widgets can use one menu.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $manifest Elementor kit manifest.
	 * @param string                           $package_file Zip package file.
	 * @param array<string,array<int,int>>     $term_id_map Term ID map by taxonomy.
	 * @param string                           $elementor_session_id Elementor native import session id.
	 * @return int Number of repaired menu items not already counted.
	 */
	protected function repair_imported_nav_menus( ImportSession $session, array $manifest, string $package_file, array &$term_id_map, string $elementor_session_id = '' ): int {
		$wxr = $this->read_elementor_kit_nav_menu_wxr( $package_file );

		if ( empty( $wxr['items'] ) || ! is_array( $wxr['items'] ) ) {
			return 0;
		}

		$items            = $wxr['items'];
		$terms_by_slug    = isset( $wxr['terms_by_slug'] ) && is_array( $wxr['terms_by_slug'] ) ? $wxr['terms_by_slug'] : [];
		$source_site_url  = (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' );
		$source_to_target = [];
		$target_menu_ids  = [];
		$recorded_items   = 0;
		$created_menu_ids = array_map( 'absint', (array) ( $session->get_created()['nav_menu_item'] ?? [] ) );
		$updated_menu_ids = array_map( 'absint', (array) ( $session->get_updated()['nav_menu_item'] ?? [] ) );

		foreach ( $items as $source_id => $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$target_menu_id = $this->resolve_imported_nav_menu_term_id( $session, $item, $terms_by_slug, $term_id_map );
			$post_id        = $this->resolve_imported_nav_menu_item_post_id( $session, $manifest, $item, $elementor_session_id );

			if ( $target_menu_id <= 0 || $post_id <= 0 ) {
				continue;
			}

			$this->record_repaired_nav_menu_mapping( $session, $item, $terms_by_slug, $term_id_map, $target_menu_id );
			$source_to_target[ absint( $source_id ) ] = $post_id;
			$target_menu_ids[ $target_menu_id ]       = $target_menu_id;
		}

		if ( empty( $source_to_target ) ) {
			return 0;
		}

		foreach ( $items as $source_id => $item ) {
			$source_id = absint( $source_id );
			$post_id   = absint( $source_to_target[ $source_id ] ?? 0 );

			if ( $post_id <= 0 || ! is_array( $item ) ) {
				continue;
			}

			$target_menu_id = $this->resolve_imported_nav_menu_term_id( $session, $item, $terms_by_slug, $term_id_map );
			$meta           = isset( $item['meta'] ) && is_array( $item['meta'] ) ? $item['meta'] : [];
			$parent_source  = absint( $meta['_menu_item_menu_item_parent'] ?? 0 );
			$parent_target  = $parent_source > 0 ? absint( $source_to_target[ $parent_source ] ?? 0 ) : 0;
			$object_id      = $this->resolve_imported_nav_menu_item_object_id( $session, $post_id, $meta );
			$source_uid     = $this->get_directorist_manifest_source_uid( $manifest, 'nav_menu_item', (string) $source_id );

			if ( '' === $source_uid ) {
				$source_uid = $session->get_package_id() . ':nav_menu_item:' . $source_id;
			}

			if ( ! in_array( $post_id, $created_menu_ids, true ) && ! in_array( $post_id, $updated_menu_ids, true ) ) {
				$was_imported = $session->get_package_id() === (string) get_post_meta( $post_id, '_directorist_import_package_id', true );

				if ( $was_imported ) {
					$session->record_updated( 'nav_menu_item', $post_id );
					$updated_menu_ids[] = $post_id;
				} else {
					$session->record_created( 'nav_menu_item', $post_id );
					$created_menu_ids[] = $post_id;
				}

				$recorded_items++;
			}

			wp_update_post(
				[
					'ID'         => $post_id,
					'menu_order' => absint( $item['menu_order'] ?? 0 ),
				]
			);

			update_post_meta( $post_id, '_menu_item_menu_item_parent', (string) $parent_target );
			update_post_meta( $post_id, '_menu_item_object_id', (string) $object_id );
			update_post_meta( $post_id, '_directorist_import_source_uid', $source_uid );
			update_post_meta( $post_id, '_directorist_import_package_id', $session->get_package_id() );
			update_post_meta( $post_id, '_directorist_import_package_version', $session->get_package_version() );
			update_post_meta( $post_id, '_directorist_import_last_imported_at', current_time( 'mysql' ) );
			$this->map_imported_post_id( $session, 'nav_menu_item', (string) $source_id, $post_id );
			$session->map_id( 'post_uid', $source_uid, $post_id );

			if ( isset( $meta['_menu_item_url'] ) ) {
				update_post_meta( $post_id, '_menu_item_url', esc_url_raw( $this->replace_imported_source_url( (string) $meta['_menu_item_url'], $session, $source_site_url ) ) );
			}

			if ( $target_menu_id > 0 ) {
				wp_set_object_terms( $post_id, [ $target_menu_id ], 'nav_menu', false );
			}
		}

		$this->delete_unselected_imported_nav_menu_items( array_values( $source_to_target ), $elementor_session_id );
		$this->delete_empty_duplicate_nav_menus( array_values( $target_menu_ids ) );

		return $recorded_items;
	}

	/**
	 * Preserve source and temporary menu aliases before duplicate cleanup.
	 *
	 * @param ImportSession                         $session Import session.
	 * @param ImportSession                         $session Import session.
	 * @param array<string,mixed>                   $item Source menu item.
	 * @param array<string,array<string,mixed>>     $terms_by_slug Source terms by slug.
	 * @param array<string,array<int,int>>          $term_id_map Term ID map by taxonomy.
	 * @param int                                   $target_menu_id Final menu term ID.
	 * @return void
	 */
	protected function record_repaired_nav_menu_mapping( ImportSession $session, array $item, array $terms_by_slug, array &$term_id_map, int $target_menu_id ): void {
		$target_menu_id = absint( $target_menu_id );

		if ( $target_menu_id <= 0 ) {
			return;
		}

		$aliases     = [];
		$target_term = get_term( $target_menu_id, 'nav_menu' );

		if ( $target_term instanceof \WP_Term ) {
			$aliases[] = $target_term->slug;
		}

		foreach ( (array) ( $item['menu_slugs'] ?? [] ) as $source_slug ) {
			$source_slug    = sanitize_title( (string) $source_slug );
			$source_term_id = absint( $terms_by_slug[ $source_slug ]['term_id'] ?? 0 );
			$native_term_id = absint( $term_id_map['nav_menu'][ $source_term_id ] ?? 0 );
			$native_term    = $native_term_id > 0 ? get_term( $native_term_id, 'nav_menu' ) : null;

			if ( '' !== $source_slug ) {
				$aliases[] = $source_slug;
			}

			if ( $native_term instanceof \WP_Term ) {
				$aliases[] = $native_term->slug;
			}

			if ( $source_term_id > 0 ) {
				$term_id_map['nav_menu'][ $source_term_id ] = $target_menu_id;
				$session->map_id( 'term_source_id:nav_menu', (string) $source_term_id, $target_menu_id );
			}
		}

		foreach ( array_unique( array_filter( array_map( 'sanitize_title', $aliases ) ) ) as $alias ) {
			$session->map_id( 'nav_menu_source_slug', $alias, $target_menu_id );
		}
	}

	/**
	 * Read nav menu item WXR files bundled in an Elementor kit package.
	 *
	 * @param string $package_file Zip package file.
	 * @return array{items:array<int,array<string,mixed>>,terms_by_slug:array<string,array<string,mixed>>}
	 */
	protected function read_elementor_kit_nav_menu_wxr( string $package_file ): array {
		$result = [
			'items'         => [],
			'terms_by_slug' => [],
		];

		if ( ! class_exists( ZipArchive::class ) || ! function_exists( 'simplexml_load_string' ) ) {
			return $result;
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return $result;
		}

		for ( $index = 0; $index < $zip->numFiles; $index++ ) {
			$name = (string) $zip->getNameIndex( $index );

			if ( ! preg_match( '#^wp-content/nav_menu_item/.+\.xml$#', $name ) ) {
				continue;
			}

			$xml = $zip->getFromIndex( $index );

			if ( ! is_string( $xml ) || '' === $xml ) {
				continue;
			}

			$parsed = $this->parse_nav_menu_wxr_xml( $xml );
			$result['items'] = array_replace( $result['items'], $parsed['items'] );
			$result['terms_by_slug'] = array_replace( $result['terms_by_slug'], $parsed['terms_by_slug'] );
		}

		$zip->close();

		return $result;
	}

	/**
	 * Parse a WordPress WXR nav menu export.
	 *
	 * @param string $xml WXR XML.
	 * @return array{items:array<int,array<string,mixed>>,terms_by_slug:array<string,array<string,mixed>>}
	 */
	protected function parse_nav_menu_wxr_xml( string $xml ): array {
		$result = [
			'items'         => [],
			'terms_by_slug' => [],
		];

		$previous = libxml_use_internal_errors( true );
		$document = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $document instanceof \SimpleXMLElement || empty( $document->channel ) ) {
			return $result;
		}

		$wp_namespace = 'http://wordpress.org/export/1.2/';
		$channel      = $document->channel;

		foreach ( $channel->children( $wp_namespace )->term as $term ) {
			$term_data = $term->children( $wp_namespace );
			$taxonomy  = sanitize_key( (string) $term_data->term_taxonomy );
			$slug      = sanitize_title( (string) $term_data->term_slug );

			if ( 'nav_menu' !== $taxonomy || '' === $slug ) {
				continue;
			}

			$result['terms_by_slug'][ $slug ] = [
				'term_id' => absint( (string) $term_data->term_id ),
				'name'    => sanitize_text_field( (string) $term_data->term_name ),
				'slug'    => $slug,
			];
		}

		foreach ( $channel->item as $item ) {
			$wp_item = $item->children( $wp_namespace );

			if ( 'nav_menu_item' !== sanitize_key( (string) $wp_item->post_type ) ) {
				continue;
			}

			$source_id = absint( (string) $wp_item->post_id );

			if ( $source_id <= 0 ) {
				continue;
			}

			$meta = [];

			foreach ( $wp_item->postmeta as $postmeta ) {
				$key = sanitize_key( (string) $postmeta->meta_key );

				if ( '' === $key ) {
					continue;
				}

				$meta[ $key ] = (string) $postmeta->meta_value;
			}

			$menu_slugs = [];

			foreach ( $item->category as $category ) {
				$attributes = $category->attributes();
				$domain     = sanitize_key( (string) ( $attributes['domain'] ?? '' ) );
				$slug       = sanitize_title( (string) ( $attributes['nicename'] ?? '' ) );

				if ( 'nav_menu' === $domain && '' !== $slug ) {
					$menu_slugs[] = $slug;
				}
			}

			$result['items'][ $source_id ] = [
				'source_id'  => $source_id,
				'title'      => sanitize_text_field( (string) $item->title ),
				'menu_order' => absint( (string) $wp_item->menu_order ),
				'menu_slugs' => array_values( array_unique( $menu_slugs ) ),
				'meta'       => $meta,
			];
		}

		return $result;
	}

	/**
	 * Resolve the imported nav menu term for a source WXR menu item.
	 *
	 * @param array<string,mixed>          $item Source menu item.
	 * @param array<string,array<string,mixed>> $terms_by_slug Source terms by slug.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @return int
	 */
	protected function resolve_imported_nav_menu_term_id( ImportSession $session, array $item, array $terms_by_slug, array $term_id_map ): int {
		$menu_slugs = isset( $item['menu_slugs'] ) && is_array( $item['menu_slugs'] ) ? $item['menu_slugs'] : [];
		$nav_map    = isset( $term_id_map['nav_menu'] ) && is_array( $term_id_map['nav_menu'] ) ? $term_id_map['nav_menu'] : [];

		foreach ( $menu_slugs as $menu_slug ) {
			$menu_slug      = sanitize_title( (string) $menu_slug );
			$source_term_id = absint( $terms_by_slug[ $menu_slug ]['term_id'] ?? 0 );

			if ( $source_term_id > 0 && ! empty( $nav_map[ $source_term_id ] ) ) {
				return absint( $nav_map[ $source_term_id ] );
			}

			$term = get_term_by( 'slug', $menu_slug, 'nav_menu' );

			if ( $term instanceof \WP_Term ) {
				$owner = (string) get_term_meta( $term->term_id, '_directorist_import_package_id', true );

				if ( $session->get_package_id() === $owner ) {
					return absint( $term->term_id );
				}
			}
		}

		return 0;
	}

	/**
	 * Resolve an imported nav menu item post ID for a source WXR item.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param array<string,mixed> $item Source menu item.
	 * @param string              $elementor_session_id Elementor native import session id.
	 * @return int
	 */
	protected function resolve_imported_nav_menu_item_post_id( ImportSession $session, array $manifest, array $item, string $elementor_session_id = '' ): int {
		$source_id  = absint( $item['source_id'] ?? 0 );
		$source_uid = $this->get_directorist_manifest_source_uid( $manifest, 'nav_menu_item', (string) $source_id );

		if ( '' !== $source_uid ) {
			$post_id = $this->find_existing_imported_post_id( $source_uid );

			if ( $post_id > 0 && 'nav_menu_item' === get_post_type( $post_id ) ) {
				return $post_id;
			}
		}

		$mapped_id = $session->get_mapped_id( 'post_source_id', (string) $source_id );

		if ( $mapped_id && 'nav_menu_item' === get_post_type( $mapped_id ) ) {
			return absint( $mapped_id );
		}

		$meta      = isset( $item['meta'] ) && is_array( $item['meta'] ) ? $item['meta'] : [];
		$type      = sanitize_key( (string) ( $meta['_menu_item_type'] ?? '' ) );
		$object    = sanitize_key( (string) ( $meta['_menu_item_object'] ?? '' ) );
		$object_id = $this->resolve_imported_nav_menu_source_object_id( $session, $meta );

		$meta_query = [
			'relation' => 'AND',
			[
				'key'   => '_menu_item_type',
				'value' => $type,
			],
			[
				'key'   => '_menu_item_object',
				'value' => $object,
			],
			[
				'key'   => '_menu_item_object_id',
				'value' => (string) $object_id,
			],
		];

		if ( '' !== $elementor_session_id ) {
			$meta_query[] = [
				'key'   => '_elementor_import_session_id',
				'value' => $elementor_session_id,
			];
		}

		$candidates = get_posts(
			[
				'post_type'      => 'nav_menu_item',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_query'     => $meta_query,
			]
		);

		$source_order = absint( $item['menu_order'] ?? 0 );

		foreach ( $candidates as $candidate_id ) {
			$post = get_post( absint( $candidate_id ) );

			if ( $post instanceof \WP_Post && absint( $post->menu_order ) === $source_order ) {
				return absint( $candidate_id );
			}
		}

		return ! empty( $candidates[0] ) ? absint( $candidates[0] ) : 0;
	}

	/**
	 * Resolve the local object ID a menu item should point to.
	 *
	 * @param ImportSession       $session Import session.
	 * @param int                 $post_id Imported nav menu item ID.
	 * @param array<string,mixed> $meta Source item meta.
	 * @return int
	 */
	protected function resolve_imported_nav_menu_item_object_id( ImportSession $session, int $post_id, array $meta ): int {
		$type             = sanitize_key( (string) ( $meta['_menu_item_type'] ?? '' ) );
		$source_object_id = absint( $meta['_menu_item_object_id'] ?? 0 );

		if ( 'custom' === $type ) {
			return $post_id;
		}

		return $this->resolve_imported_nav_menu_source_object_id( $session, $meta );
	}

	/**
	 * Resolve the local target object for post-type menu items.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $meta Source item meta.
	 * @return int
	 */
	protected function resolve_imported_nav_menu_source_object_id( ImportSession $session, array $meta ): int {
		$source_object_id = absint( $meta['_menu_item_object_id'] ?? 0 );

		if ( $source_object_id <= 0 ) {
			return 0;
		}

		$type   = sanitize_key( (string) ( $meta['_menu_item_type'] ?? '' ) );
		$object = sanitize_key( (string) ( $meta['_menu_item_object'] ?? '' ) );
		if ( 'taxonomy' === $type ) {
			$mapped = $session->get_mapped_id( 'term_source_id:' . $object, (string) $source_object_id );
			$term = $mapped ? get_term( $mapped, $object ) : null;
			return $term instanceof \WP_Term ? (int) $term->term_id : 0;
		}
		if ( 'post_type' !== $type ) {
			return 0;
		}
		$mapped = $this->get_mapped_post_id( $session, $object, (string) $source_object_id );
		return $mapped && $object === get_post_type( $mapped ) ? absint( $mapped ) : 0;
	}

	/**
	 * Delete extra native-import nav menu item copies from the same Elementor run.
	 *
	 * @param array<int,int> $selected_ids Menu item IDs selected by the WXR repair.
	 * @param string         $elementor_session_id Elementor native import session id.
	 * @return void
	 */
	protected function delete_unselected_imported_nav_menu_items( array $selected_ids, string $elementor_session_id ): void {
		if ( '' === $elementor_session_id ) {
			return;
		}

		$selected_ids = array_values( array_unique( array_filter( array_map( 'absint', $selected_ids ) ) ) );
		$items        = get_posts(
			[
				'post_type'      => 'nav_menu_item',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_elementor_import_session_id',
				'meta_value'     => $elementor_session_id,
			]
		);

		foreach ( $items as $post_id ) {
			$post_id = absint( $post_id );

			if ( $post_id > 0 && ! in_array( $post_id, $selected_ids, true ) ) {
				wp_delete_post( $post_id, true );
			}
		}
	}

	/**
	 * Remove empty duplicate nav menu terms created during native kit import.
	 *
	 * @param array<int,int> $target_menu_ids Menu term IDs that should remain.
	 * @return void
	 */
	protected function delete_empty_duplicate_nav_menus( array $target_menu_ids ): void {
		$target_menu_ids = array_values( array_unique( array_filter( array_map( 'absint', $target_menu_ids ) ) ) );

		if ( empty( $target_menu_ids ) ) {
			return;
		}

		$all_menus = wp_get_nav_menus( [ 'hide_empty' => false ] );

		foreach ( $target_menu_ids as $target_menu_id ) {
			$target = wp_get_nav_menu_object( $target_menu_id );

			if ( ! $target instanceof \WP_Term ) {
				continue;
			}

			foreach ( $all_menus as $menu ) {
				if ( ! $menu instanceof \WP_Term || absint( $menu->term_id ) === $target_menu_id ) {
					continue;
				}

				$items = wp_get_nav_menu_items( $menu->term_id );

				if ( ! empty( $items ) ) {
					continue;
				}

				if ( 0 === strpos( $menu->slug, $target->slug ) || 0 === strpos( $menu->name, $target->name ) ) {
					wp_delete_nav_menu( $menu->term_id );
				}
			}
		}
	}

	/**
	 * Get the source UID recorded in the Directorist manifest document map.
	 *
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $post_type Source post type.
	 * @param string              $source_id Source post ID.
	 * @return string
	 */
	protected function get_directorist_manifest_source_uid( array $manifest, string $post_type, string $source_id ): string {
		$documents = isset( $manifest['directorist']['documents'] ) && is_array( $manifest['directorist']['documents'] ) ? $manifest['directorist']['documents'] : [];
		$key       = sanitize_key( $post_type ) . ':' . sanitize_text_field( $source_id );

		return isset( $documents[ $key ]['source_uid'] ) ? sanitize_text_field( (string) $documents[ $key ]['source_uid'] ) : '';
	}

	/**
	 * Replace the source site URL in one imported value.
	 *
	 * @param string $value Source value.
	 * @param string $source_site_url Source site URL.
	 * @return string
	 */
	protected function replace_source_site_url( string $value, string $source_site_url ): string {
		$source_site_url = untrailingslashit( $source_site_url );
		$target_site_url = untrailingslashit( home_url( '/' ) );

		if ( '' === $value || '' === $source_site_url || '' === $target_site_url || $source_site_url === $target_site_url ) {
			return $value;
		}

		$map = [];
		foreach ( array_unique( [ $source_site_url, set_url_scheme( $source_site_url, 'http' ), set_url_scheme( $source_site_url, 'https' ) ] ) as $source_variant ) {
			$source_variant = untrailingslashit( $source_variant );
			$map[ $source_variant . '/' ] = $target_site_url . '/';
			$map[ $source_variant ]       = $target_site_url;
		}
		return strtr( $value, $map );
	}

	/**
	 * Replace an exact imported permalink before applying the site-level URL fallback.
	 *
	 * @param string        $value Source value.
	 * @param ImportSession $session Import session.
	 * @param string        $source_site_url Source site URL.
	 * @return string
	 */
	protected function replace_imported_source_url( string $value, ImportSession $session, string $source_site_url ): string {
		$url_map = $this->without_site_root_url_mappings( $session->get_url_map(), $source_site_url );

		if ( ! empty( $url_map ) ) {
			$value = $this->replace_mapped_values_once( $value, $url_map );
		}

		return $this->replace_source_site_url( $value, $source_site_url );
	}

	/**
	 * Remove site-root entries from object URL maps.
	 *
	 * Elementor can normalize the source homepage URL before Directorist records
	 * imported object URLs. Treating either site root as a page permalink would
	 * rewrite every local asset URL beneath it to the imported homepage slug.
	 * Site roots are handled separately as an authoritative site-level fallback.
	 *
	 * @param array<string,string> $map Source-to-target URL map.
	 * @param string               $source_site_url Source site URL.
	 * @return array<string,string>
	 */
	protected function without_site_root_url_mappings( array $map, string $source_site_url = '' ): array {
		$roots = [
			untrailingslashit( home_url( '/' ) ),
			trailingslashit( home_url( '/' ) ),
		];

		$source_site_url = trim( $source_site_url );

		if ( '' !== $source_site_url ) {
			$roots[] = untrailingslashit( $source_site_url );
			$roots[] = trailingslashit( $source_site_url );
		}

		foreach ( array_unique( array_filter( $roots ) ) as $root ) {
			unset( $map[ $root ] );
		}

		return $map;
	}

	/**
	 * Apply a replacement map without remapping values already at a target.
	 *
	 * @param string               $value Value to rewrite.
	 * @param array<string,string> $map Source-to-target map.
	 * @return string
	 */
	protected function replace_mapped_values_once( string $value, array $map ): string {
		$map = array_filter(
			$map,
			static function ( $target, $source ): bool {
				return is_string( $source ) && '' !== $source && is_string( $target ) && $source !== $target;
			},
			ARRAY_FILTER_USE_BOTH
		);

		if ( '' === $value || empty( $map ) ) {
			return $value;
		}

		uksort(
			$map,
			static function ( string $first, string $second ): int {
				return strlen( $second ) <=> strlen( $first );
			}
		);
		$corrections = [];

		foreach ( $map as $source => $target ) {
			if ( ! str_contains( $target, $source ) ) {
				continue;
			}

			$overmapped = str_replace( $source, $target, $target );

			if ( $overmapped !== $target ) {
				$corrections[ $overmapped ] = $target;
			}
		}

		if ( ! empty( $corrections ) ) {
			uksort(
				$corrections,
				static function ( string $first, string $second ): int {
					return strlen( $second ) <=> strlen( $first );
				}
			);
			$value = strtr( $value, $corrections );
		}

		$targets = array_values( array_unique( array_filter( array_values( $map ) ) ) );
		$site_roots = array_values(
			array_unique(
				[
					untrailingslashit( home_url( '/' ) ),
					trailingslashit( home_url( '/' ) ),
				]
			)
		);
		$targets = array_values(
			array_filter(
				$targets,
				static function ( string $target ) use ( $site_roots ): bool {
					return ! in_array( $target, $site_roots, true );
				}
			)
		);
		usort(
			$targets,
			static function ( string $first, string $second ): int {
				return strlen( $second ) <=> strlen( $first );
			}
		);
		$protected = [];

		foreach ( $targets as $index => $target ) {
			if ( ! str_contains( $value, $target ) ) {
				continue;
			}

			$placeholder = '%%DIRECTORIST_IMPORTED_TARGET_' . $index . '_' . md5( $target ) . '%%';
			$value = str_replace( $target, $placeholder, $value );
			$protected[ $placeholder ] = $target;
		}

		$value = strtr( $value, $map );

		return empty( $protected ) ? $value : strtr( $value, $protected );
	}

	/**
	 * Recursively replace source-site URLs in portable component values.
	 *
	 * @param mixed  $value Portable value.
	 * @param string $source_site_url Source site URL.
	 * @return mixed
	 */
	protected function replace_source_site_url_in_value( $value, string $source_site_url ) {
		if ( is_string( $value ) ) {
			return $this->replace_source_site_url( $value, $source_site_url );
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->replace_source_site_url_in_value( $child, $source_site_url );
		}

		return $value;
	}

	/**
	 * Remap source object IDs inside Elementor theme-builder conditions.
	 *
	 * @param array<int,mixed> $conditions Elementor conditions.
	 * @param ImportSession    $session Import session.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @return array<int,string>
	 */
	protected function remap_elementor_template_conditions( array $conditions, ImportSession $session, array $term_id_map = [] ): array {
		$remapped = [];

		foreach ( $conditions as $condition ) {
			$condition = sanitize_text_field( (string) $condition );
			$parts     = explode( '/', $condition );

			if ( count( $parts ) >= 4 && is_numeric( end( $parts ) ) ) {
				$source_id = (string) absint( end( $parts ) );
				$target_id = null;

				if ( 'directorist_listing' === (string) ( $parts[1] ?? '' ) && 'directorist_listing_directory' === (string) ( $parts[2] ?? '' ) ) {
					$target_id = $term_id_map['atbdp_listing_types'][ absint( $source_id ) ] ?? null;
				} elseif ( 'singular' === (string) ( $parts[1] ?? '' ) ) {
					$target_id = $this->get_mapped_post_id( $session, (string) ( $parts[2] ?? '' ), $source_id );
				}

				if ( $target_id ) {
					$parts[ count( $parts ) - 1 ] = (string) absint( $target_id );
					$condition = implode( '/', $parts );
				}
			}

			$remapped[] = $condition;
		}

		return array_values( array_unique( $remapped ) );
	}

	/**
	 * Remap imported Elementor documents after every source mapping is available.
	 *
	 * @param ImportSession                    $session Import session.
	 * @param array<string,mixed>              $manifest Elementor kit manifest.
	 * @param array<string,array<int,int>>     $term_id_map Term ID map by taxonomy.
	 * @param bool                             $activate_templates Whether to restore template conditions.
	 * @return array<int,string>
	 */
	protected function finalize_imported_elementor_documents( ImportSession $session, array $manifest, array $term_id_map, bool $activate_templates, ?array $selected_post_ids = null ): array {
		$errors        = [];
		$template_meta = isset( $manifest['directorist']['templates'] ) && is_array( $manifest['directorist']['templates'] ) ? $manifest['directorist']['templates'] : [];
		$document_meta = isset( $manifest['directorist']['documents'] ) && is_array( $manifest['directorist']['documents'] ) ? $manifest['directorist']['documents'] : [];
		$templates     = isset( $manifest['templates'] ) && is_array( $manifest['templates'] ) ? $manifest['templates'] : [];
		$source_url    = (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' );
		$post_ids      = $selected_post_ids ?? $this->get_imported_package_post_ids( $session );

		foreach ( $post_ids as $post_id ) {
			$post_id        = absint( $post_id );
			$post_type      = sanitize_key( (string) get_post_type( $post_id ) );
			$source_id      = $this->get_imported_source_post_id( $session, $post_type, $post_id );
			$source_payload = $this->is_source_elementor_payload( $session, $post_type, $post_id );
			$media_normalized = ! empty( $this->active_normalized_elementor_post_ids[ $post_id ] );

			if ( $post_id <= 0 || '' === $post_type ) {
				continue;
			}

			if ( 'elementor_library' === $post_type && '' !== $source_id ) {
				$meta = $this->resolve_directorist_document_meta( $document_meta, $template_meta, $post_type, $source_id );

				if ( $activate_templates && ! empty( $meta['conditions'] ) && is_array( $meta['conditions'] ) ) {
					update_post_meta( $post_id, '_elementor_conditions', $this->remap_elementor_template_conditions( $meta['conditions'], $session, $term_id_map ) );
				}

				$template      = isset( $templates[ $source_id ] ) && is_array( $templates[ $source_id ] ) ? $templates[ $source_id ] : [];
				$template_type = sanitize_key( (string) ( $template['doc_type'] ?? get_post_meta( $post_id, '_elementor_template_type', true ) ) );
				$template_type = $this->remap_elementor_document_type( $template_type, $term_id_map );

				if ( '' !== $template_type ) {
					update_post_meta( $post_id, '_elementor_template_type', $template_type );
				}
			}

			$raw_data = (string) get_post_meta( $post_id, '_elementor_data', true );
			$data     = json_decode( $raw_data, true );

			if ( '' !== trim( $raw_data ) && ! is_array( $data ) ) {
				$errors[] = sprintf(
					/* translators: 1: imported post ID, 2: JSON error message. */
					__( 'Imported Elementor post %1$d contains invalid element data: %2$s.', 'directorist-elementor' ),
					$post_id,
					json_last_error_msg()
				);
			}

			if ( is_array( $data ) ) {
				$remapped_data = $this->remap_imported_elementor_value( $data, $term_id_map, $source_url, '', $session, $source_payload, $media_normalized );

				if ( $remapped_data !== $data ) {
					update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $remapped_data ) ) );
				}

				$errors = array_merge( $errors, $this->validate_elementor_template_references( $post_id, $remapped_data ) );
			}

			$page_settings = get_post_meta( $post_id, '_elementor_page_settings', true );

			if ( is_array( $page_settings ) ) {
				$remapped_settings = $this->remap_imported_elementor_value( $page_settings, $term_id_map, $source_url, '', $session, $source_payload, $media_normalized );

				if ( $remapped_settings !== $page_settings ) {
					update_post_meta( $post_id, '_elementor_page_settings', $remapped_settings );
				}
			}

			if ( 'elementor_library' === $post_type ) {
				$error = $this->validate_elementor_document_type( $post_id );

				if ( '' !== $error ) {
					$errors[] = $error;
				}
			}

			$this->clear_elementor_cache_for_post( $post_id );
		}

		return array_values( array_unique( $errors ) );
	}

	/**
	 * Rebuild Elementor Pro's condition index after imported conditions change.
	 *
	 * @return void
	 */
	protected function refresh_elementor_theme_builder_conditions_cache(): void {
		$module_class = '\ElementorPro\Modules\ThemeBuilder\Module';

		if ( ! class_exists( $module_class ) || ! method_exists( $module_class, 'instance' ) ) {
			return;
		}

		$module = $module_class::instance();

		if ( ! is_object( $module ) || ! method_exists( $module, 'get_conditions_manager' ) ) {
			return;
		}

		$conditions_manager = $module->get_conditions_manager();

		if ( ! is_object( $conditions_manager ) ) {
			return;
		}

		if ( method_exists( $conditions_manager, 'get_cache' ) ) {
			$cache = $conditions_manager->get_cache();

			if ( is_object( $cache ) && method_exists( $cache, 'regenerate' ) ) {
				$cache->regenerate();
			}
		}

		if ( method_exists( $conditions_manager, 'clear_location_cache' ) ) {
			$conditions_manager->clear_location_cache();
		}
	}

	/**
	 * Refresh imported directory documents once Elementor has booted again.
	 *
	 * @return void
	 */
	public function maybe_refresh_deferred_theme_builder_state(): void {
		if ( ! get_option( self::DEFERRED_THEME_BUILDER_REFRESH_OPTION ) ) {
			return;
		}

		if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			return;
		}

		\DirectoristElementor\ElementorV4\ThemeBuilder\ThemeBuilderSupport::get_instance()->refresh_directory_single_documents();
		$this->refresh_elementor_theme_builder_conditions_cache();
		delete_option( self::DEFERRED_THEME_BUILDER_REFRESH_OPTION );
	}

	/**
	 * Determine whether an imported document contains IDs copied directly from
	 * the source package instead of IDs already rewritten by Elementor.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $post_type Imported post type.
	 * @param int           $post_id Imported post ID.
	 * @return bool
	 */
	protected function is_source_elementor_payload( ImportSession $session, string $post_type, int $post_id ): bool {
		if ( ! empty( $this->active_normalized_elementor_post_ids[ $post_id ] ) ) {
			return false;
		}

		if ( 'elementor_library' !== $post_type ) {
			return true;
		}

		$id_map       = $session->get_id_map();
		$fallback_map = $id_map['fallback_template_source_id'] ?? [];
		$fallback_ids = array_map( 'absint', $fallback_map );

		return in_array( $post_id, $fallback_ids, true );
	}

	/**
	 * Find the source ID that maps to one imported post.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $post_type Imported post type.
	 * @param int           $post_id Imported post ID.
	 * @return string
	 */
	protected function get_imported_source_post_id( ImportSession $session, string $post_type, int $post_id ): string {
		$map = $session->get_id_map();
		$map = $map[ 'post_source_id:' . sanitize_key( $post_type ) ] ?? [];

		foreach ( $map as $source_id => $target_id ) {
			if ( absint( $target_id ) === $post_id ) {
				return sanitize_text_field( (string) $source_id );
			}
		}

		return '';
	}

	/**
	 * Remap a directory-scoped Elementor document type.
	 *
	 * @param string                          $document_type Elementor document type.
	 * @param array<string,array<int,int>>    $term_id_map Term ID map by taxonomy.
	 * @return string
	 */
	protected function remap_elementor_document_type( string $document_type, array $term_id_map ): string {
		$prefix = 'directorist-single-listing-directory-';

		if ( 0 !== strpos( $document_type, $prefix ) ) {
			return $document_type;
		}

		$source_id = absint( substr( $document_type, strlen( $prefix ) ) );
		$target_id = absint( $term_id_map['atbdp_listing_types'][ $source_id ] ?? 0 );

		return $target_id > 0 ? $prefix . $target_id : $document_type;
	}

	/**
	 * Validate a custom Elementor document type after remapping.
	 *
	 * @param int $post_id Elementor template post ID.
	 * @return string
	 */
	protected function validate_elementor_document_type( int $post_id ): string {
		$document_type = sanitize_key( (string) get_post_meta( $post_id, '_elementor_template_type', true ) );

		if ( 0 !== strpos( $document_type, 'directorist-single-listing-directory-' ) ) {
			return '';
		}

		$directory_type_id = absint( substr( $document_type, strlen( 'directorist-single-listing-directory-' ) ) );

		if ( $directory_type_id > 0 && term_exists( $directory_type_id, 'atbdp_listing_types' ) ) {
			return '';
		}

		return sprintf(
			/* translators: 1: Elementor template ID, 2: document type. */
			__( 'Imported Elementor template %1$d has an unavailable document type: %2$s.', 'directorist-elementor' ),
			$post_id,
			$document_type
		);
	}

	/**
	 * Validate cross-template IDs after the second-pass remap.
	 *
	 * @param int   $post_id Imported post ID.
	 * @param mixed $value Elementor data.
	 * @param string $path Current data path.
	 * @return array<int,string>
	 */
	protected function validate_elementor_template_references( int $post_id, $value, string $path = '' ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$errors = [];

		foreach ( $value as $key => $child ) {
			$child_path = $path . '/' . (string) $key;

			if ( $this->is_elementor_template_reference_key( (string) $key ) && is_numeric( $child ) && absint( $child ) > 0 ) {
				$target_id = absint( $child );

				if ( 'elementor_library' !== get_post_type( $target_id ) ) {
					$errors[] = sprintf(
						/* translators: 1: imported post ID, 2: data path, 3: referenced post ID. */
						__( 'Imported Elementor post %1$d has an invalid template reference at %2$s: %3$d.', 'directorist-elementor' ),
						$post_id,
						$child_path,
						$target_id
					);
				}
			}

			if ( is_array( $child ) ) {
				$errors = array_merge( $errors, $this->validate_elementor_template_references( $post_id, $child, $child_path ) );
			} elseif ( $this->is_elementor_scoped_storage_key( (string) $key ) && is_string( $child ) ) {
				$decoded = json_decode( $child, true );

				if ( is_array( $decoded ) ) {
					$errors = array_merge( $errors, $this->validate_elementor_template_references( $post_id, $decoded, $child_path ) );
				}
			}
		}

		return $errors;
	}

	/**
	 * Rewrite static Elementor URLs for imported listing permalink structures.
	 *
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	protected function remap_imported_listing_permalink_strings( ImportSession $session, ?array $selected_post_ids = null ): void {
		$url_map = [];
		$listings = get_posts(
			[
				'post_type'      => 'at_biz_dir',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'meta_key'       => '_directorist_import_package_id',
				'meta_value'     => $session->get_package_id(),
			]
		);

		foreach ( $listings as $listing ) {
			if ( ! $listing instanceof \WP_Post || '' === $listing->post_name ) {
				continue;
			}

			$actual_permalink = get_permalink( $listing );

			if ( ! is_string( $actual_permalink ) || '' === $actual_permalink ) {
				continue;
			}

			$flat_permalink = home_url( '/directory/' . $listing->post_name . '/' );

			if ( $flat_permalink !== $actual_permalink ) {
				$url_map[ $flat_permalink ] = $actual_permalink;
				$url_map[ untrailingslashit( $flat_permalink ) ] = untrailingslashit( $actual_permalink );
			}
		}

		if ( empty( $url_map ) ) {
			return;
		}

		$elementor_posts = $selected_post_ids ?? $this->get_imported_package_post_ids( $session );

		foreach ( $elementor_posts as $post_id ) {
			$post_id = absint( $post_id );
			$data    = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$rewritten = $this->replace_imported_elementor_urls( $data, $url_map );

			if ( $rewritten === $data ) {
				continue;
			}

			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $rewritten ) ) );
			$this->clear_elementor_cache_for_post( $post_id );
		}
	}

	/**
	 * Rewrite source-site URLs in imported WordPress menu items and post content.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return void
	 */
	protected function remap_imported_source_site_urls( ImportSession $session, array $manifest, ?array $selected_post_ids = null ): void {
		$source_site_url = untrailingslashit( (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ) );
		$target_site_url = untrailingslashit( home_url( '/' ) );
		$url_map         = $this->without_site_root_url_mappings( $session->get_url_map(), $source_site_url );

		if ( '' !== $source_site_url && '' !== $target_site_url && $source_site_url !== $target_site_url ) {
			$url_map = [
				$source_site_url . '/' => $target_site_url . '/',
				$source_site_url       => $target_site_url,
			] + $url_map;
		}

		$posts = $selected_post_ids ?? get_posts(
			[
				'post_type'      => $this->get_import_lookup_post_types(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_directorist_import_package_id',
				'meta_value'     => $session->get_package_id(),
			]
		);

		foreach ( $posts as $post_id ) {
			$post_id = absint( $post_id );

			if ( $post_id <= 0 ) {
				continue;
			}

			if ( ! empty( $url_map ) && 'nav_menu_item' === get_post_type( $post_id ) ) {
				$menu_url = (string) get_post_meta( $post_id, '_menu_item_url', true );
				$new_url  = $this->replace_mapped_values_once( $menu_url, $url_map );

				if ( $new_url !== $menu_url ) {
					update_post_meta( $post_id, '_menu_item_url', esc_url_raw( $new_url ) );
				}
			}

			$post = get_post( $post_id );

			if ( $post instanceof \WP_Post && '' !== $post->post_content ) {
				$new_content = ! empty( $url_map ) ? $this->replace_mapped_values_once( $post->post_content, $url_map ) : $post->post_content;
				$new_content = $this->remap_formgent_shortcode_ids( $new_content, $session );

				if ( $new_content !== $post->post_content ) {
					wp_update_post(
						[
							'ID'           => $post_id,
							'post_content' => $new_content,
						]
					);
				}
			}
		}
	}

	/**
	 * Rewrite Elementor CSS selectors that reference source menu item post IDs.
	 *
	 * @param ImportSession $session Import session.
	 * @return void
	 */
	protected function remap_imported_menu_item_css_selectors( ImportSession $session, ?array $selected_post_ids = null ): void {
		$url_map = [];
		$items   = get_posts(
			[
				'post_type'      => 'nav_menu_item',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_directorist_import_package_id',
				'meta_value'     => $session->get_package_id(),
			]
		);

		foreach ( $items as $post_id ) {
			$post_id    = absint( $post_id );
			$source_uid = (string) get_post_meta( $post_id, '_directorist_import_source_uid', true );

			if ( $post_id <= 0 || ! preg_match( '/:nav_menu_item:(\d+)$/', $source_uid, $matches ) ) {
				continue;
			}

			$url_map[ 'menu-item-' . absint( $matches[1] ) ] = 'menu-item-' . $post_id;
		}

		if ( empty( $url_map ) ) {
			return;
		}

		$elementor_posts = $selected_post_ids ?? $this->get_imported_package_post_ids( $session );

		foreach ( $elementor_posts as $post_id ) {
			$post_id = absint( $post_id );
			$data    = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$rewritten = $this->replace_imported_elementor_urls( $data, $url_map );

			if ( $rewritten === $data ) {
				continue;
			}

			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $rewritten ) ) );
			$this->clear_elementor_cache_for_post( $post_id );
		}
	}

	/**
	 * Replace URL strings inside Elementor data.
	 *
	 * @param mixed                $value Elementor value.
	 * @param array<string,string> $url_map URL map.
	 * @return mixed
	 */
	protected function replace_imported_elementor_urls( $value, array $url_map ) {
		if ( is_string( $value ) ) {
			return $this->replace_mapped_values_once( $value, $url_map );
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->replace_imported_elementor_urls( $child, $url_map );
		}

		return $value;
	}

	/**
	 * Collect imported posts from Elementor's runner output and session metadata.
	 *
	 * @param array<string,mixed> $raw_result Elementor import result.
	 * @param string              $elementor_session_id Elementor native import session id.
	 * @return array<int,array{post_type:string,source_id:string,post_id:int}>
	 */
	protected function collect_elementor_kit_imported_posts( array $raw_result, string $elementor_session_id = '' ): array {
		$items = [];

		if ( ! empty( $raw_result['templates']['succeed'] ) && is_array( $raw_result['templates']['succeed'] ) ) {
			foreach ( $raw_result['templates']['succeed'] as $source_id => $post_id ) {
				$items[] = [
					'post_type' => 'elementor_library',
					'source_id' => (string) $source_id,
					'post_id'   => absint( $post_id ),
				];
			}
		}

		if ( ! empty( $raw_result['content'] ) && is_array( $raw_result['content'] ) ) {
			foreach ( $raw_result['content'] as $post_type => $post_type_result ) {
				if ( empty( $post_type_result['succeed'] ) || ! is_array( $post_type_result['succeed'] ) ) {
					continue;
				}

				foreach ( $post_type_result['succeed'] as $source_id => $post_id ) {
					$items[] = [
						'post_type' => sanitize_key( (string) $post_type ),
						'source_id' => (string) $source_id,
						'post_id'   => absint( $post_id ),
					];
				}
			}
		}

		if ( ! empty( $raw_result['wp-content'] ) && is_array( $raw_result['wp-content'] ) ) {
			foreach ( $raw_result['wp-content'] as $post_type => $post_type_result ) {
				if ( empty( $post_type_result['succeed'] ) || ! is_array( $post_type_result['succeed'] ) ) {
					continue;
				}

				foreach ( $post_type_result['succeed'] as $source_id => $post_id ) {
					$actual_post_type = sanitize_key( (string) get_post_type( absint( $post_id ) ) );
					$items[] = [
						'post_type' => '' !== $actual_post_type ? $actual_post_type : sanitize_key( (string) $post_type ),
						'source_id' => (string) $source_id,
						'post_id'   => absint( $post_id ),
					];
				}
			}
		}

		if ( '' !== $elementor_session_id ) {
			foreach ( $this->get_posts_by_elementor_import_session( $elementor_session_id ) as $post_id ) {
				$items[] = [
					'post_type' => sanitize_key( (string) get_post_type( $post_id ) ),
					'source_id' => '',
					'post_id'   => absint( $post_id ),
				];
			}
		}

		$unique = [];

		foreach ( $items as $item ) {
			$post_id = absint( $item['post_id'] );

			if ( $post_id <= 0 || 'attachment' === (string) $item['post_type'] ) {
				continue;
			}

			if ( isset( $unique[ $post_id ] ) ) {
				if ( '' === $unique[ $post_id ]['source_id'] && '' !== $item['source_id'] ) {
					$unique[ $post_id ]['source_id'] = $item['source_id'];
				}

				continue;
			}

			$unique[ $post_id ] = $item;
		}

		return array_values( $unique );
	}

	/**
	 * Collect source-to-local attachment IDs from Elementor's WXR runners.
	 *
	 * Elementor groups attachment results under the WXR's parent post type, so
	 * inspect the imported object instead of trusting the result group name.
	 *
	 * @param array<string,mixed> $raw_result Elementor import result.
	 * @return array<int,int>
	 */
	protected function collect_elementor_kit_media_id_map( array $raw_result ): array {
		$map = [];

		if ( empty( $raw_result['wp-content'] ) || ! is_array( $raw_result['wp-content'] ) ) {
			return $map;
		}

		foreach ( $raw_result['wp-content'] as $post_type_result ) {
			if ( empty( $post_type_result['succeed'] ) || ! is_array( $post_type_result['succeed'] ) ) {
				continue;
			}

			foreach ( $post_type_result['succeed'] as $source_id => $post_id ) {
				$post_id = absint( $post_id );

				if ( $post_id > 0 && 'attachment' === get_post_type( $post_id ) ) {
					$map[ absint( $source_id ) ] = $post_id;
				}
			}
		}

		return $map;
	}

	/**
	 * Track media returned by Elementor's native content runner.
	 *
	 * @param ImportSession $session Import session.
	 * @param array<int,int> $media_id_map Source attachment ID to local ID map.
	 * @return array{media_id_map:array<int,int>,duplicate_ids:array<int,int>}
	 */
	protected function record_elementor_kit_media( ImportSession $session, array $media_id_map ): array {
		$duplicate_ids = [];

		foreach ( $media_id_map as $source_id => $local_id ) {
			$source_id = absint( $source_id );
			$local_id  = absint( $local_id );

			if ( $source_id <= 0 || $local_id <= 0 || 'attachment' !== get_post_type( $local_id ) ) {
				continue;
			}

			$current_uid = (string) get_post_meta( $local_id, '_directorist_import_source_uid', true );

			if ( preg_match( '/^' . preg_quote( $session->get_package_id(), '/' ) . ':attachment:(\d+)$/', $current_uid, $matches ) ) {
				$tracked_source_id = absint( $matches[1] );

				if ( $tracked_source_id > 0 && $tracked_source_id !== $source_id ) {
					unset( $media_id_map[ $source_id ] );
					$media_id_map[ $tracked_source_id ] = $local_id;
					$session->map_id( 'attachment_imported_id', (string) $source_id, $local_id );
					$source_id = $tracked_source_id;
				}
			}

			$source_uid = $session->get_package_id() . ':attachment:' . $source_id;

			$existing_ids = $this->find_existing_imported_attachment_ids( $source_uid, $local_id );
			$existing_id  = 0;
			foreach ( $existing_ids as $candidate_id ) {
				if ( $this->attachment_file_exists( $candidate_id ) ) {
					$existing_id = $candidate_id;
					break;
				}
			}
			if ( $existing_id <= 0 && ! empty( $existing_ids ) ) {
				$existing_id = reset( $existing_ids );
			}

			if ( $existing_id > 0 && 'attachment' === get_post_type( $existing_id ) ) {
				if ( ! $this->attachment_file_exists( $existing_id ) ) {
					$media_id_map[ $source_id ]    = $local_id;
					$media_id_map[ $existing_id ]  = $local_id;
					foreach ( $existing_ids as $duplicate_id ) {
						$duplicate_ids[ $duplicate_id ] = $local_id;
					}
					$this->track_imported_media( $session, $source_id, $local_id, 'updated' );
					$session->map_id( 'attachment_imported_id', (string) $existing_id, $local_id );
					continue;
				}

				$media_id_map[ $source_id ] = $existing_id;
				$media_id_map[ $local_id ]  = $existing_id;
				$duplicate_ids[ $local_id ] = $existing_id;
				foreach ( $existing_ids as $duplicate_id ) {
					if ( $duplicate_id !== $existing_id ) {
						$duplicate_ids[ $duplicate_id ] = $existing_id;
					}
				}
				$this->track_imported_media( $session, $source_id, $existing_id, 'updated' );
				$session->map_id( 'attachment_imported_id', (string) $local_id, $existing_id );
				continue;
			}

			$this->track_imported_media( $session, $source_id, $local_id, 'created' );
		}

		return [
			'media_id_map'  => $media_id_map,
			'duplicate_ids' => $duplicate_ids,
		];
	}

	/** Find every prior package-owned attachment for deterministic deduplication. */
	protected function find_existing_imported_attachment_ids( string $source_uid, int $exclude_id = 0 ): array {
		return array_values(
			array_map(
				'absint',
				get_posts(
					[
						'post_type'      => 'attachment',
						'post_status'    => [ 'inherit', 'private', 'publish', 'trash' ],
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'orderby'        => 'ID',
						'order'          => 'ASC',
						'post__not_in'   => $exclude_id > 0 ? [ $exclude_id ] : [],
						'meta_key'       => '_directorist_import_source_uid',
						'meta_value'     => $source_uid,
					]
				)
			)
		);
	}

	/**
	 * Determine whether an attachment still owns a readable local file.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return bool
	 */
	protected function attachment_file_exists( int $attachment_id ): bool {
		$file = get_attached_file( $attachment_id );

		return is_string( $file ) && '' !== $file && is_file( $file ) && is_readable( $file );
	}

	/**
	 * Delete fresh native-import attachments replaced by tracked media.
	 *
	 * @param array<int,int> $duplicate_ids Fresh attachment ID to retained ID.
	 * @return void
	 */
	protected function delete_reconciled_media_duplicates( array $duplicate_ids ): void {
		foreach ( $duplicate_ids as $duplicate_id => $retained_id ) {
			$duplicate_id = absint( $duplicate_id );
			$retained_id  = absint( $retained_id );

			if ( $duplicate_id <= 0 || $retained_id <= 0 || $duplicate_id === $retained_id || 'attachment' !== get_post_type( $duplicate_id ) ) {
				continue;
			}

			$duplicate_file = get_attached_file( $duplicate_id );
			$retained_file  = get_attached_file( $retained_id );

			if (
				is_string( $duplicate_file )
				&& '' !== $duplicate_file
				&& is_string( $retained_file )
				&& wp_normalize_path( $duplicate_file ) === wp_normalize_path( $retained_file )
			) {
				$this->delete_attachment_record_preserving_files( $duplicate_id );
				continue;
			}

			wp_delete_attachment( $duplicate_id, true );
		}
	}

	/**
	 * Delete a duplicate attachment record without removing shared files.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	protected function delete_attachment_record_preserving_files( int $attachment_id ): void {
		$preserve_file = static function ( $file ): string {
			unset( $file );

			return '';
		};

		add_filter( 'wp_delete_file', $preserve_file );

		try {
			wp_delete_attachment( $attachment_id, true );
		} finally {
			remove_filter( 'wp_delete_file', $preserve_file );
		}
	}

	/**
	 * Add an imported attachment to the session and persist its source identity.
	 *
	 * @param ImportSession $session Import session.
	 * @param int           $source_id Source attachment ID.
	 * @param int           $local_id Local attachment ID.
	 * @param string        $action Created or updated.
	 * @return void
	 */
	protected function track_imported_media( ImportSession $session, int $source_id, int $local_id, string $action ): void {
		$source_uid = $session->get_package_id() . ':attachment:' . $source_id;
		$mapped_id  = $session->get_mapped_id( 'attachment', (string) $source_id );

		$session->map_id( 'attachment', (string) $source_id, $local_id );

		if ( $mapped_id !== $local_id ) {
			if ( 'updated' === $action ) {
				$session->record_updated( 'attachment', $local_id );
			} else {
				$session->record_created( 'attachment', $local_id );
			}
		}

		update_post_meta( $local_id, '_directorist_import_source_uid', $source_uid );
		update_post_meta( $local_id, '_directorist_import_package_id', $session->get_package_id() );
		update_post_meta( $local_id, '_directorist_import_package_version', $session->get_package_version() );
		update_post_meta( $local_id, '_directorist_import_last_imported_at', current_time( 'mysql' ) );
	}

	/**
	 * Import component media embedded in the package before trying its remote URL.
	 *
	 * @param ImportSession       $session Import session.
	 * @param int                 $source_id Source attachment ID.
	 * @param string              $source_url Original source URL.
	 * @param array<string,mixed> $item Component media descriptor.
	 * @param string              $package_file Package archive.
	 * @return int
	 */
	protected function import_packaged_media_attachment( ImportSession $session, int $source_id, string $source_url, array $item, string $package_file ): int {
		$package_path = ltrim( (string) ( $item['package_path'] ?? '' ), '/' );
		$filename     = basename( $package_path );

		if ( $source_id <= 0
			|| '' === $package_file
			|| ! is_readable( $package_file )
			|| ! preg_match( '#^directorist/media/' . preg_quote( (string) $source_id, '#' ) . '/[A-Za-z0-9][A-Za-z0-9._-]*$#D', $package_path )
			|| sanitize_file_name( $filename ) !== $filename ) {
			return 0;
		}

		$source_uid  = $session->get_package_id() . ':attachment:' . $source_id;
		$existing_ids = $this->find_existing_imported_attachment_ids( $source_uid );
		$mapped_id    = absint( $session->get_mapped_id( 'attachment', (string) $source_id ) );
		if ( $mapped_id > 0 && 'attachment' === get_post_type( $mapped_id ) ) {
			$existing_ids[] = $mapped_id;
		}
		$existing_ids = array_values( array_unique( array_filter( array_map( 'absint', $existing_ids ) ) ) );
		sort( $existing_ids, SORT_NUMERIC );
		$retained_id = 0;
		foreach ( $existing_ids as $candidate_id ) {
			if ( $this->attachment_file_exists( $candidate_id ) ) {
				$retained_id = $candidate_id;
				break;
			}
		}
		if ( $retained_id > 0 ) {
			$duplicates = [];
			foreach ( $existing_ids as $duplicate_id ) {
				if ( $duplicate_id !== $retained_id ) {
					$duplicates[ $duplicate_id ] = $retained_id;
				}
			}
			$this->delete_reconciled_media_duplicates( $duplicates );
			$this->track_imported_media( $session, $source_id, $retained_id, 'updated' );

			return $retained_id;
		}

		$zip = new ZipArchive();
		if ( true !== $zip->open( $package_file ) ) {
			return 0;
		}

		$index = $zip->locateName( $package_path, ZipArchive::FL_NOCASE );
		$stat  = false !== $index ? $zip->statIndex( $index ) : false;
		$bytes = absint( $item['bytes'] ?? 0 );
		$hash  = strtolower( (string) ( $item['sha256'] ?? '' ) );
		if ( false === $index || ! is_array( $stat ) || $bytes <= 0 || $bytes !== (int) $stat['size'] || ! preg_match( '/^[a-f0-9]{64}$/', $hash ) ) {
			$zip->close();
			return 0;
		}

		$stream = $zip->getStream( (string) $stat['name'] );
		require_once ABSPATH . 'wp-admin/includes/file.php';
		$temp   = wp_tempnam( $filename );
		$output = $stream && $temp ? fopen( $temp, 'wb' ) : false;
		$copied = $stream && $output ? stream_copy_to_stream( $stream, $output ) : false;
		if ( is_resource( $stream ) ) {
			fclose( $stream );
		}
		if ( is_resource( $output ) ) {
			fclose( $output );
		}
		$zip->close();

		if ( false === $copied || ! is_file( $temp ) || $bytes !== (int) filesize( $temp ) || ! hash_equals( $hash, strtolower( (string) hash_file( 'sha256', $temp ) ) ) ) {
			if ( $temp && is_file( $temp ) ) {
				wp_delete_file( $temp );
			}
			return 0;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$local_id = media_handle_sideload(
			[
				'name'     => $filename,
				'tmp_name' => $temp,
				'error'    => 0,
				'size'     => $bytes,
			],
			0,
			sanitize_text_field( (string) ( $item['title'] ?? '' ) )
		);

		if ( is_wp_error( $local_id ) ) {
			if ( is_file( $temp ) ) {
				wp_delete_file( $temp );
			}
			return 0;
		}

		$local_id = absint( $local_id );
		update_post_meta( $local_id, '_directorist_import_original_url', esc_url_raw( $source_url ) );
		$this->track_imported_media( $session, $source_id, $local_id, 'created' );

		return $local_id;
	}

	/**
	 * Import one package-owned remote media item with a stable source identity.
	 *
	 * @param ImportSession $session Import session.
	 * @param int           $source_id Source attachment ID.
	 * @param string        $source_url Source media URL.
	 * @return int
	 */
	protected function import_remote_media_attachment( ImportSession $session, int $source_id, string $source_url ): int {
		if ( $source_id <= 0 || '' === $source_url ) {
			return 0;
		}

		$mapped_id = absint( $session->get_mapped_id( 'attachment', (string) $source_id ) );

		if ( $mapped_id > 0 && 'attachment' === get_post_type( $mapped_id ) ) {
			return $mapped_id;
		}

		$source_uid  = $session->get_package_id() . ':attachment:' . $source_id;
		$existing_id = $this->find_existing_imported_post_id( $source_uid );

		if ( $existing_id > 0 && 'attachment' === get_post_type( $existing_id ) ) {
			$this->track_imported_media( $session, $source_id, $existing_id, 'updated' );

			return $existing_id;
		}

		$local_id = 0;
		$extension = strtolower( (string) pathinfo( (string) wp_parse_url( $source_url, PHP_URL_PATH ), PATHINFO_EXTENSION ) );

		if ( 'svg' === $extension ) {
			$local_id = $this->import_sanitized_svg_attachment( $source_url );
		} elseif ( class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->templates_manager ) ) {
			$importer = \Elementor\Plugin::$instance->templates_manager->get_import_images_instance();
			$imported = $importer->import(
				[
					'id'  => $source_id,
					'url' => $source_url,
				]
			);

			if ( is_array( $imported )
				&& ! empty( $imported['url'] )
				&& false === strpos( (string) $imported['url'], 'elementor/assets/images/placeholder.png' ) ) {
				$local_id = absint( $imported['id'] ?? 0 );
			}
		}

		if ( $local_id <= 0 ) {
			return 0;
		}

		update_post_meta( $local_id, '_directorist_import_original_url', esc_url_raw( $source_url ) );
		$this->track_imported_media( $session, $source_id, $local_id, 'created' );

		return $local_id;
	}

	/**
	 * Remap Directorist media fields that WordPress's WXR importer does not know.
	 *
	 * @param int            $post_id Imported post ID.
	 * @param array<int,int> $media_id_map Source attachment ID to local ID map.
	 * @return void
	 */
	protected function remap_imported_post_media_meta( int $post_id, array $media_id_map ): void {
		if ( $post_id <= 0 || empty( $media_id_map ) ) {
			return;
		}

		$local_media_ids = array_values( array_unique( array_map( 'absint', $media_id_map ) ) );

		foreach ( [ '_thumbnail_id', '_listing_prv_img', '_listing_img', '_gallery_img' ] as $meta_key ) {
			$value = get_post_meta( $post_id, $meta_key, true );

			if ( '' === $value || null === $value ) {
				continue;
			}

			$remapped = $this->remap_imported_media_id_value( $value, $media_id_map );

			if ( in_array( $meta_key, [ '_listing_img', '_gallery_img' ], true ) ) {
				$remapped = array_values(
					array_filter(
						(array) $remapped,
						static function ( $attachment_id ) use ( $local_media_ids ): bool {
							return in_array( absint( $attachment_id ), $local_media_ids, true );
						}
					)
				);
			} elseif ( in_array( $meta_key, [ '_thumbnail_id', '_listing_prv_img' ], true )
				&& ! in_array( absint( $remapped ), $local_media_ids, true ) ) {
				$remapped = '';
			}

			if ( $remapped !== $value ) {
				if ( '' === $remapped || [] === $remapped ) {
					delete_post_meta( $post_id, $meta_key );
				} else {
					update_post_meta( $post_id, $meta_key, $remapped );
				}
			}
		}
	}

	/**
	 * Recursively remap attachment IDs while preserving the stored value shape.
	 *
	 * @param mixed          $value Media ID or collection of media IDs.
	 * @param array<int,int> $media_id_map Source attachment ID to local ID map.
	 * @return mixed
	 */
	protected function remap_imported_media_id_value( $value, array $media_id_map ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $key => $child ) {
				$value[ $key ] = $this->remap_imported_media_id_value( $child, $media_id_map );
			}

			return $value;
		}

		if ( ! is_numeric( $value ) ) {
			return $value;
		}

		$source_id = absint( $value );

		if ( ! isset( $media_id_map[ $source_id ] ) ) {
			return $value;
		}

		return is_string( $value ) ? (string) $media_id_map[ $source_id ] : $media_id_map[ $source_id ];
	}

	/**
	 * Collect user-facing import errors from all Elementor kit runners.
	 *
	 * @param array<string,mixed> $raw_result Elementor import result.
	 * @return array<int,string>
	 */
	protected function collect_elementor_kit_import_errors( array $raw_result ): array {
		$errors = [];

		$collect = static function ( $value ) use ( &$collect, &$errors ): void {
			if ( is_string( $value ) && '' !== $value ) {
				$errors[] = $value;
				return;
			}

			if ( ! is_array( $value ) ) {
				return;
			}

			foreach ( $value as $child ) {
				$collect( $child );
			}
		};

		$scan = static function ( $value ) use ( &$scan, $collect ): void {
			if ( ! is_array( $value ) ) {
				return;
			}

			foreach ( $value as $key => $child ) {
				if ( 'failed' === $key || 'errors' === $key ) {
					$collect( $child );
					continue;
				}

				$scan( $child );
			}
		};

		$scan( $raw_result );

		return array_values( array_unique( $errors ) );
	}

	/**
	 * Query posts tagged by Elementor's native import session id.
	 *
	 * @param string $elementor_session_id Elementor native import session id.
	 * @return array<int,int>
	 */
	protected function get_posts_by_elementor_import_session( string $elementor_session_id ): array {
		$posts = get_posts(
			[
				'post_type'      => $this->get_import_lookup_post_types(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_elementor_import_session_id',
				'meta_value'     => $elementor_session_id,
			]
		);

		return array_values( array_map( 'absint', $posts ) );
	}

	/**
	 * Query every post imported by the current Directorist package.
	 *
	 * Elementor data can live on registered custom post types as well as pages,
	 * posts, and library documents. Late reference passes must cover all of them.
	 *
	 * @param ImportSession $session Import session.
	 * @return array<int,int>
	 */
	protected function get_imported_package_post_ids( ImportSession $session ): array {
		$posts = get_posts(
			[
				'post_type'      => $this->get_import_lookup_post_types(),
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_directorist_import_package_id',
				'meta_value'     => $session->get_package_id(),
			]
		);

		return array_values( array_map( 'absint', $posts ) );
	}

	/**
	 * Get post types that can contain imported package content.
	 *
	 * WordPress `any` excludes some internal-but-editable post types such as
	 * Elementor library templates, so use the registered post type list.
	 *
	 * @return array<int,string>
	 */
	protected function get_import_lookup_post_types(): array {
		return array_values( array_diff( get_post_types( [], 'names' ), [ 'revision', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles', 'wp_navigation', 'wp_font_face', 'wp_font_family' ] ) );
	}

	/**
	 * Read source publication dates from the kit WXR files.
	 *
	 * Elementor's content runner can assign the import timestamp to custom post
	 * types even when their source dates are present in the package.
	 *
	 * @param string                                               $package_file Zip package file.
	 * @param array<int,array{post_type:string,source_id:string}>  $imported_posts Imported post descriptors.
	 * @return array<string,array<string,array{post_date:string,post_date_gmt:string}>>
	 */
	protected function read_elementor_kit_post_dates( string $package_file, array $imported_posts ): array {
		if ( ! class_exists( ZipArchive::class ) || ! function_exists( 'simplexml_load_string' ) ) {
			return [];
		}

		$post_types = [];

		foreach ( $imported_posts as $imported_post ) {
			$post_type = sanitize_key( (string) ( $imported_post['post_type'] ?? '' ) );

			if ( '' !== $post_type && 'attachment' !== $post_type ) {
				$post_types[ $post_type ] = $post_type;
			}
		}

		if ( empty( $post_types ) ) {
			return [];
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return [];
		}

		$dates = [];

		foreach ( $post_types as $post_type ) {
			$xml_string = $zip->getFromName( 'wp-content/' . $post_type . '/' . $post_type . '.xml' );

			if ( ! is_string( $xml_string ) || '' === $xml_string ) {
				continue;
			}

			$previous_errors = libxml_use_internal_errors( true );
			$xml             = simplexml_load_string( $xml_string, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_errors );

			if ( ! $xml instanceof \SimpleXMLElement || ! isset( $xml->channel->item ) ) {
				continue;
			}

			foreach ( $xml->channel->item as $item ) {
				$wp        = $item->children( 'wp', true );
				$source_id = sanitize_text_field( (string) ( $wp->post_id ?? '' ) );
				$item_type = sanitize_key( (string) ( $wp->post_type ?? '' ) );
				$post_date = sanitize_text_field( (string) ( $wp->post_date ?? '' ) );
				$date_gmt  = sanitize_text_field( (string) ( $wp->post_date_gmt ?? '' ) );

				if ( '' === $source_id || $post_type !== $item_type || ! $this->is_valid_import_post_date( $post_date ) ) {
					continue;
				}

				$dates[ $post_type ][ $source_id ] = [
					'post_date'     => $post_date,
					'post_date_gmt' => $this->is_valid_import_post_date( $date_gmt ) ? $date_gmt : get_gmt_from_date( $post_date ),
				];
			}
		}

		$zip->close();

		return $dates;
	}

	/**
	 * Read source attachment URLs declared by the kit WXR files.
	 *
	 * @param string $package_file Package archive.
	 * @return array<int,string>
	 */
	protected function read_elementor_kit_attachment_urls( string $package_file ): array {
		if ( ! class_exists( ZipArchive::class ) || ! function_exists( 'simplexml_load_string' ) ) {
			return [];
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return [];
		}

		$urls = [];

		for ( $index = 0; $index < $zip->numFiles; $index++ ) {
			$name = (string) $zip->getNameIndex( $index );

			if ( ! preg_match( '#^wp-content/[^/]+/[^/]+\.xml$#', $name ) ) {
				continue;
			}

			$xml_string = $zip->getFromIndex( $index );

			if ( ! is_string( $xml_string ) || '' === $xml_string ) {
				continue;
			}

			$previous_errors = libxml_use_internal_errors( true );
			$xml = simplexml_load_string( $xml_string, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NONET );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous_errors );

			if ( ! $xml instanceof \SimpleXMLElement || ! isset( $xml->channel->item ) ) {
				continue;
			}

			foreach ( $xml->channel->item as $item ) {
				$wp = $item->children( 'wp', true );

				if ( 'attachment' === sanitize_key( (string) ( $wp->post_type ?? '' ) ) ) {
					$source_id = absint( $wp->post_id ?? 0 );
					$url = esc_url_raw( (string) ( $wp->attachment_url ?? $item->guid ?? '' ) );

					if ( $source_id > 0 && '' !== $url ) {
						$urls[ $source_id ] = $url;
					}
				}
			}
		}

		$zip->close();

		return $urls;
	}

	/**
	 * Persist the original source URL for each attachment imported by Elementor.
	 *
	 * @param array<int,int>    $media_id_map Source-to-local media IDs.
	 * @param array<int,string> $source_urls Source attachment URLs.
	 * @return void
	 */
	protected function record_elementor_kit_media_source_urls( array $media_id_map, array $source_urls ): void {
		foreach ( $media_id_map as $source_id => $local_id ) {
			$source_id = absint( $source_id );
			$local_id  = absint( $local_id );
			$url       = esc_url_raw( (string) ( $source_urls[ $source_id ] ?? '' ) );

			if ( $local_id > 0 && 'attachment' === get_post_type( $local_id ) && '' !== $url ) {
				update_post_meta( $local_id, '_directorist_import_original_url', $url );
			}
		}
	}

	/**
	 * Prevent failed source media IDs from resolving to unrelated local posts.
	 *
	 * @param ImportSession $session Import session.
	 * @param array<int,int> $unmapped_media_ids Source attachment IDs that failed import.
	 * @return void
	 */
	protected function clear_unmapped_elementor_media_ids( ImportSession $session, array $unmapped_media_ids, ?array $selected_post_ids = null ): void {
		$unmapped = array_fill_keys( array_filter( array_map( 'absint', $unmapped_media_ids ) ), true );

		if ( empty( $unmapped ) ) {
			return;
		}

		foreach ( $selected_post_ids ?? $this->get_imported_package_post_ids( $session ) as $post_id ) {
			$post_id = absint( $post_id );
			$data = json_decode( (string) get_post_meta( $post_id, '_elementor_data', true ), true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$normalized = $this->clear_unmapped_elementor_media_value( $data, $unmapped );

			if ( $normalized === $data ) {
				continue;
			}

			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $normalized ) ) );
			$this->clear_elementor_cache_for_post( $post_id );
		}
	}

	/**
	 * Clear an unresolved ID from Elementor media-control envelopes.
	 *
	 * @param mixed           $value Elementor data value.
	 * @param array<int,bool> $unmapped Source attachment lookup.
	 * @return mixed
	 */
	protected function clear_unmapped_elementor_media_value( $value, array $unmapped ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if (
			array_key_exists( 'id', $value )
			&& array_key_exists( 'url', $value )
			&& isset( $unmapped[ absint( $value['id'] ) ] )
			&& ! ( 'attachment' === get_post_type( absint( $value['id'] ) ) && $this->attachment_matches_source_media_url( absint( $value['id'] ), (string) $value['url'] ) )
		) {
			$value['id'] = '';
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->clear_unmapped_elementor_media_value( $child, $unmapped );
		}

		return $value;
	}

	/**
	 * Restore one imported post's source publication date.
	 *
	 * @param int                                                                 $post_id Imported post ID.
	 * @param string                                                              $post_type Imported post type.
	 * @param string                                                              $source_id Source post ID.
	 * @param array<string,array<string,array{post_date:string,post_date_gmt:string}>> $post_date_map Package date map.
	 * @return void
	 */
	protected function restore_imported_post_date( int $post_id, string $post_type, string $source_id, array $post_date_map ): void {
		$dates = $post_date_map[ $post_type ][ $source_id ] ?? null;

		if ( $post_id <= 0 || ! is_array( $dates ) || empty( $dates['post_date'] ) ) {
			return;
		}

		wp_update_post(
			[
				'ID'            => $post_id,
				'post_date'     => (string) $dates['post_date'],
				'post_date_gmt' => (string) ( $dates['post_date_gmt'] ?? '' ),
			]
		);
	}

	/**
	 * Restore a source permalink slug after native content conflict resolution.
	 *
	 * @param int                 $post_id Imported post ID.
	 * @param string              $post_type Imported post type.
	 * @param string              $source_id Source post ID.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @return void
	 */
	protected function restore_imported_post_slug( int $post_id, string $post_type, string $source_id, array $manifest ): void {
		$slug = $this->get_imported_post_slug( $post_type, $source_id, $manifest );

		if ( '' === $slug ) {
			return;
		}

		if ( $slug === (string) get_post_field( 'post_name', $post_id ) ) {
			return;
		}

		wp_update_post(
			[
				'ID'        => $post_id,
				'post_name' => $slug,
			]
		);
	}

	/** Resolve the source slug recorded for a native Elementor content post. */
	protected function get_imported_post_slug( string $post_type, string $source_id, array $manifest ): string {
		if ( ! in_array( $post_type, [ 'page', 'post', 'at_biz_dir' ], true ) || '' === $source_id ) {
			return '';
		}
		$content = isset( $manifest['content'][ $post_type ][ $source_id ] ) && is_array( $manifest['content'][ $post_type ][ $source_id ] ) ? $manifest['content'][ $post_type ][ $source_id ] : [];
		$url     = esc_url_raw( (string) ( $content['url'] ?? '' ) );
		$source  = esc_url_raw( (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['source_site_url'] ?? $manifest['site'] ?? '' ) );
		if ( '' === $url || ( '' !== $source && untrailingslashit( $url ) === untrailingslashit( $source ) ) ) {
			return '';
		}
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
		return sanitize_title( rawurldecode( (string) basename( $path ) ) );
	}

	/** Reuse only untouched WordPress or Directorist installer content. */
	protected function find_pristine_destination_post( string $post_type, string $slug, string $source_content = '' ): int {
		$defaults = [
			'search-home'     => [ 'content' => '[directorist_search_listing]', 'option' => 'search_listing' ],
			'search-result'   => [ 'content' => '[directorist_search_result]', 'option' => 'search_result_page' ],
			'add-listing'     => [ 'content' => '[directorist_add_listing]', 'option' => 'add_listing_page' ],
			'all-listings'    => [ 'content' => '[directorist_all_listing]', 'option' => 'all_listing_page' ],
			'single-category' => [ 'content' => '[directorist_category]', 'option' => 'single_category_page' ],
			'single-location' => [ 'content' => '[directorist_location]', 'option' => 'single_location_page' ],
			'single-tag'      => [ 'content' => '[directorist_tag]', 'option' => 'single_tag_page' ],
			'author-profile'  => [ 'content' => '[directorist_author_profile]', 'option' => 'author_profile_page' ],
			'dashboard'       => [ 'content' => '[directorist_user_dashboard]', 'option' => 'user_dashboard' ],
			'sign-in'         => [ 'content' => '[directorist_signin_signup]', 'option' => 'signin_signup_page' ],
			'booking-confirmation'     => [ 'content' => '[directorist_booking_confirmation]', 'option' => 'booking_confirmation' ],
			'universal-search-results' => [ 'content' => '[directorist_universal_search_result button_text="Search" placeholder="Search listings..."]', 'option' => 'universal_search_result_page' ],
			'universal-search-form'    => [ 'content' => '[directorist_universal_search_form button_text="Search" placeholder="Search listings..."]', 'option' => '' ],
		];
		$slug       = sanitize_title( $slug );
		$definition = $defaults[ $slug ] ?? null;
		if ( 'page' === $post_type && '' !== trim( $source_content ) ) {
			foreach ( $defaults as $candidate ) {
				if ( $candidate['content'] === trim( $source_content ) ) {
					$definition = $candidate;
					break;
				}
			}
		}

		$post = get_page_by_path( $slug, OBJECT, $post_type );
		if ( ! $post instanceof \WP_Post && is_array( $definition ) && '' !== $definition['option'] ) {
			$options = (array) get_option( 'atbdp_option', [] );
			$post    = get_post( absint( $options[ $definition['option'] ] ?? 0 ) );
			if ( ! $post instanceof \WP_Post || 'page' !== $post->post_type ) {
				$post = null;
			}
		}

		$is_hello = 'post' === $post_type && in_array(
			$slug,
			[
				'hello-world',
				sanitize_title( translate_with_gettext_context( 'hello-world', 'Default post slug', 'default' ) ),
			],
			true
		);
		$is_sample = 'page' === $post_type && in_array(
			$slug,
			[
				'sample-page',
				sanitize_title( translate( 'sample-page', 'default' ) ),
			],
			true
		);
		$is_privacy = 'page' === $post_type && ( 'privacy-policy' === $slug || false !== strpos( $source_content, 'privacy-policy-tutorial' ) );

		if ( ! $post instanceof \WP_Post && $is_hello ) {
			$post = get_page_by_path( sanitize_title( translate_with_gettext_context( 'hello-world', 'Default post slug', 'default' ) ), OBJECT, 'post' );
		} elseif ( ! $post instanceof \WP_Post && $is_sample ) {
			$post = get_page_by_path( sanitize_title( translate( 'sample-page', 'default' ) ), OBJECT, 'page' );
		} elseif ( ! $post instanceof \WP_Post && $is_privacy ) {
			$post = get_post( absint( get_option( 'wp_page_for_privacy_policy' ) ) );
		}
		if ( ! $post instanceof \WP_Post ) {
			return 0;
		}
		$matches   = is_array( $definition ) && 'page' === $post_type && $definition['content'] === trim( $post->post_content );
		$normalize = static fn( $text ) => trim( preg_replace( '/\\s+/u', ' ', html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES, 'UTF-8' ) ) );
		if ( $is_hello && translate( 'Hello world!', 'default' ) === $post->post_title ) {
			$expected = [ translate( 'Welcome to WordPress. This is your first post. Edit or delete it, then start writing!', 'default' ) ];
			if ( is_multisite() ) {
				$expected[] = sprintf( translate( 'Welcome to %s. This is your first post. Edit or delete it, then start writing!', 'default' ), get_network()->site_name );
			}
			$matches = in_array( $normalize( $post->post_content ), array_map( $normalize, $expected ), true );
		} elseif ( $is_sample && translate( 'Sample Page', 'default' ) === $post->post_title ) {
			$matches = true;
		} elseif ( $is_privacy && translate( 'Privacy Policy', 'default' ) === $post->post_title ) {
			$matches = (int) get_option( 'wp_page_for_privacy_policy' ) === (int) $post->ID && false !== strpos( $post->post_content, 'privacy-policy-tutorial' );
		}
		if (
			! $matches
			|| $post->post_date !== $post->post_modified
			|| '' !== (string) get_post_meta( $post->ID, '_edit_last', true )
			|| has_post_thumbnail( $post )
			|| '' !== (string) get_post_meta( $post->ID, '_directorist_import_package_id', true )
		) {
			return 0;
		}
		return (int) $post->ID;
	}

	/**
	 * Enforce destination slug uniqueness for draft imports as well as published posts.
	 *
	 * @param int $post_id Imported post ID.
	 * @return void
	 */
	protected function ensure_unique_imported_post_slug( int $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || 'attachment' === $post->post_type || '' === $post->post_name ) {
			return;
		}

		$slug = wp_unique_post_slug( $post->post_name, $post_id, 'publish', $post->post_type, (int) $post->post_parent );

		if ( $slug !== $post->post_name ) {
			wp_update_post(
				[
					'ID'        => $post_id,
					'post_name' => $slug,
				]
			);
		}
	}

	/**
	 * Store the exact source permalink mapping after the final slug is known.
	 *
	 * @param ImportSession       $session Import session.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $post_type Post type.
	 * @param string              $source_id Source post ID.
	 * @param int                 $post_id Imported post ID.
	 * @return void
	 */
	protected function map_imported_post_url( ImportSession $session, array $manifest, string $post_type, string $source_id, int $post_id ): void {
		$record = isset( $manifest['content'][ $post_type ][ $source_id ] ) && is_array( $manifest['content'][ $post_type ][ $source_id ] )
			? $manifest['content'][ $post_type ][ $source_id ]
			: [];
		$source_url = esc_url_raw( (string) ( $record['url'] ?? $record['source_url'] ?? '' ) );
		$target_url = $post_id > 0 ? get_permalink( $post_id ) : '';

		if ( '' !== $source_url && is_string( $target_url ) && '' !== $target_url ) {
			$session->map_url( $source_url, $target_url );
			$session->map_url( untrailingslashit( $source_url ), untrailingslashit( $target_url ) );

			$source_site     = $manifest['directorist']['source_site_url'] ?? $manifest['source_site_url'] ?? $manifest['site'] ?? '';
			$source_site_url = is_string( $source_site ) ? untrailingslashit( $source_site ) : '';
			$target_site_url = untrailingslashit( home_url( '/' ) );

			if ( '' !== $source_site_url && str_starts_with( $source_url, $source_site_url ) ) {
				$provisional_url = $target_site_url . substr( $source_url, strlen( $source_site_url ) );

				$session->map_url( $provisional_url, $target_url );
				$session->map_url( untrailingslashit( $provisional_url ), untrailingslashit( $target_url ) );
			}
		}
	}

	/**
	 * Check a WXR post date before applying it.
	 *
	 * @param string $date Candidate date.
	 * @return bool
	 */
	protected function is_valid_import_post_date( string $date ): bool {
		return '' !== $date && '0000-00-00 00:00:00' !== $date && false !== strtotime( $date );
	}

	/**
	 * Restore template data when Elementor's importer creates the document but
	 * skips saving data for a custom Directorist document type.
	 *
	 * @param int                 $post_id Imported post id.
	 * @param string              $source_id Source template id.
	 * @param array<string,mixed> $manifest Elementor kit manifest.
	 * @param string              $package_file Zip package file.
	 * @return void
	 */
	protected function ensure_imported_template_data( int $post_id, string $source_id, array $manifest, string $package_file, array $term_id_map = [], ?ImportSession $session = null ): void {
		$template_data = $this->read_zip_json_file( $package_file, 'templates/' . $source_id . '.json' );

		if ( is_wp_error( $template_data ) ) {
			return;
		}

		$content  = isset( $template_data['content'] ) && is_array( $template_data['content'] ) ? $template_data['content'] : [];
		$settings = isset( $template_data['settings'] ) && is_array( $template_data['settings'] ) ? $template_data['settings'] : [];
		$template = isset( $manifest['templates'][ $source_id ] ) && is_array( $manifest['templates'][ $source_id ] ) ? $manifest['templates'][ $source_id ] : [];
		$content  = $this->import_media_controls( $content, $session );
		$settings = $this->import_media_controls( $settings, $session );
		$content  = $this->import_scoped_media_controls( $content, $session );
		$settings = $this->import_scoped_media_controls( $settings, $session );
		$content  = $this->remap_imported_elementor_value( $content, $term_id_map, (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ), '', $session, true, true );
		$settings = $this->remap_imported_elementor_value( $settings, $term_id_map, (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ), '', $session, true, true );

		update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $content ) ) );
		update_post_meta( $post_id, '_elementor_page_settings', $settings );
		$this->active_normalized_elementor_post_ids[ $post_id ] = true;

		if ( ! empty( $template['doc_type'] ) ) {
			update_post_meta( $post_id, '_elementor_template_type', sanitize_key( (string) $template['doc_type'] ) );
		}
	}

	/**
	 * Restore Elementor-authored content data from native kit packages.
	 *
	 * Elementor's content runner can create lightweight page/post objects first,
	 * then Directorist merges them into existing tracked objects on repeat import.
	 * Reading the content JSON keeps page template and builder settings exact.
	 *
	 * @param int    $post_id Imported or updated post id.
	 * @param string $post_type Imported post type.
	 * @param string $source_id Source post id.
	 * @param string $package_file Zip package file.
	 * @return void
	 */
	protected function ensure_imported_content_data( int $post_id, string $post_type, string $source_id, array $manifest, string $package_file, array $term_id_map = [], ?ImportSession $session = null ): void {
		if ( '' === $source_id ) {
			return;
		}

		$content_data = $this->read_zip_json_file( $package_file, 'content/' . sanitize_key( $post_type ) . '/' . sanitize_file_name( $source_id ) . '.json' );

		if ( is_wp_error( $content_data ) ) {
			return;
		}

		$content  = isset( $content_data['content'] ) && is_array( $content_data['content'] ) ? $content_data['content'] : [];
		$settings = isset( $content_data['settings'] ) && is_array( $content_data['settings'] ) ? $content_data['settings'] : [];
		$document = isset( $manifest['content'][ $post_type ][ $source_id ] ) && is_array( $manifest['content'][ $post_type ][ $source_id ] )
			? $manifest['content'][ $post_type ][ $source_id ]
			: [];
		$document_type = sanitize_key( (string) ( $document['doc_type'] ?? '' ) );

		if ( empty( $content ) && empty( $settings ) ) {
			return;
		}

		$content  = $this->import_media_controls( $content, $session );
		$settings = $this->import_media_controls( $settings, $session );
		$content  = $this->import_scoped_media_controls( $content, $session );
		$settings = $this->import_scoped_media_controls( $settings, $session );
		$content  = $this->remap_imported_elementor_value( $content, $term_id_map, (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ), '', $session, true, true );
		$settings = $this->remap_imported_elementor_value( $settings, $term_id_map, (string) ( $manifest['directorist']['source_site_url'] ?? $manifest['site'] ?? '' ), '', $session, true, true );

		if ( ! empty( $content ) ) {
			update_post_meta( $post_id, '_elementor_edit_mode', 'builder' );
			update_post_meta( $post_id, '_elementor_data', wp_slash( wp_json_encode( $content ) ) );
			update_post_meta( $post_id, '_elementor_template_type', '' !== $document_type ? $document_type : ( 'page' === $post_type ? 'wp-page' : 'wp-post' ) );
		}

		$page_template     = array_key_exists( 'template', $settings ) ? sanitize_text_field( (string) $settings['template'] ) : '';
		$has_page_template = array_key_exists( 'template', $settings );
		unset( $settings['template'] );

		if ( 'page' === $post_type ) {
			if ( $has_page_template ) {
				update_post_meta( $post_id, '_wp_page_template', '' !== $page_template ? $page_template : 'default' );
			} elseif ( 'page' === $document_type ) {
				// Elementor omits its header/footer page layout from exported page
				// settings when that layout is the document default.
				update_post_meta( $post_id, '_wp_page_template', 'elementor_header_footer' );
			}
		}

		update_post_meta( $post_id, '_elementor_page_settings', $settings );
		$this->active_normalized_elementor_post_ids[ $post_id ] = true;
	}

	/**
	 * Remap source-specific Elementor values after native kit import.
	 *
	 * @param mixed                       $value Elementor value.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @param string                      $source_site_url Source site URL.
	 * @param string                      $key Current array key.
	 * @param ImportSession|null          $session Completed import session.
	 * @param bool                        $source_payload Whether values came directly from the source package.
	 * @param bool                        $media_normalized Whether media controls already contain local IDs.
	 * @return mixed
	 */
	protected function remap_imported_elementor_value( $value, array $term_id_map = [], string $source_site_url = '', string $key = '', ?ImportSession $session = null, bool $source_payload = false, bool $media_normalized = false ) {
		if ( $this->is_elementor_scoped_storage_key( $key ) && is_string( $value ) ) {
			$decoded = json_decode( $value, true );

			if ( is_array( $decoded ) ) {
				$decoded = $this->remap_imported_elementor_value( $decoded, $term_id_map, $source_site_url, '', $session, $source_payload, $media_normalized );
				$decoded = $this->remap_elementor_directory_scope_keys( $decoded, $term_id_map );

				return wp_json_encode( $decoded );
			}
		}

		if ( $this->is_directory_type_setting_key( $key ) ) {
			$value = $this->remap_directory_type_setting_value( $value, $term_id_map );
		}

		if ( 'custom_field' === $key ) {
			$value = $this->remap_custom_field_selection_value( $value, $term_id_map );
		}

		if ( in_array( $key, [ 'active_template_key', 'active_search_template_key', 'branch_key' ], true ) && is_string( $value ) ) {
			$value = $this->remap_elementor_directory_scope_key( $value, $term_id_map );
		}

		if ( $this->is_nav_menu_setting_key( $key ) ) {
			$value = $this->remap_nav_menu_setting_value( $value, $term_id_map, $session, $key );
		}

		if ( null !== $session && $this->is_elementor_template_reference_key( $key ) ) {
			$value = $this->remap_elementor_template_reference_value( $value, $session, $source_payload );
		}

		if ( $source_payload && in_array( $key, [ 'plan_id', 'pricing_plan_id', 'selected_plan_id', 'preview_plan_id' ], true ) && is_numeric( $value ) ) {
			$source_plan_id = absint( $value );
			$target_plan_id = absint( $this->active_pricing_plan_id_map[ $source_plan_id ] ?? 0 );

			if ( $target_plan_id > 0 ) {
				$value = is_string( $value ) ? (string) $target_plan_id : $target_plan_id;
			}
		}

		if ( $source_payload && null !== $session && 'preview_listing_id' === $key && is_numeric( $value ) ) {
			$mapped_listing_id = $this->get_mapped_post_id( $session, 'at_biz_dir', (string) absint( $value ) );

			if ( $mapped_listing_id ) {
				$value = is_string( $value ) ? (string) $mapped_listing_id : $mapped_listing_id;
			}
		}

		if ( null !== $session && in_array( $key, [ 'form_id', 'formgent_form_id' ], true ) && is_numeric( $value ) ) {
			$mapped_form_id = $this->get_mapped_post_id( $session, 'formgent_form', (string) absint( $value ) );

			if ( $mapped_form_id ) {
				$value = is_string( $value ) ? (string) $mapped_form_id : $mapped_form_id;
			}
		}

		if ( null !== $session && is_string( $value ) && false !== stripos( $value, '[formgent' ) ) {
			$value = $this->remap_formgent_shortcode_ids( $value, $session );
		}

		if ( ! $media_normalized
			&& null !== $session
			&& is_array( $value )
			&& array_key_exists( 'id', $value )
			&& array_key_exists( 'url', $value )
			&& is_numeric( $value['id'] ) ) {
			$attachment_map_type  = $source_payload ? 'attachment' : 'attachment_imported_id';
			$mapped_attachment_id = $session->get_mapped_id( $attachment_map_type, (string) absint( $value['id'] ) );

			if ( $mapped_attachment_id && 'attachment' === get_post_type( $mapped_attachment_id ) ) {
				$value['id'] = is_string( $value['id'] ) ? (string) $mapped_attachment_id : $mapped_attachment_id;

				$attachment_url = wp_get_attachment_url( $mapped_attachment_id );

				if ( $attachment_url ) {
					$value['url'] = $attachment_url;
				}
			}
		}

		if ( null !== $session && is_string( $value ) && ! empty( $session->get_url_map() ) ) {
			$value = $this->replace_mapped_values_once(
				$value,
				$this->without_site_root_url_mappings( $session->get_url_map(), $source_site_url )
			);
		}

		if ( is_string( $value ) && '' !== $source_site_url ) {
			$source_site_url = untrailingslashit( $source_site_url );
			$target_site_url = untrailingslashit( home_url( '/' ) );

			if ( '' !== $source_site_url && '' !== $target_site_url ) {
				$value = str_replace( $source_site_url, $target_site_url, $value );
			}
		}

		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $child_key => $child_value ) {
			if ( 'url' === (string) $child_key
				&& array_key_exists( 'id', $value )
				&& '' === (string) $value['id']
				&& is_string( $child_value )
				&& preg_match( '#^https?://#', $child_value ) ) {
				continue;
			}

			$value[ $child_key ] = $this->remap_imported_elementor_value( $child_value, $term_id_map, $source_site_url, (string) $child_key, $session, $source_payload, $media_normalized );
		}

		$value = $this->normalize_elementor_directory_scope_settings( $value );
		if ( $source_payload ) {
			$value = $this->remap_taxonomy_widget_slugs( $value, $term_id_map );
			$widget = (string) ( $value['widgetType'] ?? '' );
			if ( str_starts_with( $widget, 'directorist_' ) && str_contains( $widget, 'author_profile' ) ) {
				foreach ( [ 'author_id', 'preview_author_id', 'editor_preview_author_id' ] as $author_key ) {
					$author = $value['settings'][ $author_key ] ?? 0;
					if ( is_numeric( $author ) && absint( $author ) > 0 ) {
						$target_author = absint( $this->active_author_id_map[ absint( $author ) ] ?? 0 );
						$target_author = $target_author > 0 ? $target_author : get_current_user_id();
						$value['settings'][ $author_key ] = is_string( $author ) ? (string) $target_author : $target_author;
					}
				}
			}
		}

		return $this->normalize_elementor_element_directory_context( $value );
	}

	/** Restore slug-valued taxonomy controls from the same map as numeric IDs. */
	protected function remap_taxonomy_widget_slugs( array $element, array $term_id_map = [] ): array {
		$widget = $element['widgetType'] ?? $element['elType'] ?? '';
		$taxonomy = [ 'directorist_all_categories' => 'at_biz_dir-category', 'directorist_all_locations' => 'at_biz_dir-location' ][ $widget ] ?? '';
		if ( '' === $taxonomy || ! is_array( $element['settings'] ?? null ) ) {
			return $element;
		}
		$remap = static function ( $value, array $map ) {
			if ( is_array( $value ) ) {
				return array_map( static fn( $slug ) => $map[ (string) $slug ] ?? $slug, $value );
			}
			if ( ! is_string( $value ) ) {
				return $value;
			}
			return implode( ',', array_map( static fn( $slug ) => $map[ trim( $slug ) ] ?? trim( $slug ), explode( ',', $value ) ) );
		};
		foreach ( [ 'directory_type', 'default_directory_type', 'active_directory_type' ] as $key ) {
			if ( isset( $element['settings'][ $key ] ) ) {
				$element['settings'][ $key ] = $remap( $element['settings'][ $key ], (array) ( $this->active_term_slug_map['atbdp_listing_types'] ?? [] ) );
			}
		}
		if ( isset( $element['settings']['slug'] ) ) {
			$element['settings']['slug'] = $remap( $element['settings']['slug'], (array) ( $this->active_term_slug_map[ $taxonomy ] ?? [] ) );
		}
		$term_map = (array) ( $term_id_map[ $taxonomy ] ?? [] );
		if ( ! empty( $term_map ) ) {
			ksort( $term_map, SORT_NUMERIC );
			$source_order = (string) ( $element['settings']['imported_term_order'] ?? '' );
			$ordered_ids = '' === $source_order
				? array_values( $term_map )
				: array_map( static fn( $id ) => $term_map[ absint( $id ) ] ?? 0, explode( ',', $source_order ) );
			$element['settings']['imported_term_order'] = implode( ',', array_unique( array_filter( array_map( 'absint', $ordered_ids ) ) ) );
		}
		return $element;
	}

	/**
	 * Restore FormGent form blocks and settings after Elementor's WXR import.
	 *
	 * FormGent migrations may normalize imported posts before every source ID is
	 * mapped. Reapplying the package snapshot here keeps the form definition exact
	 * while retaining target-only runtime metadata.
	 *
	 * @param ImportSession $session Import session.
	 * @param string        $package_file Package archive.
	 * @return array{restored:int,errors:array<int,string>}
	 */
	protected function restore_imported_formgent_forms( ImportSession $session, string $package_file ): array {
		$result = [
			'restored' => 0,
			'errors'   => [],
		];
		$id_map = $session->get_id_map();
		$forms  = $id_map['post_source_id:formgent_form'] ?? [];

		if ( empty( $forms ) ) {
			return $result;
		}

		$snapshots = $this->read_elementor_kit_formgent_wxr( $package_file );

		if ( is_wp_error( $snapshots ) ) {
			$result['errors'][] = $snapshots->get_error_message();

			return $result;
		}

		foreach ( $forms as $source_id => $target_id ) {
			$source_id = (string) $source_id;
			$target_id = absint( $target_id );

			if ( 'skipped' === ( $this->active_import_actions['formgent_form'][ $source_id ] ?? '' ) ) {
				continue;
			}

			if ( empty( $snapshots[ $source_id ] ) ) {
				$result['errors'][] = sprintf(
					/* translators: %s: source FormGent form ID. */
					__( 'The package definition for FormGent form %s is missing.', 'directorist-elementor' ),
					$source_id
				);
				continue;
			}

			if ( $target_id <= 0 || 'formgent_form' !== get_post_type( $target_id ) ) {
				continue;
			}

			$snapshot = $snapshots[ $source_id ];
			$updated  = wp_update_post(
				[
					'ID'           => $target_id,
					'post_content' => wp_slash( (string) ( $snapshot['post_content'] ?? '' ) ),
				],
				true
			);

			if ( is_wp_error( $updated ) ) {
				$result['errors'][] = sprintf(
					/* translators: 1: FormGent form ID, 2: error message. */
					__( 'Could not restore imported FormGent form %1$d: %2$s', 'directorist-elementor' ),
					$target_id,
					$updated->get_error_message()
				);
				continue;
			}

			foreach ( (array) ( $snapshot['meta'] ?? [] ) as $meta_key => $meta_values ) {
				$meta_key = sanitize_key( (string) $meta_key );

				if ( 0 !== strpos( $meta_key, '_formgent_' ) ) {
					continue;
				}

				delete_post_meta( $target_id, $meta_key );

				foreach ( (array) $meta_values as $meta_value ) {
					add_post_meta( $target_id, $meta_key, wp_slash( maybe_unserialize( (string) $meta_value ) ) );
				}
			}

			$session->log(
				'info',
				__( 'Restored imported FormGent form definition.', 'directorist-elementor' ),
				[
					'post_id'   => $target_id,
					'source_id' => $source_id,
				]
			);
			$result['restored']++;
		}

		return $result;
	}

	/**
	 * Read FormGent form snapshots from an Elementor kit WXR file.
	 *
	 * @param string $package_file Package archive.
	 * @return array<string,array{post_content:string,meta:array<string,array<int,string>>}>|WP_Error
	 */
	protected function read_elementor_kit_formgent_wxr( string $package_file ) {
		if ( ! class_exists( ZipArchive::class ) || ! function_exists( 'simplexml_load_string' ) ) {
			return new WP_Error(
				'directorist_elementor_formgent_parser_missing',
				__( 'The imported FormGent forms could not be preserved because the required XML or ZIP extension is unavailable.', 'directorist-elementor' )
			);
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error(
				'directorist_elementor_formgent_package_open_failed',
				__( 'The imported FormGent forms could not be preserved because the package could not be opened.', 'directorist-elementor' )
			);
		}

		$forms = [];

		for ( $index = 0; $index < $zip->numFiles; $index++ ) {
			$name = (string) $zip->getNameIndex( $index );

			if ( ! preg_match( '#^wp-content/formgent_form/.+\.xml$#', $name ) ) {
				continue;
			}

			$xml = $zip->getFromIndex( $index );

			if ( ! is_string( $xml ) || '' === $xml ) {
				continue;
			}

			$forms = array_replace( $forms, $this->parse_formgent_wxr_xml( $xml ) );
		}

		$zip->close();

		if ( empty( $forms ) ) {
			return new WP_Error(
				'directorist_elementor_formgent_payload_missing',
				__( 'The package did not contain the FormGent form definitions referenced by the import.', 'directorist-elementor' )
			);
		}

		return $forms;
	}

	/**
	 * Parse FormGent post content and settings from WXR XML.
	 *
	 * @param string $xml WXR XML.
	 * @return array<string,array{post_content:string,meta:array<string,array<int,string>>}>
	 */
	protected function parse_formgent_wxr_xml( string $xml ): array {
		$previous = libxml_use_internal_errors( true );
		$document = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $document instanceof \SimpleXMLElement || empty( $document->channel ) ) {
			return [];
		}

		$forms             = [];
		$wp_namespace      = 'http://wordpress.org/export/1.2/';
		$content_namespace = 'http://purl.org/rss/1.0/modules/content/';

		foreach ( $document->channel->item as $item ) {
			$wp_item = $item->children( $wp_namespace );

			if ( 'formgent_form' !== sanitize_key( (string) $wp_item->post_type ) ) {
				continue;
			}

			$source_id = (string) absint( (string) $wp_item->post_id );

			if ( '0' === $source_id ) {
				continue;
			}

			$meta = [];

			foreach ( $wp_item->postmeta as $postmeta ) {
				$meta_key = sanitize_key( (string) $postmeta->meta_key );

				if ( 0 !== strpos( $meta_key, '_formgent_' ) ) {
					continue;
				}

				$meta[ $meta_key ][] = (string) $postmeta->meta_value;
			}

			$forms[ $source_id ] = [
				'post_content' => (string) $item->children( $content_namespace )->encoded,
				'meta'         => $meta,
			];
		}

		return $forms;
	}

	/**
	 * Remap FormGent shortcode form IDs after imported forms have local IDs.
	 *
	 * @param string        $content Elementor string setting.
	 * @param ImportSession $session Completed import session.
	 * @return string
	 */
	protected function remap_formgent_shortcode_ids( string $content, ImportSession $session ): string {
		$remapped = preg_replace_callback(
			'/(\[formgent\b[^\]]*\bid\s*=\s*)(["\']?)(\d+)\2/i',
			function ( array $matches ) use ( $session ): string {
				$source_id = absint( $matches[3] ?? 0 );
				$target_id = $source_id > 0 ? $this->get_mapped_post_id( $session, 'formgent_form', (string) $source_id ) : null;

				if ( ! $target_id ) {
					return (string) $matches[0];
				}

				return (string) $matches[1] . (string) $matches[2] . $target_id . (string) $matches[2];
			},
			$content
		);

		return is_string( $remapped ) ? $remapped : $content;
	}

	/**
	 * Check whether a setting contains JSON-encoded per-directory composition.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	protected function is_elementor_scoped_storage_key( string $key ): bool {
		return in_array( $key, [ 'scoped_templates', 'scoped_search_templates' ], true );
	}

	/**
	 * Remap the directory prefix on stored composition keys.
	 *
	 * @param array<string,mixed>              $scopes Stored composition scopes.
	 * @param array<string,array<int,int>>     $term_id_map Term ID map by taxonomy.
	 * @return array<string,mixed>
	 */
	protected function remap_elementor_directory_scope_keys( array $scopes, array $term_id_map ): array {
		$remapped = [];

		foreach ( $scopes as $scope_key => $scope ) {
			$remapped[ $this->remap_elementor_directory_scope_key( (string) $scope_key, $term_id_map ) ] = $scope;
		}

		return $remapped;
	}

	/**
	 * Remap one `dir-{directory}` or `dir-{directory}-{view}` key.
	 *
	 * @param string                          $scope_key Composition scope key.
	 * @param array<string,array<int,int>>    $term_id_map Term ID map by taxonomy.
	 * @return string
	 */
	protected function remap_elementor_directory_scope_key( string $scope_key, array $term_id_map ): string {
		if ( ! preg_match( '/^dir-(\d+)(.*)$/', $scope_key, $matches ) ) {
			return $scope_key;
		}

		$source_id = absint( $matches[1] );
		$target_id = absint( $term_id_map['atbdp_listing_types'][ $source_id ] ?? 0 );

		if ( $target_id <= 0 ) {
			return $scope_key;
		}

		return 'dir-' . $target_id . (string) ( $matches[2] ?? '' );
	}

	/**
	 * Keep active composition keys aligned with the remapped stored scopes.
	 *
	 * @param array<mixed> $settings Elementor settings or nested data.
	 * @return array<mixed>
	 */
	protected function normalize_elementor_directory_scope_settings( array $settings ): array {
		$directory_ids = $this->normalize_elementor_directory_type_ids( $settings['directory_type_ids'] ?? [] );

		if ( ! empty( $directory_ids ) ) {
			foreach ( [ 'default_directory_type_id', 'active_directory_type_id' ] as $setting_key ) {
				if ( isset( $settings[ $setting_key ] ) && ! in_array( absint( $settings[ $setting_key ] ), $directory_ids, true ) ) {
					$settings[ $setting_key ] = (string) $directory_ids[0];
				}
			}
		}

		foreach (
			[
				'scoped_search_templates' => 'active_search_template_key',
				'scoped_templates'        => 'active_template_key',
			] as $storage_key => $active_key
		) {
			if ( empty( $settings[ $storage_key ] ) || ! is_string( $settings[ $storage_key ] ) ) {
				continue;
			}

			$scopes = json_decode( $settings[ $storage_key ], true );

			if ( ! is_array( $scopes ) || empty( $scopes ) ) {
				continue;
			}

			$current_scope = sanitize_key( (string) ( $settings[ $active_key ] ?? '' ) );

			if ( '' === $current_scope || ! array_key_exists( $current_scope, $scopes ) ) {
				$settings[ $active_key ] = (string) array_key_first( $scopes );
			}
		}

		return $settings;
	}

	/**
	 * Recover an element's active directory from its child composition when the
	 * exported element did not store its own directory selection.
	 *
	 * @param array<mixed> $element Elementor element data.
	 * @return array<mixed>
	 */
	protected function normalize_elementor_element_directory_context( array $element ): array {
		if (
			empty( $element['settings'] ) ||
			! is_array( $element['settings'] ) ||
			! isset( $element['settings']['active_directory_type_id'] )
		) {
			return $element;
		}

		$directory_ids = $this->collect_elementor_composition_directory_ids( $element['settings'] );

		if ( empty( $directory_ids ) && ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
			$directory_ids = $this->collect_elementor_composition_directory_ids( $element['elements'] );
		}

		if (
			! empty( $directory_ids ) &&
			! in_array( absint( $element['settings']['active_directory_type_id'] ), $directory_ids, true )
		) {
			$element['settings']['active_directory_type_id'] = (string) $directory_ids[0];
		}

		return $element;
	}

	/**
	 * Collect authoritative directory IDs from selection and composition data.
	 *
	 * Active editor-only IDs are intentionally ignored because they can outlive
	 * the directory term that originally produced them.
	 *
	 * @param mixed $value Elementor data.
	 * @return array<int,int>
	 */
	protected function collect_elementor_composition_directory_ids( $value ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$directory_ids = [];

		foreach ( $value as $key => $child ) {
			$key = (string) $key;

			if ( 'directory_type_ids' === $key ) {
				$directory_ids = array_merge( $directory_ids, $this->normalize_elementor_directory_type_ids( $child ) );
				continue;
			}

			if ( in_array( $key, [ 'default_directory_type_id', 'directory_type_id' ], true ) ) {
				$directory_id = absint( $child );

				if ( $directory_id > 0 ) {
					$directory_ids[] = $directory_id;
				}

				continue;
			}

			if ( $this->is_elementor_scoped_storage_key( $key ) && is_string( $child ) ) {
				$scopes = json_decode( $child, true );

				if ( is_array( $scopes ) ) {
					foreach ( array_keys( $scopes ) as $scope_key ) {
						if ( preg_match( '/^dir-(\d+)/', (string) $scope_key, $matches ) ) {
							$directory_ids[] = absint( $matches[1] );
						}
					}
				}

				continue;
			}

			if ( is_array( $child ) ) {
				$directory_ids = array_merge( $directory_ids, $this->collect_elementor_composition_directory_ids( $child ) );
			}
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $directory_ids ) ) ) );
	}

	/**
	 * Normalize directory selection values stored as arrays or JSON.
	 *
	 * @param mixed $value Directory selection value.
	 * @return array<int,int>
	 */
	protected function normalize_elementor_directory_type_ids( $value ): array {
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );

			if ( is_array( $decoded ) ) {
				$value = $decoded;
			}
		}

		if ( ! is_array( $value ) ) {
			$value = [ $value ];
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Check whether an Elementor setting references another template document.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	protected function is_elementor_template_reference_key( string $key ): bool {
		return in_array(
			$key,
			[
				'template_id',
				'saved_template_id',
				'loop_item_id',
				'loop_template_id',
				'empty_loop_template_id',
				'popup_id',
				'popup_action_popup_id',
				'theme_template_id',
				'maintenance_mode_template_id',
				'base_template_id',
				'from_template_id',
			],
			true
		);
	}

	/**
	 * Remap one Elementor template reference while preserving its scalar type.
	 *
	 * @param mixed         $value Source template reference.
	 * @param ImportSession $session Completed import session.
	 * @param bool          $source_payload Whether the reference came directly from the source package.
	 * @return mixed
	 */
	protected function remap_elementor_template_reference_value( $value, ImportSession $session, bool $source_payload = false ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = $this->remap_elementor_template_reference_value( $item, $session, $source_payload );
			}

			return $value;
		}

		if ( ! is_numeric( $value ) || absint( $value ) <= 0 ) {
			return $value;
		}

		$reference_id = absint( $value );
		$id_map       = $session->get_id_map();
		$native_map   = $id_map['post_import_id:elementor_library'] ?? [];
		$source_map   = $id_map['post_source_id:elementor_library'] ?? [];

		if ( $source_payload && isset( $source_map[ (string) $reference_id ] ) ) {
			$mapped_id = absint( $source_map[ (string) $reference_id ] );

			return is_string( $value ) ? (string) $mapped_id : $mapped_id;
		}

		if ( isset( $native_map[ (string) $reference_id ] ) ) {
			$mapped_id = absint( $native_map[ (string) $reference_id ] );

			return is_string( $value ) ? (string) $mapped_id : $mapped_id;
		}

		if ( in_array( $reference_id, array_map( 'absint', $source_map ), true ) ) {
			return $value;
		}

		$mapped_id = $this->get_mapped_post_id( $session, 'elementor_library', (string) $reference_id );

		if ( null === $mapped_id || $mapped_id <= 0 ) {
			return $value;
		}

		return is_string( $value ) ? (string) $mapped_id : $mapped_id;
	}

	/**
	 * Check whether an Elementor setting stores Directorist directory type IDs.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	protected function is_directory_type_setting_key( string $key ): bool {
		return in_array( $key, [ 'directory_type_ids', 'default_directory_type_id', 'active_directory_type_id', 'directory_type_id' ], true );
	}

	/**
	 * Check whether an Elementor setting stores a WordPress nav menu term id.
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	protected function is_nav_menu_setting_key( string $key ): bool {
		return in_array( $key, [ 'menu', 'menu_id', 'nav_menu_id' ], true );
	}

	/**
	 * Remap source directory type IDs to imported target IDs.
	 *
	 * @param mixed                       $value Setting value.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @return mixed
	 */
	protected function remap_directory_type_setting_value( $value, array $term_id_map ) {
		$directory_type_map = isset( $term_id_map['atbdp_listing_types'] ) && is_array( $term_id_map['atbdp_listing_types'] ) ? $term_id_map['atbdp_listing_types'] : [];

		if ( empty( $directory_type_map ) ) {
			return $value;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = $this->remap_directory_type_setting_value( $item, $term_id_map );
			}

			return $value;
		}

		$directory_type_id = absint( $value );

		if ( $directory_type_id <= 0 || empty( $directory_type_map[ $directory_type_id ] ) ) {
			return $value;
		}

		return (string) absint( $directory_type_map[ $directory_type_id ] );
	}

	/**
	 * Remap the directory prefix in a `{directory}|{field}` selection.
	 *
	 * @param mixed                       $value Custom field selection.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @return mixed
	 */
	protected function remap_custom_field_selection_value( $value, array $term_id_map ) {
		if ( ! is_string( $value ) || ! preg_match( '/^(\d+)\|(.+)$/', $value, $matches ) ) {
			return $value;
		}

		$source_id = absint( $matches[1] );
		$target_id = absint( $term_id_map['atbdp_listing_types'][ $source_id ] ?? 0 );

		if ( $target_id <= 0 ) {
			return $value;
		}

		return $target_id . '|' . $matches[2];
	}

	/**
	 * Remap source nav menu IDs to imported nav menu IDs.
	 *
	 * @param mixed                        $value Setting value.
	 * @param array<string,array<int,int>> $term_id_map Term ID map by taxonomy.
	 * @param ImportSession|null           $session Completed import session.
	 * @param string                       $key Setting key.
	 * @return mixed
	 */
	protected function remap_nav_menu_setting_value( $value, array $term_id_map, ?ImportSession $session = null, string $key = '' ) {
		if ( 'menu' === $key && is_string( $value ) && ! is_numeric( $value ) && null !== $session ) {
			$target_id = $session->get_mapped_id( 'nav_menu_source_slug', sanitize_title( $value ) );
			$term      = $target_id ? get_term( $target_id, 'nav_menu' ) : null;

			if ( $term instanceof \WP_Term ) {
				return $term->slug;
			}
		}

		$nav_menu_map = isset( $term_id_map['nav_menu'] ) && is_array( $term_id_map['nav_menu'] ) ? $term_id_map['nav_menu'] : [];

		if ( empty( $nav_menu_map ) ) {
			return $value;
		}

		$menu_id = absint( $value );

		if ( $menu_id <= 0 || empty( $nav_menu_map[ $menu_id ] ) ) {
			return $value;
		}

		return (string) absint( $nav_menu_map[ $menu_id ] );
	}

	/**
	 * Import media control arrays in fallback-restored Elementor data.
	 *
	 * @param mixed              $value Elementor value.
	 * @param ImportSession|null $session Import session.
	 * @return mixed
	 */
	protected function import_media_controls( $value, ?ImportSession $session = null ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( array_key_exists( 'id', $value ) && ! empty( $value['url'] ) && is_string( $value['url'] ) && preg_match( '#^https?://#', $value['url'] ) && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->templates_manager ) ) {
			$source_id = absint( $value['id'] ?? 0 );
			$local_id  = $session instanceof ImportSession && $source_id > 0
				? absint( $session->get_mapped_id( 'attachment', (string) $source_id ) )
				: 0;

			if ( $local_id <= 0 && $session instanceof ImportSession && $source_id > 0 ) {
				$source_uid = $session->get_package_id() . ':attachment:' . $source_id;
				$local_id   = $this->find_existing_imported_post_id( $source_uid );
			}
			if ( $local_id > 0 && ! $this->attachment_file_exists( $local_id ) ) {
				$local_id = 0;
			}

			if ( $local_id > 0 && ! $this->attachment_matches_source_media_url( $local_id, (string) $value['url'] ) ) {
				$local_id    = 0;
				$source_id   = 0;
				$value['id'] = '';
			}
			$reused_by_url = false;
			if ( $local_id <= 0 ) {
				$local_id = $this->find_readable_media_by_source_url( $value['url'] );
				$reused_by_url = $local_id > 0;
			}

			if ( $local_id > 0 && 'attachment' === get_post_type( $local_id ) ) {
				if ( $session instanceof ImportSession && $source_id > 0 ) {
					if ( $reused_by_url ) {
						$session->map_id( 'attachment', (string) $source_id, $local_id );
					} else {
						$this->track_imported_media( $session, $source_id, $local_id, 'updated' );
					}
				}
				$value['id']  = $local_id;
				$value['url'] = wp_get_attachment_url( $local_id ) ?: $value['url'];
			} else {
				$importer = \Elementor\Plugin::$instance->templates_manager->get_import_images_instance();
				$imported = $importer->import(
					[
						// URL identity was checked above; avoid Elementor's source-ID cache collisions.
						'id'  => '',
						'url' => $value['url'],
					]
				);

				if ( is_array( $imported )
					&& ! empty( $imported['url'] )
					&& false === strpos( (string) $imported['url'], 'elementor/assets/images/placeholder.png' ) ) {
					$source_url = $value['url'];
					$value['id']  = $imported['id'] ?? '';
					$value['url'] = $imported['url'];

					if ( $session instanceof ImportSession && $source_id > 0 && absint( $value['id'] ) > 0 ) {
						$this->track_imported_media( $session, $source_id, absint( $value['id'] ), 'created' );
					} elseif ( $session instanceof ImportSession && absint( $value['id'] ) > 0 ) {
						$attachment_id = absint( $value['id'] );
						$session->record_created( 'attachment', $attachment_id );
						update_post_meta( $attachment_id, '_directorist_import_source_uid', $session->get_package_id() . ':attachment-url:' . sha1( $source_url ) );
						update_post_meta( $attachment_id, '_directorist_import_package_id', $session->get_package_id() );
						update_post_meta( $attachment_id, '_directorist_import_package_version', $session->get_package_version() );
					}
					if ( absint( $value['id'] ) > 0 ) {
						update_post_meta( absint( $value['id'] ), '_directorist_import_original_url', esc_url_raw( $source_url ) );
					}
				} elseif ( array_key_exists( 'id', $value ) ) {
					$local_id = $session instanceof ImportSession
						? $this->import_remote_media_attachment( $session, $source_id, $value['url'] )
						: 0;

					if ( $local_id > 0 ) {
						$value['id']  = $local_id;
						$value['url'] = wp_get_attachment_url( $local_id ) ?: $value['url'];
					} else {
						// Avoid resolving a source attachment ID to unrelated local media.
						$value['id'] = '';
						$scheme      = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );

						if ( in_array( $scheme, [ 'http', 'https' ], true ) ) {
							$value['url'] = set_url_scheme( $value['url'], $scheme );
						}
					}
				}
			}
		}

		foreach ( $value as $key => $child ) {
			if ( is_array( $child ) ) {
				$value[ $key ] = $this->import_media_controls( $child, $session );
			}
		}

		return $value;
	}

	/** Reuse exact URL identities, including controls that have no source media ID. */
	protected function find_readable_media_by_source_url( string $url ): int {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON p.ID = m.post_id WHERE p.post_type = 'attachment' AND ((m.meta_key = '_elementor_source_image_hash' AND m.meta_value = %s) OR (m.meta_key = '_directorist_import_original_url' AND m.meta_value = %s)) ORDER BY p.ID ASC",
			sha1( $url ), esc_url_raw( $url )
		) );
		foreach ( $ids as $id ) {
			if ( $this->attachment_file_exists( (int) $id ) ) {
				return (int) $id;
			}
		}
		return 0;
	}

	/**
	 * Import media controls stored inside JSON-encoded composition scopes.
	 *
	 * Elementor's normal recursive media pass cannot inspect scoped card and
	 * search payloads because those controls are persisted as JSON strings.
	 * Normalize them while their source attachment map is still available.
	 *
	 * @param mixed              $value Elementor value.
	 * @param ImportSession|null $session Import session.
	 * @return mixed
	 */
	protected function import_scoped_media_controls( $value, ?ImportSession $session = null ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			if ( $this->is_elementor_scoped_storage_key( (string) $key ) && is_string( $child ) ) {
				$decoded = json_decode( html_entity_decode( $child, ENT_QUOTES, 'UTF-8' ), true );

				if ( is_array( $decoded ) ) {
					$value[ $key ] = wp_json_encode( $this->import_media_controls( $decoded, $session ) );
				}

				continue;
			}

			if ( is_array( $child ) ) {
				$value[ $key ] = $this->import_scoped_media_controls( $child, $session );
			}
		}

		return $value;
	}

	/**
	 * Confirm a mapped attachment represents the media URL used by a control.
	 *
	 * @param int    $attachment_id Local attachment ID.
	 * @param string $source_url Media-control source URL.
	 * @return bool
	 */
	protected function attachment_matches_source_media_url( int $attachment_id, string $source_url ): bool {
		$original_url = (string) get_post_meta( $attachment_id, '_directorist_import_original_url', true );

		if ( '' === $original_url ) {
			$original_url = (string) wp_get_attachment_url( $attachment_id );
		}

		$source_identity   = $this->normalize_media_url_identity( $source_url );
		$original_identity = $this->normalize_media_url_identity( $original_url );

		return '' !== $source_identity && $source_identity === $original_identity;
	}

	/**
	 * Normalize generated image-size and scaled suffixes for identity comparison.
	 *
	 * @param string $url Media URL.
	 * @return string
	 */
	protected function normalize_media_url_identity( string $url ): string {
		$path = rawurldecode( (string) wp_parse_url( $url, PHP_URL_PATH ) );

		if ( '' === $path ) {
			return '';
		}

		if ( preg_match( '#/wp-content/uploads/(?:sites/\d+/)?(.+)$#', $path, $matches ) ) {
			$path = (string) $matches[1];
		}

		$directory = trim( (string) dirname( $path ), '/' );
		$extension = strtolower( (string) pathinfo( $path, PATHINFO_EXTENSION ) );
		$filename  = (string) pathinfo( $path, PATHINFO_FILENAME );
		$filename  = preg_replace( '/(?:-scaled|-\d+x\d+)+$/', '', $filename ) ?: $filename;

		return strtolower( ( '' !== $directory && '.' !== $directory ? $directory . '/' : '' ) . $filename . ( '' !== $extension ? '.' . $extension : '' ) );
	}

	/**
	 * Download and sanitize an SVG before adding it to the media library.
	 *
	 * @param string $source_url Remote SVG URL.
	 * @return int
	 */
	protected function import_sanitized_svg_attachment( string $source_url ): int {
		if ( ! class_exists( '\Elementor\Core\Utils\Svg\Svg_Sanitizer' ) ) {
			return 0;
		}

		$response = wp_safe_remote_get(
			set_url_scheme( $source_url, 'https' ),
			[
				'timeout'     => 20,
				'redirection' => 3,
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return 0;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( ! is_string( $body ) || '' === $body || strlen( $body ) > MB_IN_BYTES ) {
			return 0;
		}

		$sanitizer = new \Elementor\Core\Utils\Svg\Svg_Sanitizer();
		$sanitized = $sanitizer->sanitize( $body );

		if ( ! is_string( $sanitized ) || '' === $sanitized ) {
			return 0;
		}

		$filename = sanitize_file_name( wp_basename( (string) wp_parse_url( $source_url, PHP_URL_PATH ) ) );

		if ( '' === $filename || 'svg' !== strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) ) ) {
			$filename = 'directorist-imported-icon.svg';
		}

		$allow_svg = static function ( array $mimes ): array {
			$mimes['svg'] = 'image/svg+xml';

			return $mimes;
		};
		$upload = [];

		add_filter( 'upload_mimes', $allow_svg );

		try {
			$upload = wp_upload_bits( $filename, null, $sanitized );
		} finally {
			remove_filter( 'upload_mimes', $allow_svg );
		}

		if ( ! empty( $upload['error'] ) || empty( $upload['file'] ) ) {
			return 0;
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => 'image/svg+xml',
				'post_status'    => 'inherit',
				'post_title'     => sanitize_text_field( pathinfo( $filename, PATHINFO_FILENAME ) ),
			],
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) || $attachment_id <= 0 ) {
			wp_delete_file( $upload['file'] );

			return 0;
		}

		update_post_meta( $attachment_id, '_elementor_inline_svg', $sanitized );

		return absint( $attachment_id );
	}

	/**
	 * Read a JSON file from a zip package.
	 *
	 * @param string $package_file Zip package file.
	 * @param string $relative_path Relative file path.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function read_zip_json_file( string $package_file, string $relative_path ) {
		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error( 'directorist_elementor_zip_missing', __( 'The PHP ZipArchive extension is required to import Directorist Elementor website packages.', 'directorist-elementor' ) );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$json = $zip->getFromName( $relative_path );
		$zip->close();

		if ( ! is_string( $json ) ) {
			return new WP_Error( 'directorist_elementor_zip_json_missing', __( 'The requested package file is missing.', 'directorist-elementor' ) );
		}

		$data = json_decode( $json, true );

		if ( ! is_array( $data ) ) {
			return new WP_Error( 'directorist_elementor_zip_json_invalid', __( 'The requested package file is not valid JSON.', 'directorist-elementor' ) );
		}

		return $data;
	}

	/**
	 * Read manifest.json directly from a zip package.
	 *
	 * @param string $package_file Zip package file.
	 * @return array<string,mixed>|WP_Error
	 */
	protected function read_zip_manifest( string $package_file ) {
		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error( 'directorist_elementor_zip_missing', __( 'The PHP ZipArchive extension is required to import Directorist Elementor website packages.', 'directorist-elementor' ) );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$manifest_json = $zip->getFromName( 'manifest.json' );
		$zip->close();

		if ( ! is_string( $manifest_json ) ) {
			return new WP_Error( 'directorist_elementor_manifest_missing', __( 'The package manifest is missing.', 'directorist-elementor' ) );
		}

		$manifest = json_decode( $manifest_json, true );

		if ( ! is_array( $manifest ) ) {
			return new WP_Error( 'directorist_elementor_manifest_invalid', __( 'The package manifest is not valid JSON.', 'directorist-elementor' ) );
		}

		return $manifest;
	}

	/**
	 * Replace manifest.json inside a temporary downloaded zip package.
	 *
	 * @param string              $package_file Zip package file.
	 * @param array<string,mixed> $manifest Manifest data.
	 * @return true|WP_Error
	 */
	protected function replace_zip_manifest( string $package_file, array $manifest ) {
		if ( ! class_exists( ZipArchive::class ) ) {
			return new WP_Error( 'directorist_elementor_zip_missing', __( 'The PHP ZipArchive extension is required to import Directorist Elementor website packages.', 'directorist-elementor' ) );
		}

		$zip = new ZipArchive();

		if ( true !== $zip->open( $package_file ) ) {
			return new WP_Error( 'directorist_elementor_zip_open_failed', __( 'Could not open the Directorist Elementor website package.', 'directorist-elementor' ) );
		}

		$zip->deleteName( 'manifest.json' );
		$zip->addFromString( 'manifest.json', wp_json_encode( $manifest ) );
		$zip->close();

		return true;
	}

	/**
	 * Determine whether a manifest should be delegated to Elementor's kit importer.
	 *
	 * @param array<string,mixed> $manifest Manifest.
	 * @param array<string,mixed> $args Import args.
	 * @return bool
	 */
	protected function is_elementor_kit_manifest( array $manifest, array $args = [] ): bool {
		$catalog_item = isset( $args['catalog_item'] ) && is_array( $args['catalog_item'] ) ? $args['catalog_item'] : [];
		$package_schema = sanitize_key( (string) ( $manifest['directorist']['package_schema'] ?? '' ) );

		if ( 'directorist-elementor-site-kit-v1' === $package_schema ) {
			return true;
		}

		return (
			! empty( $manifest['templates'] ) &&
			is_array( $manifest['templates'] ) &&
			(
				! empty( $manifest['elementor_version'] ) ||
				! empty( $manifest['directorist'] ) ||
				'elementor_kit' === sanitize_key( (string) ( $catalog_item['import_format'] ?? '' ) )
			)
		);
	}

	/**
	 * Clear Elementor cache metadata and generated CSS for one post.
	 *
	 * @param int $post_id Post id.
	 * @return void
	 */
	protected function clear_elementor_cache_for_post( int $post_id ): void {
		delete_post_meta( $post_id, '_elementor_element_cache' );

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $post_id );

			if ( method_exists( $css_file, 'delete' ) ) {
				$css_file->delete();
			}
		}
	}

	/**
	 * Allow Elementor media imports from the plugin-controlled catalog host.
	 *
	 * Elementor's media importer intentionally uses wp_safe_remote_get(). That
	 * blocks local development hosts such as templates.test unless the host is
	 * explicitly trusted for the current import.
	 *
	 * @return callable
	 */
	protected function get_safe_remote_host_filter(): callable {
		$trusted_hosts = CatalogClient::get_instance()->get_trusted_hosts();

		return static function ( bool $external, string $host ) use ( $trusted_hosts ): bool {
			$host = strtolower( trim( $host ) );

			foreach ( $trusted_hosts as $trusted_host ) {
				$trusted_host = strtolower( trim( (string) $trusted_host ) );

				if ( '' === $trusted_host ) {
					continue;
				}

				if ( $host === $trusted_host || ( str_starts_with( $trusted_host, '*.' ) && str_ends_with( $host, substr( $trusted_host, 1 ) ) ) ) {
					return true;
				}
			}

			return $external;
		};
	}

	/**
	 * Create a temporary package extraction directory under uploads.
	 *
	 * @return string|WP_Error
	 */
	protected function create_temp_directory() {
		$uploads = wp_upload_dir();

		if ( ! empty( $uploads['error'] ) || empty( $uploads['basedir'] ) ) {
			return new WP_Error( 'directorist_elementor_uploads_unavailable', __( 'The WordPress uploads directory is not writable.', 'directorist-elementor' ) );
		}

		$base = trailingslashit( $uploads['basedir'] ) . 'directorist-elementor-template-imports';

		if ( ! wp_mkdir_p( $base ) ) {
			return new WP_Error( 'directorist_elementor_import_temp_failed', __( 'Could not create the Directorist template import directory.', 'directorist-elementor' ) );
		}

		$dir = trailingslashit( $base ) . wp_generate_uuid4();

		if ( ! wp_mkdir_p( $dir ) ) {
			return new WP_Error( 'directorist_elementor_import_session_temp_failed', __( 'Could not create the Directorist template import session directory.', 'directorist-elementor' ) );
		}

		return $dir;
	}

	/**
	 * Unzip package.
	 *
	 * @param string $package_file Package file.
	 * @param string $package_dir Destination directory.
	 * @return true|WP_Error
	 */
	protected function unzip_package( string $package_file, string $package_dir ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		global $wp_filesystem;

		if ( ! $wp_filesystem ) {
			WP_Filesystem();
		}

		$result = unzip_file( $package_file, $package_dir );

		return is_wp_error( $result ) ? $result : true;
	}

	/**
	 * Delete temporary file.
	 *
	 * @param string $file File path.
	 * @return void
	 */
	protected function cleanup_file( string $file ): void {
		if ( '' !== $file && file_exists( $file ) ) {
			@unlink( $file );
		}
	}

	/**
	 * Delete temporary directory recursively.
	 *
	 * @param string $dir Directory path.
	 * @return void
	 */
	protected function cleanup_directory( string $dir ): void {
		if ( '' === $dir || ! is_dir( $dir ) ) {
			return;
		}

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS ),
			\RecursiveIteratorIterator::CHILD_FIRST
		);

		foreach ( $iterator as $file_info ) {
			if ( $file_info->isDir() ) {
				@rmdir( $file_info->getPathname() );
			} else {
				@unlink( $file_info->getPathname() );
			}
		}

		@rmdir( $dir );
	}
}
