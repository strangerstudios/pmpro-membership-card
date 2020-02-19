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
	.pmpro_membership_card {clear: both;}
	.pmpro_membership_card-print {background: #FFF; border: 1px solid #000000; -webkit-border-radius: 10px; -moz-border-radius: 10px; border-radius: 10px; margin: 0 0 20px 0;}
	.pmpro_membership_card-inner {padding: 5%;}
	.pmpro_membership_card-print h1 {font-size: 28px; margin: 0 0 10px 0;}
	.pmpro_membership_card-print p {font-size: 12px; margin: 10px 0 0 0; padding: 0;}
	img.pmpro_membership_card_image {border: none; box-shadow: none; float: right;}
	.pmpro_membership_card-print-md .pmpro_membership_card_image {max-width: 150px;}
	.pmpro_membership_card-print-md img.pmpro_membership_card_image {margin-bottom: 5%;}
	.pmpro_membership_card-print-sm, .pmpro_membership_card-print-lg {display: none; visibility: hidden !important;}
	.pmpro_clear {clear: both;}
	.pmpro_membership_card_inner_left, .pmpro_membership_card_inner_right {
	    width: 49%;
	    display: inline-block;
	}

	/* Print Styles */
	@media print
	{	
		.page, .page .pmpro_membership_card #nav-below {visibility: hidden !important;}
		.page .pmpro_membership_card {left: 2%; position: fixed; top: 2%; visibility: visible !important; width: 96%;}		
		<?php if(!empty($print_small)) { ?>
			.pmpro_membership_card-print-sm {display: block; float: right; visibility: visible !important; width: 42%;}
			.pmpro_membership_card-print-sm img.pmpro_membership_card_image {margin-bottom: 5%; max-width: 110px !important; }
		<?php } ?>		
		<?php if(!empty($print_medium)) { ?>
			.pmpro_membership_card-print-md {float: left; margin-bottom: 10%; visibility: visible !important; width: 48%;}
			.pmpro_membership_card-print-md .pmpro_membership_card-inner {padding: 10% 5%;}
			.pmpro_membership_card-print-md img.pmpro_membership_card_image {max-width: 150px !important; }
		<?php } else { ?>
			.pmpro_membership_card-print-md {display: none; }
		<?php } ?>
		<?php if(!empty($print_large)) { ?>
			.pmpro_membership_card_inner_left, .pmpro_membership_card_inner_right{ float: left; width: 49%; }
			.pmpro_membership_card-print-lg {clear: both; display: block; line-height: 26px; visibility: visible !important; width: 100%;}
			.pmpro_membership_card-print-lg .pmpro_membership_card-inner {padding: 10% 5%;}
			.pmpro_membership_card-print-lg img.pmpro_membership_card_image {max-width: 250px !important;}
			.pmpro_membership_card-print-lg h1 {font-size: 60px; margin: 0 0 50px 0;}
			.pmpro_membership_card-print-lg p {font-size: 22px; margin: 20px 0 0 0;}
		<?php } ?>
	}
</style>
<a class="pmpro_a-print" href="javascript:window.print()">Print</a>
<div class="pmpro_membership_card">
	<?php 
		$featured_image = wp_get_attachment_url( get_post_thumbnail_id($post->ID) ); 
		if(function_exists("pmpro_getMemberStartDate"))
			$since = pmpro_getMemberStartDate($pmpro_membership_card_user->ID);
		else
			$since = $pmpro_membership_card_user->user_registered;
	?>
	<div class="pmpro_membership_card-print pmpro_membership_card-print-md">
		<div class="pmpro_membership_card-inner">
			<div class="pmpro_membership_card_inner_left">
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
					<p><strong><?php _e( 'Member Since', 'pmpro' ); ?>:</strong> <?php echo date_i18n(get_option("date_format"), strtotime($pmpro_membership_card_user->user_registered));?></p>
					<?php
					}
				?>
					
				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>
				<p><strong><?php _e("Level", "pmpro");?>:</strong> <?php echo pmpro_membership_card_return_level_name( $pmpro_membership_card_user ); ?></p>		
				<p><strong><?php _e("Membership Expires", "pmpro");?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>
			</div>
			<div class="pmpro_membership_card_inner_right">
				<?php 
					$qr_data = pmpro_getOption( 'pmpro_mcard_data' );
					$src = pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data );
					echo apply_filters( 'pmpro_mcard_qr_code_image', "<img src='".$src."' />", $pmpro_membership_card_user, $qr_data );
				?>
			</div>
			<?php } ?>				
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-md -->
	<div class="pmpro_membership_card-print pmpro_membership_card-print-sm"<?php if(empty($print_small)) { ?> style="display: none;"<?php } ?>>
		<div class="pmpro_membership_card-inner">
			<div class='pmpro_membership_card_inner_left'>
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
					<p><strong><?php _e( 'Member Since', 'pmpro' ); ?>:</strong> <?php echo date_i18n(get_option("date_format"), strtotime($pmpro_membership_card_user->user_registered));?></p>
					<?php
					}
				?>
					
				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>
				<p><strong><?php _e("Level", "pmpro");?>:</strong> <?php echo pmpro_membership_card_return_level_name( $pmpro_membership_card_user ); ?></p>		
				<p><strong><?php _e("Membership Expires", "pmpro");?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>
			</div>
			<div class="pmpro_membership_card_inner_right">
				<?php 
					$qr_data = pmpro_getOption( 'pmpro_mcard_data' );
					$src = pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data );
					echo apply_filters( 'pmpro_mcard_qr_code_image', "<img src='".$src."' />", $pmpro_membership_card_user, $qr_data );
				?>
			</div>
			<?php } ?>				
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-sm -->
	<div class="pmpro_membership_card-print pmpro_membership_card-print-lg"<?php if(empty($print_large)) { ?> style="display: none;"<?php } ?>>
		<div class="pmpro_membership_card-inner">
			<div class='pmpro_membership_card_inner_left'>
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
					<p><strong><?php _e( 'Member Since', 'pmpro' ); ?>:</strong> <?php echo date_i18n(get_option("date_format"), strtotime($pmpro_membership_card_user->user_registered));?></p>
					<?php
					}
				?>
					
				<?php if(function_exists("pmpro_hasMembershipLevel")) { ?>
				<p><strong><?php _e("Level", "pmpro");?>:</strong> <?php echo pmpro_membership_card_return_level_name( $pmpro_membership_card_user ); ?></p>		
				<p><strong><?php _e("Membership Expires", "pmpro");?>:</strong> 
					<?php 
						echo pmpro_membership_card_return_end_date( $pmpro_membership_card_user );
					?>
				</p>
			</div>
			<div class="pmpro_membership_card_inner_right">
				<?php 
					$qr_data = pmpro_getOption( 'pmpro_mcard_data' );
					$src = pmpro_membership_card_return_qr_code_data( $pmpro_membership_card_user, $qr_data );
					echo apply_filters( 'pmpro_mcard_qr_code_image', "<img src='".$src."' />", $pmpro_membership_card_user, $qr_data );
				?>
			</div>
			<?php } ?>				
		</div><div class="pmpro_clear"></div>
	</div> <!-- end pmpro_membership_card-print-lg -->	
	<nav id="nav-below" class="navigation" role="navigation">
		<div class="nav-previous alignleft">
			<?php if(function_exists("pmpro_hasMembershipLevel") && pmpro_hasMembershipLevel(NULL, $pmpro_membership_card_user->ID)) { ?>
				<a href="<?php echo pmpro_url("account")?>"><?php _e('&larr; Return to Your Account', 'pmpro');?></a>
			<?php } else { ?>
				<a href="<?php echo home_url()?>"><?php _e('&larr; Return to Home', 'pmpro');?></a>
			<?php } ?>
		</div>
	</nav>
</div> <!-- end #pmpro_membership_card -->
	