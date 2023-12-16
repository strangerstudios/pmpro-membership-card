<?php 
	global $wpdb, $pmpro_membership_card_user, $pmpro_currency_symbol, $post;
	if( (in_array('small',$print_sizes)) || (in_array('Small',$print_sizes)) || (in_array('all',$print_sizes)) || empty($print_sizes) )
		$print_small = true;
	else
		$print_small = false;
		
	if( (in_array('medium',$print_sizes)) || (in_array('Medium',$print_sizes)) || (in_array('all',$print_sizes)) || empty($print_sizes) )
		$print_medium = true;
	else
		$print_medium = false;
		
	if( (in_array('large',$print_sizes)) || (in_array('Large',$print_sizes)) || (in_array('all',$print_sizes)) || empty($print_sizes) )
		$print_large = true;
	else
		$print_large = false;


?>
<style>
	/* Hide any thumbnail that might be on the page. */
	.page .attachment-post-thumbnail, .page .wp-post-image {display: none;}
	.post .attachment-post-thumbnail, .post .wp-post-image {display: none;}

	/* Page Styles */
	.pmpro_membership_card {
		clear: both;
	}
	.pmpro_membership_card-print {
		background: #FFF;
		border: 1px solid #000000;
		border-radius: 10px;
		margin: 0 0 20px 0;
	}
	.pmpro_membership_card-print h1,
	.pmpro_membership_card-print p {
		margin: 0 0 15px 0;
		padding: 0;
	}
	.pmpro_membership_card-inner {
		padding: 25px;
	}
	img.pmpro_membership_card_image {
		border: none;
		box-shadow: none;
		float: right;
	}
	.pmpro_membership_card-print-md .pmpro_membership_card_image {
		max-width: 200px;
	}
	.pmpro_membership_card-print-md img.pmpro_membership_card_image {
		margin-bottom: 15px;
	}
	.pmpro_membership_card-print-sm,
	.pmpro_membership_card-print-lg {
		display: none;
		visibility: hidden !important;
	}
	.pmpro_clear {
		clear: both;
	}
	.pmpro_membership_card-inner .pmpro_membership_card-after p:last-of-type {
		margin-bottom: 0;
	}
	.pmpro-qr-code-active .pmpro_membership_card-after img {
		height: 100px;
		width: 100px;
	}
	/* Print Styles */
	@media print
	{	
		.page, .page .pmpro_membership_card #nav-below {
			visibility: hidden !important;
		}
		.page .pmpro_membership_card {
			left: 1mm;
			position: fixed;
			top: 1mm;
			visibility: visible !important;
		}
		.pmpro-qr-code-active .pmpro_membership_card-after img {
			height: 40px;
			width: 40px;
		}
		<?php if ( ! empty( $print_small ) ) { ?>
			.pmpro_membership_card-print-sm {
				display: block;
				height: 54mm;
				margin-bottom: 5mm;
				overflow: hidden;
				width: 86mm;
				visibility: visible !important;
			}
			.pmpro_membership_card-print-sm .pmpro_membership_card-inner {
				display: flex;
				align-items: center;
				height: 100%;
				padding: 5mm;
			}
			.pmpro_membership_card-print-sm .pmpro_membership_card-inner * {
				flex: 1;
			}
			.pmpro_membership_card-print-sm img.pmpro_membership_card_image {
				margin-bottom: 5mm;
				max-width: 18mm !important;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-sm h1 {
				font-size: 16pt;
				line-height: 20pt;
				margin: 0;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-sm p {
				font-size: 11pt;
				line-height: 14pt;
				margin: 2mm 0 0 0;
			}
		<?php } ?>		
		<?php if(!empty($print_medium)) { ?>
			.pmpro_membership_card-print-md {
				height: 64mm;
				margin-bottom: 5mm;
				overflow: hidden;
				width: 102mm;
				visibility: visible !important;
			}
			.pmpro_membership_card-print-md .pmpro_membership_card-inner {
				display: flex;
				align-items: center;
				height: 100%;
				padding: 8mm;
			}
			.pmpro_membership_card-print-md .pmpro_membership_card-inner * {
				flex: 1;
			}
			.pmpro_membership_card-print-md img.pmpro_membership_card_image {
				margin-bottom: 5mm;
				max-width: 24mm !important;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-md h1 {
				font-size: 24pt;
				line-height: 30pt;
				margin: 0;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-md p {
				font-size: 12pt;
				line-height: 16pt;
				margin: 2mm 0 0 0;
			}
		<?php } else { ?>
			.pmpro_membership_card-print-md {
				display: none;
			}
		<?php } ?>
		<?php if(!empty($print_large)) { ?>
			.pmpro_membership_card-print-lg {
				display: block;
				height: 115mm;
				overflow: hidden;
				width: 185mm;
				visibility: visible !important;
			}
			.pmpro_membership_card-print-lg .pmpro_membership_card-inner {
				display: flex;
				align-items: center;
				height: 100%;
				padding: 10mm;
			}
			.pmpro_membership_card-print-lg .pmpro_membership_card-inner * {
				flex: 1;
			}
			.pmpro_membership_card-print-lg img.pmpro_membership_card_image {
				margin-bottom: 5mm;
				max-width: 60mm !important;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-lg h1 {
				font-size: 28pt;
				line-height: 32pt;
				margin: 0;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-lg p {
				font-size: 14pt;
				line-height: 18pt;
				margin: 5mm 0 0 0;
			}
			.pmpro_membership_card-print.pmpro_membership_card-print-lg .pmpro-qr-code-active .pmpro_membership_card-after img {
				height: 60px;
				width: 60px;
			}
		<?php } ?>
	}
</style>
<a class="pmpro_a-print" href="javascript:window.print()">Print</a>
<div class="pmpro_membership_card">
	<?php 
		$featured_image = wp_get_attachment_url( get_post_thumbnail_id($post->ID) ); 
		if(function_exists("pmpro_getMemberStartDate") && isset( $pmpro_membership_card_user->ID ) )
			$since = pmpro_getMemberStartDate($pmpro_membership_card_user->ID); // Will get the lowest membership_user ID, which should be the oldest startdate.
		else
			$since = isset( $pmpro_membership_card_user->user_registered ) ? $pmpro_membership_card_user->user_registered : '';
	?>
	<div class="pmpro_membership_card-print pmpro_membership_card-print-sm"<?php if(empty($print_small)) { ?> style="display: none;"<?php } ?>>
		<div class="pmpro_membership_card-inner <?php do_action( 'pmpro_membership_card-extra_classes', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>">
			<div class="pmpro_membership_card-data">
				<h1>
					<?php 
						echo pmpro_membership_card_return_user_name( $pmpro_membership_card_user );
					?>
				</h1>		
				<?php
					if(!empty($featured_image))
					{
					?>
					<img id="pmpro_membership_card_image" class="pmpro_membership_card_image" src="<?php echo esc_attr($featured_image);?>" border="0" />
					<?php
					}
				?>	
				<?php
					if(!empty($since))
					{
					?>
					<p id="pmpro_membership_card_member_since"><strong><?php _e( 'Member Since', 'pmpro-membership-card' ); ?>:</strong> <?php echo date_i18n(get_option("date_format"), strtotime($pmpro_membership_card_user->user_registered));?></p>
					<?php
					}
				?>
				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>

				<p><strong><?php _e("Level", 'pmpro-membership-card');?>:</strong>
				<?php
					pmpro_membership_card_output_levels_for_user( $pmpro_membership_card_user );
				?>
				</p>		
				<p><strong><?php _e("Membership Expires", 'pmpro-membership-card');?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>
				<?php } ?>
				<?php if( has_action( 'pmpro_membership_card_after_card' ) ){ ?>
					<div class="pmpro_membership_card-after">
						<?php do_action( 'pmpro_membership_card_after_card', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>
					</div>
				<?php } ?>
			</div>
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-sm -->
	<div class="pmpro_membership_card-print pmpro_membership_card-print-md">
		<div class="pmpro_membership_card-inner <?php do_action( 'pmpro_membership_card-extra_classes', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>">
			<div class="pmpro_membership_card-data">
				<h1>
					<?php 
						echo pmpro_membership_card_return_user_name( $pmpro_membership_card_user );
					?>
				</h1>		
				<?php
					if(!empty($featured_image))
					{
					?>
					<img id="pmpro_membership_card_image" class="pmpro_membership_card_image" src="<?php echo esc_attr($featured_image);?>" border="0" />
					<?php
					}
				?>	
				<?php
					if(!empty($since))
					{
					?>
					<p id="pmpro_membership_card_member_since"><strong><?php _e( 'Member Since', 'pmpro-membership-card' ); ?>:</strong> <?php echo apply_filters('pmpro_membership_card_since_date', date_i18n( get_option("date_format"), strtotime( $pmpro_membership_card_user->user_registered ) ), $pmpro_membership_card_user );?></p>
					<?php
					}
				?>

				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>

				<p><strong><?php _e("Level", 'pmpro-membership-card');?>:</strong>
				<?php
					pmpro_membership_card_output_levels_for_user( $pmpro_membership_card_user );
				?>
				</p>
				<p><strong><?php _e("Membership Expires", 'pmpro-membership-card');?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>
				<?php } ?>
				<?php if( has_action( 'pmpro_membership_card_after_card' ) ){ ?>
					<div class="pmpro_membership_card-after">
						<?php do_action( 'pmpro_membership_card_after_card', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>
					</div>
				<?php } ?>
			</div>
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-md -->
	<div class="pmpro_membership_card-print pmpro_membership_card-print-lg"<?php if(empty($print_large)) { ?> style="display: none;"<?php } ?>>
		<div class="pmpro_membership_card-inner <?php do_action( 'pmpro_membership_card-extra_classes', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>">
			<div class="pmpro_membership_card-data">
				<h1>
					<?php 
						echo pmpro_membership_card_return_user_name( $pmpro_membership_card_user );
					?>
				</h1>		
				<?php
					if(!empty($featured_image))
					{
					?>
					<img id="pmpro_membership_card_image" class="pmpro_membership_card_image" src="<?php echo esc_attr($featured_image);?>" border="0" />
					<?php
					}
				?>		
				<?php
					if(!empty($since))
					{
					?>
					<p id="pmpro_membership_card_member_since"><strong><?php _e( 'Member Since', 'pmpro-membership-card' ); ?>:</strong> <?php echo date_i18n(get_option("date_format"), strtotime($pmpro_membership_card_user->user_registered));?></p>
					<?php
					}
				?>
					
				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>

				<p><strong><?php _e("Level", 'pmpro-membership-card');?>:</strong>
				<?php
					pmpro_membership_card_output_levels_for_user( $pmpro_membership_card_user );
				?>
				</p>		
				<p><strong><?php _e("Membership Expires", 'pmpro-membership-card');?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>				
				<?php } ?>
				<?php if( has_action( 'pmpro_membership_card_after_card' ) ){ ?>
					<div class="pmpro_membership_card-after">
						<?php do_action( 'pmpro_membership_card_after_card', $pmpro_membership_card_user, $print_sizes, $qr_code, $qr_data ); ?>
					</div>
				<?php } ?>
			</div>
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-lg -->	
	<nav id="nav-below" class="navigation" role="navigation">
		<div class="nav-previous alignleft">
			<?php if(function_exists("pmpro_hasMembershipLevel") && isset( $pmpro_membership_card_user->ID ) && pmpro_hasMembershipLevel(NULL, $pmpro_membership_card_user->ID)) { ?>
				<a href="<?php echo pmpro_url("account")?>"><?php _e('&larr; Return to Your Account', 'pmpro-membership-card');?></a>
			<?php } else { ?>
				<a href="<?php echo home_url()?>">&larr;<?php _e( 'Return to Home', 'pmpro-membership-card' );?></a>
			<?php } ?>
		</div>
	</nav>
</div> <!-- end #pmpro_membership_card -->
	