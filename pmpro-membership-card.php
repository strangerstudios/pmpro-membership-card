<?php
/*
Plugin Name: Paid Memberships Pro - Membership Card Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-membership-card/
Description: Display a printable Membership Card for Paid Memberships Pro members or WP users.
Version: 1.1.3
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmpro-membership-card
Domain Path: /languages
*/

function pmpro_membership_card_load_textdomain(){
	load_plugin_textdomain( 'pmpro-membership-card', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmpro_membership_card_load_textdomain' );
/*
	Load on the membership card page to setup vars and possibly redirect away
*/
// loads class to find post based on content (and supports WP caching).
require_once( plugin_dir_path(__FILE__) . 'class.pmpro_posts_by_content.php');

function pmpro_membership_card_wp()
{
	/*
		Check if we're on the membership card page.
	*/
	global $post;
	if(is_admin() || empty($post) || ! has_shortcode($post->post_content, "pmpro_membership_card"))
		return;
	
	/*
		Set the $pmpro_membership_card_user
	*/
	global $pmpro_membership_card_user, $current_user;
	if(!empty($_REQUEST['u']))	
		$pmpro_membership_card_user = get_userdata(intval($_REQUEST['u']));
	else
		$pmpro_membership_card_user = $current_user;
	
	/*
		No user? Die
	*/
	if(empty($pmpro_membership_card_user))
	{
		wp_die("Invalid user.");
	}	
	
	/*
		Make sure we have level data for user.
	*/
	if(function_exists("pmpro_getMembershipLevelForUser"))
		$pmpro_membership_card_user->membership_level = pmpro_getMembershipLevelForUser($pmpro_membership_card_user->ID);
	
	/**
	 * For MMPU compatibility, let's also set $pmpro_membership_card_user->membership_levels.
	 */
	if ( function_exists( 'pmpro_getMembershipLevelsForUser' ) ) {
		$pmpro_membership_card_user->membership_levels = pmpro_getMembershipLevelsForUser( $pmpro_membership_card_user->ID );
	}
	
	/*
		Make sure that the current user can "edit" the user being viewed.
	*/
	if(!current_user_can("edit_user", $pmpro_membership_card_user->ID))
	{
		wp_die("You do not have permission to view the membership card for this user.");
	}
	
	/*
		If PMPro is activated, make sure the current user is a member or admin.
		If not, make sure they are at least logged in.
	*/
	if(function_exists("pmpro_hasMembershipLevel"))
	{
		if(!pmpro_hasMembershipLevel() && !current_user_can("manage_options"))
		{
			wp_redirect(pmpro_url("levels"));
			exit;
		}
	}
	else
	{
		if(!is_user_logged_in())
		{
			wp_redirect(wp_login_url());
			exit;
		}		
	}
}
add_action('wp', 'pmpro_membership_card_wp');

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

/*
	Figure out which post/page id is the membership card page.
*/
function pmpro_membership_card_get_post_id()
{
	//check cache
	global $pmpro_membership_card_get_post_id;
	if(isset($pmpro_membership_card_get_post_id))
		return $pmpro_membership_card_get_post_id;

	//default to false
	$pmpro_membership_card_get_post_id = false;
		
	//look in options
	$from_options = get_option("pmpro_membership_card_post_ids", array());		

	// Make this an array so we can check it.
	if (!is_array($from_options) && !empty($from_options))
		$from_options = array($from_options);

	// check status of the stored posts ID(s) for the membership card(s).
	if (is_array($from_options) && !empty($from_options))
	{
		foreach($from_options as $k => $mc_id)
		{
			$p = get_post($mc_id);

			// remove any entry that isn't a published post/page
			if ( empty( $p ) || !in_array( $p->post_status, array('publish', 'private')))
				unset($from_options[$k]);
		}
	}

	if(!empty($from_options) && is_array($from_options))
		$pmpro_membership_card_get_post_id = end($from_options);
	elseif(!empty($from_options))
		$pmpro_membership_card_get_post_id = $from_options;
	else
	{
		// Search for post based on content
		// returns a single post or page (the first one found).
		$args = array(
			'posts_per_page' => 1,
			'content' => '%pmpro_membership_card%',
			'post_type' => array( 'post', 'page'),
			'post_status' => array('publish', 'private')
		);

		$posts = pmpro_posts_by_content::get( $args );


		if ( ! empty( $posts ) && is_array( $posts ) ) {
			$from_post_content = $posts[0]->ID;
		}
		

		//look for a post with the shortcode in it
		if(!empty($from_post_content))
			$pmpro_membership_card_get_post_id = $from_post_content;
	}

	//didn't find anything
	return $pmpro_membership_card_get_post_id;
}

/*
	Use an option to track pages with the [pmpro_membership_card] shortcode.
*/
function pmpro_membership_card_save_post( $post_id ) {
	global $post;

	if ( !isset( $post->post_type) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) !== false ) {
		return;
	}

	if ( 'trash' == get_post_status( $post_id ) ){
		return;
	}

	$args = array(
		'p' => $post_id,
		'posts_per_page' => 1,
		'post_type' => array_unique( array( 'post', 'page', $post->post_type ) ),
		'post_status' => array('publish', 'private')
	);

	$posts = pmpro_posts_by_content::get($args);
	$post = isset($posts[0]) ? $posts[0] : null;

	$option = get_option("pmpro_membership_card_post_ids", array());
	
	if ( empty( $option ) ) {
		$option = array();
	}
		
	if ( isset( $post->post_content ) && has_shortcode( $post->post_content, "pmpro_membership_card" ) && in_array( $post->post_status,  array( 'publish', 'private' ) ) ) {
		$option[$post_id] = $post_id;
	} else {
		unset( $option[$post_id] );
	}
		
	update_option( "pmpro_membership_card_post_ids", $option );
}
add_action( 'save_post', 'pmpro_membership_card_save_post' );

/*
	Add the link to view the card in the user profile
*/
function pmpro_membership_card_profile_fields( $user ) {

	$membership_level_capability = apply_filters('pmpro_edit_member_capability', 'manage_options');

	if ( ! current_user_can( $membership_level_capability ) ) {
		return false;
	}

	if ( ! function_exists( 'pmpro_hasMembershipLevel' ) || (function_exists( 'pmpro_hasMembershipLevel' ) && pmpro_hasMembershipLevel( NULL, $user->ID ) ) ) {

		$membership_card_page_url = get_permalink( pmpro_membership_card_get_post_id() );

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
}
add_action('edit_user_profile', 'pmpro_membership_card_profile_fields');
add_action('show_user_profile', 'pmpro_membership_card_profile_fields');

/*
	Add the link to view the card in the Member Links section of the Membership Account page
*/
function pmpro_membership_card_member_links_top() {
	global $current_user;

	$membership_card_page_url = get_permalink( pmpro_membership_card_get_post_id() );

	// Bail if the card's URL is empty.
	if ( ! $membership_card_page_url ) {
		return;
	}

	$membership_card_user_url = add_query_arg( 'u', $current_user->ID, $membership_card_page_url );

	?>
		<li><a href="<?php echo esc_url( $membership_card_user_url ); ?>"><?php esc_html_e( 'View and Print Membership Card', 'pmpro-membership-card' ); ?></a></li>
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

	if ( isset( $pmpro_membership_card_user->user_firstname ) ) {
		$details = $pmpro_membership_card_user->user_firstname. " ". $pmpro_membership_card_user->user_lastname;
	} else {
		$details = isset( $pmpro_membership_card_user->display_name ) ? $pmpro_membership_card_user->display_name : '';
	}

	return $details;
}

/**
 * Returns the members most distant expiration date for their memberships.
 */
function pmpro_membership_card_return_end_date( $pmpro_membership_card_user ){

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
		return esc_html_e( 'None', 'pmpro-membership-card' );
	}

	// Get the user's current levels.
	$levels = $pmpro_membership_card_user->membership_levels;
	if ( empty( $levels ) ) {
		return _e( 'None', 'pmpro-membership-card' );
	}

	// Get the level names.
	$level_names = wp_list_pluck( $levels, 'name' );
	sort( $level_names );

	// Output the level names.
	$display = '';
	if ( count( $level_names ) > 1 ) {
		$display = '<ul>';
		$display .= '<li>' . implode( '</li><li>', $level_names ) . '</li>';
		$display .= '</ul>';
	} else {
		$level_name = current( $level_names );
		$display = esc_html( $level_name );
	}

	echo apply_filters( 'pmpro_membership_card_mmpu_output', $display, $levels, $pmpro_membership_card_user );
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
