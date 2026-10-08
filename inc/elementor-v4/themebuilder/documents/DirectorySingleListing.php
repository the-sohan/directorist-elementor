<?php
/**
 * Directory-scoped Directorist single listing Theme Builder document.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\ThemeBuilder\Documents;

abstract class DirectorySingleListing extends SingleListing {
	/**
	 * Per-class runtime config.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	protected static array $directory_configs = [];

	/**
	 * Register config for a generated directory document class.
	 *
	 * @param string              $class_name Generated class name.
	 * @param array<string,mixed> $config Directory config.
	 * @return void
	 */
	public static function register_directory_config( string $class_name, array $config ): void {
		self::$directory_configs[ ltrim( $class_name, '\\' ) ] = $config;
	}

	/**
	 * Resolve config for the current generated document class.
	 *
	 * @return array<string,mixed>
	 */
	protected static function get_directory_config(): array {
		return self::$directory_configs[ ltrim( static::class, '\\' ) ] ?? [];
	}

	/**
	 * Resolve document type.
	 *
	 * @return string
	 */
	public static function get_type() {
		return (string) ( static::get_directory_config()['type'] ?? '' );
	}

	/**
	 * Resolve document title.
	 *
	 * @return string
	 */
	public static function get_title() {
		return (string) ( static::get_directory_config()['title'] ?? parent::get_title() );
	}

	/**
	 * Resolve document plural title.
	 *
	 * @return string
	 */
	public static function get_plural_title() {
		return (string) ( static::get_directory_config()['plural_title'] ?? parent::get_plural_title() );
	}

	/**
	 * Resolve Theme Builder sub condition.
	 *
	 * This becomes:
	 * include/directorist_listing/directorist_listing_directory/{term_id}
	 *
	 * @return string
	 */
	public static function get_sub_type() {
		$directory_type_id = absint( static::get_directory_config()['directory_type_id'] ?? 0 );

		if ( $directory_type_id <= 0 ) {
			return '';
		}

		return 'directorist_listing_directory/' . $directory_type_id;
	}

	/**
	 * Register preview defaults scoped to this directory type.
	 *
	 * Overrides the parent's generic query to only pick listings
	 * from the directory type this template belongs to.
	 *
	 * @return void
	 */
	protected function register_controls() {
		parent::register_controls();

		$directory_type_id = absint( static::get_directory_config()['directory_type_id'] ?? 0 );

		if ( $directory_type_id <= 0 ) {
			return;
		}

		$bridge    = \DirectoristElementor\ElementorV4\Bridge\DirectoristBridge::get_instance();
		$post_type = $bridge->get_listing_post_type();

		$latest_posts = get_posts(
			[
				'posts_per_page' => 1,
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'meta_query'     => [
					[
						'key'     => '_directory_type',
						'value'   => (string) $directory_type_id,
						'compare' => '=',
					],
				],
			]
		);

		if ( ! empty( $latest_posts[0] ) ) {
			$this->update_control(
				'preview_id',
				[
					'default' => (int) $latest_posts[0]->ID,
				]
			);
		}
	}

	/**
	 * Directory-scoped single templates should not expose Site Editor instance
	 * controls because the directory condition is implicit in the document type.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_site_editor_config() {
		$config = parent::get_site_editor_config();
		$directory_config = static::get_directory_config();

		$config['show_instances'] = false;

		if ( ! empty( $directory_config['site_editor_thumbnail'] ) ) {
			$config['urls']['thumbnail'] = (string) $directory_config['site_editor_thumbnail'];
		}

		return $config;
	}

	/**
	 * Remote library category.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_remote_library_config() {
		$config = parent::get_remote_library_config();
		$config['category'] = 'single listing';

		return $config;
	}
}
