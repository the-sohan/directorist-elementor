<?php
/**
 * Pricing plan render helpers.
 *
 * @package DirectoristElementor
 */

namespace DirectoristElementor\ElementorV4\Render;

use DirectoristElementor\Services\ExtensionStatusService;
use DirectoristElementor\Traits\Singleton;

class PricingPlanRenderService {
	use Singleton;

	/**
	 * Cached prepared plans.
	 *
	 * @var array<int,array<string,mixed>|null>
	 */
	protected array $prepared_plans = [];

	/**
	 * Check whether Directorist Pricing Plans is available.
	 *
	 * @return bool
	 */
	public function is_active(): bool {
		return ExtensionStatusService::get_instance()->is_extension_active( 'pricing_plans' )
			|| defined( 'DIRECTORIST_PRICING_PLANS_FILE' )
			|| function_exists( 'directorist_pricing_plan_repository' )
			|| function_exists( 'directorist_get_pricing_plan_by_id' );
	}

	/**
	 * Enqueue frontend assets that the pricing plan extension normally uses.
	 *
	 * @return void
	 */
	public function enqueue_assets(): void {
		if ( wp_style_is( 'directorist-pricing-plans-frontend', 'registered' ) ) {
			wp_enqueue_style( 'directorist-pricing-plans-frontend' );
		}

		if ( wp_script_is( 'directorist-pricing-plans-plans', 'registered' ) ) {
			wp_enqueue_script( 'directorist-pricing-plans-plans' );
		}
	}

	/**
	 * Build pricing plan control options.
	 *
	 * @param int $limit Result limit.
	 * @return array<int|string,string>
	 */
	public function get_plan_options( int $limit = 100 ): array {
		global $wpdb;

		if ( ! $this->is_active() ) {
			return [];
		}

		$table_name = $wpdb->prefix . 'directorist_plans';
		$limit      = max( 1, min( 300, $limit ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name is fixed by the Pricing Plans extension.
		$plans = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, title, interval_count, interval_type, type FROM {$table_name} WHERE is_published = 1 AND is_hidden_from_plans_list = 0 ORDER BY sort_order ASC, id ASC LIMIT %d",
				$limit
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $plans ) ) {
			return [];
		}

		$options = [];

		foreach ( $plans as $plan ) {
			$plan_id = absint( $plan->id ?? 0 );

			if ( $plan_id <= 0 ) {
				continue;
			}

			$label = trim( (string) ( $plan->title ?? '' ) );
			if ( '' === $label ) {
				$label = sprintf(
					/* translators: %d: plan id. */
					__( 'Plan #%d', 'directorist-elementor' ),
					$plan_id
				);
			}

			$duration = $this->duration_label_from_parts(
				sanitize_key( (string) ( $plan->interval_type ?? '' ) ),
				absint( $plan->interval_count ?? 1 )
			);

			if ( '' !== $duration ) {
				$label .= ' - ' . $duration;
			}

			$package_type = $this->package_type_label( $plan );
			if ( '' !== $package_type ) {
				$label .= ' - ' . $package_type;
			}

			$options[ $plan_id ] = $label;
		}

		return $options;
	}

	/**
	 * Get first visible pricing plan ids.
	 *
	 * @param int $limit Result limit.
	 * @return array<int>
	 */
	public function get_default_plan_ids( int $limit = 3 ): array {
		return array_slice( array_map( 'absint', array_keys( $this->get_plan_options( $limit ) ) ), 0, $limit );
	}

	/**
	 * Get a pricing plan object.
	 *
	 * @param int $plan_id Plan id.
	 * @return \stdClass|null
	 */
	public function get_plan( int $plan_id ): ?\stdClass {
		if ( $plan_id <= 0 || ! $this->is_active() || ! function_exists( 'directorist_get_pricing_plan_by_id' ) ) {
			return null;
		}

		$plan = directorist_get_pricing_plan_by_id( $plan_id );

		return $plan instanceof \stdClass ? $plan : null;
	}

