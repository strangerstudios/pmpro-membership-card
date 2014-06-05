<?php
/*
Plugin Name: PMPro Membership Card
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-membership-card/
Description: Display a printable Membership Card for Paid Memberships Pro members or WP users.
Version: .2.1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/

/*
	Load on the membership card page to setup vars and possibly redirect away
*/
function pmpro_membership_card_wp()
{
	/*
		Check if we're on the membership card page.
	*/
	global $post;
	if(is_admin() || empty($post) || strpos($post->post_content, "[pmpro_membership_card]") === false)
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
		
	if(!empty($from_options) && is_array($from_options))
		$pmpro_membership_card_get_post_id = end($from_options);
	elseif(!empty($from_options))
		$pmpro_membership_card_get_post_id = $from_options;
	else
	{
		global $wpdb;
				
		//look for a post with the shortcode in it
		$from_post_content = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_content LIKE '%[pmpro_membership_card]%' LIMIT 1");			
		if(!empty($from_post_content))
			$pmpro_membership_card_get_post_id = $from_post_content;
	
		//look for a page with slug "membership-card"		
		$from_slug = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status = 'publish' AND post_name = 'membership-card' LIMIT 1");		
		if(!empty($from_slug))
			$pmpro_membership_card_get_post_id = $from_slug;
	}

	//didn't find anything
	return $pmpro_membership_card_get_post_id;
}

/*
	Use an option to track pages with the [pmpro_membership_card] shortcode.
*/
function pmpro_membership_card_save_post($post_id)
{	
	$post = get_post($post_id);		
	$option = get_option("pmpro_membership_card_post_ids", array());
	
	if(empty($option))
		$option = array();
		
	if(strpos($post->post_content, "[pmpro_membership_card]") !== false && $post->post_status = 'publish')
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
				<td><a href="<?php echo get_permalink(pmpro_membership_card_get_post_id()); ?>?u=<?php echo $user->ID; ?>">View and Print Membership Card</a></td>
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
		<li><a href="<?php echo get_permalink(pmpro_membership_card_get_post_id()); ?>?u=<?php echo $user->ID; ?>"><?php _e("View and Print Membership Card", "pmpro"); ?></a></li>
	<?php
}
add_action("pmpro_member_links_top", "pmpro_membership_card_member_links_top");