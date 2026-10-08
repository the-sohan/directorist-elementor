<?php
/**
 * Import session state container.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Templates;

class ImportSession {

	/**
	 * Session id.
	 *
	 * @var string
	 */
	protected string $id;

	/**
	 * Package id.
	 *
	 * @var string
	 */
	protected string $package_id;

	/**
	 * Package version.
	 *
	 * @var string
	 */
	protected string $package_version;

	/**
	 * Created object ids by object type.
	 *
	 * @var array<string,array<int>>
	 */
	protected array $created = [];

	/**
	 * Updated object ids by object type.
	 *
	 * @var array<string,array<int>>
	 */
	protected array $updated = [];

	/**
	 * Source UID to local id map.
	 *
	 * @var array<string,array<string,int>>
	 */
	protected array $id_map = [];

	/**
	 * Source URL to local URL map.
	 *
	 * @var array<string,string>
	 */
	protected array $url_map = [];

	/**
	 * Session log entries.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	protected array $log = [];

	/**
	 * Constructor.
	 *
	 * @param string $package_id Package id.
	 * @param string $package_version Package version.
	 */
	public function __construct( string $package_id, string $package_version ) {
		$this->id              = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'directorist-import-', true );
		$this->package_id      = $package_id;
		$this->package_version = $package_version;
	}

	/**
	 * Get session id.
	 *
	 * @return string
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * Get package id.
	 *
	 * @return string
	 */
	public function get_package_id(): string {
		return $this->package_id;
	}

	/**
	 * Get package version.
	 *
	 * @return string
	 */
	public function get_package_version(): string {
		return $this->package_version;
	}

	/**
	 * Record a created object.
	 *
	 * @param string $type Object type.
	 * @param int    $id Object id.
	 * @return void
	 */
	public function record_created( string $type, int $id ): void {
		$this->created[ $type ][] = $id;
	}

	/**
	 * Record an updated object.
	 *
	 * @param string $type Object type.
	 * @param int    $id Object id.
	 * @return void
	 */
	public function record_updated( string $type, int $id ): void {
		$this->updated[ $type ][] = $id;
	}

	/**
	 * Add an id mapping.
	 *
	 * @param string $scope Source scope, for example media or post.
	 * @param string $source_id Source id or UID.
	 * @param int    $local_id Local id.
	 * @return void
	 */
	public function map_id( string $scope, string $source_id, int $local_id ): void {
		$this->id_map[ $scope ][ $source_id ] = $local_id;
	}

	/**
	 * Get mapped id.
	 *
	 * @param string $scope Source scope.
	 * @param string $source_id Source id or UID.
	 * @return int|null
	 */
	public function get_mapped_id( string $scope, string $source_id ): ?int {
		return $this->id_map[ $scope ][ $source_id ] ?? null;
	}

	/**
	 * Get id map.
	 *
	 * @return array<string,array<string,int>>
	 */
	public function get_id_map(): array {
		return $this->id_map;
	}

	/**
	 * Get created objects.
	 *
	 * @return array<string,array<int>>
	 */
	public function get_created(): array {
		return $this->created;
	}

	/**
	 * Get updated objects.
	 *
	 * @return array<string,array<int>>
	 */
	public function get_updated(): array {
		return $this->updated;
	}

	/**
	 * Add a URL mapping.
	 *
	 * @param string $source_url Source URL.
	 * @param string $local_url Local URL.
	 * @return void
	 */
	public function map_url( string $source_url, string $local_url ): void {
		if ( '' === $source_url || '' === $local_url ) {
			return;
		}

		$this->url_map[ $source_url ] = $local_url;
	}

	/**
	 * Get URL map.
	 *
	 * @return array<string,string>
	 */
	public function get_url_map(): array {
		return $this->url_map;
	}

	/**
	 * Add a log entry.
	 *
	 * @param string              $level Log level.
	 * @param string              $message Log message.
	 * @param array<string,mixed> $context Optional context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = [] ): void {
		$this->log[] = [
			'time'    => current_time( 'mysql' ),
			'level'   => $level,
			'message' => $message,
			'context' => $context,
		];
	}

	/**
	 * Convert session to array.
	 *
	 * @return array<string,mixed>
	 */
	public function to_array(): array {
		return [
			'id'              => $this->id,
			'package_id'      => $this->package_id,
			'package_version' => $this->package_version,
			'created'         => $this->created,
			'updated'         => $this->updated,
			'id_map'          => $this->id_map,
			'url_map'         => $this->url_map,
			'log'             => $this->log,
			'created_at'      => current_time( 'mysql' ),
		];
	}

	public function apply_delta( array $data ): void {
		$this->id = (string) $data['id'];
		foreach ( [ 'created', 'updated' ] as $group ) {
			foreach ( (array) $data[ $group ] as $type => $ids ) {
				$this->{$group}[ $type ] = array_values( array_unique( array_merge( $this->{$group}[ $type ] ?? [], $ids ) ) );
			}
		}
		foreach ( (array) $data['id_map'] as $type => $map ) {
			$this->id_map[ $type ] = array_replace( $this->id_map[ $type ] ?? [], $map );
		}
		$this->url_map = array_replace( $this->url_map, (array) $data['url_map'] );
		$this->log = array_merge( $this->log, (array) $data['log'] );
	}

	public function delta_since( array $before ): array {
		$data = $this->to_array();
		foreach ( [ 'created', 'updated' ] as $group ) {
			foreach ( $data[ $group ] as $type => $ids ) {
				$data[ $group ][ $type ] = array_values( array_diff( $ids, $before[ $group ][ $type ] ?? [] ) );
			}
		}
		foreach ( $data['id_map'] as $type => $map ) {
			$data['id_map'][ $type ] = array_diff_assoc( $map, $before['id_map'][ $type ] ?? [] );
		}
		$data['url_map'] = array_diff_assoc( $data['url_map'], $before['url_map'] ?? [] );
		$data['log'] = array_slice( $data['log'], count( $before['log'] ?? [] ) );
		return $data;
	}
}
