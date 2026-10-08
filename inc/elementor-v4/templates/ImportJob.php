<?php
/**
 * Durable checkpoints for one site-scoped website import.
 */
namespace DirectoristElementor\ElementorV4\Templates;

use WP_Error;

class ImportJobYield extends \RuntimeException {}

class ImportJobFailure extends \RuntimeException {
	public WP_Error $error;
	public function __construct( WP_Error $error ) {
		parent::__construct( $error->get_error_message() );
		$this->error = $error;
	}
}

class ImportJob {
	private const DOMAIN = 'directorist-elementor';
	private const PREFIX = 'directorist_elementor_import_job_';
	private array $data;
	private float $started;
	private int $work = 0;
	private bool $yielded = false;

	private function __construct( array $data ) {
		$this->data = $data;
		$this->started = microtime( true );
	}

	public static function register_hooks(): void {
		add_action( self::PREFIX . 'expire', [ self::class, 'expire' ], 10, 1 );
	}

	public static function create( array $payload ): self {
		$job = new self( [
			'id' => wp_generate_uuid4(),
			'user_id' => get_current_user_id(),
			'blog_id' => get_current_blog_id(),
			'created' => time(),
			'status' => 'running',
			'payload' => $payload,
			'phases' => [],
			'stage' => 'prepare',
		] );
		$job->save();
		wp_schedule_single_event( time() + DAY_IN_SECONDS, self::PREFIX . 'expire', [ $job->data['id'] ] );
		return $job;
	}

	public static function load( string $id ) {
		if ( ! preg_match( '/^[a-f0-9-]{36}$/', $id ) ) {
			return new WP_Error( 'import_job_missing', 'Import session not found.', [ 'status' => 404 ] );
		}
		$data = get_option( self::PREFIX . $id );
		if ( ! is_array( $data ) || (int) ( $data['blog_id'] ?? 0 ) !== get_current_blog_id() || (int) ( $data['user_id'] ?? 0 ) !== get_current_user_id() ) {
			return new WP_Error( 'import_job_missing', 'Import session not found.', [ 'status' => 404 ] );
		}
		if ( time() - (int) $data['created'] >= DAY_IN_SECONDS ) {
			self::expire( $id );
			return new WP_Error( 'import_job_expired', __( 'This import session has expired. Start a new import.', self::DOMAIN ), [ 'status' => 404 ] );
		}
		return new self( $data );
	}

	public function payload(): array { return $this->data['payload']; }
	public function did_yield(): bool { return $this->yielded; }
	public function has_phase( string $key ): bool { return array_key_exists( $key, $this->data['phases'] ); }
	public function is_busy(): bool {
		$lock = self::PREFIX . $this->data['id'] . '_lock';
		$started = get_option( $lock );
		if ( false === $started ) {
			return false;
		}
		if ( time() - (int) $started > 900 ) {
			$this->data['status'] = 'failed';
			$this->data['error'] = __( 'The previous import step did not finish. The session has been retained for diagnosis; uncheckpointed work will not be run again automatically.', self::DOMAIN );
			$this->save();
			delete_option( $lock );
			return false;
		}
		return true;
	}

	public function phase( string $key, callable $callback, bool $isolated = false ) {
		if ( array_key_exists( $key, $this->data['phases'] ) ) {
			return $this->data['phases'][ $key ];
		}
		$this->data['stage'] = $key;
		$this->save();
		if ( $isolated && $this->work > 0 ) {
			$this->yielded = true;
			throw new ImportJobYield();
		}
		$result = $callback();
		if ( is_wp_error( $result ) ) {
			throw new ImportJobFailure( $result );
		}
		$this->data['phases'][ $key ] = $result;
		$this->save();
		$this->checkpoint();
		return $result;
	}

	private function checkpoint(): void {
		$this->work++;
		if ( $this->work >= 20 || microtime( true ) - $this->started >= 8 ) {
			$this->yielded = true;
			throw new ImportJobYield();
		}
	}

	public function response(): array {
		$stage = $this->data['stage'];
		$label = str_starts_with( $stage, 'post-' ) || str_starts_with( $stage, 'kit-post-' )
			? __( 'Importing pages and listings', self::DOMAIN )
			: ( str_starts_with( $stage, 'block-' ) || str_starts_with( $stage, 'document-' ) || str_starts_with( $stage, 'references-' )
				? __( 'Applying template designs and references', self::DOMAIN )
				: __( 'Processing the website package', self::DOMAIN ) );
		return $this->data['result'] ?? [
			'status' => $this->data['status'],
			'message' => 'failed' === $this->data['status'] ? $this->data['error'] : $label,
			'job' => [ 'id' => $this->data['id'], 'stage' => $this->data['stage'], 'completed_steps' => count( $this->data['phases'] ) ],
		];
	}

