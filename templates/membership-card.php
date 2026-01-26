<?php 
/**
 * Available variables coming from the shortcode atts
 *
 * @var string $elements
 * @var string|bool $show_avatar
 * @var string[] $print_sizes
 * @var string $qr_code
 * @var string $qr_data
 */
global $pmpro_membership_card_user;

if ( ! defined( 'PMPRO_VERSION' ) ) {
	return;
}

// Try to ensure we have *some* user object.
if ( empty( $pmpro_membership_card_user ) || ! is_object( $pmpro_membership_card_user ) ) {
	$pmpro_membership_card_user = wp_get_current_user();
}

// Bail if we don't have a card user (common in editor/admin contexts).
if ( empty( $pmpro_membership_card_user ) || empty( $pmpro_membership_card_user->ID ) ) {
	return;
}

// Determine which sizes to display and print.
if ( in_array( 'all', $print_sizes ) ) {
	$print_sizes = array( 'small', 'medium', 'large' );
}

// Safeguard against someone loading this template directly without attributes.
if ( empty( $atts ) || ! is_array( $atts ) ) {
	$atts = array();
}

// Validate boolean variables.
$show_avatar = filter_var( $show_avatar, FILTER_VALIDATE_BOOLEAN );
$qr_code = filter_var( $qr_code, FILTER_VALIDATE_BOOLEAN );

