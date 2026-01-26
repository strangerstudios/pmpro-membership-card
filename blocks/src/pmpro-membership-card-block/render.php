<?php
/**
 * Renders the PMPro Membership Card block.
 *
 * @package PMPro_Membership_Card/Blocks
 */
$output = pmpro_membership_card_shortcode( $attributes );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo  wp_kses_post( $output ); ?>
</div>