	public function execute( callable $callback ) {
		$busy = $this->is_busy();
		if ( isset( $this->data['result'] ) ) {
			return $this->data['result'];
		}
		if ( 'failed' === $this->data['status'] ) {
			return new WP_Error( 'import_job_failed', $this->data['error'], [ 'status' => 500, 'job_id' => $this->data['id'] ] );
		}
		$lock = self::PREFIX . $this->data['id'] . '_lock';
		if ( $busy || ! add_option( $lock, time(), '', false ) ) {
			return new WP_Error( 'import_job_busy', 'This import step is already running. The session has been retained.', [ 'status' => 409, 'job_id' => $this->data['id'] ] );
		}
		$finished = false;
		register_shutdown_function( function () use ( &$finished, $lock ): void {
			$error = error_get_last();
			if ( ! $finished && $error && in_array( $error['type'], [ E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR ], true ) ) {
				$this->data['status'] = 'failed';
				$this->data['error'] = sprintf( 'Import stopped during %s: %s. The session has been retained.', $this->data['stage'], $error['message'] );
				$this->save();
				delete_option( $lock );
			}
		} );
		try {
			$fresh = self::load( $this->data['id'] );
			if ( is_wp_error( $fresh ) ) {
				return $fresh;
			}
			$this->data = $fresh->data;
			if ( isset( $this->data['result'] ) ) {
				return $this->data['result'];
			}
			if ( 'failed' === $this->data['status'] ) {
				return new WP_Error( 'import_job_failed', $this->data['error'], [ 'status' => 500, 'job_id' => $this->data['id'] ] );
			}
			$this->started = microtime( true );
			$this->work = 0;
			$this->yielded = false;
			$result = $callback();
			if ( is_wp_error( $result ) ) {
				throw new ImportJobFailure( $result );
			}
			$this->data['result'] = $result;
			$this->data['status'] = 'complete';
			$this->save();
			return $result;
		} catch ( ImportJobYield $yield ) {
			return $this->response();
		} catch ( \Throwable $error ) {
			$this->data['status'] = 'failed';
			$this->data['error'] = $error->getMessage();
			$this->save();
			$result = $error instanceof ImportJobFailure ? $error->error : new WP_Error( 'import_job_failed', $error->getMessage(), [ 'status' => 500 ] );
			$details = (array) $result->get_error_data();
			$result->add_data( $details + [ 'status' => 500, 'job_id' => $this->data['id'], 'stage' => $this->data['stage'] ] );
			return $result;
		} finally {
			$finished = true;
			delete_option( $lock );
		}
	}

	private function save(): void {
		update_option( self::PREFIX . $this->data['id'], $this->data, false );
	}

	public static function expire( string $id ): void {
		wp_clear_scheduled_hook( self::PREFIX . 'expire', [ $id ] );
		$data = get_option( self::PREFIX . $id );
		if ( ! is_array( $data ) ) {
			return;
		}
		$paths = [ $data['payload']['file'] ?? '' ];
		foreach ( [ 'download', 'extract', 'native-session' ] as $phase ) {
			$value = $data['phases'][ $phase ] ?? '';
			if ( is_array( $value ) && array_key_exists( 'value', $value ) ) {
				$value = $value['value'];
			}
			$paths[] = is_array( $value ) ? ( $value['file'] ?? $value['directory'] ?? '' ) : $value;
		}
		foreach ( array_unique( array_filter( $paths, 'is_string' ) ) as $path ) {
			if ( '' === $path ) {
				continue;
			}
			if ( is_file( $path ) || is_link( $path ) ) {
				wp_delete_file( $path );
			} elseif ( is_dir( $path ) ) {
				$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $path, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST );
				foreach ( $files as $file ) {
					if ( $file->isDir() && ! $file->isLink() ) {
						@rmdir( $file->getPathname() );
					} else {
						wp_delete_file( $file->getPathname() );
					}
				}
				@rmdir( $path );
			}
		}
		delete_option( self::PREFIX . $id . '_lock' );
		delete_option( self::PREFIX . $id );
	}
}