	/**
	 * Prepare computed plan display data.
	 *
	 * @param int $plan_id Plan id.
	 * @return array<string,mixed>|null
	 */
	public function prepare( int $plan_id ): ?array {
		if ( $plan_id <= 0 ) {
			return null;
		}

		if ( array_key_exists( $plan_id, $this->prepared_plans ) ) {
			return $this->prepared_plans[ $plan_id ];
		}

		$plan = $this->get_plan( $plan_id );

		if ( ! $plan ) {
			$this->prepared_plans[ $plan_id ] = null;

			return null;
		}

		$directory_type_id = absint( $plan->directory_type_id ?? 0 );
		$directory_term    = $directory_type_id > 0 && defined( 'ATBDP_DIRECTORY_TYPE' )
			? get_term( $directory_type_id, ATBDP_DIRECTORY_TYPE )
			: null;
		$currency_parts    = $this->currency_parts();
		$is_paid           = 'paid' === (string) ( $plan->fee_type ?? '' );
		$is_pay_per        = 'pay_per_listing' === (string) ( $plan->type ?? '' );
		$tax_label         = '';

		if ( ! empty( $plan->is_taxable ) && (float) ( $plan->tax_rate ?? 0 ) > 0 ) {
			if ( 'percent' === (string) ( $plan->tax_type ?? '' ) ) {
				$tax_label = $this->format_amount( $plan->tax_rate ) . '%';
			} else {
				$tax_label = trim( $currency_parts['before'] . ' ' . $this->format_amount( $plan->tax_rate ) . ' ' . $currency_parts['after'] );
			}
		}

		$is_active      = false;
		$exceeded_usage = false;
		$active_packages = [];

		if ( is_user_logged_in() && $directory_type_id > 0 && function_exists( 'directorist_user_package_repository' ) ) {
			try {
				$user_package_repository = directorist_user_package_repository();
				$active_packages         = $user_package_repository->get_active_packages_for_directory( get_current_user_id(), $directory_type_id );

				foreach ( $active_packages as $package ) {
					if ( method_exists( $user_package_repository, 'attach_plan_usage_data' ) ) {
						$user_package_repository->attach_plan_usage_data( $package );
					}

					if ( (int) ( $package->plan_id ?? 0 ) === $plan_id ) {
						$is_active = true;
					}
				}

				$is_free_one_time = ! $is_active && 'free' === (string) ( $plan->fee_type ?? '' ) && 'lifetime' !== (string) ( $plan->interval_type ?? '' );
				if ( $is_free_one_time && method_exists( $user_package_repository, 'has_ever_used_plan' ) ) {
					$exceeded_usage = (bool) $user_package_repository->has_ever_used_plan( get_current_user_id(), $plan_id );
				}
			} catch ( \Throwable $throwable ) {
				$is_active      = false;
				$exceeded_usage = false;
			}
		}

		$user_trial_eligible = is_user_logged_in() && function_exists( 'directorist_is_user_trial_eligible' )
			? directorist_is_user_trial_eligible( $directory_type_id )
			: true;
		$has_trial           = $user_trial_eligible && function_exists( 'directorist_is_plan_trial_eligible' )
			? directorist_is_plan_trial_eligible( $plan )
			: false;
		$featured_text       = __( ' ( None of them can be featured )', 'directorist-elementor' );

		if ( 1 === (int) ( $plan->is_allowed_unlimited_listings ?? 0 ) || (int) ( $plan->allowed_listings ?? 0 ) > 0 ) {
			if ( 1 === (int) ( $plan->is_allowed_unlimited_featured_listings ?? 0 ) ) {
				$featured_text = __( ' ( All can be featured )', 'directorist-elementor' );
			} elseif ( (int) ( $plan->allowed_featured_listings ?? 0 ) > 0 ) {
				$featured_text = sprintf(
					/* translators: %d: featured listing count. */
					__( ' ( %d of them can be featured )', 'directorist-elementor' ),
					(int) $plan->allowed_featured_listings
				);
			}
		}

		$listing_limit_text = 1 === (int) ( $plan->is_allowed_unlimited_listings ?? 0 )
			? __( 'Unlimited listings', 'directorist-elementor' ) . $featured_text
			: sprintf(
				/* translators: %d: listing count. */
				__( '%d Total listings', 'directorist-elementor' ),
				(int) ( $plan->allowed_listings ?? 0 )
			) . $featured_text;

		$this->prepared_plans[ $plan_id ] = [
			'plan'                  => $plan,
			'features'              => $this->get_features( $plan ),
			'directory_term'        => $directory_term instanceof \WP_Term ? $directory_term : null,
			'currency_before'       => $currency_parts['before'],
			'currency_after'        => $currency_parts['after'],
			'is_paid'               => $is_paid,
			'is_pay_per'            => $is_pay_per,
			'is_active'             => $is_active,
			'exceeded_usage'        => $exceeded_usage,
			'has_trial'             => $has_trial,
			'tax_label'             => $tax_label,
			'duration_key'          => $this->duration_key( $plan ),
			'duration_label'        => $this->duration_label( $plan ),
			'package_type_key'      => $this->package_type_key( $plan ),
			'package_type_label'    => $this->package_type_label( $plan ),
			'type_label'            => $is_pay_per ? __( 'Pay Per Listing', 'directorist-elementor' ) : __( 'Package', 'directorist-elementor' ),
			'listing_limit_text'    => $listing_limit_text,
			'active_packages'       => $active_packages,
			'is_marked_recommended' => 1 === (int) ( $plan->is_marked_as_recommended ?? 0 ),
		];

		return $this->prepared_plans[ $plan_id ];
	}

