<?php
/**
 * Functions for the Membership Card Add On.
 */

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
 * Get the URL for the membership card for a specific user.
 *
 * @param int $user_id The user ID.
 */
function pmpro_membership_card_get_card_user_url( $user_id ) {
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

	$levels = pmpro_getMembershipLevelsForUser( $user_id );
	if ( empty( $levels ) ) {
		return;
	}

	$membership_card_user_url = add_query_arg( 'u', $user_id, $membership_card_page_url );

	return $membership_card_user_url;
}

/**
 * Returns the member's first and last name
 */
function pmpro_membership_card_return_user_name( $pmpro_membership_card_user ) {

	if ( ! empty( $pmpro_membership_card_user->user_firstname ) ) {
		$details = $pmpro_membership_card_user->user_firstname. " ". $pmpro_membership_card_user->user_lastname;
	} else {
		$details = ! empty( $pmpro_membership_card_user->display_name ) ? $pmpro_membership_card_user->display_name : '';
	}

	return $details;
}

/**
 * Returns the member's avatar or site default
 */
function pmpro_membership_card_return_user_avatar( $pmpro_membership_card_user ) {
	$avatar_args = apply_filters( 'pmpro_membership_card_avatar_args', 
	array( 
		'size' => 256,
		'default' => '',
		'alt' => esc_attr( pmpro_membership_card_return_user_name( $pmpro_membership_card_user ) . ' ' . __( 'avatar', 'pmpro-membership-card' ) ),
		'args' => array( 
			'class' => 'pmpro_membership_card_avatar'
			)
		) 
	);
	$avatar = get_avatar( $pmpro_membership_card_user->ID, $avatar_args['size'], $avatar_args['default'], $avatar_args['alt'], $avatar_args['args'] );
	return $avatar;
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
 * Prepare the elements attribute of the shortcodes.
 */
function pmpro_membership_card_prepare_elements_array( $elements ) {
	// Initialize the elements array.
	$elements_array = array();

	if ( ! empty( $elements ) ) {
		// Remove a trailing comma or semicolon if it exists.
		$elements = rtrim( $elements, ',;' );

		// Convert line breaks to semicolons (for the block editor).
		if ( strpos( $elements, "\n" ) !== FALSE ) {
			$elements = str_replace( "\n", ';', $elements );
		}

		// Build the elements array.
		$elements = explode( ';', $elements );
		foreach ( $elements as $element ) {
			// Remove spaces from the beginning and end of the element.
			$element = trim( $element );

			// Check if the element is empty.
			if ( empty( $element ) ) {
				continue;
			}

			if ( str_contains( $element, ',' ) ) {
				// If there is a comma, then we know it has label/field pair.
				$elements_array[] = array_map( 'trim', explode( ',', $element ) );
			} else {
				// Otherwise we have just the field with no label.
				$elements_array[] = array( '', trim( $element ) );
			}
		}
	}

	return $elements_array;
}

/**
 * Get the value of a specific element from a string of HTML.
 */
function pmpro_membership_card_get_display_value( $element, $pmpro_membership_card_user ) {
	global $post;

	// Initialize the value.
	$value = '';

	// Is this a user field?
	if ( class_exists( 'PMPro_Field_Group' ) ) {
		$user_field = PMPro_Field_Group::get_field( $element );
	} else {
		$user_field = pmpro_get_user_field( $element );
	}

	// Yes, this is a user field. Check that the user has the required level for this field.
	if ( ! empty( $user_field ) ) {
		if ( ! empty( $user_field->levels ) && ! pmpro_hasMembershipLevel( $user_field->levels, $pmpro_membership_card_user->ID ) ) {
			// The user does not have the required level for this field.
			$value = '';
		} else {
			$value = $user_field->displayValue( $pmpro_membership_card_user->{$element}, false );
		}
	} else {
		// Let's try to get the value from other places and format it for return.

		// Get a list of fields related to the user's level.
		$pmpro_level_fields = array(
			'membership_name',
			'membership_startdate',
			'membership_enddate',
		);

		// Get a list of fields that should be formatted as dates.
		$date_fields = array(
			'membership_startdate',
			'membership_enddate',
			'user_registered',
		);

		// Check if the $element is a PMPro level field.
		if ( in_array( $element, $pmpro_level_fields ) ) {
			$levels = $pmpro_membership_card_user->membership_levels;

			// Get the names of the levels to display.
			if ( empty( $levels ) ) {
				$levels = array( esc_html__( 'None', 'pmpro-membership-card' ) );
			} else {
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
						return '<span>' . $level->name . '</span>';
					} else {
						return '<span>' . $level->name . ' <em>(' . sprintf( esc_html__( 'Expires %s', 'pmpro-membership-card' ), esc_html( $expiration_date_text ) ) . ')</em></span>';
					}
				}, $levels, array( $pmpro_membership_card_user ) );
				sort( $level_names );
				$pmpro_membership_card_user->membership_level_names = implode( ' ', $level_names );
			}

			// Calculate the oldest start date and the soonest end date, if levels are available.
			$start_dates = array_column( $levels, 'startdate' );
			$end_dates   = array_column( $levels, 'enddate' );

			$startdate = ! empty( $start_dates ) ? min( $start_dates ) : null;

			// Only use real end dates (ignore null/empty = never expires).
			$end_dates = array_filter( $end_dates, function( $enddate ) {
				return ! empty( $enddate );
			} );

			$enddate = ! empty( $end_dates ) ? min( $end_dates ) : null;
		}

		// Additional formatting and special cases.
		switch ( $element ) {
			case 'display_name':
				$value = pmpro_membership_card_return_user_name( $pmpro_membership_card_user );
				break;
			case 'site_logo':
				$image_url =  wp_get_attachment_url( get_theme_mod( 'custom_logo' ) );
				$value = '<img class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_image' ) ) . '" src="' . esc_attr( $image_url ) . '" border="0" />';
				break;
			case 'featured_image':
				$image_url = wp_get_attachment_url( get_post_thumbnail_id( $post->ID ) );
				$value = ! empty( $image_url ) ? '<img class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_image' ) ) . '" src="' . esc_attr( $image_url ) . '" border="0" />' : '';
				break;
			case 'membership_name':
				$value = $pmpro_membership_card_user->membership_level_names;
				break;
			case 'membership_startdate':
				$value = $startdate;
				break;
			case 'membership_enddate':
				$value = $enddate;
				break;
			case 'pmpro_shipping_address':
			case 'pmpro_mailing_address':
				$value = pmpro_formatAddress(
					trim( $pmpro_membership_card_user->pmpro_sfirstname . ' ' . $pmpro_membership_card_user->pmpro_slastname ),
					$pmpro_membership_card_user->pmpro_saddress1,
					$pmpro_membership_card_user->pmpro_saddress2,
					$pmpro_membership_card_user->pmpro_scity,
					$pmpro_membership_card_user->pmpro_sstate,
					$pmpro_membership_card_user->pmpro_szipcode,
					$pmpro_membership_card_user->pmpro_scountry,
					$pmpro_membership_card_user->pmpro_sphone
				);
				break;
		}

		// If we still do not have a value, try user or usermeta. Format using User Fields display method.
		if ( empty( $value ) ) {
			if ( isset( $pmpro_membership_card_user->data ) && in_array( $element, array_keys( get_object_vars( $pmpro_membership_card_user->data ) ) ) ) {
				// This is a user table field. Only allow certain fields.
				$user_column_fields = array(
					'user_login',
					'user_email',
					'user_url',
					'user_registered',
					'display_name',
					'ID',
				);

				if ( in_array( $element, $user_column_fields ) ) {
					$value = $pmpro_membership_card_user->data->$element;
				}
			} else {
				// This is a usermeta field.
				$value = $pmpro_membership_card_user->$element;
			}

			if ( ! empty( $value ) ) {
				// Try to guess the field type, default to text.
				$field_type = 'text';

				// Special handling for arrays.
				if ( is_array( $value ) ) {
					if ( isset( $value['filename'] ) ) {
						$field_type = 'file';
					} else {
						$field_type = 'multiselect';
					}
				}

				// Create a new PMPro_Field object.
				$user_field = new PMPro_Field( $element, $field_type );
				$value = $user_field->displayValue( $value, false );
			}
		}

		// Format the date fields.
		if ( in_array( $element, $date_fields ) && ! empty( $value ) ) {
			$value = date_i18n( get_option('date_format'), $value );
		}

	}

	/**
	 * Filter the value of a specific element from a string of HTML.
	 *
	 * @since 2.0
	 * @param string $value The value of the element.
	 * @param string $element The element to get the value for.
	 * @param object $pmpro_membership_card_user The user object.
	 *
	 * @return string The value of the element.
	 */
	$value = apply_filters( 'pmpro_membership_card_get_display_value', $value, $element, $pmpro_membership_card_user );

	return $value;
}
