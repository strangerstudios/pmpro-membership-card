<?php
/**
 * Deprecated features in the Membership Card Add On.
 */

/**
 * Check for deprecated filters.
 */
function pmpro_membership_card_init_check_for_deprecated_filters() {
	// Deprecated filter name => new filter name (or null if there is no alternative).
	$pmpro_map_deprecated_filters = array(
		'pmpro_membership_card_after_card' => 'pmpro_membership_card_right',
		'pmpro_membership_card-extra_classes' => 'pmpro_element_class',
	);
	
	foreach ( $pmpro_map_deprecated_filters as $old => $new ) {
		if ( has_filter( $old ) ) {
			$message = $new ? sprintf(
				/* translators: 1: Old hook name, 2: New hook name. */
				esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro - Membership Card Add On. Please use the %2$s hook instead.', 'pmpro-membership-card' ),
				$old,
				$new
			) : sprintf(
				/* translators: 1: Old hook name */
				esc_html__( 'The %1$s hook has been deprecated in Paid Memberships Pro - Membership Card Add On and is no longer available.', 'pmpro-membership-card' ),
				$old
			);

			trigger_error( $message );
		}
	
	}
}
add_action( 'init', 'pmpro_membership_card_init_check_for_deprecated_filters', 99 );

/**
 * Returns the members most distant expiration date for their memberships.
 *
 * @deprecated 1.2
 */
function pmpro_membership_card_return_end_date( $pmpro_membership_card_user ){
	// Show deprecation message.
	_deprecated_function( __FUNCTION__, '1.2', 'pmpro_membership_card_get_display_value' );

	// Make sure the user exists.
	if ( empty( $pmpro_membership_card_user ) ) {
		return __( 'Never', 'pmpro-membership-card' );
	}

	$furthest_enddate = null;
	foreach ( $pmpro_membership_card_user->membership_levels as $level ) {
		if ( $furthest_enddate == null || $level->enddate > $furthest_enddate ) {
			$furthest_enddate = $level->enddate;
		}
	}

	if( ! empty( $furthest_enddate ) )
		return date_i18n( get_option('date_format'), $furthest_enddate );
	else
		return __('Never', 'pmpro-membership-card');
}

/**
 * Returns member's active levels.
 *
 * @deprecated 1.1 No longer necessary.
 *
 * @param object $pmpro_membership_card_user The membership user.
 *
 * @return array User Levels.
 */
function pmpro_membership_card_get_levels_for_user( $pmpro_membership_card_user ){
	// Show deprecation message.
	_deprecated_function( __FUNCTION__, '1.1', 'pmpro_membership_card_get_display_value' );

	if ( ! isset( $pmpro_membership_card_user->ID ) ) {
		return false;
	}

	if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		$levels = pmpro_getMembershipLevelsForUser( $pmpro_membership_card_user->ID );
	} else {
		$levels = pmpro_membership_card_return_level_name( $pmpro_membership_card_user );
	}

	if ( empty( $levels ) ) {
		return esc_html__( 'None', 'pmpro-membership-card' );
	} else {
		return $levels;
	}

}

/**
 * Returns member's level name
 *
 * @deprecated 1.1 No longer necessary.
 */
function pmpro_membership_card_return_level_name( $pmpro_membership_card_user ){
	// Show deprecation message.
	_deprecated_function( __FUNCTION__, '1.1', 'pmpro_membership_card_get_display_value' );

	return isset( $pmpro_membership_card_user->membership_level->name ) ? $pmpro_membership_card_user->membership_level->name : __( 'None', 'pmpro-membership-card' );

}

/**
 * Output Levels
 *
 * @param object $pmpro_membership_card_user The membership user.
 */
 function pmpro_membership_card_output_levels_for_user( $pmpro_membership_card_user ) {
	// Show deprecation message.
	_deprecated_function( __FUNCTION__, '2.0', 'pmpro_membership_card_get_display_value' );

	// Make sure the user exists.
	if ( empty( $pmpro_membership_card_user ) ) {
		return esc_html__( 'None', 'pmpro-membership-card' );
	}

	// Get the user's current levels.
	$levels = $pmpro_membership_card_user->membership_levels;
	if ( empty( $levels ) ) {
		return esc_html__( 'None', 'pmpro-membership-card' );
	}

	// Get the level names.
	$level_names = array_map( function( $level, $pmpro_membership_card_user ) {
		// Get the expiration date to maybe show.
		$expiration_date_text = '';
		if ( function_exists( 'pmpro_get_membership_expiration_text' ) ) {
			$expiration_date_text = pmpro_get_membership_expiration_text( $level, $pmpro_membership_card_user, '' );
		} else {
			if ( ! empty( $level->enddate ) ) {
				$expiration_date_text = date_i18n( get_option('date_format'), $level->enddate );
			}
		}

		// Return the level name and expiration date if it exists.
		if ( empty( $expiration_date_text ) ) {
			return $level->name;
		} else {
			/* translators: %s: Expiration date */
			return $level->name . ' <em>(' . sprintf( esc_html__( 'Expires %s', 'pmpro-membership-card' ), esc_html( $expiration_date_text ) ) . ')</em>';
		}
	}, $levels, array( $pmpro_membership_card_user ) );
	sort( $level_names );

	// Output the level names.
	$display = '';
	if ( count( $level_names ) > 1 ) {
		$display = '<ul>';
		$display .= '<li>' . implode( '</li><li>', $level_names ) . '</li>';
		$display .= '</ul>';
	} else {
		$display = current( $level_names );
	}

	return wp_kses_post( apply_filters( 'pmpro_membership_card_mmpu_output', $display, $levels, $pmpro_membership_card_user ) );
}

/**
 * Adds an extra class to the inner container for QR code styling
 */
function pmpro_membership_card_qr_code_class( $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ){
	if( intval( $qr_code ) || $qr_code == 'true' ){
		echo 'pmpro-qr-code-active';
	}
}
//add_action( 'pmpro_membership_card-extra_classes', 'pmpro_membership_card_qr_code_class', 10, 4 );

/**
 * Load QR code in membership card
 */
function pmpro_membership_card_qr_code( $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ){

	if( intval( $qr_code ) || $qr_code == 'true' ){
		echo "<img src='" . esc_url( pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data ) ) . "' />";
	}
}
//add_action( 'pmpro_membership_card_after_card', 'pmpro_membership_card_qr_code', 10, 4 );
