<?php
/**
 * Admin functions for the Membership Card Add On.
 */

/**
 * Add a page setting for the Membership Card page.
 *
 * @param array $pages Array of settings for the PMPro settings page.
 */
function pmpro_membership_card_extra_page_settings( $pages ) {
	$pages['membership_card'] = array(
		'title' => esc_html__( 'Membership Card', 'pmpro-membership-card' ),
		'content' => '[pmpro_membership_card]',
		'hint' => esc_html__( 'Include the shortcode [pmpro_membership_card].', 'pmpro-membership-card' ),
	);

	return $pages;
}
add_filter( 'pmpro_extra_page_settings', 'pmpro_membership_card_extra_page_settings');

/**
 * Add the link to view the card in the user profile for admins.
 */
function pmpro_membership_card_profile_fields( $user ) {
	$membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	$membership_card_user_url = pmpro_membership_card_get_card_user_url( $user->ID );
	if ( empty( $membership_card_user_url ) ) {
		return;
	}
	?>
	<h2><?php esc_html_e( 'Membership Card', 'pmpro-membership-card' ); ?></h2>
	<p><a href="<?php echo esc_url( $membership_card_user_url );?>"><?php esc_html_e( 'View and Print Membership Card', 'pmpro-membership-card' ); ?></a></p>
	<?php
}
add_action('edit_user_profile', 'pmpro_membership_card_profile_fields');
add_action('show_user_profile', 'pmpro_membership_card_profile_fields');

/**
 * Function to add links to the plugin row meta
 */
function pmpro_membership_card_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-membership-card.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/pmpro-membership-card/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-membership-card' ) ) . '">' . __( 'Docs', 'pmpro-membership-card' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr(__( 'Visit Customer Support Forum', 'pmpro-membership-card' ) ) . '">' . __( 'Support', 'pmpro-membership-card' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmpro_membership_card_plugin_row_meta', 10, 2 );
