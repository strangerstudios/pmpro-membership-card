<?php
/**
 * All code pertaining to Apple Wallet logic should be in this file.
 * @since TBD.
 */
use PKPass\PKPass;

// Require the autoloader file.
require( plugin_dir_path( __FILE__ ) . 'pkpass/PKPass.php' );

/**
 * Determine if the device shown is a valid Apple device or browser.
 * @since TBD
 * @return boolean True if the device is an Apple device or browser.
 */
function pmpro_membership_card_should_show_wallet() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    // Check for iOS devices (iPhone or iPad)
    $is_ios_device = strpos( $userAgent, 'iPhone' ) !== false || strpos( $userAgent, 'iPad' ) !== false;

    // Check for macOS devices (Macintosh)
    $is_mac = strpos( $userAgent, 'Macintosh' ) !== false;

    // Check for Safari browser
    $is_safari = strpos( $userAgent, 'Safari' ) !== false;

    return $is_safari || $is_ios_device || $is_mac;
}

/**
 * Function to generate the Apple Wallet Pass
 * @since TBD
 */
function pmpro_membership_card_generate_apple_pass( $user = NULL ) {
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
	

	// Let's show the add to wallet button
	if ( empty( $_REQUEST['pmpro_membership_add_to_wallet'] ) ) {
		ob_start();
		?>
		<div id="pmpro_membership_add_apple_wallet">
			<?php 
			$wallet_query_args = array( 
				'pmpro_membership_add_to_wallet' => 'true',
				'pmpro_membership_wallet_nonce' => wp_create_nonce( 'pmpro_membership_add_to_wallet' )
			);
			?>
			<a href="<?php echo esc_url( add_query_arg( $wallet_query_args ) ); ?>" id="pmpro_membership_card_add_to_apple"><img src="<?php echo plugin_dir_url( __FILE__ ) . 'images/add-to-apple-wallet.svg'; ?>" alt="Add to Apple Pay" /></a>
		</div>
		<?php
		return ob_get_clean();
	} else {

		//If nonce isn't valid, don't run any code and die quietly.
		if ( ! wp_verify_nonce( $_REQUEST['pmpro_membership_wallet_nonce'], 'pmpro_membership_add_to_wallet' ) ) {
			return;
		}

		$certificate_url = 'https://paidmembershipspro.com/Certificates.p12'; /// Make sure this file is uploaded to the right place.
		$certificate_pass = 'pmpro1234';
		// Instantiate the PKPass class.
		$pass = new PKPass( $certificate_url, $certificate_pass );

		// Get all levels as a comma-separated string
		$levels = $user->membership_levels;
		$levels = array_slice( $levels, 0, 3 );
		$level_names = wp_list_pluck( $levels, 'name' );
		sort( $level_names );
		$user_levels = implode( ', ', $level_names );


		/**
		 * Arguments used for Apple Wallet Pass.
		 * @param array $args The arguments for the Apple Wallet Pass.
		 * @param user $user The WordPress user.
		 */
		$args = apply_filters( 'pmpro_membership_card_apple_wallet_args', array(
			'site_name' => get_bloginfo('name'),
			'serial_number' => $user->ID,
			'member_name' => pmpro_membership_card_return_user_name( $user ),
			'background_color' => 'rgb(255,255,255)',
			'membership_name' => $user->membership_level->name,
			'membership_since' => date_i18n( get_option( 'date_format' ), pmpro_getMemberStartDate( $user->ID ) ),
			'membership_enddate' => pmpro_membership_card_return_end_date( $user ),
			'barcode_message' => sprintf( __( '%s - %s - expires %s', 'pmpro-membership-card' ), pmpro_membership_card_return_user_name( $user ), $user->membership_level->name, pmpro_membership_card_return_end_date( $user ) )
			), $user );

		
		$pass->setData('{
		"passTypeIdentifier": "pass.pmpro",
		"teamIdentifier": "RGZMG86H2D",
		"formatVersion": 1,
		"organizationName": "Paid Memberships Pro",
		"serialNumber": "' . esc_html( $args['serial_number'] ) . '",
		"backgroundColor": "' . esc_html( $args['background_color'] ) . '",
		"logoText": "' . esc_html( $args['site_name'] ) . '",
		"description": "' . esc_html__( 'Membership Card', 'pmpro-membership-card' ) . '",
		"generic": {
			"primaryFields": [
				{
					"key" : "member",
					"label" : "Member Name",
					"value" : "' .  esc_html( $args['member_name'] ) . '"
				}
			],
			"secondaryFields": [
				{
					"key" : "membership_level",
					"label" : "Membership Level",
					"value" : "' . esc_html( $args['membership_name'] ) . '"
				},
				{
					"key": "membership_since",
					"label": "Membership Since",
					"value": "' . esc_html( $args['membership_since'] ) . '"
				}
			],
			"auxiliaryFields": [
				{
					"key": "membership_status",
					"label": "Status",
					"value": "' . esc_html__( 'Active', 'pmpro-membership-card' ) . '"
				},
				{
					"key": "membership_enddate",
					"label": "Membership Expires",
					"value": "' . esc_html( $args['membership_enddate'] ) . '"
				}
			]
		},
		"barcode": {
			"message": "' . esc_html( $args['barcode_message'] ) . '",
			"format": "PKBarcodeFormatQR",
			"messageEncoding": "iso-8859-1"
		},
		}');

		/**
		 * Assets for the pass. This allows developers to change or add new assets to the pass.
		 * Note: The icon.png is the only required file. The files have to confirm to the icon.png, logo.png, thumbnail.png as no other file names will work.
		 * @param array $assets The assets for the pass with a key => path to file value.
		 * @since TBD
		 */
		$assets = apply_filters( 'pmpro_membership_card_apple_wallet_assets', array(
			'logo' => plugin_dir_path( __FILE__ ) . 'images/logo.png',
			'thumbnail' => plugin_dir_path( __FILE__ ) . 'images/thumbnail.png',
			'icon' => plugin_dir_path( __FILE__ ) . 'images/icon.png',
			'icon@2x' => plugin_dir_path( __FILE__ ) . 'images/icon@2x.png'
		) );

		// Loop through to add assets to the pass.
		foreach( $assets as $key => $path ) {
			if ( file_exists( $path ) ) {
				$pass->addFile( esc_url( $path ) );
			}
		}

		// Generate the pass and let the browser open it.
		$pass->create(true);
}
}
add_shortcode( 'pmpro_membership_card_show_wallet', 'pmpro_membership_card_generate_apple_pass' );
