<?php
/*
Plugin Name: Paid Memberships Pro - Membership Card Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-membership-card/
Description: Display a printable Membership Card for Paid Memberships Pro members or WP users.
Version: 1.2
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmpro-membership-card
Domain Path: /languages
*/

function pmpro_membership_card_load_textdomain(){
	load_plugin_textdomain( 'pmpro-membership-card', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmpro_membership_card_load_textdomain' );

/**
 * Setup membership card user and handle redirects.
 */
function pmpro_membership_card_wp() {
	global $pmpro_pages, $post, $current_user, $pmpro_membership_card_user;

	// Only run on the front end and when PMPro is available.
	if ( is_admin() || ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		return;
	}

	// Must be on the Membership Card page OR the current content has the shortcode.
	$membership_card_page_id = pmpro_membership_card_get_post_id();
	$is_membership_card_page = $membership_card_page_id && is_page( $membership_card_page_id );
	$has_shortcode = ( ! empty( $post ) && has_shortcode( $post->post_content, 'pmpro_membership_card' ) );

	// Return if this is not the Membership Card page or using the shortcode.
	if ( ! $is_membership_card_page && ! $has_shortcode ) {
		return;
	}

	// Get requested user (if any) once.
	$u = isset( $_REQUEST['u'] ) ? (int) $_REQUEST['u'] : 0;

	// Redirect if not logged in.
	if ( ! is_user_logged_in() ) {
		$redirect_to = get_permalink();
		if ( ! empty( $_REQUEST['u'] ) ) {
			$redirect_to = add_query_arg( 'u', intval( $_REQUEST['u'] ), $redirect_to );
		}

		wp_safe_redirect( pmpro_login_url( $redirect_to ) );
		exit;
	}

	// Set the pmpro membership card user object.
	$pmpro_membership_card_user = $u ? get_userdata( $u ) : $current_user;

	// No card user to show? Redirect.
	if ( empty( $pmpro_membership_card_user->ID ) ) {
		if ( ! empty( $pmpro_pages['account'] ) && get_post( $pmpro_pages['account'] ) ) {
			wp_safe_redirect( get_permalink( $pmpro_pages['account'] ) );
			exit;
		}
		wp_safe_redirect( home_url() );
		exit;
	}

	// Check if the current user can view this page.
	$membership_level_capability = current_user_can( apply_filters( 'pmpro_edit_member_capability', 'manage_options' ) );
	if ( ! $membership_level_capability && ( $pmpro_membership_card_user->ID !== $current_user->ID ) ) {
		if ( ! empty( $pmpro_pages['account'] ) && get_post( $pmpro_pages['account'] ) ) {
			wp_safe_redirect( get_permalink( $pmpro_pages['account'] ) );
			exit;
		}
		wp_safe_redirect( home_url() );
		exit;
	}

	// Ok, make sure we have the level data.
	$pmpro_membership_card_user->membership_levels = pmpro_getMembershipLevelsForUser( $pmpro_membership_card_user->ID );

	// If no level and not admin, redirect to account page.
	if ( ! $membership_level_capability && empty( $pmpro_membership_card_user->membership_levels ) ) {
		if ( ! empty( $pmpro_pages['account'] ) && get_post( $pmpro_pages['account'] ) ) {
			wp_safe_redirect( get_permalink( $pmpro_pages['account'] ) );
			exit;
		}
		wp_safe_redirect( home_url() );
		exit;
	}
}
add_action( 'wp', 'pmpro_membership_card_wp' );

/*
	The membership card shortcode/template
*/
function pmpro_membership_card_shortcode($atts, $content=null, $code="")
{		
	/*
		Look for a custom template.
	*/
	if(file_exists(get_stylesheet_directory() . "/membership-card.php")) {
		$template_path = get_stylesheet_directory() . "/membership-card.php";
	} elseif(file_exists(get_template_directory() . "/membership-card.php")) {
		$template_path = get_template_directory() . "/membership-card.php";
	} else {
		$template_path = plugin_dir_path(__FILE__) . "templates/membership-card.php";
	}
	
	$template_path = apply_filters( 'pmpro_membership_card_template_path', $template_path, $atts, $content, $code );

	extract(shortcode_atts(array(
		'print_size' => 'all',
		'qr_code' => 'false',
		'qr_data' => 'ID', // Accepts ID, email and level
		'show_avatar' => 'false'
	), $atts));

	$print_sizes = explode(",", $print_size);
	/*
		Load the Template
	*/
	ob_start();
	include($template_path);
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode('pmpro_membership_card', 'pmpro_membership_card_shortcode');

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
 * Get the post ID for the membership card page.
 *
 * @return int|false The post ID for the membership card page, or false if no post ID is found.
 */
function pmpro_membership_card_get_post_id() {
	// First, check if we have a PMPro page set.
	global $pmpro_pages;
	if ( isset( $pmpro_pages['membership_card'] ) && is_numeric( $pmpro_pages['membership_card'] ) && (int) $pmpro_pages['membership_card'] > 0 ) {
		return $pmpro_pages['membership_card'];
	}

	// Check legacy options.
	$legacy_options = get_option("pmpro_membership_card_post_ids", array());
	if ( ! empty( $legacy_options ) ) {
		// We have legacy options. Choose the first ID and save it in the new option.
		$first_id = reset( $legacy_options );
		update_option( 'pmpro_membership_card_page_id', $first_id );
		delete_option( 'pmpro_membership_card_post_ids' );
		return $first_id;
	}

	// Get the page ID.
	return get_option( 'pmpro_membership_card_page_id' );
}

/**
 * Add the link to view the card in the user profile for admins.
 */
function pmpro_membership_card_profile_fields( $user ) {

	$membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	// Get the membership card post ID.
	$membership_card_post_id = pmpro_membership_card_get_post_id();
	if ( empty( $membership_card_post_id )  ) {
		return;
	}

	// Only show the link if the current user has a membership.
	if ( ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		return;
	}

	$levels = pmpro_getMembershipLevelsForUser( $user->ID );
	if ( empty( $levels ) ) {
		return;
	}

	$membership_card_page_url = get_permalink( $membership_card_post_id );

	// Bail if the card's URL is empty.
	if ( ! $membership_card_page_url ) {
		return;
	}

	$membership_card_user_url = add_query_arg( 'u', $user->ID, $membership_card_page_url );
	?>
	<h2><?php esc_html_e( 'Membership Card', 'pmpro-membership-card' ); ?></h2>
	<p><a href="<?php echo esc_url( $membership_card_user_url );?>"><?php esc_html_e( 'View and Print Membership Card', 'pmpro-membership-card' ); ?></a></p>
	<?php
}
add_action('edit_user_profile', 'pmpro_membership_card_profile_fields');
add_action('show_user_profile', 'pmpro_membership_card_profile_fields');

/**
 * Add the link to view the card in the Member Links section of the Membership Account page
 */
function pmpro_membership_card_member_links_top() {
	// Get the membership card post ID.
	$membership_card_post_id = pmpro_membership_card_get_post_id();
	if ( empty( $membership_card_post_id ) ) {
		return;
	}

	$membership_card_page_url = get_permalink( $membership_card_post_id );
	if ( empty( $membership_card_page_url ) ) {
		return;
	}

	// Only show the link if the current user has a membership.
	if ( ! function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		return;
	}

	$levels = pmpro_getMembershipLevelsForUser( get_current_user_id() );
	if ( empty( $levels ) ) {
		return;
	}

	$membership_card_user_url = add_query_arg( 'u', get_current_user_id(), $membership_card_page_url );
	?>
		<li>
			<a href="<?php echo esc_url( $membership_card_user_url ); ?>">
				<?php esc_html_e( 'View and Print Membership Card', 'pmpro-membership-card' ); ?>
			</a>
		</li>
	<?php
}
add_action( 'pmpro_member_links_top', 'pmpro_membership_card_member_links_top' );

/*
Function to add links to the plugin row meta
*/
function pmpro_membership_card_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-membership-card.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-membership-card/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-membership-card' ) ) . '">' . __( 'Docs', 'pmpro-membership-card' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-membership-card' ) ) . '">' . __( 'Support', 'pmpro-membership-card' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_membership_card_plugin_row_meta', 10, 2);

/**
 * Returns the member's first and last name
 */
function pmpro_membership_card_return_user_name( $pmpro_membership_card_user ){

	if ( ! empty( $pmpro_membership_card_user->user_firstname ) ) {
		$details = $pmpro_membership_card_user->user_firstname. " ". $pmpro_membership_card_user->user_lastname;
	} else {
		$details = ! empty( $pmpro_membership_card_user->display_name ) ? $pmpro_membership_card_user->display_name : '';
	}

	return $details;
}

/**
 * Returns the members most distant expiration date for their memberships.
 *
 * @deprecated 1.2
 */
function pmpro_membership_card_return_end_date( $pmpro_membership_card_user ){
	// Show deprecation message.
	_deprecated_function( __FUNCTION__, '1.2', 'pmpro_membership_card_output_levels_for_user' );

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
 * Output Levels
 *
 * @param object $pmpro_membership_card_user The membership user.
 */
 function pmpro_membership_card_output_levels_for_user( $pmpro_membership_card_user ) {

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
	_deprecated_function( __FUNCTION__, '1.1', 'pmpro_membership_card_output_levels_for_user' );

	if ( ! isset( $pmpro_membership_card_user->ID ) ) {
		return false;
	}

	if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		$levels = pmpro_getMembershipLevelsForUser( $pmpro_membership_card_user->ID );
	} else {
		$levels = pmpro_membership_card_return_level_name( $pmpro_membership_card_user );
	}

	if ( empty( $levels ) ) {
		return _e( 'None', 'pmpro-membership-card' );
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
	_deprecated_function( __FUNCTION__, '1.1', 'pmpro_membership_card_output_levels_for_user' );

	return isset( $pmpro_membership_card_user->membership_level->name ) ? $pmpro_membership_card_user->membership_level->name : __( 'None', 'pmpro-membership-card' );

}

/**
 * Return QR Code Data for QR Code
 */
function pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $option ){

	if( $option == 'ID' ){
		$data = isset( $pmpro_membership_card_user->ID ) ? intval( $pmpro_membership_card_user->ID ) : '';
	} elseif ( $option == 'level' ){
		$data = isset( $pmpro_membership_card_user->membership_levels ) ? implode( ',', wp_list_pluck( $pmpro_membership_card_user->membership_levels, 'id' ) ) : null;
	} elseif ( $option == 'email' ){
		$data = isset( $pmpro_membership_card_user->data->user_email ) ? sanitize_text_field( $pmpro_membership_card_user->data->user_email ) : '';
	} else {
		$data = apply_filters( 'pmpro_membership_card_qr_data_other', $pmpro_membership_card_user, $option );
	}

	if ( ! empty( $data ) && ( is_string( $data ) || is_numeric( $data ) ) ) {
		return "https://api.qrserver.com/v1/create-qr-code/?size=" . apply_filters( 'pmpro_membership_card_qr_code_size', '125x125' ) . "&data=".urlencode( $data );
	} else {
		return;
	}

}

/**
 * Load QR code in membership card
 */
function pmpro_membership_card_qr_code( $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ){

	if( intval( $qr_code ) || $qr_code == 'true' ){
		echo "<p><img src='".pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data )."' /></p>";
	}
}
add_action( 'pmpro_membership_card_after_card', 'pmpro_membership_card_qr_code', 10, 4 );

/**
 * Adds an extra class to the inner container for QR code styling
 */
function pmpro_membership_card_qr_code_class( $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ){
	if( intval( $qr_code ) || $qr_code == 'true' ){
		echo 'pmpro-qr-code-active';
	}
}
add_action( 'pmpro_membership_card-extra_classes', 'pmpro_membership_card_qr_code_class', 10, 4 );