	/**
	 * Get plan features.
	 *
	 * @param \stdClass $plan Plan object.
	 * @return array<int,object>
	 */
	public function get_features( \stdClass $plan ): array {
		if (
			! function_exists( 'directorist_pricing_plans_singleton' )
			|| ! class_exists( '\DirectoristPricingPlan\App\Repositories\Admin\PlanFeatureRepository' )
		) {
			return [];
		}

		try {
			$repository = directorist_pricing_plans_singleton( \DirectoristPricingPlan\App\Repositories\Admin\PlanFeatureRepository::class );

			return $repository ? (array) $repository->get( $plan ) : [];
		} catch ( \Throwable $throwable ) {
			return [];
		}
	}

	/**
	 * Get available feature order choices.
	 *
	 * @return array<string,string>
	 */
	public function get_feature_order_options(): array {
		$options = [
			'auto_renew'    => __( 'Auto Renew', 'directorist-elementor' ),
			'listing_limit' => __( 'Listing Limit', 'directorist-elementor' ),
		];

		if ( function_exists( 'directorist_pricing_plans_config' ) ) {
			$registered = directorist_pricing_plans_config( 'plan-features' );

			if ( is_array( $registered ) ) {
				foreach ( $registered as $key => $feature ) {
					if ( ! is_array( $feature ) ) {
						continue;
					}

					$options[ sanitize_key( (string) $key ) ] = (string) ( $feature['name'] ?? $key );
				}
			}
		}

		return $options;
	}

	/**
	 * Render one pricing plan field.
	 *
	 * @param string              $field Field key.
	 * @param int                 $plan_id Plan id.
	 * @param array<string,mixed> $settings Field settings.
	 * @return string
	 */
	public function render_field( string $field, int $plan_id, array $settings = [] ): string {
		if ( ! $this->is_active() ) {
			return $this->placeholder( __( 'Directorist Pricing Plans is required to render this widget.', 'directorist-elementor' ) );
		}

		$data = $this->prepare( $plan_id );

		if ( ! $data ) {
			return $this->placeholder( __( 'Select a pricing plan to preview this field.', 'directorist-elementor' ) );
		}

		$plan = $data['plan'];

		switch ( $field ) {
			case 'title':
				$level = absint( $settings['heading_level'] ?? 4 );
				$level = $level >= 1 && $level <= 6 ? $level : 4;

				return sprintf(
					'<h%1$d class="directorist-elementor-pricing-plan-title directorist-elementor-pricing-plan-text">%2$s</h%1$d>',
					$level,
					esc_html( $plan->title ?? '' )
				);

			case 'description':
				if ( '' === trim( (string) ( $plan->description ?? '' ) ) ) {
					return '';
				}

				return sprintf(
					'<p class="directorist-elementor-pricing-plan-description directorist-elementor-pricing-plan-text">%s</p>',
					esc_html( $plan->description )
				);

			case 'price':
				return $this->render_price( $plan, $data, $settings );

			case 'duration':
				if ( '' === (string) $data['duration_label'] ) {
					return '';
				}

				return sprintf(
					'<span class="directorist-elementor-pricing-plan-duration directorist-elementor-pricing-plan-text">%s</span>',
					esc_html( (string) $data['duration_label'] )
				);

			case 'trial-note':
				return $this->render_trial_note( $plan, $data );

			case 'type-badge':
				return sprintf(
					'<span class="atbd_plan-type directorist-elementor-pricing-plan-badge directorist-elementor-pricing-plan-type-badge directorist-elementor-pricing-plan-type-badge--%1$s">%2$s</span>',
					$data['is_pay_per'] ? 'pay-per-listing' : 'package',
					esc_html( (string) $data['type_label'] )
				);

			case 'recommended-badge':
				if ( empty( $data['is_marked_recommended'] ) ) {
					return '';
				}

				return $this->render_recommended_badge( $settings );

			case 'active-badge':
				if ( empty( $data['is_active'] ) ) {
					return '';
				}

				return sprintf(
					'<span class="atbd_plan-active directorist-elementor-pricing-plan-badge directorist-elementor-pricing-plan-active-badge">%s</span>',
					esc_html( (string) ( $settings['label'] ?? __( 'Active', 'directorist-elementor' ) ) )
				);

			case 'features':
				return $this->render_features( $plan, $data, $settings );

			case 'action-button':
				return $this->render_action_button( $plan, $data, $settings );
		}

		return '';
	}

