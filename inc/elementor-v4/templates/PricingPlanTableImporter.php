<?php
/**
 * Import the portable pricing schema on stock Pricing Plans v4 releases.
 */
namespace DirectoristElementor\ElementorV4\Templates;

use WP_Error;

defined( 'ABSPATH' ) || exit;

class PricingPlanTableImporter {
	private const PRICING_COMPONENT_SCHEMA = 'directorist-pricing-plans-v1';
	private const PRICING_IMPORT_MAP_OPTION = 'directorist_pricing_plans_template_import_map';

	/** @var array<int,string> */
	private const PRICING_PLAN_COLUMNS = [
		'title',
		'description',
		'directory_type_id',
		'allowed_listings',
		'is_allowed_unlimited_listings',
		'allowed_featured_listings',
		'is_allowed_unlimited_featured_listings',
		'fee_type',
		'price',
		'is_taxable',
		'tax_type',
		'tax_rate',
		'interval_type',
		'interval_count',
		'is_subscription_enabled',
		'is_trial_enabled',
		'trial_interval_type',
		'trial_interval_count',
		'sort_order',
		'is_published',
		'is_hidden_from_plans_list',
		'is_marked_as_recommended',
		'is_fallback',
		'listing_display_priority',
		'type',
		'is_featured',
	];

	/** @return array|WP_Error */
	public function import( array $pricing, array $args ) {
		global $wpdb;

		if ( self::PRICING_COMPONENT_SCHEMA !== (string) ( $pricing['schema_version'] ?? '' ) ) {
			return new WP_Error( 'directorist_pricing_schema_invalid', __( 'The pricing plans component uses an unsupported schema.', 'directorist-elementor' ) );
		}

		$tables = [
			'plans'          => $wpdb->prefix . 'directorist_plans',
			'features'       => $wpdb->prefix . 'directorist_plan_features',
			'configurations' => $wpdb->prefix . 'directorist_plan_app_configurations',
		];

		foreach ( $tables as $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );

			if ( $table !== $exists ) {
				return new WP_Error( 'directorist_pricing_unavailable', __( 'Pricing plans were not imported because Directorist Pricing Plans is unavailable.', 'directorist-elementor' ) );
			}
		}

		$package_id = sanitize_text_field( (string) ( $args['package_id'] ?? '' ) );
		$conflict   = in_array( (string) ( $args['conflict_behavior'] ?? 'update' ), [ 'update', 'skip', 'duplicate', 'replace' ], true )
			? (string) $args['conflict_behavior']
			: 'update';
		$term_map = isset( $args['term_id_map'] ) && is_array( $args['term_id_map'] ) ? $args['term_id_map'] : [];
		$directory_map = isset( $term_map['atbdp_listing_types'] ) && is_array( $term_map['atbdp_listing_types'] )
			? $term_map['atbdp_listing_types']
			: [];
		$stored_maps = get_option( self::PRICING_IMPORT_MAP_OPTION, [] );
		$stored_maps = is_array( $stored_maps ) ? $stored_maps : [];
		$package_map = isset( $stored_maps[ $package_id ] ) && is_array( $stored_maps[ $package_id ] )
			? $stored_maps[ $package_id ]
			: [];
		$output = [
			'imported'    => 0,
			'updated'     => 0,
			'replaced'    => 0,
			'skipped'     => 0,
			'plan_id_map' => [],
			'warnings'    => [],
		];
		$fallbacks = [];

