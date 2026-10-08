<?php
/**
 * Elementor override for Directorist OpenStreet map popup cards.
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
$map_card_class  = 'map-listing-card-single directorist-elementor-map-card' . ( '' !== $map_card_style ? ' directorist-elementor-map-card--card-styled' : '' );
?>

<div class="<?php echo esc_attr( $map_card_class ); ?>" data-directorist-listing-id="<?php echo esc_attr( $listing_id ); ?>"<?php echo '' !== $map_card_style ? ' style="' . esc_attr( $map_card_style ) . '"' : ''; ?>>
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