	/**
	 * Render recommended badge.
	 *
	 * @param array<string,mixed> $settings Field settings.
	 * @return string
	 */
	protected function render_recommended_badge( array $settings ): string {
		$label         = '' !== trim( (string) ( $settings['label'] ?? '' ) )
			? (string) $settings['label']
			: __( 'Recommended', 'directorist-elementor' );
		$show_icon     = 'yes' === (string) ( $settings['show_icon'] ?? '' );
		$icon_only     = $show_icon && 'yes' === (string) ( $settings['icon_only'] ?? '' );
		$show_label    = ! $icon_only && 'yes' === (string) ( $settings['show_label'] ?? 'yes' );
		$icon_position = 'after' === (string) ( $settings['icon_position'] ?? 'before' ) ? 'after' : 'before';
		$icon_markup   = $show_icon ? $this->render_elementor_icon( $settings['icon'] ?? [] ) : '';

		if ( '' === $icon_markup ) {
			$icon_only = false;
		}

		if ( ! $show_label && '' === $icon_markup ) {
			$show_label = true;
		}

		$parts = [];

		if ( $show_label ) {
			$parts[] = sprintf(
				'<span class="directorist-elementor-pricing-plan-recommended-badge__label">%s</span>',
				esc_html( $label )
			);
		}

		if ( '' !== $icon_markup ) {
			if ( 'after' === $icon_position && ! $icon_only ) {
				$parts[] = $icon_markup;
			} else {
				array_unshift( $parts, $icon_markup );
			}
		}

		return sprintf(
			'<span class="%1$s">%2$s</span>',
			esc_attr(
				trim(
					'directorist-elementor-pricing-plan-badge directorist-elementor-pricing-plan-recommended-badge ' .
					'directorist-elementor-pricing-plan-recommended-badge--icon-' . $icon_position . ' ' .
					( $icon_only ? 'directorist-elementor-pricing-plan-recommended-badge--icon-only' : '' )
				)
			),
			implode( '', $parts )
		);
	}

	/**
	 * Render Elementor icon control markup.
	 *
	 * @param mixed $icon Icon setting.
	 * @return string
	 */
	protected function render_elementor_icon( $icon ): string {
		if ( ! is_array( $icon ) || empty( $icon['value'] ) || ! class_exists( '\Elementor\Icons_Manager' ) ) {
			return '';
		}

		ob_start();
		echo '<span class="directorist-elementor-pricing-plan-recommended-badge__icon" aria-hidden="true">';
		\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
		echo '</span>';
		$html = trim( (string) ob_get_clean() );

		return false !== strpos( $html, '<svg' ) || false !== strpos( $html, '<i ' )
			? $html
			: '';
	}