// Build our elements array. If we have an elements attribute on the shortcode, use that and ignore any other legacy attributes.
if ( ! empty( $elements ) ) {
	$elements_array = pmpro_membership_card_prepare_elements_array( $elements );
} else {
	// We need to support the legacy attributes for backwards compatibility.
	$elements = '';
	$elements .= 'display_name;';
	$elements .= 'featured_image;';
	$since = pmpro_getMemberStartDate( $pmpro_membership_card_user->ID );
	if ( ! empty( $since ) ) {
		$elements .= __( 'Member Since', 'pmpro-membership-card' ) . ',membership_startdate;';
	}
	$elements .= __( 'Level', 'pmpro-membership-card' ) . ',membership_name;';
	$elements_array = pmpro_membership_card_prepare_elements_array( $elements );
}
?>
<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro' ) ); ?>">
	<section class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_membership_card_display', 'pmpro_membership_card_display' ) ); ?>">
		<p class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_membership_card-print-button' ) ); ?>">
			<button class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_btn-plain pmpro_btn-print' ) ); ?>" onclick="window.print()">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-printer"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
				<?php echo esc_html__( 'Print or Save as PDF', 'pmpro-membership-card' ); ?>
			</button>
		</p>
		<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card' ) ); ?>">
			<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_membership_card-left' ) ); ?>">
					<?php
						$card_content_left = array();
						if ( $show_avatar ) {
							// Show the avatar if we have one.
							$avatar = pmpro_membership_card_return_user_avatar( $pmpro_membership_card_user );
							if ( ! empty( $avatar ) ) {
								$card_content_left['avatar'] = $avatar;
							}
						}

						if ( $qr_code ) {
							// Show the QR code if enabled.
							$qr_code_data = pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data );
							if ( ! empty( $qr_code_data ) ) {
								$card_content_left['qr_code'] = '<img src="' . esc_url( $qr_code_data ) . '" />';
							}
						}

						/**
						 * Filter the Membership Card left column content.
						 *
						 * @since 2.0
						 * @param array $card_content_left The array of HTML content to show in the left column.
						 * @param WP_User $pmpro_membership_card_user The user object for the membership card.
						 * @param array $atts The shortcode attributes.
						 *
						 * @return array The modified array of HTML content to show in the left column.
						 */
						$card_content_left = apply_filters( 'pmpro_membership_card_left', $card_content_left, $pmpro_membership_card_user, $atts );
						foreach ( $card_content_left as $item => $value ) {
							echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_field pmpro_membership_card_field-' . $item ) ) . '">';
							echo wp_kses_post( $value );
							echo '</div>';
						}
					?>
				</div> <!-- end pmpro_membership_card-left -->
				<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_membership_card-right') ); ?>">
					<?php
						$card_content_right = array();
						foreach ( $elements_array as $element ) {
							$value = pmpro_membership_card_get_display_value( $element[1], $pmpro_membership_card_user );
							if ( ! empty( $value ) || $value === '0' ) {
								// If this is the display_name, we need to wrap it in an h2 tag.
								if ( 'display_name' === $element[1] ) {
									$card_content_right['display_name'] = '<h2 class="' . pmpro_get_element_class( 'pmpro_font-x-large' ) . '">' . $value . '</h2>';
								} else {
									$card_content_right[ $element[1] ] = '';
									// Include a label if we have one.
									if ( ! empty( $element[0] ) ) {
										$card_content_right[ $element[1] ] .= '<span class="'. pmpro_get_element_class( 'pmpro_membership_card_field_label' ) . '">' . esc_html( $element[0] ) . '</span>';
									}
									$card_content_right[ $element[1] ] .= '<span class="' . pmpro_get_element_class( 'pmpro_membership_card_field_data' ) . '">';
									$card_content_right[ $element[1] ] .= $value;
									$card_content_right[ $element[1] ] .= '</span>';
								}
							}
						}

						/**
						 * Filter the Membership Card right column content.
						 *
						 * @since 2.0
						 * @param array $card_content_right The array of HTML content to show in the right column.
						 * @param WP_User $pmpro_membership_card_user The user object for the membership card.
						 * @param array $atts The shortcode attributes.
						 *
						 * @return array The modified array of HTML content to show in the right column.
						 */
						$card_content_right = apply_filters( 'pmpro_membership_card_right', $card_content_right, $pmpro_membership_card_user, $atts );
						foreach ( $card_content_right as $item => $value ) {
							echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_field pmpro_membership_card_field-' . $item ) ) . '">';
							echo wp_kses_post( $value );
							echo '</div>';
						}
					?>
				</div> <!-- end pmpro_membership_card-right -->
			</div> <!-- end pmpro_card_content -->
		</div> <!-- end pmpro_card -->
	</section> <!-- end pmpro_section pmpro_membership_card -->
	<section class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_section pmpro_membership_card_print_sizes', 'pmpro_membership_card' ) ); ?>">
		<?php
			foreach ( $print_sizes as $size ) {
				$card_classes = array( 'pmpro_card', 'pmpro_membership_card-print' );
				$card_classes[] = 'pmpro_membership_card-print-' . $size;
				$card_class = implode( ' ', $card_classes );
				?>
				<div class="<?php echo esc_attr( pmpro_get_element_class( $card_class ) ); ?>">
					<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_card_content' ) ); ?>">
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_membership_card-left' ) ); ?>">
							<?php
								$card_content_left = array();
								if ( $show_avatar ) {
									// Show the avatar if we have one.
									$avatar = pmpro_membership_card_return_user_avatar( $pmpro_membership_card_user );
									if ( ! empty( $avatar ) ) {
										$card_content_left['avatar'] = $avatar;
									}
								}

								if ( $qr_code ) {
									// Show the QR code if enabled.
									$qr_code_data = pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data );
									if ( ! empty( $qr_code_data ) ) {
										$card_content_left['qr_code'] = '<img src="' . esc_url( $qr_code_data ) . '" />';
									}
								}

								/**
								 * Filter the Membership Card left column content.
								 *
								 * @since 2.0
								 * @param array $card_content_left The array of HTML content to show in the left column.
								 * @param WP_User $pmpro_membership_card_user The user object for the membership card.
								 * @param array $atts The shortcode attributes.
								 *
								 * @return array The modified array of HTML content to show in the left column.
								 */
								$card_content_left = apply_filters( 'pmpro_membership_card_left', $card_content_left, $pmpro_membership_card_user, $atts );
								foreach ( $card_content_left as $item => $value ) {
									echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_field pmpro_membership_card_field-' . $item ) ) . '">';
									echo wp_kses_post( $value );
									echo '</div>';
								}
							?>
						</div> <!-- end pmpro_membership_card-left -->
						<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_membership_card-right') ); ?>">
							<?php
								$card_content_right = array();
								foreach ( $elements_array as $element ) {
									$value = pmpro_membership_card_get_display_value( $element[1], $pmpro_membership_card_user );
									if ( ! empty( $value ) || $value === '0' ) {
										// If this is the display_name, we need to wrap it in an h2 tag.
										if ( 'display_name' === $element[1] ) {
											$card_content_right['display_name'] = '<h2 class="' . pmpro_get_element_class( 'pmpro_font-x-large' ) . '">' . $value . '</h2>';
										} else {
											$card_content_right[ $element[1] ] = '';
											// Include a label if we have one.
											if ( ! empty( $element[0] ) ) {
												$card_content_right[ $element[1] ] .= '<span class="'. pmpro_get_element_class( 'pmpro_membership_card_field_label' ) . '">' . esc_html( $element[0] ) . '</span>';
											}
											$card_content_right[ $element[1] ] .= '<span class="' . pmpro_get_element_class( 'pmpro_membership_card_field_data' ) . '">';
											$card_content_right[ $element[1] ] .= $value;
											$card_content_right[ $element[1] ] .= '</span>';
										}
									}
								}

								/**
								 * Filter the Membership Card right column content.
								 *
								 * @since 2.0
								 * @param array $card_content_right The array of HTML content to show in the right column.
								 * @param WP_User $pmpro_membership_card_user The user object for the membership card.
								 * @param array $atts The shortcode attributes.
								 *
								 * @return array The modified array of HTML content to show in the right column.
								 */
								$card_content_right = apply_filters( 'pmpro_membership_card_right', $card_content_right, $pmpro_membership_card_user, $atts );
								foreach ( $card_content_right as $item => $value ) {
									echo '<div class="' . esc_attr( pmpro_get_element_class( 'pmpro_membership_card_field pmpro_membership_card_field-' . $item ) ) . '">';
									echo wp_kses_post( $value );
									echo '</div>';
								}
							?>
						</div> <!-- end pmpro_membership_card-right -->
					</div> <!-- end pmpro_card_content -->
				</div> <!-- end pmpro_card -->
				<?php
			}
		?>
	</section> <!-- end pmpro_section pmpro_membership_card_print -->
	<div class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav' ) ); ?>">
		<span class="<?php echo esc_attr( pmpro_get_element_class( 'pmpro_actions_nav-right' ) ); ?>"><a href="<?php echo esc_url( pmpro_url( "account" ) ) ?>"><?php esc_html_e('View Your Membership Account &rarr;', 'pmpro-membership-card' );?></a></span>
	</div> <!-- end pmpro_actions_nav -->
</div> <!-- end pmpro -->
