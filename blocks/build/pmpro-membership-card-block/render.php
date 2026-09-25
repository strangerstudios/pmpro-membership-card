<?php
/**
 * Renders the PMPro Membership Card block.
 *
 * @package PMPro_Membership_Card/Blocks
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$output = pmpro_membership_card_shortcode( $attributes );
?>
<div <?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes the block wrapper attributes. ?>>
	<?php echo  $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shortcode HTML; the template escapes its own output. ?>
</div>