	/**
	 * Render price.
	 *
	 * @param \stdClass           $plan Plan object.
	 * @param array<string,mixed> $data Prepared plan.
	 * @param array<string,mixed> $settings Field settings.
	 * @return string
	 */
	protected function render_price( \stdClass $plan, array $data, array $settings ): string {
		$show_duration = array_key_exists( 'show_duration', $settings ) ? 'yes' === (string) $settings['show_duration'] : true;
		$show_tax      = array_key_exists( 'show_tax_tooltip', $settings ) ? 'yes' === (string) $settings['show_tax_tooltip'] : true;

		ob_start();
		?>
		<div class="directorist-elementor-pricing-plan-price">
			<span class="directorist-elementor-pricing-plan-price__value">
				<?php if ( $data['is_paid'] ) : ?>
					<?php if ( '' !== $data['currency_before'] ) : ?>
						<sup class="directorist-elementor-pricing-plan-price__currency"><?php echo esc_html( (string) $data['currency_before'] ); ?></sup>
					<?php endif; ?>
					<span class="directorist-elementor-pricing-plan-price__amount"><?php echo esc_html( $this->format_amount( $plan->price ?? 0 ) ); ?></span>
					<?php if ( '' !== $data['currency_after'] ) : ?>
						<sup class="directorist-elementor-pricing-plan-price__currency"><?php echo esc_html( (string) $data['currency_after'] ); ?></sup>
					<?php endif; ?>
				<?php else : ?>
					<span class="directorist-elementor-pricing-plan-price__free"><?php esc_html_e( 'Free', 'directorist-elementor' ); ?></span>
				<?php endif; ?>
				<?php if ( $show_duration && '' !== $data['duration_label'] ) : ?>
					<small class="directorist-elementor-pricing-plan-price__duration">/ <?php echo esc_html( (string) $data['duration_label'] ); ?></small>
				<?php endif; ?>
			</span>
			<?php if ( $show_tax && '' !== $data['tax_label'] ) : ?>
				<span class="directorist-pricing-info directorist-elementor-pricing-plan-price__tax" tabindex="0">
					<?php echo wp_kses_post( function_exists( 'directorist_icon' ) ? directorist_icon( 'fas fa-question-circle', false ) : '?' ); ?>
					<span class="directorist-tooltip-pricing directorist-tooltip-top-pricing">
						<?php
						printf(
							/* translators: %s: tax amount. */
							esc_html__( 'Plus %s tax', 'directorist-elementor' ),
							esc_html( (string) $data['tax_label'] )
						);
						?>
					</span>
				</span>
			<?php endif; ?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render trial note.
	 *
	 * @param \stdClass           $plan Plan object.
	 * @param array<string,mixed> $data Prepared plan.
	 * @return string
	 */
	protected function render_trial_note( \stdClass $plan, array $data ): string {
		if ( empty( $data['has_trial'] ) ) {
			return '';
		}

		$trial_count = absint( $plan->trial_interval_count ?? 0 );
		$trial_type  = sanitize_key( (string) ( $plan->trial_interval_type ?? '' ) );

		if ( $trial_count <= 0 || '' === $trial_type ) {
			return '';
		}

		return sprintf(
			'<p class="directorist-elementor-pricing-plan-trial-note directorist-elementor-pricing-plan-text directorist-text-sm directorist-text-muted directorist-pt-10">%s</p>',
			esc_html(
				sprintf(
					/* translators: 1: trial count, 2: trial interval. */
					__( 'After %1$d %2$s of trial', 'directorist-elementor' ),
					$trial_count,
					$trial_type . ( $trial_count > 1 ? 's' : '' )
				)
			)
		);
	}

	/**
	 * Render features.
	 *
	 * @param \stdClass           $plan Plan object.
	 * @param array<string,mixed> $data Prepared plan.
	 * @param array<string,mixed> $settings Field settings.
	 * @return string
	 */
	protected function render_features( \stdClass $plan, array $data, array $settings ): string {
		$show_auto_renew    = array_key_exists( 'show_auto_renew', $settings ) ? 'yes' === (string) $settings['show_auto_renew'] : true;
		$show_listing_limit = array_key_exists( 'show_listing_limit', $settings ) ? 'yes' === (string) $settings['show_listing_limit'] : true;
		$show_plan_features = array_key_exists( 'show_plan_features', $settings ) ? 'yes' === (string) $settings['show_plan_features'] : true;
		$show_unavailable   = array_key_exists( 'show_unavailable_features', $settings ) ? 'yes' === (string) $settings['show_unavailable_features'] : true;
		$show_suffix        = array_key_exists( 'show_feature_suffix', $settings ) ? 'yes' === (string) $settings['show_feature_suffix'] : true;
		$available_icon     = (array) ( $settings['available_icon'] ?? [] );
		$unavailable_icon   = (array) ( $settings['unavailable_icon'] ?? [] );
		$items              = [];

		if ( $show_auto_renew ) {
			$auto_renew = function_exists( 'directorist_plan_has_subscription' ) ? (bool) directorist_plan_has_subscription( $plan ) : false;
			if ( $auto_renew || $show_unavailable ) {
				$items[] = [
					'key'     => 'auto_renew',
					'enabled' => $auto_renew,
					'label'   => __( 'Auto Renew', 'directorist-elementor' ),
				];
			}
		}

		if ( $show_listing_limit ) {
			$items[] = [
				'key'     => 'listing_limit',
				'enabled' => true,
				'label'   => $data['listing_limit_text'],
			];
		}

		if ( $show_plan_features ) {
			foreach ( $data['features'] as $feature ) {
				if ( empty( $feature->is_show_in_pricing_table ) ) {
					continue;
				}

				$enabled = ! empty( $feature->is_enabled );
				if ( ! $enabled && ! $show_unavailable ) {
					continue;
				}

				$items[] = [
					'key'     => sanitize_key( (string) ( $feature->key ?? ( $feature->feature_key ?? ( $feature->id ?? '' ) ) ) ),
					'enabled' => $enabled,
					'label'   => (string) ( $feature->name ?? '' ) . ( $show_suffix ? $this->feature_suffix( $feature ) : '' ),
				];
			}
		}

		$order = array_values( array_filter( array_map( 'sanitize_key', (array) ( $settings['feature_order'] ?? [] ) ) ) );
		if ( ! empty( $order ) ) {
			$ordered_items = [];
			$remaining     = [];

			foreach ( $items as $item ) {
				$key = sanitize_key( (string) ( $item['key'] ?? '' ) );
				if ( '' !== $key && in_array( $key, $order, true ) ) {
					$ordered_items[ $key ] = $item;
				} else {
					$remaining[] = $item;
				}
			}

			$items = [];
			foreach ( $order as $ordered_key ) {
				if ( isset( $ordered_items[ $ordered_key ] ) ) {
					$items[] = $ordered_items[ $ordered_key ];
				}
			}

			$items = array_merge( $items, $remaining );
		}

		if ( empty( $items ) ) {
			return '';
		}

		ob_start();
		?>
		<div class="directorist-elementor-pricing-plan-features directorist-pricing__features">
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li class="<?php echo ! empty( $item['enabled'] ) ? 'is-available' : 'is-unavailable'; ?>">
						<?php echo $this->feature_icon( (bool) $item['enabled'], ! empty( $item['enabled'] ) ? $available_icon : $unavailable_icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted Elementor icon output with internal SVG fallback. ?>
						<span class="directorist-elementor-pricing-plan-features__text"><?php echo esc_html( (string) $item['label'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render action button.
	 *
	 * @param \stdClass           $plan Plan object.
	 * @param array<string,mixed> $data Prepared plan.
	 * @param array<string,mixed> $settings Field settings.
	 * @return string
	 */
	protected function render_action_button( \stdClass $plan, array $data, array $settings ): string {
		$label          = '' !== trim( (string) ( $settings['label'] ?? '' ) ) ? (string) $settings['label'] : __( 'Continue', 'directorist-elementor' );
		$is_free_plan   = ! $data['is_paid'] || (float) ( $plan->price ?? 0 ) <= 0;
		$directory_term = $data['directory_term'];
		$button_class   = 'directorist-btn directorist-btn-lighter directorist-btn-block directorist-pricing__action--btn directorist-elementor-pricing-plan-action-button';

		if ( function_exists( 'directorist_direct_purchase' ) && directorist_direct_purchase() && ! get_directorist_option( 'guest_listings' ) && ! is_user_logged_in() ) {
			$button_class .= ' directorist_required_login';
		} elseif ( function_exists( 'directorist_direct_purchase' ) && directorist_direct_purchase() && get_directorist_option( 'guest_listings' ) && ! is_user_logged_in() ) {
			$button_class .= ' directorist_required_email';
		}

		if ( ! $directory_term instanceof \WP_Term ) {
			return '';
		}

		if ( ! empty( $data['exceeded_usage'] ) ) {
			return sprintf(
				'<span class="%1$s directorist-elementor-pricing-plan-action-button--disabled" aria-disabled="true" style="opacity:0.5;cursor:not-allowed;pointer-events:none;">%2$s</span>',
				esc_attr( $button_class ),
				esc_html( $label )
			);
		}

		return sprintf(
			'<a class="%1$s" href="%2$s" data-is_free_plan="%3$s" data-plan_id="%4$d">%5$s</a>',
			esc_attr( $button_class ),
			esc_url( $this->continue_url( $plan, $directory_term ) ),
			$is_free_plan ? '1' : '0',
			absint( $plan->id ?? 0 ),
			esc_html( $label )
		);
	}

	/**
	 * Build feature availability icon.
	 *
	 * @param bool                $enabled Whether feature is enabled.
	 * @param array<string,mixed> $icon Icon control.
	 * @return string
	 */
	protected function feature_icon( bool $enabled, array $icon = [] ): string {
		$wrapper_class = $enabled
			? 'directorist-elementor-pricing-plan-features__icon directorist-elementor-pricing-plan-features__icon--available'
			: 'directorist-elementor-pricing-plan-features__icon directorist-elementor-pricing-plan-features__icon--unavailable';
		$icon_value    = sanitize_text_field( (string) ( $icon['value'] ?? '' ) );

		if ( '' !== $icon_value && class_exists( '\Elementor\Icons_Manager' ) ) {
			ob_start();
			echo '<span class="' . esc_attr( $wrapper_class ) . '" aria-hidden="true">';
			\Elementor\Icons_Manager::render_icon( $icon, [ 'aria-hidden' => 'true' ] );
			echo '</span>';
			$html = (string) ob_get_clean();

			if ( '' !== trim( wp_strip_all_tags( $html ) ) || false !== strpos( $html, '<svg' ) || false !== strpos( $html, '<i ' ) ) {
				return $html;
			}
		}

		if ( function_exists( 'directorist_icon' ) ) {
			$html = directorist_icon( $enabled ? 'fas fa-check' : 'fas fa-times', false, $enabled ? 'directorist_green' : 'directorist_red' );
			if ( is_string( $html ) && '' !== trim( $html ) ) {
				return sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $wrapper_class ), $html );
			}
		}

		return sprintf(
			'<span class="%1$s" aria-hidden="true">%2$s</span>',
			esc_attr( $wrapper_class ),
			$this->default_feature_icon_svg( $enabled )
		);
	}

	/**
	 * Build the internal fallback feature icon.
	 *
	 * @param bool $enabled Whether feature is enabled.
	 * @return string
	 */
	protected function default_feature_icon_svg( bool $enabled ): string {
		if ( $enabled ) {
			return '<svg viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="M6.2 11.3 2.9 8l1.1-1.1 2.2 2.2 5.8-5.8 1.1 1.1-6.9 6.9Z"/></svg>';
		}

		return '<svg viewBox="0 0 16 16" focusable="false" aria-hidden="true"><path d="m4.1 3 3.9 3.9L11.9 3 13 4.1 9.1 8l3.9 3.9-1.1 1.1L8 9.1 4.1 13 3 11.9 6.9 8 3 4.1 4.1 3Z"/></svg>';
	}

	/**
	 * Build duration key.
	 *
	 * @param \stdClass $plan Plan object.
	 * @return string
	 */
	public function duration_key( \stdClass $plan ): string {
		$interval_type  = sanitize_key( (string) ( $plan->interval_type ?? '' ) );
		$interval_count = max( 1, absint( $plan->interval_count ?? 1 ) );

		if ( 'lifetime' === $interval_type ) {
			return 'lifetime';
		}

		if ( '' === $interval_type ) {
			return 'unknown';
		}

		return $interval_count . '-' . $interval_type;
	}

	/**
	 * Build duration label.
	 *
	 * @param \stdClass $plan Plan object.
	 * @return string
	 */
	public function duration_label( \stdClass $plan ): string {
		if ( function_exists( 'directorist_plan_duration_text' ) ) {
			return (string) directorist_plan_duration_text( $plan );
		}

		return $this->duration_label_from_parts(
			sanitize_key( (string) ( $plan->interval_type ?? '' ) ),
			absint( $plan->interval_count ?? 1 )
		);
	}

	/**
	 * Build package type key.
	 *
	 * @param \stdClass $plan Plan object.
	 * @return string
	 */
	public function package_type_key( \stdClass $plan ): string {
		$type = sanitize_key( (string) ( $plan->type ?? '' ) );

		return '' !== $type ? $type : 'package';
	}

	/**
	 * Build package type label.
	 *
	 * @param \stdClass $plan Plan object.
	 * @return string
	 */
	public function package_type_label( \stdClass $plan ): string {
		$type = $this->package_type_key( $plan );

		if ( 'pay_per_listing' === $type ) {
			return __( 'Pay Per Listing', 'directorist-elementor' );
		}

		if ( 'package' === $type ) {
			return __( 'Package', 'directorist-elementor' );
		}

		return ucwords( str_replace( [ '_', '-' ], ' ', $type ) );
	}

	/**
	 * Normalize tab grouping type.
	 *
	 * @param string $tab_type Raw tab type.
	 * @return string
	 */
	public function normalize_tab_type( string $tab_type ): string {
		return in_array(
			$tab_type,
			[ 'duration', 'package_type' ],
			true
		) ? $tab_type : 'duration';
	}

	/**
	 * Get tab key for prepared plan data.
	 *
	 * @param array<string,mixed> $data Prepared plan data.
	 * @param string              $tab_type Tab grouping type.
	 * @return string
	 */
	public function tab_key( array $data, string $tab_type ): string {
		$tab_type = $this->normalize_tab_type( $tab_type );

		return 'package_type' === $tab_type
			? sanitize_key( (string) ( $data['package_type_key'] ?? '' ) )
			: sanitize_key( (string) ( $data['duration_key'] ?? '' ) );
	}

	/**
	 * Get tab label for prepared plan data.
	 *
	 * @param array<string,mixed> $data Prepared plan data.
	 * @param string              $tab_type Tab grouping type.
	 * @return string
	 */
	public function tab_label( array $data, string $tab_type ): string {
		$tab_type = $this->normalize_tab_type( $tab_type );

		return 'package_type' === $tab_type
			? (string) ( $data['package_type_label'] ?? $data['type_label'] ?? __( 'Package', 'directorist-elementor' ) )
			: (string) ( $data['duration_label'] ?? '' );
	}

	/**
	 * Build duration label from stored parts.
	 *
	 * @param string $interval_type Interval type.
	 * @param int    $interval_count Interval count.
	 * @return string
	 */
	protected function duration_label_from_parts( string $interval_type, int $interval_count = 1 ): string {
		if ( 'lifetime' === $interval_type ) {
			return __( 'Lifetime', 'directorist-elementor' );
		}

		if ( '' === $interval_type ) {
			return '';
		}

		$interval_count = max( 1, $interval_count );
		$label          = ucfirst( $interval_type );

		return 1 === $interval_count ? $label : $interval_count . ' ' . $label . 's';
	}

	/**
	 * Currency display parts.
	 *
	 * @return array{before:string,after:string}
	 */
	protected function currency_parts(): array {
		$currency = function_exists( 'directorist_get_currency' ) ? directorist_get_currency() : 'USD';
		$symbol   = function_exists( 'atbdp_currency_symbol' ) ? atbdp_currency_symbol( $currency ) : '$';
		$position = function_exists( 'directorist_get_currency_position' ) ? directorist_get_currency_position() : 'before';

		return [
			'before' => 'after' === $position ? '' : (string) $symbol,
			'after'  => 'after' === $position ? (string) $symbol : '',
		];
	}

	/**
	 * Format an amount.
	 *
	 * @param mixed $amount Amount.
	 * @return string
	 */
	protected function format_amount( $amount ): string {
		$text = number_format_i18n( (float) $amount, 2 );

		return preg_replace( '/\.00$/', '', $text ) ?: $text;
	}

	/**
	 * Build feature limit suffix.
	 *
	 * @param object $feature Feature object.
	 * @return string
	 */
	protected function feature_suffix( object $feature ): string {
		$feature_data = ! empty( $feature->data ) && is_array( $feature->data ) ? $feature->data : [];
		$has_limit    = isset( $feature_data['limit'] ) && '' !== $feature_data['limit'] && null !== $feature_data['limit'];
		$has_exclude  = ! empty( $feature_data['exclude'] );
		$is_unlimited = ! empty( $feature_data['is_unlimited'] );

		if ( $is_unlimited ) {
			return $has_exclude
				? ' ( ' . __( 'Unlimited | Partial', 'directorist-elementor' ) . ' )'
				: ' ( ' . __( 'Unlimited', 'directorist-elementor' ) . ' )';
		}

		if ( $has_limit && $has_exclude ) {
			return ' ( ' . sprintf(
				/* translators: %s: maximum limit number. */
				__( 'Up to %s | Partial', 'directorist-elementor' ),
				esc_html( $feature_data['limit'] )
			) . ' )';
		}

		if ( $has_limit ) {
			return ' ( ' . sprintf(
				/* translators: %s: maximum limit number. */
				__( 'Up to %s', 'directorist-elementor' ),
				esc_html( $feature_data['limit'] )
			) . ' )';
		}

		if ( $has_exclude ) {
			return ' ( ' . __( 'Partial', 'directorist-elementor' ) . ' )';
		}

		return '';
	}

	/**
	 * Build continue URL.
	 *
	 * @param \stdClass $plan Plan object.
	 * @param \WP_Term  $directory_term Directory term.
	 * @return string
	 */
	protected function continue_url( \stdClass $plan, \WP_Term $directory_term ): string {
		$query_args = [
			'plan_id'        => absint( $plan->id ?? 0 ),
			'directory_type' => $directory_term->slug,
		];

		$base_url = '';
		if ( class_exists( '\ATBDP_Permalink' ) && method_exists( '\ATBDP_Permalink', 'get_add_listing_page_link' ) ) {
			$base_url = \ATBDP_Permalink::get_add_listing_page_link();
		}
		if ( ! $base_url ) {
			$base_url = get_permalink();
		}
		if ( ! $base_url ) {
			$base_url = home_url( '/' );
		}

		$url = add_query_arg( $query_args, $base_url );

		return (string) apply_filters( 'directorist_pricing_plans_continue_url', $url, $query_args, $plan, $directory_term );
	}

	/**
	 * Render placeholder.
	 *
	 * @param string $message Message.
	 * @return string
	 */
	protected function placeholder( string $message ): string {
		return sprintf(
			'<div class="directorist-elementor-placeholder"><p class="directorist-elementor-placeholder__title">%1$s</p><p>%2$s</p></div>',
			esc_html__( 'Pricing Plan', 'directorist-elementor' ),
			esc_html( $message )
		);
	}
}
