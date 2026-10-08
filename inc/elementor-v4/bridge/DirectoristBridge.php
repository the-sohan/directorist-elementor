<?php
/**
 * Directorist data access helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Bridge;

use DirectoristElementor\Traits\Singleton;

class DirectoristBridge {
	use Singleton;

	/**
	 * Query flag used by the standalone Homepage Search result route.
	 */
	public const HOME_SEARCH_RESULT_QUERY_ARG = 'directorist_home_search';

	/**
	 * Cached Homepage Search Result contract.
	 *
	 * @var array<string,mixed>|null
	 */
	protected ?array $home_search_contract_cache = null;

	/**
	 * Request-local listing rating summaries keyed by listing ID.
	 *
	 * @var array<int,array{rating:float,count:int}>
	 */
	protected array $listing_rating_summary_cache = [];

	/**
	 * Get listing post type.
	 *
	 * @return string
	 */
	public function get_listing_post_type(): string {
		return defined( 'ATBDP_POST_TYPE' ) ? ATBDP_POST_TYPE : 'at_biz_dir';
	}

	/**
	 * Resolve the URL used by standalone Homepage Search widgets.
	 *
	 * @return string
	 */
	public function get_home_search_result_url(): string {
		return add_query_arg( self::HOME_SEARCH_RESULT_QUERY_ARG, '1', home_url( '/' ) );
	}

	/**
	 * Check whether the current frontend request should render the Homepage
	 * Search Result template.
	 *
	 * @return bool
	 */
	public function is_home_search_result_request(): bool {
		if ( is_admin() ) {
			return false;
		}

		if ( empty( $_GET[ self::HOME_SEARCH_RESULT_QUERY_ARG ] ) ) {
			return false;
		}

		$value = sanitize_text_field( wp_unslash( (string) $_GET[ self::HOME_SEARCH_RESULT_QUERY_ARG ] ) );

		return '' !== $value && '0' !== $value;
	}

	/**
	 * Resolve canonical Homepage Search behavior from the Elementor result template.
	 *
	 * @return array<string,mixed>
	 */
	public function resolve_home_search_contract(): array {
		if ( null !== $this->home_search_contract_cache ) {
			return $this->home_search_contract_cache;
		}

		$contract = [
			'configured'                => false,
			'status'                    => 'missing_template',
			'template_id'               => 0,
			'directory_type_ids'        => [],
			'default_directory_type_id' => 0,
			'directory_type_id'         => 0,
			'loop_settings'             => [],
			'directory_types_element'   => [],
			'search_settings'           => [],
			'search_elements'           => [],
			'search_element'            => [],
		];

		$template_id = $this->resolve_home_search_result_template_id();
		if ( $template_id <= 0 ) {
			$this->home_search_contract_cache = $contract;
			return $contract;
		}

		$contract['template_id'] = $template_id;
		$elementor_data = get_post_meta( $template_id, '_elementor_data', true );
		if ( is_string( $elementor_data ) ) {
			$elementor_data = json_decode( $elementor_data, true );
		}

		if ( empty( $elementor_data ) || ! is_array( $elementor_data ) ) {
			$contract['status'] = 'empty_template';
			$this->home_search_contract_cache = $contract;
			return $contract;
		}

		$contract_elements = $this->find_home_search_contract_elements( $elementor_data );
		if ( empty( $contract_elements['loop'] ) ) {
			$contract['status'] = 'missing_loop';
			$this->home_search_contract_cache = $contract;
			return $contract;
		}

		if ( empty( $contract_elements['search'] ) ) {
			$contract['status'] = 'missing_search';
			$this->home_search_contract_cache = $contract;
			return $contract;
		}

		$search_settings = is_array( $contract_elements['search']['settings'] ?? null )
			? (array) $contract_elements['search']['settings']
			: [];
		$loop_settings = is_array( $contract_elements['loop']['settings'] ?? null )
			? (array) $contract_elements['loop']['settings']
			: [];
		$directory_ids = $this->normalize_id_list( $loop_settings['directory_type_ids'] ?? [] );
		$default_id    = absint(
			$loop_settings['default_directory_type_id']
			?? $loop_settings['active_directory_type_id']
			?? 0
		);

		if ( $default_id > 0 && ! empty( $directory_ids ) && ! in_array( $default_id, $directory_ids, true ) ) {
			$default_id = (int) $directory_ids[0];
		}

		if ( $default_id <= 0 && ! empty( $directory_ids ) ) {
			$default_id = (int) $directory_ids[0];
		}

		$contract['configured']                = true;
		$contract['status']                    = 'configured';
		$contract['directory_type_ids']        = $directory_ids;
		$contract['default_directory_type_id'] = $default_id;
		$contract['directory_type_id']         = $default_id;
		$contract['loop_settings']             = $loop_settings;
		$contract['directory_types_element']   = is_array( $contract_elements['directory_types'] ?? null )
			? (array) $contract_elements['directory_types']
			: [];
		$contract['search_settings']           = $search_settings;
		$contract['search_elements']           = is_array( $contract_elements['search']['elements'] ?? null )
			? (array) $contract_elements['search']['elements']
			: [];
		$contract['search_element']            = is_array( $contract_elements['search'] ?? null )
			? (array) $contract_elements['search']
			: [];

		$this->home_search_contract_cache = $contract;
		return $contract;
	}

	/**
	 * Resolve the Elementor document used for Homepage Search Result.
	 *
	 * @return int
	 */
	protected function resolve_home_search_result_template_id(): int {
		$post_status = $this->is_home_search_editor_contract_request()
			? [ 'publish', 'draft', 'pending', 'private', 'future' ]
			: [ 'publish' ];

		$template_ids = get_posts(
			[
				'post_type'      => 'elementor_library',
				'post_status'    => $post_status,
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'meta_key'       => '_elementor_template_type',
				'meta_value'     => 'directorist-home-search-result',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $template_ids ) ) {
			return 0;
		}

		foreach ( $template_ids as $template_id ) {
			$template_id = absint( $template_id );
			$conditions = get_post_meta( $template_id, '_elementor_conditions', true );
			$conditions = is_array( $conditions ) ? $conditions : [];

			foreach ( $conditions as $condition ) {
				if ( false !== strpos( (string) $condition, 'directorist_home_search_result' ) ) {
					return $template_id;
				}
			}
		}

		return absint( $template_ids[0] );
	}

	/**
	 * Determine whether unpublished Elementor result templates may be used.
	 *
	 * Frontend visitors should only resolve published Theme Builder templates.
	 * Elementor editor previews, including authenticated AJAX refreshes from the
	 * preview iframe, need the draft document so standalone Homepage Search
	 * widgets can mirror the canonical result template before it is published.
	 *
	 * @return bool
	 */
	protected function is_home_search_editor_contract_request(): bool {
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		if ( ! is_admin() ) {
			return true;
		}

		if ( ! empty( $_GET['elementor-preview'] ) ) {
			return true;
		}

		if ( class_exists( '\\Elementor\\Plugin' ) ) {
			$editor = \Elementor\Plugin::$instance->editor ?? null;
			if ( $editor && method_exists( $editor, 'is_edit_mode' ) && $editor->is_edit_mode() ) {
				return true;
			}
		}

		$action = isset( $_REQUEST['action'] )
			? sanitize_key( wp_unslash( (string) $_REQUEST['action'] ) )
			: '';

		if ( 0 === strpos( $action, 'directorist_elementor_v4_preview_' ) || 'elementor_ajax' === $action ) {
			return true;
		}

		if ( 'directorist_elementor_v4_render_homepage_search_form' === $action ) {
			return true;
		}

		return false;
	}

	/**
	 * Find the Homepage Search Loop and nested Homepage Search widget.
	 *
	 * @param array<int,mixed> $elements Elementor data tree.
	 * @return array{loop:?array,search:?array,directory_types:?array}
	 */
	protected function find_home_search_contract_elements( array $elements ): array {
		$first_loop = null;
		$first_directory_types = null;

		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$element_type = sanitize_key( (string) ( $element['elType'] ?? '' ) );

			if ( 'directorist_homepage_search_loop' === $element_type ) {
				if ( null === $first_loop ) {
					$first_loop = $element;
				}

				$loop_children = (array) ( $element['elements'] ?? [] );
				$directory_types = $this->find_first_widget_by_type(
					$loop_children,
					'directorist_search_directory_types'
				);
				if ( null === $first_directory_types && ! empty( $directory_types ) ) {
					$first_directory_types = $directory_types;
				}

				$search = $this->find_first_homepage_search_widget( $loop_children );
				if ( ! empty( $search ) ) {
					return [
						'loop'            => $element,
						'search'          => $search,
						'directory_types' => $directory_types,
					];
				}
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$result = $this->find_home_search_contract_elements( $element['elements'] );
				if ( null === $first_directory_types && ! empty( $result['directory_types'] ) ) {
					$first_directory_types = $result['directory_types'];
				}
				if ( ! empty( $result['search'] ) && ! empty( $result['loop'] ) ) {
					if ( empty( $result['directory_types'] ) && null !== $first_directory_types ) {
						$result['directory_types'] = $first_directory_types;
					}
					return $result;
				}
				if ( null === $first_loop && ! empty( $result['loop'] ) ) {
					$first_loop = $result['loop'];
				}
			}
		}

		return [
			'loop'            => $first_loop,
			'search'          => null,
			'directory_types' => $first_directory_types,
		];
	}

	/**
	 * Find the first widget by type in an Elementor element tree.
	 *
	 * @param array<int,mixed> $elements Elementor data tree.
	 * @param string           $widget_type Widget type.
	 * @return array<string,mixed>|null
	 */
	protected function find_first_widget_by_type( array $elements, string $widget_type ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$element_type = sanitize_key( (string) ( $element['widgetType'] ?? $element['elType'] ?? '' ) );

			if ( $widget_type === $element_type ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$matched = $this->find_first_widget_by_type( $element['elements'], $widget_type );
				if ( ! empty( $matched ) ) {
					return $matched;
				}
			}
		}

		return null;
	}

	/**
	 * Enqueue generated Elementor CSS for the Homepage Search Result template.
	 *
	 * @param array<string,mixed>|null $contract Optional resolved contract.
	 * @return void
	 */
	public function enqueue_home_search_contract_styles( ?array $contract = null ): void {
		$contract = is_array( $contract ) ? $contract : $this->resolve_home_search_contract();
		$template_id = absint( $contract['template_id'] ?? 0 );

		if ( $template_id <= 0 || ! class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
			return;
		}

		$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );

		if ( method_exists( $css_file, 'enqueue' ) ) {
			$css_file->enqueue();
		}
	}

	/**
	 * Find the first Homepage Search widget under a loop.
	 *
	 * @param array<int,mixed> $elements Elementor data tree.
	 * @return array<string,mixed>|null
	 */
	protected function find_first_homepage_search_widget( array $elements ): ?array {
		foreach ( $elements as $element ) {
			if ( ! is_array( $element ) ) {
				continue;
			}

			$element_type = sanitize_key( (string) ( $element['widgetType'] ?? $element['elType'] ?? '' ) );

			if ( 'directorist_homepage_search' === $element_type ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$search = $this->find_first_homepage_search_widget( $element['elements'] );
				if ( ! empty( $search ) ) {
					return $search;
				}
			}
		}

		return null;
	}

	/**
	 * Normalize ID lists from Elementor settings.
	 *
	 * @param mixed $value Raw setting.
	 * @return array<int,int>
	 */
	protected function normalize_id_list( $value ): array {
		if ( is_string( $value ) ) {
			$value = array_filter( array_map( 'trim', explode( ',', $value ) ) );
		}

		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Get directory type taxonomy.
	 *
	 * @return string
	 */
	public function get_directory_taxonomy(): string {
		return defined( 'ATBDP_DIRECTORY_TYPE' ) ? ATBDP_DIRECTORY_TYPE : 'atbdp_listing_types';
	}

	/**
	 * Get category taxonomy.
	 *
	 * @return string
	 */
	public function get_category_taxonomy(): string {
		return defined( 'ATBDP_CATEGORY' ) ? ATBDP_CATEGORY : $this->get_listing_post_type() . '-category';
	}

	/**
	 * Get location taxonomy.
	 *
	 * @return string
	 */
	public function get_location_taxonomy(): string {
		return defined( 'ATBDP_LOCATION' ) ? ATBDP_LOCATION : $this->get_listing_post_type() . '-location';
	}

	/**
	 * Get tag taxonomy.
	 *
	 * @return string
	 */
	public function get_tag_taxonomy(): string {
		return defined( 'ATBDP_TAGS' ) ? ATBDP_TAGS : $this->get_listing_post_type() . '-tags';
	}

	/**
	 * Resolve the Directorist author profile page id.
	 *
	 * @return int
	 */
	public function get_author_profile_page_id(): int {
		if ( function_exists( 'directorist_get_page_id' ) ) {
			$page_id = absint( directorist_get_page_id( 'author' ) );

			if ( $page_id > 0 ) {
				return $page_id;
			}
		}

		return function_exists( 'get_directorist_option' )
			? absint( get_directorist_option( 'author_profile_page' ) )
			: 0;
	}

	/**
	 * Check whether the current request is Directorist's page-based author profile route.
	 *
	 * @return bool
	 */
	public function is_author_profile_page_request(): bool {
		$page_id = $this->get_author_profile_page_id();

		if ( $page_id > 0 && is_page( $page_id ) ) {
			return true;
		}

		return function_exists( 'atbdp_is_page' ) && atbdp_is_page( 'author' );
	}

	/**
	 * Check whether the current request can render a Directorist author profile template.
	 *
	 * @return bool
	 */
	public function is_author_profile_request(): bool {
		if ( is_author() ) {
			return $this->get_current_author_profile_user_id() > 0;
		}

		return $this->is_author_profile_page_request() && $this->get_current_author_profile_user_id() > 0;
	}

	/**
	 * Resolve the current Directorist author profile user id.
	 *
	 * Directorist author profiles are page-based and store the routed author in
	 * the `author_id` query var. Frontend AJAX rerenders carry the same value in
	 * request vars because admin-ajax does not have the pretty-route query state.
	 *
	 * @return int
	 */
	public function get_current_author_profile_user_id(): int {
		if ( is_author() ) {
			$author_id = absint( get_queried_object_id() );

			return $this->user_exists( $author_id ) ? $author_id : 0;
		}

		$raw_author = get_query_var( 'author_id' );

		if ( '' === (string) $raw_author && isset( $_REQUEST['author_id'] ) ) {
			$raw_author = wp_unslash( $_REQUEST['author_id'] );
		}

		if ( is_array( $raw_author ) ) {
			$raw_author = reset( $raw_author );
		}

		$author_id = $this->resolve_author_id_from_value( is_scalar( $raw_author ) ? (string) $raw_author : '' );

		if ( $author_id > 0 ) {
			return $author_id;
		}

		$path_author_id = $this->resolve_author_id_from_request_path();
		if ( $path_author_id > 0 ) {
			return $path_author_id;
		}

		return $this->is_author_profile_page_request() && is_user_logged_in()
			? get_current_user_id()
			: 0;
	}

	/**
	 * Resolve the author slug from Directorist pretty profile URLs.
	 *
	 * WordPress does not always expose Directorist's custom `author_id` rewrite
	 * var by the time Theme Builder conditions run. The profile page still owns
	 * the route, so parse the first path segment after that page.
	 *
	 * @return int
	 */
	protected function resolve_author_id_from_request_path(): int {
		$page_id = $this->get_author_profile_page_id();
		if ( $page_id <= 0 || empty( $_SERVER['REQUEST_URI'] ) || ! is_scalar( $_SERVER['REQUEST_URI'] ) ) {
			return 0;
		}

		$page_path = trim( (string) get_page_uri( $page_id ), '/' );
		if ( '' === $page_path ) {
			$page_path = trim( (string) get_post_field( 'post_name', $page_id ), '/' );
		}
		if ( '' === $page_path ) {
			return 0;
		}

		$request_path = wp_parse_url( (string) wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH );
		if ( ! is_string( $request_path ) || '' === $request_path ) {
			return 0;
		}

		$request_path = trim( rawurldecode( $request_path ), '/' );
		$home_path    = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );

		if ( '' !== $home_path && 0 === strpos( $request_path . '/', $home_path . '/' ) ) {
			$request_path = trim( substr( $request_path, strlen( $home_path ) ), '/' );
		}

		if ( $request_path === $page_path || 0 !== strpos( $request_path . '/', $page_path . '/' ) ) {
			return 0;
		}

		$relative_path = trim( substr( $request_path, strlen( $page_path ) ), '/' );
		if ( '' === $relative_path ) {
			return 0;
		}

		$path_segments = explode( '/', $relative_path );
		$author_slug   = isset( $path_segments[0] ) ? sanitize_text_field( $path_segments[0] ) : '';

		return $this->resolve_author_id_from_value( $author_slug );
	}

	/**
	 * Resolve the current author profile directory type id, when routed.
	 *
	 * @return int
	 */
	public function get_current_author_profile_directory_type_id(): int {
		$raw_directory = get_query_var( 'directory-type' );

		if ( '' === (string) $raw_directory && isset( $_REQUEST['directory_type'] ) ) {
			$raw_directory = wp_unslash( $_REQUEST['directory_type'] );
		}

		if ( is_array( $raw_directory ) ) {
			$raw_directory = reset( $raw_directory );
		}

		if ( ! is_scalar( $raw_directory ) ) {
			return 0;
		}

		$raw_directory = trim( (string) $raw_directory );

		if ( '' === $raw_directory || 'all' === strtolower( $raw_directory ) ) {
			return 0;
		}

		$directory_id = absint( $raw_directory );

		if ( $directory_id > 0 ) {
			return $directory_id;
		}

		$term = get_term_by( 'slug', sanitize_title( urldecode( $raw_directory ) ), $this->get_directory_taxonomy() );

		return $term instanceof \WP_Term ? absint( $term->term_id ) : 0;
	}

	/**
	 * Resolve an author id from a routed value.
	 *
	 * @param string $value User id or login.
	 * @return int
	 */
	public function resolve_author_id_from_value( string $value ): int {
		$value = trim( urldecode( $value ) );

		if ( '' === $value ) {
			return 0;
		}

		$author_id = is_numeric( $value ) ? absint( $value ) : 0;

		if ( $author_id <= 0 ) {
			$user = get_user_by( 'login', $value );

			if ( $user instanceof \WP_User ) {
				$author_id = absint( $user->ID );
			}
		}

		return $this->user_exists( $author_id ) ? $author_id : 0;
	}

	/**
	 * Get author options for editor controls.
	 *
	 * @param int $limit Max options.
	 * @param int $directory_type_id Optional directory scope.
	 * @return array<int,string>
	 */
	public function get_recent_author_options( int $limit = 100, int $directory_type_id = 0 ): array {
		$limit      = max( 1, $limit );
		$post_type  = $this->get_listing_post_type();
		$query_args = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => $limit,
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( $directory_type_id > 0 ) {
			$query_args['meta_query'] = [
				[
					'key'     => '_directory_type',
					'value'   => (string) $directory_type_id,
					'compare' => '=',
				],
			];
		}

		$listings = get_posts( $query_args );
		$options  = [];

		foreach ( $listings as $listing_id ) {
			$author_id = absint( get_post_field( 'post_author', absint( $listing_id ) ) );

			if ( $author_id <= 0 || isset( $options[ $author_id ] ) ) {
				continue;
			}

			$user = get_userdata( $author_id );

			if ( ! $user instanceof \WP_User ) {
				continue;
			}

			$options[ $author_id ] = $user->display_name;
		}

		if ( empty( $options ) && is_user_logged_in() ) {
			$user = wp_get_current_user();

			if ( $user instanceof \WP_User && $user->ID > 0 ) {
				$options[ absint( $user->ID ) ] = $user->display_name;
			}
		}

		return $options;
	}

	/**
	 * Resolve a default author id for editor previews.
	 *
	 * @param int $directory_type_id Optional directory scope.
	 * @return int
	 */
	public function get_default_preview_author_id( int $directory_type_id = 0 ): int {
		$options = $this->get_recent_author_options( 1, $directory_type_id );

		if ( ! empty( $options ) ) {
			return absint( array_key_first( $options ) );
		}

		return is_user_logged_in() ? get_current_user_id() : 0;
	}

	/**
	 * Check whether a user exists.
	 *
	 * @param int $user_id User id.
	 * @return bool
	 */
	protected function user_exists( int $user_id ): bool {
		return $user_id > 0 && get_userdata( $user_id ) instanceof \WP_User;
	}

	/**
	 * Check whether Directorist native taxonomy archive templates are enabled.
	 *
	 * @return bool
	 */
	public function is_archive_template_enabled(): bool {
		return function_exists( 'directorist_is_archive_template_enabled' )
			? (bool) directorist_is_archive_template_enabled()
			: false;
	}

	/**
	 * Resolve the Directorist page id used for a taxonomy archive route when the
	 * legacy page-based archive mode is active.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	public function get_archive_page_id_for_taxonomy( string $taxonomy ): int {
		if ( ! function_exists( 'get_directorist_option' ) ) {
			return 0;
		}

		if ( '' === $taxonomy ) {
			return 0;
		}

		switch ( $taxonomy ) {
			case $this->get_category_taxonomy():
				return absint( get_directorist_option( 'single_category_page' ) );

			case $this->get_location_taxonomy():
				return absint( get_directorist_option( 'single_location_page' ) );

			case $this->get_tag_taxonomy():
				return absint( get_directorist_option( 'single_tag_page' ) );
		}

		return 0;
	}

	/**
	 * Resolve the legacy query var used for a taxonomy archive route.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return string
	 */
	public function get_archive_query_var_for_taxonomy( string $taxonomy ): string {
		switch ( $taxonomy ) {
			case $this->get_category_taxonomy():
				return 'atbdp_category';

			case $this->get_location_taxonomy():
				return 'atbdp_location';

			case $this->get_tag_taxonomy():
				return 'atbdp_tag';
		}

		return '';
	}

	/**
	 * Resolve the current Directorist archive term for a taxonomy from either a
	 * native taxonomy request or the legacy page-based archive route.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return \WP_Term|null
	 */
	public function get_current_archive_term_for_taxonomy( string $taxonomy ) {
		$taxonomy = sanitize_key( $taxonomy );

		if ( '' === $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}

		if ( is_tax( $taxonomy ) ) {
			$queried_object = get_queried_object();

			return $queried_object instanceof \WP_Term ? $queried_object : null;
		}

		if ( $this->is_archive_template_enabled() ) {
			return null;
		}

		$page_id   = $this->get_archive_page_id_for_taxonomy( $taxonomy );
		$query_var = $this->get_archive_query_var_for_taxonomy( $taxonomy );

		if ( $page_id <= 0 || '' === $query_var || ! is_page( $page_id ) ) {
			return null;
		}

		$slug = get_query_var( $query_var );

		if ( ! is_string( $slug ) || '' === trim( $slug ) ) {
			return null;
		}

		$term = get_term_by( 'slug', sanitize_title( urldecode( $slug ) ), $taxonomy );

		return $term instanceof \WP_Term ? $term : null;
	}

	/**
	 * Resolve the current Directorist archive term across category, location, and
	 * tag archive routes.
	 *
	 * @return \WP_Term|null
	 */
	public function get_current_archive_term() {
		foreach ( $this->get_supported_archive_taxonomies() as $taxonomy ) {
			$term = $this->get_current_archive_term_for_taxonomy( $taxonomy );

			if ( $term instanceof \WP_Term ) {
				return $term;
			}
		}

		return null;
	}

	/**
	 * Check whether the current request is a legacy page-based Directorist archive
	 * route for a supported taxonomy.
	 *
	 * @return bool
	 */
	public function is_page_based_archive_request(): bool {
		if ( $this->is_archive_template_enabled() ) {
			return false;
		}

		return $this->get_current_archive_term() instanceof \WP_Term;
	}

	/**
	 * Resolve supported Directorist archive taxonomies.
	 *
	 * @return array<int,string>
	 */
	public function get_supported_archive_taxonomies(): array {
		return array_values(
			array_filter(
				[
					$this->get_category_taxonomy(),
					$this->get_location_taxonomy(),
					$this->get_tag_taxonomy(),
				],
				static fn( $taxonomy ) => is_string( $taxonomy ) && '' !== $taxonomy && taxonomy_exists( $taxonomy )
			)
		);
	}

	/**
	 * Get the FormGent post type slug.
	 *
	 * @return string
	 */
	public function get_formgent_post_type(): string {
		if ( function_exists( 'formgent_post_type' ) ) {
			$post_type = (string) formgent_post_type();

			if ( '' !== $post_type ) {
				return $post_type;
			}
		}

		return 'formgent_form';
	}

	/**
	 * Get directory type options for controls.
	 *
	 * @return array<int|string,string>
	 */
	public function get_directory_options(): array {
		$options = [];

		$terms = get_terms(
			[
				'taxonomy'   => $this->get_directory_taxonomy(),
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[ $term->term_id ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get directory options keyed by slug.
	 *
	 * @return array<int|string,string>
	 */
	public function get_directory_slug_options(): array {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $this->get_directory_taxonomy(),
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get taxonomy term options for controls.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int|string,string>
	 */
	public function get_taxonomy_term_options( string $taxonomy ): array {
		$options = [];
		$terms   = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			return $options;
		}

		foreach ( $terms as $term ) {
			if ( ! $term instanceof \WP_Term ) {
				continue;
			}

			$options[ $term->slug ] = $term->name;
		}

		return $options;
	}

	/**
	 * Get category control options.
	 *
	 * @return array<int|string,string>
	 */
	public function get_category_options(): array {
		return $this->get_taxonomy_term_options( $this->get_category_taxonomy() );
	}

	/**
	 * Get location control options.
	 *
	 * @return array<int|string,string>
	 */
	public function get_location_options(): array {
		return $this->get_taxonomy_term_options( $this->get_location_taxonomy() );
	}

	/**
	 * Get tag control options.
	 *
	 * @return array<int|string,string>
	 */
	public function get_tag_options(): array {
		return $this->get_taxonomy_term_options( $this->get_tag_taxonomy() );
	}

	/**
	 * Resolve the directory type id for a listing.
	 *
	 * @param int $listing_id Listing post id.
	 * @return int
	 */
	public function get_listing_directory_type_id( int $listing_id ): int {
		if ( $listing_id <= 0 ) {
			return 0;
		}

		$terms = wp_get_post_terms(
			$listing_id,
			$this->get_directory_taxonomy(),
			[
				'fields' => 'ids',
			]
		);

		if ( is_wp_error( $terms ) || empty( $terms[0] ) ) {
			return 0;
		}

		return absint( $terms[0] );
	}

	/**
	 * Resolve listing directory label.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return string
	 */
	public function get_directory_label( int $directory_type_id ): string {
		if ( $directory_type_id <= 0 ) {
			return '';
		}

		$term = get_term( $directory_type_id, $this->get_directory_taxonomy() );

		return $term instanceof \WP_Term ? (string) $term->name : '';
	}

	/**
	 * Get submission form fields for a directory type.
	 *
	 * @param int $directory_type_id Directory type id.
	 * @return array<string,mixed>
	 */
	public function get_submission_form_fields( int $directory_type_id ): array {
		if ( $directory_type_id <= 0 ) {
			return [];
		}

		$fields = get_term_meta( $directory_type_id, 'submission_form_fields', true );

		return is_array( $fields ) ? $fields : [];
	}

	/**
	 * Get custom submission field definitions for one or all directory types.
	 *
	 * @param int    $directory_type_id Optional directory type id.
	 * @param string $widget_name Optional custom widget name filter.
	 * @return array<int,array<string,mixed>>
	 */
	public function get_custom_submission_fields( int $directory_type_id = 0, string $widget_name = '' ): array {
		$widget_name = sanitize_key( $widget_name );
		$directories = $directory_type_id > 0
			? [ $directory_type_id => $this->get_directory_label( $directory_type_id ) ]
			: $this->get_directory_options();
		$fields      = [];

		foreach ( $directories as $term_id => $term_label ) {
			$term_id          = absint( $term_id );
			$directory_fields = $this->get_submission_form_fields( $term_id );
			$raw_fields       = is_array( $directory_fields['fields'] ?? null )
				? (array) $directory_fields['fields']
				: ( is_array( $directory_fields ) ? $directory_fields : [] );

			foreach ( $raw_fields as $field_data ) {
				if ( ! is_array( $field_data ) ) {
					continue;
				}

				if ( 'custom' !== (string) ( $field_data['widget_group'] ?? '' ) ) {
					continue;
				}

				$current_widget_name = sanitize_key( (string) ( $field_data['widget_name'] ?? '' ) );
				if ( '' === $current_widget_name ) {
					$current_widget_name = sanitize_key( (string) ( $field_data['type'] ?? '' ) );
				}

				if ( '' !== $widget_name && $widget_name !== $current_widget_name ) {
					continue;
				}

				$field_key = trim( (string) ( $field_data['field_key'] ?? '' ) );

				if ( '' === $field_key ) {
					continue;
				}

				$field_data['widget_name']       = $current_widget_name;
				$field_data['field_key']         = $field_key;
				$field_data['directory_type_id'] = $term_id;
				$field_data['directory_label']   = (string) $term_label;
				$fields[]                        = $field_data;
			}
		}

		usort(
			$fields,
			static function ( array $left, array $right ): int {
				$left_directory  = strtolower( (string) ( $left['directory_label'] ?? '' ) );
				$right_directory = strtolower( (string) ( $right['directory_label'] ?? '' ) );

				if ( $left_directory !== $right_directory ) {
					return $left_directory <=> $right_directory;
				}

				$left_label  = strtolower( (string) ( $left['label'] ?? $left['field_key'] ?? '' ) );
				$right_label = strtolower( (string) ( $right['label'] ?? $right['field_key'] ?? '' ) );

				if ( $left_label !== $right_label ) {
					return $left_label <=> $right_label;
				}

				return strtolower( (string) ( $left['field_key'] ?? '' ) ) <=> strtolower( (string) ( $right['field_key'] ?? '' ) );
			}
		);

		return $fields;
	}

	/**
	 * Build a stable control option key for a custom field.
	 *
	 * @param int    $directory_type_id Directory type id.
	 * @param string $field_key Custom field key.
	 * @return string
	 */
	public function build_custom_field_selection_key( int $directory_type_id, string $field_key ): string {
		return absint( $directory_type_id ) . '|' . sanitize_key( $field_key );
	}

	/**
	 * Get control options for a custom field widget type.
	 *
	 * @param string $widget_name Custom widget type.
	 * @param int    $directory_type_id Optional directory type id.
	 * @return array<string,string>
	 */
	public function get_custom_field_control_options( string $widget_name, int $directory_type_id = 0 ): array {
		$fields            = $this->get_custom_submission_fields( $directory_type_id, $widget_name );
		$directory_ids     = array_unique( array_map( 'absint', array_column( $fields, 'directory_type_id' ) ) );
		$has_many_dirs     = count( array_filter( $directory_ids ) ) > 1;
		$options           = [];

		foreach ( $fields as $field_data ) {
			$field_key         = (string) ( $field_data['field_key'] ?? '' );
			$directory_type_id = absint( $field_data['directory_type_id'] ?? 0 );

			if ( '' === $field_key || $directory_type_id <= 0 ) {
				continue;
			}

			$label = trim( (string) ( $field_data['label'] ?? '' ) );

			if ( '' === $label ) {
				$label = $field_key;
			}

			$label .= sprintf(
				/* translators: %s: Field key. */
				__( ' (%s)', 'directorist-elementor' ),
				$field_key
			);

			if ( $has_many_dirs ) {
				$label .= sprintf(
					/* translators: %s: Directory label. */
					__( ' - %s', 'directorist-elementor' ),
					(string) ( $field_data['directory_label'] ?? '' )
				);
			}

			$options[ $this->build_custom_field_selection_key( $directory_type_id, $field_key ) ] = $label;
		}

		return $options;
	}

	/**
	 * Resolve a saved custom field control selection into its field definition.
	 *
	 * @param string $selection Selection key.
	 * @param string $widget_name Expected widget type.
	 * @return array<string,mixed>
	 */
	public function resolve_custom_field_definition( string $selection, string $widget_name = '' ): array {
		$selection = trim( $selection );

		if ( '' === $selection ) {
			return [];
		}

		$parts             = array_pad( explode( '|', $selection, 2 ), 2, '' );
		$directory_type_id = absint( $parts[0] );
		$field_key         = trim( (string) $parts[1] );

		if ( 0 === $directory_type_id && '' === $field_key ) {
			$field_key = $selection;
		}

		$field_key = sanitize_key( $field_key );

		if ( '' === $field_key ) {
			return [];
		}

		foreach ( $this->get_custom_submission_fields( $directory_type_id, $widget_name ) as $field_data ) {
			$current_field_key = sanitize_key( (string) ( $field_data['field_key'] ?? '' ) );

			if ( $current_field_key === $field_key ) {
				return $field_data;
			}
		}

		return [];
	}

	/**
	 * Get listing title.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_title( int $listing_id ): string {
		return $listing_id > 0 ? (string) get_the_title( $listing_id ) : '';
	}

	/**
	 * Get listing permalink.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_permalink( int $listing_id ): string {
		return $listing_id > 0 ? (string) get_permalink( $listing_id ) : '';
	}

	/**
	 * Get listing terms for a taxonomy.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $taxonomy Taxonomy.
	 * @return array<int,\WP_Term>
	 */
	public function get_listing_terms( int $listing_id, string $taxonomy ): array {
		if ( $listing_id <= 0 || '' === $taxonomy ) {
			return [];
		}

		$terms = get_the_terms( $listing_id, $taxonomy );

		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return [];
		}

		return array_values(
			array_filter(
				$terms,
				static fn( $term ) => $term instanceof \WP_Term
			)
		);
	}

	/**
	 * Get the first location label for a listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_location_label( int $listing_id ): string {
		$terms = $this->get_listing_terms( $listing_id, $this->get_location_taxonomy() );

		return ! empty( $terms[0] ) ? (string) $terms[0]->name : '';
	}

	/**
	 * Get the primary category term for a listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return \WP_Term|null
	 */
	public function get_listing_primary_category( int $listing_id ) {
		$terms = $this->get_listing_terms( $listing_id, $this->get_category_taxonomy() );

		return $terms[0] ?? null;
	}

	/**
	 * Get a normalized listing meta value using fallback keys.
	 *
	 * @param int              $listing_id Listing id.
	 * @param array<int,string> $meta_keys Candidate meta keys.
	 * @return string
	 */
	public function get_listing_meta_value( int $listing_id, array $meta_keys ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		foreach ( $meta_keys as $meta_key ) {
			$meta_key = (string) $meta_key;

			if ( '' === $meta_key ) {
				continue;
			}

			$value = get_post_meta( $listing_id, $meta_key, true );

			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return trim( (string) $value );
			}
		}

		return '';
	}

	/**
	 * Get listing address.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_address( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'address', '_address' ] );
	}

	/**
	 * Get listing email.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_email( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'email', '_email' ] );
	}

	/**
	 * Get listing phone.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_phone( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'phone', '_phone' ] );
	}

	/**
	 * Get listing secondary phone.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_phone_two( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'phone2', '_phone2' ] );
	}

	/**
	 * Get listing fax.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_fax( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'fax', '_fax' ] );
	}

	/**
	 * Get listing website.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_website( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'website', '_website' ] );
	}

	/**
	 * Get listing video URL.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_video_url( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'videourl', '_videourl' ] );
	}

	/**
	 * Get normalized listing social links.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<int,array{id:string,url:string}>
	 */
	public function get_listing_social_links( int $listing_id ): array {
		if ( $listing_id <= 0 ) {
			return [];
		}

		$social_links = get_post_meta( $listing_id, '_social', true );

		if ( ! is_array( $social_links ) || empty( $social_links ) ) {
			return [];
		}

		$normalized_links = [];

		foreach ( $social_links as $social_link ) {
			if ( ! is_array( $social_link ) ) {
				continue;
			}

			$social_id  = sanitize_key( (string) ( $social_link['id'] ?? '' ) );
			$social_url = esc_url_raw( (string) ( $social_link['url'] ?? '' ) );

			if ( '' === $social_id || '' === $social_url ) {
				continue;
			}

			$normalized_links[] = [
				'id'  => $social_id,
				'url' => $social_url,
			];
		}

		return $normalized_links;
	}

	/**
	 * Get listing zip/post code.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_zip_code( int $listing_id ): string {
		return $this->get_listing_meta_value( $listing_id, [ 'zip', '_zip' ] );
	}

	/**
	 * Get listing excerpt.
	 *
	 * Directorist stores the short description as the native post excerpt when
	 * available, with `_excerpt` kept as a compatibility fallback.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_excerpt( int $listing_id ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		$post = get_post( $listing_id );

		if ( ! $post instanceof \WP_Post ) {
			return '';
		}

		$excerpt = trim( (string) $post->post_excerpt );

		if ( '' !== $excerpt ) {
			return $excerpt;
		}

		return $this->get_listing_meta_value( $listing_id, [ 'excerpt', '_excerpt' ] );
	}

	/**
	 * Get listing category labels.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<int,string>
	 */
	public function get_listing_category_labels( int $listing_id ): array {
		$terms = $this->get_listing_terms( $listing_id, $this->get_category_taxonomy() );

		return array_values(
			array_filter(
				array_map(
					static fn( \WP_Term $term ): string => (string) $term->name,
					$terms
				)
			)
		);
	}

	/**
	 * Get listing tag labels.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<int,string>
	 */
	public function get_listing_tag_labels( int $listing_id ): array {
		$terms = $this->get_listing_terms( $listing_id, $this->get_tag_taxonomy() );

		return array_values(
			array_filter(
				array_map(
					static fn( \WP_Term $term ): string => (string) $term->name,
					$terms
				)
			)
		);
	}

	/**
	 * Get a separator-joined tag label string.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $separator Label separator.
	 * @return string
	 */
	public function get_listing_tags_label( int $listing_id, string $separator = ', ' ): string {
		return implode( $separator, $this->get_listing_tag_labels( $listing_id ) );
	}

	/**
	 * Get a comma-separated category label string.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $separator Label separator.
	 * @return string
	 */
	public function get_listing_categories_label( int $listing_id, string $separator = ', ' ): string {
		return implode( $separator, $this->get_listing_category_labels( $listing_id ) );
	}

	/**
	 * Get the primary category label for a listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_primary_category_label( int $listing_id ): string {
		$term = $this->get_listing_primary_category( $listing_id );

		return $term instanceof \WP_Term ? (string) $term->name : '';
	}

	/**
	 * Get normalized listing price HTML.
	 *
	 * @param int $listing_id Listing id.
	 * @return string
	 */
	public function get_listing_price_html( int $listing_id ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		$price       = get_post_meta( $listing_id, '_price', true );
		$price_range = get_post_meta( $listing_id, '_price_range', true );

		if ( '' !== (string) $price_range && function_exists( 'atbdp_display_price_range' ) ) {
			$markup = atbdp_display_price_range( $price_range );

			return is_string( $markup ) ? trim( $markup ) : '';
		}

		if ( '' !== (string) $price && function_exists( 'atbdp_display_price' ) ) {
			$markup = atbdp_display_price( $price, false, '', '', '', false );

			return is_string( $markup ) ? trim( $markup ) : '';
		}

		return '';
	}

	/**
	 * Get normalized listing rating value.
	 *
	 * @param int $listing_id Listing id.
	 * @return float
	 */
	public function get_listing_rating_value( int $listing_id ): float {
		$summary = $this->get_listing_rating_summary( $listing_id );

		return $summary['rating'];
	}

	/**
	 * Get normalized listing review count.
	 *
	 * @param int $listing_id Listing id.
	 * @return int
	 */
	public function get_listing_review_count( int $listing_id ): int {
		$summary = $this->get_listing_rating_summary( $listing_id );

		return $summary['count'];
	}

	/**
	 * Resolve a rating from approved root reviews instead of cached post meta.
	 *
	 * Directorist's aggregate post meta can outlive imported or deleted reviews.
	 * Reading the underlying review rows keeps card output consistent with the
	 * review section while the request cache avoids duplicate widget queries.
	 *
	 * @param int $listing_id Listing id.
	 * @return array{rating:float,count:int}
	 */
	public function get_listing_rating_summary( int $listing_id ): array {
		if ( $listing_id <= 0 ) {
			return [
				'rating' => 0.0,
				'count'  => 0,
			];
		}

		if ( isset( $this->listing_rating_summary_cache[ $listing_id ] ) ) {
			return $this->listing_rating_summary_cache[ $listing_id ];
		}

		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Core table names are supplied by wpdb.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS review_count, COALESCE( AVG( review_rating ), 0 ) AS average_rating
				FROM (
					SELECT comments.comment_ID, MAX( CAST( commentmeta.meta_value AS DECIMAL(10,2) ) ) AS review_rating
					FROM {$wpdb->comments} AS comments
					INNER JOIN {$wpdb->commentmeta} AS commentmeta
						ON commentmeta.comment_id = comments.comment_ID
						AND commentmeta.meta_key = 'rating'
					WHERE comments.comment_post_ID = %d
						AND comments.comment_parent = 0
						AND comments.comment_approved = '1'
						AND comments.comment_type = 'review'
						AND CAST( commentmeta.meta_value AS DECIMAL(10,2) ) > 0
					GROUP BY comments.comment_ID
				) AS directorist_listing_reviews",
				$listing_id
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$summary = [
			'rating' => max( 0.0, min( 5.0, (float) ( $row['average_rating'] ?? 0 ) ) ),
			'count'  => absint( $row['review_count'] ?? 0 ),
		];

		$this->listing_rating_summary_cache[ $listing_id ] = $summary;

		return $summary;
	}

	/**
	 * Get formatted listing posted date.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $format Optional date format.
	 * @return string
	 */
	public function get_listing_posted_date( int $listing_id, string $format = '' ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		return get_the_date( '' !== $format ? $format : get_option( 'date_format' ), $listing_id ) ?: '';
	}

	/**
	 * Get listing views count.
	 *
	 * @param int $listing_id Listing id.
	 * @return int
	 */
	public function get_listing_views_count( int $listing_id ): int {
		if ( $listing_id <= 0 ) {
			return 0;
		}

		if ( function_exists( 'directorist_get_listing_views_count' ) ) {
			return absint( directorist_get_listing_views_count( $listing_id ) );
		}

		return absint( get_post_meta( $listing_id, '_atbdp_post_views_count', true ) );
	}

	/**
	 * Get the current listing author avatar URL.
	 *
	 * @param int $listing_id Listing id.
	 * @param int $size Avatar size.
	 * @return string
	 */
	public function get_listing_author_avatar_url( int $listing_id, int $size = 96 ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		$author_id = absint( get_post_field( 'post_author', $listing_id ) );

		if ( $author_id <= 0 ) {
			return '';
		}

		return (string) get_avatar_url(
			$author_id,
			[
				'size' => max( 24, $size ),
			]
		);
	}

	/**
	 * Determine whether a listing matches a badge.
	 *
	 * Directorist 8.9+ owns badge enabled state and rule evaluation. Older core
	 * versions retain the legacy eligibility behavior for built-in badges.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $badge_key Badge key.
	 * @return bool
	 */
	public function is_listing_badge_visible( int $listing_id, string $badge_key ): bool {
		$badge_key = sanitize_key( $badge_key );
		if ( $listing_id <= 0 || '' === $badge_key || ! class_exists( '\\Directorist\\Helper' ) ) {
			return false;
		}

		if ( method_exists( '\\Directorist\\Helper', 'display_badge' ) ) {
			return (bool) \Directorist\Helper::display_badge( $listing_id, $badge_key );
		}

		switch ( $badge_key ) {
			case 'featured':
				return method_exists( '\\Directorist\\Helper', 'is_featured' )
					? (bool) \Directorist\Helper::is_featured( $listing_id )
					: (bool) get_post_meta( $listing_id, '_featured', true );

			case 'popular':
				return method_exists( '\\Directorist\\Helper', 'is_popular' )
					? (bool) \Directorist\Helper::is_popular( $listing_id )
					: false;

			case 'new':
				return method_exists( '\\Directorist\\Helper', 'is_new' )
					? (bool) \Directorist\Helper::is_new( $listing_id )
					: false;
		}

		return false;
	}

	/**
	 * Get normalized custom badge definitions from Directorist 8.9+.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_custom_badge_definitions(): array {
		if (
			! class_exists( '\\Directorist\\Helper' ) ||
			! method_exists( '\\Directorist\\Helper', 'custom_badge_definitions' )
		) {
			return [];
		}

		$definitions = \Directorist\Helper::custom_badge_definitions();
		if ( ! is_array( $definitions ) ) {
			return [];
		}

		return array_filter(
			$definitions,
			static function ( $definition, $badge_key ): bool {
				return is_array( $definition ) && 1 === preg_match( '/^custom_badge_[a-z0-9_]+$/', sanitize_key( (string) $badge_key ) );
			},
			ARRAY_FILTER_USE_BOTH
		);
	}

	/**
	 * Get one custom badge definition.
	 *
	 * @param string $badge_key Custom badge key.
	 * @return array<string,mixed>
	 */
	public function get_custom_badge_definition( string $badge_key ): array {
		$badge_key = sanitize_key( $badge_key );
		if ( ! preg_match( '/^custom_badge_[a-z0-9_]+$/', $badge_key ) ) {
			return [];
		}

		$definitions = $this->get_custom_badge_definitions();

		return ! empty( $definitions[ $badge_key ] ) ? (array) $definitions[ $badge_key ] : [];
	}

	/**
	 * Render a Directorist badge-manager icon.
	 *
	 * @param array<string,mixed> $definition Badge definition.
	 * @return string
	 */
	public function get_badge_definition_icon_markup( array $definition ): string {
		if (
			empty( $definition['icon'] ) ||
			! class_exists( '\\Directorist\\Helper' ) ||
			! method_exists( '\\Directorist\\Helper', 'badge_icon_markup' )
		) {
			return '';
		}

		return (string) \Directorist\Helper::badge_icon_markup( (string) $definition['icon'], [] );
	}

	/**
	 * Determine whether a listing is featured.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	public function is_listing_featured( int $listing_id ): bool {
		return $this->is_listing_badge_visible( $listing_id, 'featured' );
	}

	/**
	 * Determine whether a listing is popular.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	public function is_listing_popular( int $listing_id ): bool {
		return $this->is_listing_badge_visible( $listing_id, 'popular' );
	}

	/**
	 * Determine whether a listing should display the "new" badge.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	public function is_listing_new( int $listing_id ): bool {
		return $this->is_listing_badge_visible( $listing_id, 'new' );
	}

	/**
	 * Determine whether the current user has favorited the listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return bool
	 */
	public function is_listing_favorited_by_current_user( int $listing_id ): bool {
		if ( $listing_id <= 0 || ! is_user_logged_in() || ! function_exists( 'directorist_get_user_favorites' ) ) {
			return false;
		}

		return in_array( $listing_id, directorist_get_user_favorites( get_current_user_id() ), true );
	}

	/**
	 * Get normalized badge labels for the listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<string,string>
	 */
	public function get_listing_badge_labels( int $listing_id ): array {
		$labels = [];

		if ( $this->is_listing_featured( $listing_id ) ) {
			$labels['featured'] = (string) get_directorist_option( 'featured_listing_title', __( 'Featured', 'directorist' ) );
		}

		if ( $this->is_listing_new( $listing_id ) ) {
			$labels['new'] = class_exists( '\\Directorist\\Helper' ) && method_exists( '\\Directorist\\Helper', 'new_badge_text' )
				? (string) \Directorist\Helper::new_badge_text()
				: (string) get_directorist_option( 'new_badge_text', __( 'New', 'directorist' ) );
		}

		if ( $this->is_listing_popular( $listing_id ) ) {
			$labels['popular'] = class_exists( '\\Directorist\\Helper' ) && method_exists( '\\Directorist\\Helper', 'popular_badge_text' )
				? (string) \Directorist\Helper::popular_badge_text()
				: (string) get_directorist_option( 'popular_badge_text', __( 'Popular', 'directorist' ) );
		}

		return $labels;
	}

	/**
	 * Get listing image url with Directorist fallbacks.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $size Image size.
	 * @return string
	 */
	public function get_listing_image_url( int $listing_id, string $size = 'full' ): string {
		if ( $listing_id <= 0 ) {
			return '';
		}

		$image_id = 0;

		if ( function_exists( 'directorist_get_listing_preview_image' ) ) {
			$preview_candidates = array_filter( (array) directorist_get_listing_preview_image( $listing_id ), 'is_numeric' );
			$image_id           = ! empty( $preview_candidates ) ? (int) reset( $preview_candidates ) : 0;
		}

		if ( $image_id <= 0 && function_exists( 'directorist_get_listing_gallery_images' ) ) {
			$gallery_candidates = array_filter( (array) directorist_get_listing_gallery_images( $listing_id ), 'is_numeric' );
			$image_id           = ! empty( $gallery_candidates ) ? (int) reset( $gallery_candidates ) : 0;
		}

		if ( $image_id <= 0 ) {
			$image_id = get_post_thumbnail_id( $listing_id );
		}

		if ( $image_id > 0 ) {
			$image_url = wp_get_attachment_image_url( $image_id, $size );

			if ( is_string( $image_url ) && '' !== $image_url ) {
				return $image_url;
			}
		}

		if ( class_exists( '\\Directorist\\Helper' ) ) {
			return (string) \Directorist\Helper::default_preview_image_src( $this->get_listing_directory_type_id( $listing_id ) );
		}

		return '';
	}

	/**
	 * Get gallery image ids for a listing.
	 *
	 * @param int $listing_id Listing id.
	 * @return array<int,int>
	 */
	public function get_listing_gallery_image_ids( int $listing_id ): array {
		if ( $listing_id <= 0 || ! function_exists( 'directorist_get_listing_gallery_images' ) ) {
			return [];
		}

		return array_values(
			array_filter(
				array_map( 'absint', (array) directorist_get_listing_gallery_images( $listing_id ) )
			)
		);
	}

	/**
	 * Build normalized slider slides for a listing.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $image_quality Requested image size.
	 * @param bool   $preview_image_first Whether preview image should lead.
	 * @return array<int,array{src:string,alt:string}>
	 */
	public function get_listing_slider_slides( int $listing_id, string $image_quality = 'default', bool $preview_image_first = true ): array {
		if ( $listing_id <= 0 ) {
			return [];
		}

		$preview_image_candidates = function_exists( 'directorist_get_listing_preview_image' )
			? array_filter( (array) directorist_get_listing_preview_image( $listing_id ), 'is_numeric' )
			: [];
		$preview_image_id         = ! empty( $preview_image_candidates ) ? (int) reset( $preview_image_candidates ) : 0;
		$gallery_image_ids        = $this->get_listing_gallery_image_ids( $listing_id );
		$image_ids                = [];

		if ( $preview_image_first && $preview_image_id > 0 ) {
			$image_ids[] = $preview_image_id;
		}

		$image_ids = array_merge( $image_ids, $gallery_image_ids );

		if ( empty( $image_ids ) && $preview_image_id > 0 ) {
			$image_ids[] = $preview_image_id;
		}

		$post_thumbnail_id = (int) get_post_thumbnail_id( $listing_id );

		if ( empty( $image_ids ) && $post_thumbnail_id > 0 ) {
			$image_ids[] = $post_thumbnail_id;
		}

		$image_ids = array_values(
			array_filter(
				array_unique( array_map( 'absint', $image_ids ) )
			)
		);

		$resolved_image_quality = 'default' === $image_quality
			? sanitize_key( (string) get_directorist_option( 'preview_image_quality', 'directorist_preview' ) )
			: sanitize_key( $image_quality );

		if ( '' === $resolved_image_quality ) {
			$resolved_image_quality = 'directorist_preview';
		}

		$slides = [];

		foreach ( $image_ids as $image_id ) {
			$image_src = '';

			if ( function_exists( 'atbdp_get_image_source' ) ) {
				$image_src = (string) atbdp_get_image_source( $image_id, $resolved_image_quality );
			}

			if ( '' === $image_src ) {
				$image_src = (string) wp_get_attachment_image_url( $image_id, $resolved_image_quality );
			}

			if ( '' === $image_src ) {
				$image_src = (string) wp_get_attachment_image_url( $image_id, 'full' );
			}

			if ( '' === $image_src ) {
				continue;
			}

			$image_alt = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );

			if ( '' === $image_alt ) {
				$image_alt = (string) get_the_title( $image_id );
			}

			if ( '' === $image_alt ) {
				$image_alt = $this->get_listing_title( $listing_id );
			}

			$slides[] = [
				'src' => esc_url_raw( $image_src ),
				'alt' => sanitize_text_field( $image_alt ),
			];
		}

		if ( empty( $slides ) ) {
			$default_image_src = $this->get_listing_image_url( $listing_id, $resolved_image_quality );

			if ( '' !== $default_image_src ) {
				$slides[] = [
					'src' => esc_url_raw( $default_image_src ),
					'alt' => sanitize_text_field( $this->get_listing_title( $listing_id ) ),
				];
			}
		}

		return $slides;
	}

	/**
	 * Get recent listing ids for editor preview rendering.
	 *
	 * @param int $limit Max number of listings.
	 * @param int $directory_type_id Optional directory filter.
	 * @return array<int,int>
	 */
	public function get_recent_listing_ids( int $limit = 20, int $directory_type_id = 0 ): array {
		$query_args = [
			'post_type'              => $this->get_listing_post_type(),
			'post_status'            => 'publish',
			'posts_per_page'         => max( 1, $limit ),
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'fields'                 => 'ids',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		if ( $directory_type_id > 0 ) {
			$query_args['tax_query'] = [
				[
					'taxonomy' => $this->get_directory_taxonomy(),
					'field'    => 'term_id',
					'terms'    => [ $directory_type_id ],
				],
			];
		}

		$query = new \WP_Query( $query_args );

		return array_values(
			array_filter(
				array_map( 'absint', (array) $query->posts )
			)
		);
	}

	/**
	 * Get recent listing options for editor preview selectors.
	 *
	 * @param int $limit Max number of listings.
	 * @param int $directory_type_id Optional directory filter.
	 * @return array<int,string>
	 */
	public function get_recent_listing_options( int $limit = 20, int $directory_type_id = 0 ): array {
		$options = [];

		foreach ( $this->get_recent_listing_ids( $limit, $directory_type_id ) as $listing_id ) {
			$options[ $listing_id ] = $this->get_listing_title( $listing_id );
		}

		return $options;
	}

	/**
	 * Get raw custom field meta value with Directorist-style fallback keys.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $field_key Custom field meta key.
	 * @return mixed
	 */
	public function get_listing_custom_field_raw_value( int $listing_id, string $field_key ) {
		if ( $listing_id <= 0 || '' === trim( $field_key ) ) {
			return '';
		}

		$field_key = trim( $field_key );
		$values    = [
			get_post_meta( $listing_id, '_' . $field_key, true ),
			get_post_meta( $listing_id, $field_key, true ),
		];

		foreach ( $values as $value ) {
			if ( $this->has_resolved_meta_value( $value ) ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * Format a custom field value for listing card output.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $field_data Field definition.
	 * @return string
	 */
	public function get_listing_custom_field_display_value( int $listing_id, array $field_data ): string {
		$raw_value    = $this->get_listing_custom_field_raw_value( $listing_id, (string) ( $field_data['field_key'] ?? '' ) );
		$widget_name  = sanitize_key( (string) ( $field_data['widget_name'] ?? '' ) );
		$string_value = is_array( $raw_value )
			? implode( ', ', array_filter( array_map( 'strval', $raw_value ) ) )
			: trim( (string) $raw_value );

		if ( '' === $string_value && ! is_array( $raw_value ) ) {
			return '';
		}

		switch ( $widget_name ) {
			case 'select':
			case 'radio':
				return $this->map_custom_field_option_labels( $field_data, $raw_value, false );

			case 'checkbox':
				return $this->map_custom_field_option_labels( $field_data, $raw_value, true );

			case 'date':
				if ( function_exists( 'directorist_format_date' ) ) {
					return (string) directorist_format_date( $string_value );
				}

				return $string_value;

			case 'time':
				if ( function_exists( 'directorist_format_time' ) ) {
					return (string) directorist_format_time( $string_value );
				}

				return $string_value;

			case 'number':
				$prepend = trim( (string) ( $field_data['prepend'] ?? '' ) );
				$append  = trim( (string) ( $field_data['append'] ?? '' ) );

				return trim( $prepend . ' ' . $string_value . ' ' . $append );

			default:
				return $string_value;
		}
	}

	/**
	 * Get normalized file items for a custom file field.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $field_data Field definition.
	 * @return array<int,array{url:string,label:string}>
	 */
	public function get_listing_custom_field_file_items( int $listing_id, array $field_data ): array {
		$raw_value = $this->get_listing_custom_field_raw_value( $listing_id, (string) ( $field_data['field_key'] ?? '' ) );
		$queue     = [ $raw_value ];
		$items     = [];

		while ( ! empty( $queue ) ) {
			$current = array_shift( $queue );

			if ( is_array( $current ) ) {
				foreach ( $current as $nested_value ) {
					$queue[] = $nested_value;
				}
				continue;
			}

			if ( ! is_scalar( $current ) ) {
				continue;
			}

			$current_string = trim( (string) $current );

			if ( '' === $current_string ) {
				continue;
			}

			$current_parts = preg_split( '/[\r\n,]+|\|\|\|/', $current_string );

			if ( empty( $current_parts ) ) {
				$current_parts = [ $current_string ];
			}

			foreach ( $current_parts as $current_part ) {
				$current_part = trim( (string) $current_part );

				if ( '' === $current_part ) {
					continue;
				}

				$url = '';

				if ( is_numeric( $current_part ) ) {
					$attachment_url = wp_get_attachment_url( (int) $current_part );
					$url            = is_string( $attachment_url ) ? $attachment_url : '';
				} elseif ( filter_var( $current_part, FILTER_VALIDATE_URL ) ) {
					$url = $current_part;
				}

				if ( '' === $url ) {
					continue;
				}

				$path  = wp_parse_url( $url, PHP_URL_PATH );
				$label = $path ? wp_basename( $path ) : '';

				if ( '' === $label ) {
					$label = __( 'Download file', 'directorist-elementor' );
				}

				$items[] = [
					'url'   => $url,
					'label' => $label,
				];
			}
		}

		return array_values(
			array_unique( $items, SORT_REGULAR )
		);
	}

	/**
	 * Get published FormGent forms for widget controls.
	 *
	 * @param int $limit Max number of forms.
	 * @return array<int,string>
	 */
	public function get_formgent_form_options( int $limit = 100 ): array {
		$options = [];

		$query = new \WP_Query(
			[
				'post_type'              => $this->get_formgent_post_type(),
				'post_status'            => [ 'publish', 'private' ],
				'posts_per_page'         => max( 1, $limit ),
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			]
		);

		foreach ( (array) $query->posts as $form_id ) {
			$form_id = absint( $form_id );

			if ( $form_id <= 0 ) {
				continue;
			}

			$title = trim( (string) get_the_title( $form_id ) );

			if ( '' === $title ) {
				$title = sprintf(
					/* translators: %d: Form post ID. */
					__( 'Form #%d', 'directorist-elementor' ),
					$form_id
				);
			}

			$options[ $form_id ] = $title;
		}

		return $options;
	}

	/**
	 * Resolve a single preview listing id for editor rendering.
	 *
	 * @param int $directory_type_id Optional directory filter.
	 * @return int
	 */
	public function get_default_preview_listing_id( int $directory_type_id = 0 ): int {
		$ids = $this->get_recent_listing_ids( 1, $directory_type_id );

		return ! empty( $ids[0] ) ? absint( $ids[0] ) : 0;
	}

	/**
	 * Check whether a preset widget is enabled for a listing directory type.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $widget_name Widget name.
	 * @return bool
	 */
	public function is_preset_widget_allowed( int $listing_id, string $widget_name ): bool {
		$widget_name = sanitize_key( $widget_name );

		if ( $listing_id <= 0 || '' === $widget_name ) {
			return false;
		}

		$directory_type_id = $this->get_listing_directory_type_id( $listing_id );

		if ( $directory_type_id <= 0 ) {
			return true;
		}

		$submission_form_fields = $this->get_submission_form_fields( $directory_type_id );
		$fields                 = is_array( $submission_form_fields['fields'] ?? null )
			? (array) $submission_form_fields['fields']
			: ( is_array( $submission_form_fields ) ? $submission_form_fields : [] );

		if ( empty( $fields ) ) {
			return true;
		}

		foreach ( $fields as $field_data ) {
			if ( ! is_array( $field_data ) ) {
				continue;
			}

			if ( 'preset' !== (string) ( $field_data['widget_group'] ?? '' ) ) {
				continue;
			}

			if ( $widget_name === sanitize_key( (string) ( $field_data['widget_name'] ?? '' ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether the listing directory exposes any preset widget with a prefix.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $prefix Widget prefix.
	 * @return bool
	 */
	public function has_preset_widget_prefix( int $listing_id, string $prefix ): bool {
		$prefix            = sanitize_key( $prefix );
		$directory_type_id = $this->get_listing_directory_type_id( $listing_id );

		if ( $listing_id <= 0 || '' === $prefix || $directory_type_id <= 0 ) {
			return false;
		}

		$submission_form_fields = $this->get_submission_form_fields( $directory_type_id );
		$fields                 = is_array( $submission_form_fields['fields'] ?? null )
			? (array) $submission_form_fields['fields']
			: ( is_array( $submission_form_fields ) ? $submission_form_fields : [] );

		foreach ( $fields as $field_data ) {
			if ( ! is_array( $field_data ) ) {
				continue;
			}

			if ( 'preset' !== (string) ( $field_data['widget_group'] ?? '' ) ) {
				continue;
			}

			$widget_name = sanitize_key( (string) ( $field_data['widget_name'] ?? '' ) );

			if ( 0 === strpos( $widget_name, $prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Resolve the directory-linking widget name for the current listing.
	 *
	 * @param int $listing_id Listing id.
	 * @param int $linked_directory_type_id Selected linked directory type id.
	 * @return string
	 */
	public function resolve_directory_linking_widget_name( int $listing_id, int $linked_directory_type_id = 0 ): string {
		$current_directory_type_id = $this->get_listing_directory_type_id( $listing_id );
		$has_linked_value_for_type = static function ( int $target_type_id ) use ( $listing_id ): bool {
			if ( $target_type_id <= 0 ) {
				return false;
			}

			$field_key = 'swbdp_dirlink_type-' . $target_type_id;
			$values    = get_post_meta( $listing_id, $field_key, false );

			if ( empty( $values ) ) {
				$values = get_post_meta( $listing_id, '_' . $field_key, false );
			}

			foreach ( (array) $values as $value ) {
				if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
					return true;
				}
			}

			return false;
		};

		if ( $linked_directory_type_id > 0 ) {
			if ( $linked_directory_type_id === $current_directory_type_id ) {
				return '';
			}

			return 'linking-' . $linked_directory_type_id;
		}

		$listing = $this->get_single_listing_instance( $listing_id );

		if ( $listing && ! empty( $listing->content_data ) && is_array( $listing->content_data ) ) {
			foreach ( $listing->content_data as $section_data ) {
				if ( ! is_array( $section_data ) ) {
					continue;
				}

				$widget_name = sanitize_key( (string) ( $section_data['widget_name'] ?? '' ) );

				if ( 0 === strpos( $widget_name, 'linking-' ) ) {
					return $widget_name;
				}
			}
		}

		foreach ( array_keys( $this->get_directory_options() ) as $type_id ) {
			$type_id = absint( $type_id );

			if ( $type_id <= 0 || $type_id === $current_directory_type_id ) {
				continue;
			}

			$widget_name = 'linking-' . $type_id;

			if ( $has_linked_value_for_type( $type_id ) ) {
				return $widget_name;
			}
		}

		foreach ( array_keys( $this->get_directory_options() ) as $type_id ) {
			$type_id = absint( $type_id );

			if ( $type_id <= 0 || $type_id === $current_directory_type_id ) {
				continue;
			}

			return 'linking-' . $type_id;
		}

		return '';
	}

	/**
	 * Render directory-linking extension content.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $attributes Render attributes.
	 * @return string
	 */
	public function render_directory_linking_content( int $listing_id, array $attributes = [] ): string {
		if ( $listing_id <= 0 || ! $this->has_preset_widget_prefix( $listing_id, 'linking-' ) ) {
			return '';
		}

		$selected_directory_type_id = absint( $attributes['linked_directory_type_id'] ?? 0 );
		$widget_name                = $this->resolve_directory_linking_widget_name( $listing_id, $selected_directory_type_id );

		if ( '' === $widget_name || ! $this->is_preset_widget_allowed( $listing_id, $widget_name ) ) {
			return '';
		}

		$type_id = absint( str_replace( 'linking-', '', $widget_name ) );

		if ( $type_id <= 0 ) {
			return '';
		}

		$field_key = 'swbdp_dirlink_type-' . $type_id;
		$field_data = [
			'widget_group'          => 'other_widgets',
			'widget_name'           => $widget_name,
			'field_key'             => $field_key,
			'posts_per_page'        => max( 1, absint( $attributes['posts_per_page'] ?? 8 ) ),
			'display_image'         => ! array_key_exists( 'display_image', $attributes ) || ! empty( $attributes['display_image'] ),
			'display_title'         => ! array_key_exists( 'display_title', $attributes ) || ! empty( $attributes['display_title'] ),
			'display_category'      => ! array_key_exists( 'display_category', $attributes ) || ! empty( $attributes['display_category'] ),
			'display_rating'        => ! array_key_exists( 'display_rating', $attributes ) || ! empty( $attributes['display_rating'] ),
			'display_see_post'      => ! array_key_exists( 'display_see_post', $attributes ) || ! empty( $attributes['display_see_post'] ),
			'display_navigation'    => ! array_key_exists( 'display_navigation', $attributes ) || ! empty( $attributes['display_navigation'] ),
			'linking_view_all_text' => ! empty( $attributes['linking_view_all_text'] )
				? sanitize_text_field( (string) $attributes['linking_view_all_text'] )
				: __( 'View all listings', 'directorist-directory-linking' ),
		];

		$stored_linked_values = get_post_meta( $listing_id, $field_key, false );

		if ( empty( $stored_linked_values ) ) {
			$stored_linked_values = get_post_meta( $listing_id, '_' . $field_key, false );
		}

		if ( ! empty( $stored_linked_values ) ) {
			$field_data['value'] = $stored_linked_values;
		}

		$linked_ids = [];

		foreach ( (array) $stored_linked_values as $stored_value ) {
			if ( ! is_scalar( $stored_value ) ) {
				continue;
			}

			$parts = array_filter( array_map( 'trim', explode( ',', (string) $stored_value ) ) );

			foreach ( $parts as $part ) {
				$linked_id = absint( $part );

				if ( $linked_id > 0 ) {
					$linked_ids[] = $linked_id;
				}
			}
		}

		$linked_ids = array_values( array_unique( $linked_ids ) );

		if (
			! empty( $linked_ids ) &&
			function_exists( 'swbdp_dirlink_load_template' ) &&
			defined( 'ATBDP_POST_TYPE' )
		) {
			$linking_posts = new \WP_Query(
				[
					'post_type'      => ATBDP_POST_TYPE,
					'post__in'       => $linked_ids,
					'posts_per_page' => max( 1, (int) $field_data['posts_per_page'] ),
				]
			);

			$linked_type_term   = get_term_by( 'id', $type_id, $this->get_directory_taxonomy() );
			$current_types      = wp_get_post_terms( $listing_id, $this->get_directory_taxonomy() );
			$current_type_name  = '';

			if ( ! is_wp_error( $current_types ) && ! empty( $current_types ) && isset( $current_types[0]->name ) ) {
				$current_type_name = (string) $current_types[0]->name;
			}

			ob_start();
			swbdp_dirlink_load_template(
				'single-listing',
				[
					'linking_posts'         => $linking_posts,
					'directory_type'        => $type_id,
					'type_name'             => ( $linked_type_term instanceof \WP_Term ) ? (string) $linked_type_term->slug : '',
					'post_type'             => $current_type_name,
					'display_image'         => ! empty( $field_data['display_image'] ),
					'display_title'         => ! empty( $field_data['display_title'] ),
					'display_category'      => ! empty( $field_data['display_category'] ),
					'display_rating'        => ! empty( $field_data['display_rating'] ),
					'display_see_post'      => ! empty( $field_data['display_see_post'] ),
					'linking_view_all_text' => (string) $field_data['linking_view_all_text'],
					'display_navigation'    => ! empty( $field_data['display_navigation'] ),
				]
			);
			$template_markup = trim( (string) ob_get_clean() );

			if ( '' !== $template_markup ) {
				return $template_markup;
			}
		}

		return $this->render_single_listing_field( $listing_id, $field_data );
	}

	/**
	 * Execute a callback within a listing post context.
	 *
	 * @template T
	 *
	 * @param int         $listing_id Listing id.
	 * @param callable():T $callback  Callback to execute.
	 * @return mixed
	 */
	public function with_listing_post_context( int $listing_id, callable $callback ) {
		$listing_post_type = $this->get_listing_post_type();
		$listing_post      = get_post( $listing_id );

		if ( ! ( $listing_post instanceof \WP_Post ) || $listing_post->post_type !== $listing_post_type ) {
			return $callback();
		}

		$previous_post = get_post();
		$did_switch    = ! ( $previous_post instanceof \WP_Post ) || (int) $previous_post->ID !== (int) $listing_post->ID;

		if ( $did_switch ) {
			$GLOBALS['post'] = $listing_post;
			setup_postdata( $listing_post );
		}

		try {
			return $callback();
		} finally {
			if ( ! $did_switch ) {
			} elseif ( $previous_post instanceof \WP_Post ) {
				$GLOBALS['post'] = $previous_post;
				setup_postdata( $previous_post );
			} else {
				wp_reset_postdata();
			}
		}
	}

	/**
	 * Ensure Directorist single-listing assets are loaded.
	 *
	 * @param string $template Optional template key for conditional scripts.
	 * @return void
	 */
	public function ensure_single_listing_assets( string $template = '' ): void {
		if ( ! class_exists( '\\Directorist\\Asset_Loader\\Asset_Loader' ) ) {
			return;
		}

		if ( method_exists( '\\Directorist\\Asset_Loader\\Asset_Loader', 'register_scripts' ) ) {
			\Directorist\Asset_Loader\Asset_Loader::register_scripts();
		}

		if ( method_exists( '\\Directorist\\Asset_Loader\\Asset_Loader', 'enqueue_styles' ) ) {
			\Directorist\Asset_Loader\Asset_Loader::enqueue_styles();
		}

		if ( '' !== $template && method_exists( '\\Directorist\\Asset_Loader\\Asset_Loader', 'load_template_scripts' ) ) {
			\Directorist\Asset_Loader\Asset_Loader::load_template_scripts( $template );
		}

		if ( method_exists( '\\Directorist\\Asset_Loader\\Asset_Loader', 'enqueue_single_listing_scripts' ) ) {
			\Directorist\Asset_Loader\Asset_Loader::enqueue_single_listing_scripts();
		}
	}

	/**
	 * Get a Directorist single listing model instance for a listing id.
	 *
	 * @param int $listing_id Listing id.
	 * @return \Directorist\Directorist_Single_Listing|null
	 */
	public function get_single_listing_instance( int $listing_id = 0 ) {
		if ( ! class_exists( '\\Directorist\\Directorist_Single_Listing' ) ) {
			return null;
		}

		$listing = \Directorist\Directorist_Single_Listing::instance( $listing_id );

		if ( ! ( $listing instanceof \Directorist\Directorist_Single_Listing ) ) {
			return null;
		}

		if ( $listing_id > 0 && (int) $listing->id !== $listing_id && method_exists( $listing, 'prepare_data' ) ) {
			$listing->id = $listing_id;
			$listing->prepare_data();
		}

		return $listing;
	}

	/**
	 * Get header widget configuration from the Directorist single listing model.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $key Widget key.
	 * @param string $group Header placeholder group.
	 * @param string $subgroup Header placeholder subgroup.
	 * @return array<string,mixed>
	 */
	public function get_single_listing_header_widget( int $listing_id, string $key, string $group, string $subgroup ): array {
		$listing = $this->get_single_listing_instance( $listing_id );

		if ( ! $listing || ! method_exists( $listing, 'listing_header' ) ) {
			return [];
		}

		$widget_data = $listing->listing_header( $key, $group, $subgroup );

		return is_array( $widget_data ) ? $widget_data : [];
	}

	/**
	 * Get section configuration from the Directorist single listing model.
	 *
	 * @param int    $listing_id Listing id.
	 * @param string $widget_name Section widget name.
	 * @return array<string,mixed>
	 */
	public function get_single_listing_section_data( int $listing_id, string $widget_name ): array {
		$listing = $this->get_single_listing_instance( $listing_id );

		if ( ! $listing || ! is_array( $listing->content_data ?? null ) ) {
			return [];
		}

		foreach ( $listing->content_data as $section_data ) {
			if ( ! is_array( $section_data ) ) {
				continue;
			}

			if ( $widget_name === sanitize_key( (string) ( $section_data['widget_name'] ?? '' ) ) ) {
				return $section_data;
			}
		}

		return [];
	}

	/**
	 * Resolve related listing ids for a single listing section configuration.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $section_data Related listings section config.
	 * @return array<int,int>
	 */
	public function get_related_listing_ids( int $listing_id, array $section_data = [] ): array {
		$listing = $this->get_single_listing_instance( $listing_id );

		if ( ! $listing || ! method_exists( $listing, 'get_related_listings' ) ) {
			return [];
		}

		$related_collection = $this->with_listing_post_context(
			$listing_id,
			static function () use ( $listing, $section_data ) {
				return $listing->get_related_listings( $section_data );
			}
		);

		if ( ! is_object( $related_collection ) || ! method_exists( $related_collection, 'post_ids' ) ) {
			return [];
		}

		$related_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) $related_collection->post_ids() )
				)
			)
		);

		return array_values(
			array_diff( $related_ids, [ $listing_id ] )
		);
	}

	/**
	 * Render a Directorist single section template.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $section_data Section config.
	 * @return string
	 */
	public function render_single_listing_section( int $listing_id, array $section_data ): string {
		$listing = $this->get_single_listing_instance( $listing_id );

		if ( ! $listing ) {
			return '';
		}

		return (string) $this->with_listing_post_context(
			$listing_id,
			static function () use ( $listing, $section_data ): string {
				ob_start();
				$listing->section_template( $section_data );

				return trim( (string) ob_get_clean() );
			}
		);
	}

	/**
	 * Render a Directorist single field template.
	 *
	 * @param int                 $listing_id Listing id.
	 * @param array<string,mixed> $field_data Field config.
	 * @return string
	 */
	public function render_single_listing_field( int $listing_id, array $field_data ): string {
		$listing = $this->get_single_listing_instance( $listing_id );

		if ( ! $listing ) {
			return '';
		}

		return (string) $this->with_listing_post_context(
			$listing_id,
			static function () use ( $listing, $field_data ): string {
				ob_start();
				$listing->field_template( $field_data );

				return trim( (string) ob_get_clean() );
			}
		);
	}

	/**
	 * Check whether a post meta lookup resolved a meaningful value.
	 *
	 * @param mixed $value Meta value.
	 * @return bool
	 */
	protected function has_resolved_meta_value( $value ): bool {
		if ( is_array( $value ) ) {
			return ! empty( $value );
		}

		if ( is_scalar( $value ) ) {
			return '' !== trim( (string) $value );
		}

		return false;
	}

	/**
	 * Normalize custom field options to the expected label/value shape.
	 *
	 * @param mixed $options Raw field options.
	 * @return array<int,array{option_label:string,option_value:string}>
	 */
	protected function normalize_custom_field_options( $options ): array {
		if ( ! is_array( $options ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $options as $option ) {
			if ( ! is_array( $option ) ) {
				continue;
			}

			$option_label = trim( (string) ( $option['option_label'] ?? '' ) );
			$option_value = trim( (string) ( $option['option_value'] ?? '' ) );

			if ( '' === $option_value ) {
				continue;
			}

			if ( '' === $option_label ) {
				$option_label = $option_value;
			}

			$normalized[] = [
				'option_label' => $option_label,
				'option_value' => $option_value,
			];
		}

		return $normalized;
	}

	/**
	 * Map raw custom field values to option labels for select/radio/checkbox.
	 *
	 * @param array<string,mixed> $field_data Field definition.
	 * @param mixed               $raw_value Raw stored value.
	 * @param bool                $multiple Whether multiple values are allowed.
	 * @return string
	 */
	protected function map_custom_field_option_labels( array $field_data, $raw_value, bool $multiple ): string {
		$options = $this->normalize_custom_field_options( $field_data['options'] ?? [] );

		if ( empty( $options ) ) {
			return is_array( $raw_value )
				? implode( ', ', array_filter( array_map( 'strval', $raw_value ) ) )
				: trim( (string) $raw_value );
		}

		$raw_values = is_array( $raw_value )
			? array_filter( array_map( 'strval', $raw_value ) )
			: array_filter( array_map( 'trim', preg_split( '/[\r\n,]+/', (string) $raw_value ) ?: [] ) );
		$labels     = [];

		foreach ( $options as $option ) {
			if ( in_array( $option['option_value'], $raw_values, true ) ) {
				$labels[] = $option['option_label'];

				if ( ! $multiple ) {
					break;
				}
			}
		}

		if ( empty( $labels ) ) {
			return is_array( $raw_value )
				? implode( ', ', array_filter( array_map( 'strval', $raw_value ) ) )
				: trim( (string) $raw_value );
		}

		return implode( ', ', $labels );
	}
}