		if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
			return new WP_Error( 'directorist_pricing_transaction_failed', __( 'The pricing plan import transaction could not be started.', 'directorist-elementor' ) );
		}

		foreach ( (array) ( $pricing['plans'] ?? [] ) as $plan ) {
			if ( ! is_array( $plan ) ) {
				continue;
			}

			$source_id = absint( $plan['source_id'] ?? 0 );
			$source_directory_id = absint( $plan['directory_type_id'] ?? 0 );
			$directory_id = absint( $directory_map[ $source_directory_id ] ?? 0 );

			if ( $source_id <= 0 || $directory_id <= 0 ) {
				$output['warnings'][] = sprintf(
					/* translators: %s: pricing plan title. */
					__( 'Pricing plan "%s" was skipped because its directory type could not be mapped.', 'directorist-elementor' ),
					sanitize_text_field( (string) ( $plan['title'] ?? $source_id ) )
				);
				++$output['skipped'];
				continue;
			}

			$existing_id = 'duplicate' === $conflict ? 0 : absint( $package_map[ $source_id ] ?? 0 );

			if ( $existing_id > 0 ) {
				$existing_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$tables['plans']} WHERE id = %d", $existing_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			}

			if ( $existing_id > 0 && 'skip' === $conflict ) {
				$output['plan_id_map'][ $source_id ] = $existing_id;
				++$output['skipped'];
				continue;
			}

			$was_replaced = $existing_id > 0 && 'replace' === $conflict;

			if ( $existing_id > 0 && 'replace' === $conflict ) {
				if ( ! $this->delete_pricing_plan_children( $existing_id, $tables ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'directorist_pricing_children_failed', $wpdb->last_error ?: __( 'Pricing plan features could not be replaced.', 'directorist-elementor' ) );
				}

				if ( false === $wpdb->delete( $tables['plans'], [ 'id' => $existing_id ], [ '%d' ] ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'directorist_pricing_replace_failed', $wpdb->last_error ?: __( 'A pricing plan could not be replaced.', 'directorist-elementor' ) );
				}

				$existing_id = 0;
			}

			$row = $this->normalize_pricing_plan_row( $plan, $directory_id );
			$target_id = $existing_id;

			if ( $target_id > 0 ) {
				if ( false === $wpdb->update( $tables['plans'], $row, [ 'id' => $target_id ] ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'directorist_pricing_update_failed', $wpdb->last_error ?: __( 'A pricing plan could not be updated.', 'directorist-elementor' ) );
				}

				if ( ! $this->delete_pricing_plan_children( $target_id, $tables ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'directorist_pricing_children_failed', $wpdb->last_error ?: __( 'Pricing plan features could not be updated.', 'directorist-elementor' ) );
				}
				++$output['updated'];
			} else {
				if ( false === $wpdb->insert( $tables['plans'], $row ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error( 'directorist_pricing_insert_failed', $wpdb->last_error ?: __( 'A pricing plan could not be created.', 'directorist-elementor' ) );
				}

				$target_id = absint( $wpdb->insert_id );
				$was_replaced ? ++$output['replaced'] : ++$output['imported'];
			}

			if ( ! $this->insert_pricing_plan_children( $target_id, $plan, $tables ) ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'directorist_pricing_children_failed', $wpdb->last_error ?: __( 'Pricing plan features could not be restored.', 'directorist-elementor' ) );
			}

			$output['plan_id_map'][ $source_id ] = $target_id;
			$fallbacks[ $target_id ] = absint( $plan['fallback_source_plan_id'] ?? 0 );

			if ( 'duplicate' !== $conflict ) {
				$package_map[ $source_id ] = $target_id;
			}
		}

		foreach ( $fallbacks as $target_id => $fallback_source_id ) {
			$fallback_id = absint( $output['plan_id_map'][ $fallback_source_id ] ?? 0 );

			if ( false === $wpdb->update( $tables['plans'], [ 'fallback_plan_id' => $fallback_id > 0 ? $fallback_id : null ], [ 'id' => $target_id ] ) ) {
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error( 'directorist_pricing_fallback_failed', $wpdb->last_error ?: __( 'Pricing plan relationships could not be restored.', 'directorist-elementor' ) );
			}
		}

		if ( false === $wpdb->query( 'COMMIT' ) ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'directorist_pricing_commit_failed', $wpdb->last_error ?: __( 'Pricing plans could not be saved.', 'directorist-elementor' ) );
		}

		if ( 'duplicate' !== $conflict && '' !== $package_id ) {
			$stored_maps[ $package_id ] = $package_map;
			update_option( self::PRICING_IMPORT_MAP_OPTION, $stored_maps, false );
		}

		return $output;
	}

	/**
	 * Normalize one portable plan for the Pricing Plans v4 tables.
	 *
	 * @param array<string,mixed> $plan Plan payload.
	 * @param int                 $directory_id Local directory type ID.
	 * @return array<string,mixed>
	 */
	protected function normalize_pricing_plan_row( array $plan, int $directory_id ): array {
		$integer_columns = [
			'allowed_listings',
			'is_allowed_unlimited_listings',
			'allowed_featured_listings',
			'is_allowed_unlimited_featured_listings',
			'is_taxable',
			'interval_count',
			'is_subscription_enabled',
			'is_trial_enabled',
			'trial_interval_count',
			'sort_order',
			'is_published',
			'is_hidden_from_plans_list',
			'is_marked_as_recommended',
			'is_fallback',
			'listing_display_priority',
			'is_featured',
		];
		$row = [];

		foreach ( self::PRICING_PLAN_COLUMNS as $column ) {
			if ( 'directory_type_id' === $column ) {
				$row[ $column ] = $directory_id;
			} elseif ( in_array( $column, $integer_columns, true ) ) {
				$row[ $column ] = (int) ( $plan[ $column ] ?? 0 );
			} elseif ( in_array( $column, [ 'price', 'tax_rate' ], true ) ) {
				$row[ $column ] = number_format( (float) ( $plan[ $column ] ?? 0 ), 2, '.', '' );
			} elseif ( 'description' === $column ) {
				$row[ $column ] = sanitize_textarea_field( (string) ( $plan[ $column ] ?? '' ) );
			} else {
				$row[ $column ] = sanitize_text_field( (string) ( $plan[ $column ] ?? '' ) );
			}
		}

		$row['fee_type']           = in_array( $row['fee_type'], [ 'free', 'paid' ], true ) ? $row['fee_type'] : 'free';
		$row['tax_type']           = in_array( $row['tax_type'], [ 'flat', 'percent' ], true ) ? $row['tax_type'] : 'flat';
		$row['interval_type']      = in_array( $row['interval_type'], [ 'day', 'week', 'month', 'year', 'lifetime' ], true ) ? $row['interval_type'] : 'month';
		$row['trial_interval_type'] = in_array( $row['trial_interval_type'], [ 'day', 'week', 'month', 'year' ], true ) ? $row['trial_interval_type'] : 'month';
		$row['type']               = in_array( $row['type'], [ 'package', 'pay_per_listing' ], true ) ? $row['type'] : 'package';

		return $row;
	}

	/**
	 * Restore pricing plan features and application configurations.
	 *
	 * @param int                 $plan_id Local plan ID.
	 * @param array<string,mixed> $plan Plan payload.
	 * @param array<string,string> $tables Pricing table names.
	 * @return bool
	 */
	protected function insert_pricing_plan_children( int $plan_id, array $plan, array $tables ): bool {
		global $wpdb;

		foreach ( (array) ( $plan['features'] ?? [] ) as $feature ) {
			if ( ! is_array( $feature ) || ! is_string( $feature['feature_key'] ?? null ) || '' === trim( $feature['feature_key'] ) ) {
				continue;
			}

			$data = $feature['data'] ?? null;
			$data = null === $data ? null : wp_json_encode( $data );

			if ( false === $wpdb->insert(
				$tables['features'],
				[
					'plan_id'                  => $plan_id,
					'feature_key'              => sanitize_text_field( $feature['feature_key'] ),
					'is_enabled'               => (int) ( $feature['is_enabled'] ?? 0 ),
					'is_show_in_pricing_table' => (int) ( $feature['is_show_in_pricing_table'] ?? 0 ),
					'data'                     => $data,
					'sort_order'               => (int) ( $feature['sort_order'] ?? 0 ),
				]
			) ) {
				return false;
			}
		}

		foreach ( (array) ( $plan['app_configurations'] ?? [] ) as $configuration ) {
			if ( ! is_array( $configuration ) || '' === sanitize_key( (string) ( $configuration['type'] ?? '' ) ) ) {
				continue;
			}

			if ( false === $wpdb->insert(
				$tables['configurations'],
				[
					'plan_id'       => $plan_id,
					'type'          => sanitize_key( (string) $configuration['type'] ),
					'product_id'    => sanitize_text_field( (string) ( $configuration['product_id'] ?? '' ) ),
					'product_price' => sanitize_text_field( (string) ( $configuration['product_price'] ?? '' ) ),
				]
			) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Restore source access or provision non-recurring access for imported demos.
	 *
	 * @return array{status:string,listing_id:int,plan_id:int,package_id:int}|WP_Error
	 */
	public function ensure_listing_package( int $listing_id, array $listing, array $plan_id_map, string $package_id ) {
		global $wpdb;
		$user_id = (int) get_post_field( 'post_author', $listing_id );
		$directory_id = absint( get_post_meta( $listing_id, '_directory_type', true ) );
		if ( 'at_biz_dir' !== get_post_type( $listing_id ) || ! $user_id || ! $directory_id || '' === $package_id ) {
			return new WP_Error( 'directorist_import_package_mapping', __( 'The imported listing has no valid owner or directory for plan assignment.', 'directorist-elementor' ) );
		}
		if ( ! function_exists( 'directorist_user_package_repository' ) ) {
			return new WP_Error( 'directorist_import_demo_plan_unavailable', __( 'Activate Pricing Plans to assign access to the imported demo listings.', 'directorist-elementor' ) );
		}
		$assignment = $listing['pricing_package'] ?? null;
		if ( is_array( $assignment ) ) {
			$source_package_id = absint( $assignment['source_id'] ?? 0 );
			$mapped_plan_id = absint( $plan_id_map[ absint( $assignment['plan_source_id'] ?? 0 ) ] ?? 0 );
			$maps = get_option( 'directorist_template_listing_packages', [] );
			$maps = is_array( $maps ) ? $maps : [];
			$current_key = $source_package_id . ':' . $user_id . ':' . $mapped_plan_id;
			foreach ( (array) ( $maps[ $package_id ] ?? [] ) as $mapped_key => $mapped_package_id ) {
				$identity = 0 === strpos( (string) $mapped_key, 'demo:' ) ? substr( (string) $mapped_key, 5 ) : (string) $mapped_key;
				$parts = explode( ':', $identity );
				if ( absint( $parts[0] ?? 0 ) !== $source_package_id || $identity === $current_key ) { continue; }
				$obsolete_id = absint( $mapped_package_id );
				$obsolete = $obsolete_id ? $wpdb->get_row( $wpdb->prepare( "SELECT last_order_id,is_recurring,subscription_id FROM {$wpdb->prefix}directorist_user_packages WHERE id=%d", $obsolete_id ) ) : null;
				if ( $obsolete && empty( $obsolete->last_order_id ) && empty( $obsolete->is_recurring ) && empty( $obsolete->subscription_id ) ) {
					$wpdb->delete( $wpdb->prefix . 'directorist_user_packages', [ 'id' => $obsolete_id ], [ '%d' ] );
					unset( $maps[ $package_id ][ $mapped_key ] );
				}
			}
			update_option( 'directorist_template_listing_packages', $maps, false );
		}
		$result = [ 'status' => 'preserved', 'listing_id' => $listing_id, 'plan_id' => 0, 'package_id' => 0 ];
		$local_plan_id = absint( get_post_meta( $listing_id, directorist_plan_key(), true ) );
		if ( $local_plan_id ) {
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT p.id, p.plan_id, p.status, p.last_order_id, p.is_recurring, p.subscription_id FROM {$wpdb->prefix}directorist_user_packages p INNER JOIN {$wpdb->prefix}directorist_plans plan ON plan.id = p.plan_id AND plan.directory_type_id = p.directory_type_id WHERE p.user_id = %d AND p.directory_type_id = %d AND p.plan_id = %d ORDER BY (p.status IN ('active', 'canceled_at_period_end')) DESC, p.id DESC LIMIT 1",
				$user_id, $directory_id, $local_plan_id
			) );
			if ( $existing && in_array( $existing->status, [ 'active', 'canceled_at_period_end' ], true ) ) {
				return array_merge( $result, [ 'plan_id' => $local_plan_id, 'package_id' => (int) $existing->id ] );
			}
			if ( $existing && ( ! empty( $existing->last_order_id ) || ! empty( $existing->subscription_id ) || ! empty( $existing->is_recurring ) ) ) {
				return new WP_Error( 'directorist_import_demo_plan_unavailable', __( 'The existing purchased listing package is inactive and was preserved. Demo import did not reactivate it.', 'directorist-elementor' ) );
			}
		}
		if ( null !== $assignment && ! is_array( $assignment ) ) {
			return new WP_Error( 'directorist_import_package_mapping', __( 'The source listing pricing assignment is malformed.', 'directorist-elementor' ) );
		}
		if ( is_array( $assignment ) ) {
			$restored = $this->restore_listing_package( $listing_id, $assignment, $plan_id_map, $package_id );
			return is_wp_error( $restored ) ? $restored : array_merge( $result, [
				'status' => 'restored', 'plan_id' => absint( $plan_id_map[ absint( $assignment['plan_source_id'] ?? 0 ) ] ?? 0 ), 'package_id' => $restored,
			] );
		}
		if ( $user_id !== get_current_user_id() ) {
			return new WP_Error( 'directorist_import_demo_plan_unavailable', __( 'Automatic demo access was skipped because the imported listing is not owned by the importing user.', 'directorist-elementor' ) );
		}
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $plan_id_map ) ) ) );
		if ( empty( $ids ) ) {
			return new WP_Error( 'directorist_import_demo_plan_unavailable', __( 'No imported pricing plan is available for the demo listings.', 'directorist-elementor' ) );
		}
		$plans = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, title, type, allowed_listings, is_allowed_unlimited_listings, allowed_featured_listings, is_allowed_unlimited_featured_listings, listing_display_priority FROM {$wpdb->prefix}directorist_plans WHERE directory_type_id = %d AND is_published = 1 AND id IN (" . implode( ',', array_fill( 0, count( $ids ), '%d' ) ) . ") AND (type = 'pay_per_listing' OR allowed_listings > 0 OR is_allowed_unlimited_listings = 1 OR allowed_featured_listings > 0 OR is_allowed_unlimited_featured_listings = 1)",
			array_merge( [ $directory_id ], $ids )
		), ARRAY_A );
		if ( empty( $plans ) ) {
			return new WP_Error( 'directorist_import_demo_plan_unavailable', __( 'No eligible published plan from the imported directory can be assigned to the demo listings.', 'directorist-elementor' ) );
		}
		$source_by_target = array_flip( $plan_id_map );
		$source_plan_values = (array) ( $listing['meta']['_plan_id'] ?? [] );
		$preferred = absint( $plan_id_map[ absint( reset( $source_plan_values ) ) ] ?? 0 );
		$score = static fn( $plan ) => [
			(int) ( (int) $plan['id'] === $preferred ),
			(int) ( 'package' === $plan['type'] ),
			(int) $plan['is_allowed_unlimited_listings'], (int) $plan['allowed_listings'],
			(int) $plan['is_allowed_unlimited_featured_listings'], (int) $plan['allowed_featured_listings'],
			-absint( $source_by_target[ $plan['id'] ] ),
		];
		usort( $plans, static fn( $a, $b ) => $score( $b ) <=> $score( $a ) );
		$plan = $plans[0];
		$source_plan_id = absint( $source_by_target[ $plan['id'] ] );
		$assigned = $this->restore_listing_package( $listing_id, [
			'origin' => 'demo', 'source_id' => $source_plan_id, 'plan_source_id' => $source_plan_id,
			'status' => 'active', 'listing_display_priority' => (int) $plan['listing_display_priority'],
		], $plan_id_map, $package_id );
		return is_wp_error( $assigned ) ? $assigned : array_merge( $result, [ 'status' => 'assigned', 'plan_id' => (int) $plan['id'], 'package_id' => $assigned ] );
	}

	/**
	 * Restore an exported access assignment without importing billing history.
	 *
	 * @return int|WP_Error
	 */
	public function restore_listing_package( int $listing_id, array $assignment, array $plan_id_map, string $package_id ) {
		global $wpdb;
		$plan_id = absint( $plan_id_map[ absint( $assignment['plan_source_id'] ?? 0 ) ] ?? 0 );
		$user_id = (int) get_post_field( 'post_author', $listing_id );
		$directory_id = absint( get_post_meta( $listing_id, '_directory_type', true ) );
		$source_id = absint( $assignment['source_id'] ?? 0 );
		if ( ! $plan_id || ! $user_id || ! $directory_id || ! $source_id || '' === $package_id || 'at_biz_dir' !== get_post_type( $listing_id ) || ! in_array( $assignment['status'] ?? '', [ 'active', 'canceled_at_period_end' ], true ) ) {
			return new WP_Error( 'directorist_import_package_mapping', __( 'A listing pricing-package assignment could not be mapped.', 'directorist-elementor' ) );
		}
		if ( ! function_exists( 'directorist_user_package_repository' ) || ! class_exists( \DirectoristPricingPlan\App\DTO\UserPackage\DTO::class ) ) {
			return new WP_Error( 'directorist_import_package_api', __( 'Pricing Plans does not provide the package API required to restore listing access.', 'directorist-elementor' ) );
		}
		$plan_directory = (int) $wpdb->get_var( $wpdb->prepare( "SELECT directory_type_id FROM {$wpdb->prefix}directorist_plans WHERE id = %d", $plan_id ) );
		if ( $directory_id !== $plan_directory ) {
			return new WP_Error( 'directorist_import_package_directory', __( 'The imported pricing plan belongs to a different listing directory.', 'directorist-elementor' ) );
		}
		$option = 'directorist_template_listing_packages';
		$maps = get_option( $option, [] );
		$maps = is_array( $maps ) ? $maps : [];
		$key = $source_id . ':' . $user_id . ':' . $plan_id;
		if ( 'demo' === ( $assignment['origin'] ?? '' ) ) {
			$key = 'demo:' . $key;
		}
		foreach ( (array) ( $maps[ $package_id ] ?? [] ) as $mapped_key => $mapped_package_id ) {
			$identity = 0 === strpos( (string) $mapped_key, 'demo:' ) ? substr( (string) $mapped_key, 5 ) : (string) $mapped_key;
			$parts = explode( ':', $identity );
			if ( absint( $parts[0] ?? 0 ) !== $source_id || (string) $mapped_key === $key ) {
				continue;
			}
			$obsolete_id = absint( $mapped_package_id );
			$obsolete = $obsolete_id ? $wpdb->get_row( $wpdb->prepare( "SELECT last_order_id, is_recurring, subscription_id FROM {$wpdb->prefix}directorist_user_packages WHERE id = %d", $obsolete_id ) ) : null;
			if ( $obsolete && empty( $obsolete->last_order_id ) && empty( $obsolete->is_recurring ) && empty( $obsolete->subscription_id ) ) {
				$wpdb->delete( $wpdb->prefix . 'directorist_user_packages', [ 'id' => $obsolete_id ], [ '%d' ] );
				unset( $maps[ $package_id ][ $mapped_key ] );
			}
		}
		$target_id = absint( $maps[ $package_id ][ $key ] ?? 0 );
		$repository = directorist_user_package_repository();
		$existing = $target_id ? $repository->get_by_id( $target_id ) : null;
		if ( ! $existing || (int) $existing->user_id !== $user_id || (int) $existing->plan_id !== $plan_id || (int) $existing->directory_type_id !== $directory_id ) {
			$target_id = 0;
		}
		if ( $target_id && ( ! empty( $existing->last_order_id ) || ! empty( $existing->subscription_id ) || ! empty( $existing->is_recurring ) ) ) {
			// Once used for a real purchase, its billing and access state are local.
			update_post_meta( $listing_id, directorist_plan_key(), $plan_id );
			clean_post_cache( $listing_id );
			return $target_id;
		}
		try {
			$dto = ( new \DirectoristPricingPlan\App\DTO\UserPackage\DTO() )
				->set_user_id( $user_id )
				->set_directory_type_id( $directory_id )
				->set_plan_id( $plan_id )
				->set_listing_display_priority( (int) ( $assignment['listing_display_priority'] ?? 0 ) )
				->set_last_order_id( 0 )
				->set_is_recurring( false )
				->set_is_trial( ! empty( $assignment['is_trial'] ) )
				->set_is_legacy( true )
				->set_status( $assignment['status'] )
				->set_subscription_id( null )
				->set_subscription_method( null )
				->set_subscription_currency( null )
				->set_subscription_amount( null )
				->set_started_at( ! empty( $assignment['started_at'] ) ? new \Directorist\Helpers\DateTime( $assignment['started_at'] ) : directorist_now() )
				->set_current_period_end( ! empty( $assignment['current_period_end'] ) ? new \Directorist\Helpers\DateTime( $assignment['current_period_end'] ) : null )
				->set_cancelled_at( null );
			if ( $target_id ) {
				$dto->set_id( $target_id );
				$saved = $repository->update( $dto );
			} else {
				$saved = $repository->create( $dto );
				$target_id = absint( $saved );
			}
			if ( false === $saved || ! $target_id ) {
				throw new \RuntimeException( 'Listing package could not be saved.' );
			}
			$maps[ $package_id ][ $key ] = $target_id;
			update_option( $option, $maps, false );
			update_post_meta( $listing_id, directorist_plan_key(), $plan_id );
			clean_post_cache( $listing_id );
			return $target_id;
		} catch ( \Throwable $error ) {
			return new WP_Error( 'directorist_import_package_restore', $error->getMessage() );
		}
	}

	private function delete_pricing_plan_children( int $plan_id, array $tables ): bool {
		global $wpdb;
		return false !== $wpdb->delete( $tables['features'], [ 'plan_id' => $plan_id ], [ '%d' ] )
			&& false !== $wpdb->delete( $tables['configurations'], [ 'plan_id' => $plan_id ], [ '%d' ] );
	}
}
