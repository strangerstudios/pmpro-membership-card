<?php
/*
Plugin Name: Paid Memberships Pro - Membership Card Add On
Plugin URI: http://www.paidmembershipspro.com/wp/pmpro-membership-card/
Description: Display a printable Membership Card for Paid Memberships Pro members or WP users.
Version: 2.1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
Text Domain: pmpro-membership-card
Domain Path: /languages
*/

// Definitions
define( 'PMPRO_MEMBERSHIP_CARD_VERSION', '2.1' );
define( 'PMPRO_MEMBERSHIP_CARD_DIR', dirname( __FILE__ ) );

// Includes
require_once( PMPRO_MEMBERSHIP_CARD_DIR . '/includes/admin.php' );
require_once( PMPRO_MEMBERSHIP_CARD_DIR . '/includes/deprecated.php' );
require_once( PMPRO_MEMBERSHIP_CARD_DIR . '/includes/functions.php' );

/**
 * Register the Membership Card page styles.
 * Loaded only when viewing the Membership Card page
 * See pmpro_membership_card_wp() function.
 */
function pmpro_membership_card_register_styles() {
	wp_register_style(
		'pmpro-membership-card-styles',
		plugins_url( 'css/pmpro-membership-card.css', __FILE__ ),
		array(),
		PMPRO_MEMBERSHIP_CARD_VERSION
	);
	wp_enqueue_style( 'pmpro-membership-card-styles' );
}

/**
 * Load the languages folder for translations.
 */
function pmpro_membership_card_load_textdomain() {
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
	$has_block = ( ! empty( $post ) && has_block( 'pmpro-membership-card-block/pmpro-membership-card-block', $post ) );

	// Return if this is not the Membership Card page or using the shortcode.
	if ( ! $is_membership_card_page && ! $has_shortcode && ! $has_block ) {
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

	// Only enqueue the CSS/Scripts needed if the page has the membership card and is being viewed.
	add_action( 'wp_enqueue_scripts', 'pmpro_membership_card_register_styles' );

}
add_action( 'wp', 'pmpro_membership_card_wp' );

/**
 * Load the membership card shortcode and template.
 */
function pmpro_membership_card_shortcode( $atts, $content=null, $code="" ) {
	// Look for a custom template.
	if ( file_exists( get_stylesheet_directory() . '/membership-card.php' ) ) {
		$template_path = get_stylesheet_directory() . '/membership-card.php';
	} elseif ( file_exists( get_template_directory() . '/membership-card.php' ) ) {
		$template_path = get_template_directory() . '/membership-card.php';
	} else {
		$template_path = plugin_dir_path( __FILE__ ) . 'templates/membership-card.php';
	}

	// Set shortcode attributes.
	extract( shortcode_atts( array(
		'elements' => NULL,
		'print_size' => 'all',
		'qr_code' => 'false',
		'qr_data' => 'ID', // Accepts ID, email and level
		'show_avatar' => 'false'
	), $atts ) );

	/**
	 * Filter the template path for the membership card.
	 *
	 * @since 1.2
	 * @param string $template_path The path to the template file.
	 * @param array $atts The shortcode attributes.
	 * @param string|null $content The shortcode content.
	 * @param string $code The shortcode name.
	 */
	$template_path = apply_filters( 'pmpro_membership_card_template_path', $template_path, $atts, $content, $code );

	// Prepare print sizes.
	$print_sizes = explode(",", $print_size);

	// Load the template.
	ob_start();
	include($template_path);
	$temp_content = ob_get_contents();
	ob_end_clean();
	return $temp_content;
}
add_shortcode( 'pmpro_membership_card', 'pmpro_membership_card_shortcode' );

/**
 * Add the link to view the card in the Member Links section of the Membership Account page
 */
function pmpro_membership_card_member_links_top() {
	$membership_card_user_url = pmpro_membership_card_get_card_user_url( get_current_user_id() );

	if ( empty( $membership_card_user_url ) ) {
		return;
	}
	?>
		<li>
			<a href="<?php echo esc_url( $membership_card_user_url ); ?>">
				<?php esc_html_e( 'View and Print Membership Card', 'pmpro-membership-card' ); ?>
			</a>
		</li>
	<?php
}
add_action( 'pmpro_member_links_top', 'pmpro_membership_card_member_links_top' );

/**
 * Register the Membership Card block.
 * 
 * @since 2.0
 *
 * @return void
 */
function pmpro_membership_card_register_block() {
	register_block_type( __DIR__ . '/blocks/build/pmpro-membership-card-block/block.json' );
}
add_action( 'init', 'pmpro_membership_card_register_block' );
