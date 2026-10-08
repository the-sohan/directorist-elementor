<?php
/**
 * REST controller for Directorist Elementor template imports.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

use DirectoristElementor\Traits\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

class TemplateImportRestController {
	use Singleton;

	private const REST_NAMESPACE = 'directorist-elementor/v1';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route( self::REST_NAMESPACE, '/template-import/jobs/(?P<id>[a-f0-9-]{36})', [
			'methods' => 'GET',
			'callback' => function ( WP_REST_Request $request ) {
				$job = ImportJob::load( (string) $request->get_param( 'id' ) );
				if ( is_wp_error( $job ) ) {
					return $job;
				}
				$busy = $job->is_busy();
				$response = $job->response();
				if ( isset( $response['job'] ) ) {
					$response['job']['busy'] = $busy;
				}
				return $response;
			},
			'permission_callback' => [ $this, 'permissions_check' ],
		] );
		register_rest_route( self::REST_NAMESPACE, '/template-import/jobs/(?P<id>[a-f0-9-]{36})/step', [
			'methods' => 'POST',
			'callback' => function ( WP_REST_Request $request ) {
				return TemplateImportService::get_instance()->step_import_job( (string) $request->get_param( 'id' ) );
			},
			'permission_callback' => [ $this, 'permissions_check' ],
		] );
		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/catalog',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_catalog' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'refresh' => [
						'type'    => 'boolean',
						'default' => false,
					],
				],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/items/(?P<id>[A-Za-z0-9_\-\.]+)',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_item' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/items/(?P<id>[A-Za-z0-9_\-\.]+)/related',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_related_items' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/import',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'import_item' ],
				'permission_callback' => [ $this, 'permissions_check' ],
				'args'                => [
					'item_id' => [
						'type'     => 'string',
						'required' => true,
					],
					'conflict_behavior' => [
						'type'    => 'string',
						'default' => 'update',
						'enum'    => [ 'skip', 'duplicate', 'update', 'replace' ],
					],
						'activate_templates' => [
							'type'    => 'boolean',
						],
						'include_templates' => [
							'type'    => 'boolean',
							'default' => true,
						],
						'include_content' => [
							'type'    => 'boolean',
							'default' => true,
						],
						'apply_site_settings' => [
							'type'    => 'boolean',
						],
						'apply_template_conditions' => [
							'type'    => 'boolean',
						],
						'set_homepage' => [
							'type'    => 'boolean',
						],
					],
				]
			);

		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/upload',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'upload_package' ],
				'permission_callback' => [ $this, 'permissions_check' ],
			]
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/template-import/requirements/activate',
			[
				'methods'             => 'POST',
				'callback'            => [ $this, 'activate_requirement' ],
				'permission_callback' => [ $this, 'activation_permissions_check' ],
				'args'                => [
					'plugin' => [
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					],
					'slug' => [
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_key',
					],
				],
			]
		);
	}

	/**
	 * Permission check.
	 *
	 * @return bool
	 */
	public function permissions_check(): bool {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Check whether the current user can activate installed plugins.
	 *
	 * @return bool
	 */
	public function activation_permissions_check(): bool {
		return current_user_can( 'activate_plugins' );
	}

	/**
	 * Get catalog.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_catalog( WP_REST_Request $request ) {
		$catalog = CatalogClient::get_instance()->fetch_catalog( (bool) $request->get_param( 'refresh' ) );

		if ( is_wp_error( $catalog ) ) {
			return $catalog;
		}

		$catalog = ExtensionRequirementService::get_instance()->annotate_catalog( $catalog );

		return rest_ensure_response( $catalog );
	}

	/**
	 * Get item detail.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_item( WP_REST_Request $request ) {
		$item = CatalogClient::get_instance()->fetch_item( (string) $request->get_param( 'id' ) );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$item = ExtensionRequirementService::get_instance()->annotate_item( $item );

		return rest_ensure_response( $item );
	}

	/**
	 * Activate one installed plugin required by a catalog item.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function activate_requirement( WP_REST_Request $request ) {
		$result = ExtensionRequirementService::get_instance()->activate_requirement(
			(string) $request->get_param( 'plugin' ),
			(string) $request->get_param( 'slug' )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( [ 'requirement' => $result ] );
	}

	/**
	 * Get related items.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_related_items( WP_REST_Request $request ) {
		$items = CatalogClient::get_instance()->fetch_related( (string) $request->get_param( 'id' ) );

		if ( is_wp_error( $items ) ) {
			return $items;
		}

		$items = array_values(
			array_map(
				static function ( array $item ): array {
					return ExtensionRequirementService::get_instance()->annotate_item( $item );
				},
				$items
			)
		);

		return rest_ensure_response( [ 'items' => $items ] );
	}

	/**
	 * Import selected item.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_item( WP_REST_Request $request ) {
		$item_id = sanitize_text_field( (string) $request->get_param( 'item_id' ) );
		$item    = CatalogClient::get_instance()->fetch_item( $item_id, true );

		if ( is_wp_error( $item ) ) {
			return $item;
		}

		$requirements = ExtensionRequirementService::get_instance()->verify_item( $item );

		if ( is_wp_error( $requirements ) ) {
			return $requirements;
		}

		$result = TemplateImportService::get_instance()->begin_import_job(
			$item,
			$this->get_import_options_from_request( $request )
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Import an uploaded Directorist Elementor package ZIP.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_package( WP_REST_Request $request ) {
		$files = $request->get_file_params();
		$file  = isset( $files['template_zip'] ) && is_array( $files['template_zip'] ) ? $files['template_zip'] : null;

		if ( empty( $file ) || empty( $file['name'] ) || empty( $file['tmp_name'] ) ) {
			return new WP_Error( 'directorist_elementor_upload_missing', __( 'Choose a Directorist Elementor website package ZIP to import.', 'directorist-elementor' ), [ 'status' => 400 ] );
		}

		if ( ! empty( $file['error'] ) ) {
			return new WP_Error( 'directorist_elementor_upload_failed', __( 'The uploaded package could not be received. Please choose the ZIP again.', 'directorist-elementor' ), [ 'status' => 400 ] );
		}

		if ( 'zip' !== strtolower( pathinfo( (string) $file['name'], PATHINFO_EXTENSION ) ) ) {
			return new WP_Error( 'directorist_elementor_upload_not_zip', __( 'Directorist Elementor website packages must be uploaded as ZIP files.', 'directorist-elementor' ), [ 'status' => 400 ] );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$uploaded = wp_handle_upload(
			$file,
			[
				'test_form' => false,
				'mimes'     => [
					'zip' => 'application/zip|application/x-zip-compressed|multipart/x-zip|application/octet-stream',
				],
			]
		);

		if ( ! empty( $uploaded['error'] ) || empty( $uploaded['file'] ) ) {
			return new WP_Error(
				'directorist_elementor_upload_store_failed',
				sanitize_text_field( (string) ( $uploaded['error'] ?? __( 'The uploaded package could not be stored.', 'directorist-elementor' ) ) ),
				[ 'status' => 400 ]
			);
		}

		$args                  = $this->get_import_options_from_request( $request );
		$args['uploaded_file'] = sanitize_file_name( (string) $file['name'] );

		$result = TemplateImportService::get_instance()->begin_import_job( [], $args, (string) $uploaded['file'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( $result );
	}

	/**
	 * Normalize import options shared by catalog and uploaded ZIP imports.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @return array<string,mixed>
	 */
	private function get_import_options_from_request( WP_REST_Request $request ): array {
		$options = [
			'conflict_behavior'          => sanitize_key( (string) $request->get_param( 'conflict_behavior' ) ),
			'include_templates'         => null === $request->get_param( 'include_templates' ) ? true : (bool) $request->get_param( 'include_templates' ),
			'include_content'           => null === $request->get_param( 'include_content' ) ? true : (bool) $request->get_param( 'include_content' ),
		];
		foreach ( [ 'activate_templates', 'apply_site_settings', 'apply_template_conditions', 'set_homepage' ] as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value ) {
				$options[ $key ] = (bool) $value;
			}
		}
		return $options;
	}
}
