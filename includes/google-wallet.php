<?php
// Include the class so we can build functions off of this yo!
require( plugin_dir_path( __FILE__ ) . 'class.google-wallet.php' );

function pmpro_membership_card_generate_google_wallet_link( $user = NULL ) {
  global $current_user;

	// Don't show in the admin area.
	if ( is_admin() ) {
		return;
	}


	// It's not an Apple device, lets not show it.
	if ( ! pmpro_membership_card_should_show_wallet() ) {
		return;
	}

	// If no user object passed through, let's use the current user.
	if ( empty( $user->ID ) ) {
		$user = $current_user;
	}

	// If the member doens't have any level, let's not show the button.
	if ( ! pmpro_hasMembershipLevel( $user->ID ) ) {
		return;
	}

	// No issuer ID, let's bail.
	if ( ! defined( 'PMPRO_GWALLET_ISSUER_ID' ) ) {
		return;
	}

	$demo         = new PMPro_Google_Wallet_Generic();
	$issuerId     = PMPRO_GWALLET_ISSUER_ID;
	$classSuffix  = 'pmpro_membership_card_gwallet';
	$objectSuffix = 'pmpro_membership_card_gwallet_default';

	// Create the class if it doesn't exist, no need to check if it exists since we will quietly fail.
	$demo->createClass( $issuerId, $classSuffix );

	// Check if the object exists, if not create it or try to update it. Note: This is cached for a bit.
	if ( ! $demo->createObject( $issuerId, $classSuffix, $objectSuffix, $user ) && apply_filters( 'update_wallet_on_load', true ) ) {
		$demo->updateObject( $issuerId, $classSuffix, $objectSuffix, $user );
	}

	// Create the link, then display it.
	$wallet_url = $demo->createJwtNewObjects( $issuerId, $classSuffix, $objectSuffix );

	// Generate add to wallet link.
	return '<a href="' . esc_url( $wallet_url ) . '"><img src="' . esc_url( plugin_dir_url( __FILE__ ) . 'images/add-to-google-wallet.svg' ) . '" alt="Add to Google Wallet"/></a>';

}
add_shortcode( 'my_pmpro_gwallet', 'pmpro_membership_card_generate_google_wallet_link' );

// This is to be used within the template.
function my_pmpro_gwallet_show( $user = NULL ) {

}
