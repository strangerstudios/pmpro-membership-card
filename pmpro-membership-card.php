<?php
/*
Plugin Name: Paid Memberships Pro - Membership Card Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-membership-card/
Description: Display a printable Membership Card for Paid Memberships Pro members or WP users.
Version: .4
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

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
	if(file_exists(get_stylesheet_directory() . "/membership-card.php")) 
		$template_path = get_stylesheet_directory() . "/membership-card.php";
	elseif(file_exists(get_template_directory() . "/membership-card.php")) 
		$template_path = get_template_directory() . "/membership-card.php";
	else
		$template_path = plugin_dir_path(__FILE__) . "templates/membership-card.php";
	

	extract(shortcode_atts(array(
		'print_size' => 'all',
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
			if (!in_array( $p->post_status, array('publish', 'private')))
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
		$from_post_content = $posts[0]->ID;

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
function pmpro_membership_card_save_post($post_id)
{
	global $post;

	if ( !isset( $post->post_type) )
		return;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		return;

	if ( wp_is_post_revision( $post_id ) !== false )
		return;

	if ( 'trash' == get_post_status( $post_id ) )
		return;

	$args = array(
		'p' => $post_id,
		'posts_per_page' => 1,
		'post_type' => array( 'post', 'page'),
		'post_status' => array('publish', 'private')
	);

	$posts = pmpro_posts_by_content::get($args);
	$post = isset($posts[0]) ? $posts[0] : null;

	$option = get_option("pmpro_membership_card_post_ids", array());
	
	if(empty($option))
		$option = array();
		
	if(isset($post->post_content) && has_shortcode($post->post_content, "pmpro_membership_card") && in_array($post->post_status,  array('publish', 'private')) )
		$option[$post_id] = $post_id;
	else
		unset($option[$post_id]);
		
	update_option("pmpro_membership_card_post_ids", $option);
}
add_action('save_post', 'pmpro_membership_card_save_post');

/*
	Add the link to view the card in the user profile
*/
function pmpro_membership_card_profile_fields($user)
{
	global $current_user;

	$membership_level_capability = apply_filters("pmpro_edit_member_capability", "manage_options");
	if(!current_user_can($membership_level_capability))
		return false;

	if(!function_exists("pmpro_hasMembershipLevel") || (function_exists("pmpro_hasMembershipLevel") && pmpro_hasMembershipLevel(NULL, $user->ID)))
	{
		?>
		<h3><?php _e("Membership Card", "pmpro"); ?></h3>
		<table class="form-table">
			<tr>
				<th>&nbsp;</th>
				<td><a href="<?php echo add_query_arg('u', $user->ID, get_permalink(pmpro_membership_card_get_post_id()));?>">View and Print Membership Card</a></td>
			</tr>
		</table>
		<?php
	}	
}
add_action('edit_user_profile', 'pmpro_membership_card_profile_fields');
add_action('show_user_profile', 'pmpro_membership_card_profile_fields');

/*
	Add the link to view the card in the Member Links section of the Membership Account page
*/
function pmpro_membership_card_member_links_top()
{
	global $current_user;
	?>
		<li><a href="<?php echo add_query_arg('u', $current_user->ID, get_permalink(pmpro_membership_card_get_post_id()));?>"><?php _e("View and Print Membership Card", "pmpro"); ?></a></li>
	<?php
}
add_action("pmpro_member_links_top", "pmpro_membership_card_member_links_top");

/*
Function to add links to the plugin row meta
*/
function pmpro_membership_card_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-membership-card.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://www.paidmembershipspro.com/add-ons/plugins-on-github/pmpro-membership-card/')  . '" title="' . esc_attr( __( 'View Documentation', 'pmpro' ) ) . '">' . __( 'Docs', 'pmpro' ) . '</a>',
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmpro_membership_card_plugin_row_meta', 10, 2);

/**
 * Adds settings to Advanced tab
 */
function pmpro_membership_card_settings( $fields ){

	$fields[] = array(
		'field_name' 	=> __( 'pmpro_mcard_enable_qr', 'paid-memberships-pro' ),
		'label' 		=> __( 'Enable QR Code on Membership Card', 'paid-memberships-pro' ),
		'field_type' 	=> 'select',
		'options' 		=> array( 'no' => __('No', 'pmpro' ), 'yes' => __('Yes','pmpro') ),
		'description' 	=> __( 'Display a QR Code on the Membership Card', 'paid-memberships-pro' )
	);

	$fields[] = array(
		'field_name' 	=> __( 'pmpro_mcard_data', 'paid-memberships-pro' ),
		'label' 		=> __( 'Data Used in QR Code', 'paid-memberships-pro' ),
		'field_type' 	=> 'select',
		'options' 		=> apply_filters( 'pmpro_mcard_qr_code_data_options', array( 
			'ID' 	=> __('User ID', 'pmpro' ), 
			'level' => __('Membership Level','pmpro'),
			'email' => __('Email Address', 'pmpro'),
			)
		),
		'description' 	=> __( 'What member data should be available when scanning a QR code?', 'paid-memberships-pro' )
	);

	return $fields;

}
add_filter( 'pmpro_custom_advanced_settings', 'pmpro_membership_card_settings' );

/**
 * Returns the member's first and last name
 */
function pmpro_membership_card_return_user_name( $pmpro_membership_card_user ){

	if($pmpro_membership_card_user->user_firstname)
		return $pmpro_membership_card_user->user_firstname. " ". $pmpro_membership_card_user->user_lastname;
	else
		return $pmpro_membership_card_user->display_name;

}

/**
 * Returns the members expiration date for their membership
 */
function pmpro_membership_card_return_end_date( $pmpro_membership_card_user ){

	if(isset( $pmpro_membership_card_user->membership_level->enddate ) && $pmpro_membership_card_user->membership_level->enddate)
		return date_i18n(get_option('date_format'), $pmpro_membership_card_user->membership_level->enddate);
	else
		__('Never', 'pmpro');

}

/**
 * Returns member's level name
 */
function pmpro_membership_card_return_level_name( $pmpro_membership_card_user ){

	return isset( $pmpro_membership_card_user->membership_level->name ) ? $pmpro_membership_card_user->membership_level->name : __('None', 'pmpro');

}

/**
 * If QR Codes are enabled, use this template instead
 */
function pmpro_membership_card_template_override( $template_path, $atts, $content, $code ){

	$qr_code = pmpro_getOption( 'pmpro_mcard_enable_qr' );

	if( $qr_code == 'yes' )
		return plugin_dir_path(__FILE__) . "templates/membership-card-qr-code.php";
	else
		return $template_path;

}
add_filter( 'pmpro_membership_card_template_path', 'pmpro_membership_card_template_override', 10, 4 );

/**
 * Return QR Code Data for QR Code
 */
function pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $option ){

	$data = $pmpro_membership_card_user->ID;

	if( $option == 'ID' ){
		$data = $pmpro_membership_card_user->ID;
	} else if( $option == 'level' ){
		$data = $pmpro_membership_card_user->membership_level->ID;
	} else if( $option == 'email' ){
		$data = $pmpro_membership_card_user->data->user_email;
	} else {
		$data = apply_filters( 'pmpro_mcard_alternative_qr_code_data', $pmpro_membership_card_user, $option );
	}

	return "https://api.qrserver.com/v1/create-qr-code/?size=".apply_filters( 'pmpro_mcard_qr_code_dimensions', '150x150' )."&data=".urlencode( $data );

}