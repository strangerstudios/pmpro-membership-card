<?php
/**
 * Renders the PMPro Membership Card block.
 *
 * @package PMPro_Membership_Card/Blocks
 */

// Sanitize the 'elements' attribute before using it.
if ( isset( $attributes['elements'] ) ) {
	$attributes['elements'] = sanitize_text_field( $attributes['elements'] );
}

$output = pmpro_membership_card_shortcode( $attributes );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo  wp_kses_post( $output ); ?>
</div>
