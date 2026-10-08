<?php
/**
 * Extension availability detection service.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\Services;

use DirectoristElementor\Traits\Singleton;

class ExtensionStatusService {
	use Singleton;

	/**
	 * Cached extension availability flags.
	 *
	 * @var array<string,bool>
	 */
	protected array $status_cache = [];

	/**
	 * Extension signatures used for runtime detection.
	 *
	 * @var array<string,array<string,array<int,string>>>
	 */
	protected array $signatures = [
		'booking' => [
			'constants' => [ 'BDB_VERSION' ],
			'classes'   => [ 'Directorist_Booking\\WpMVC\\App' ],
			'functions' => [ 'directorist_booking' ],
		],
		'directory_linking' => [
			'constants' => [ 'SWBDP_DIRLINK_VERSION' ],
			'classes'   => [ 'Directorist_Directory_Linking' ],
			'functions' => [],
		],
		'job_manager' => [
			'constants' => [ 'DIRJOB_BASE_DIR' ],
			'classes'   => [ 'Directorist_Job_Manager' ],
			'functions' => [],
		],
		'gallery' => [
			'constants' => [ 'BDG_VERSION' ],
			'classes'   => [ 'BD_Gallery' ],
			'functions' => [ 'BD_Gallery' ],
		],
		'faq' => [
			'constants' => [ 'FAQS_VERSION' ],
			'classes'   => [ 'Listings_fAQs' ],
			'functions' => [ 'Listings_fAQs' ],
		],
		'compare' => [
			'constants' => [ 'ATDLC_PLUGIN_VERSION' ],
			'classes'   => [ 'ATDListingCompare' ],
			'functions' => [ 'ATDListingCompare' ],
		],
		'claim' => [
			'constants' => [ 'DCL_VERSION' ],
			'classes'   => [ 'DCL_Base' ],
			'functions' => [ 'DCL_Base' ],
		],
		'live_chat' => [
			'constants' => [ 'DLC_VERSION' ],
			'classes'   => [ 'Directorist_Live_Chat' ],
			'functions' => [ 'Directorist_Live_Chat' ],
		],
		'marketplace' => [
			'constants' => [ 'DDM_VERSION' ],
			'classes'   => [ 'DirectoristDigitalMarketplace' ],
			'functions' => [ 'DirectoristDigitalMarketplace' ],
		],
		'formgent' => [
			'constants' => [],
			'classes'   => [ 'ATBDP_Formgent', 'FormGent\\App\\Models\\Post' ],
			'functions' => [ 'formgent_get_form_by_id', 'formgent_response_repository' ],
		],
		'pricing_plans' => [
			'constants' => [ 'DIRECTORIST_PRICING_PLANS_FILE' ],
			'classes'   => [ 'DirectoristPricingPlan\\App\\App' ],
			'functions' => [ 'directorist_pricing_plan_repository', 'directorist_get_pricing_plan_by_id' ],
		],
		'business_hours' => [
			'constants' => [ 'BDBH_VERSION' ],
			'classes'   => [ 'BD_Business_Hour' ],
			'functions' => [ 'show_business_hours', 'directorist_business_open_close_status' ],
		],
	];

	/**
	 * Determine if an extension is active.
	 *
	 * @param string $extension Extension slug.
	 * @return bool
	 */
	public function is_extension_active( string $extension ): bool {
		$extension = sanitize_key( $extension );

		if ( isset( $this->status_cache[ $extension ] ) ) {
			return $this->status_cache[ $extension ];
		}

		$is_active = false;

		if ( ! empty( $this->signatures[ $extension ] ) ) {
			$is_active = $this->matches_signature( $this->signatures[ $extension ] );
		}

		/**
		 * Filters extension active state resolved for Elementor rendering.
		 *
		 * @param bool   $is_active Whether extension is active.
		 * @param string $extension Extension slug.
		 */
		$is_active = (bool) apply_filters( 'directorist_elementor/is_extension_active', $is_active, $extension );

		$this->status_cache[ $extension ] = $is_active;

		return $is_active;
	}

	/**
	 * Check whether any signature marker matches.
	 *
	 * @param array<string,array<int,string>> $signature Signature definition.
	 * @return bool
	 */
	protected function matches_signature( array $signature ): bool {
		$constants = $signature['constants'] ?? [];
		$classes   = $signature['classes'] ?? [];
		$functions = $signature['functions'] ?? [];

		foreach ( $constants as $constant_name ) {
			if ( is_string( $constant_name ) && '' !== $constant_name && defined( $constant_name ) ) {
				return true;
			}
		}

		foreach ( $classes as $class_name ) {
			if ( is_string( $class_name ) && '' !== $class_name && class_exists( $class_name ) ) {
				return true;
			}
		}

		foreach ( $functions as $function_name ) {
			if ( is_string( $function_name ) && '' !== $function_name && function_exists( $function_name ) ) {
				return true;
			}
		}

		return false;
	}
}
