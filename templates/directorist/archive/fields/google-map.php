<?php
/**
 * Elementor override for Directorist Google map popup cards.
 *
 * @package DirectoristElementor
 */

use DirectoristElementor\ElementorV4\Render\MapCardTemplateContext;

defined( 'ABSPATH' ) || exit;

$listing_id      = (int) get_the_ID();
$map_context     = MapCardTemplateContext::get_instance();
$show_map_card   = $map_context->should_render_current_map_card();
$map_card_markup = $show_map_card ? $map_context->render_current_map_card_markup( $listing_id ) : '';
$map_card_style  = $show_map_card ? $map_context->get_current_map_card_style( $listing_id ) : '';
$map_card_class  = 'map-info-wrapper map-listing-card-single directorist-elementor-map-card' . ( '' !== $map_card_style ? ' directorist-elementor-map-card--card-styled' : '' );
?>

<div class="marker" data-listing-id="<?php echo esc_attr( $listing_id ); ?>" data-directorist-listing-id="<?php echo esc_attr( $listing_id ); ?>" data-latitude="<?php echo esc_attr( $ls_data['manual_lat'] ?? '' ); ?>" data-longitude="<?php echo esc_attr( $ls_data['manual_lng'] ?? '' ); ?>" data-icon="<?php echo esc_attr( $ls_data['cat_icon'] ?? '' ); ?>">
	<?php if ( ! $map_is_disabled ) : ?>
		<div class="<?php echo esc_attr( $map_card_class ); ?>" style="display:none;<?php echo esc_attr( $map_card_style ); ?>" data-directorist-listing-id="<?php echo esc_attr( $listing_id ); ?>">
			<?php if ( $show_map_card ) : ?>
				<?php if ( '' !== trim( $map_card_markup ) ) : ?>
					<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $map_card_markup; ?>
				<?php else : ?>
					<div class="map-listing-card-single__content">
						<h3 class="map-listing-card-single__content__title">
							<?php if ( ! empty( $disable_single_listing ) ) : ?>
								<?php the_title(); ?>
							<?php else : ?>
								<a href="<?php echo esc_url( get_the_permalink() ); ?>"><?php the_title(); ?></a>
							<?php endif; ?>
						</h3>
					</div>
				<?php endif; ?>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</div>
