=== Paid Memberships Pro - Membership Card Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, card, membership card, members, badge, logo
Requires at least: 3.5
Tested up to: 5.4
Stable tag: 1.0

Display a printable Membership Card for Paid Memberships Pro members or WP users.

== Description ==

If Paid Memberships Pro is activated, then only members will be able to view the membership card. If not, the card will show for all WP users.

Specify the print size of the cards to display using the shortcode attribute "print_size". Sizes include small (roughly the size of a credit card), medium (slightly larger than credit card size), and large (roughly half sheet size).

== Installation ==

1. Upload the `pmpro-membership-card` directory to the `/wp-content/plugins/` directory of your site.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Create a page and add the [pmpro_membership_card] shortcode to it.
1. (Optional) The page's "featured image" will be added to the membership card.
1. (Optional) Specify the shortcode attribute 'print_size'. Options include: small, medium, large or all. Default is: all. (ex: [pmpro_membership_card print_size="small,medium"])
1. (Optional) Copy the pmpro-membership-card/templates/membership-card.php file to your active theme's directory and edit it to change the card template.

== Frequently Asked Questions ==

= I found a bug in the plugin. =

Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-membership-card/issues

== Changelog ==
= 1.0 =
* Enhancement: Added QR Code functionality and QR data. Attributes: `qr_code="true"` and `qr_data="email"` for example. Please see documentation for more information. Thanks @jarrydlong.
* Enhancement: Added filter to allow custom data to be added: `pmpro_membership_card_after_card`. 
* Enhancement: Added filter for QR code if `"other"` is set in attribute: `pmpro_membership_card_qr_data_other` and `pmpro_membership_card_qr_code_size`.
* Bug Fix/Enhancements: Fixed notices and warnings, general improvements.
* Enhancement: Support Multiple Memberships Per User Add On. Thanks @ronalfy

= .4 =
* BUG: Include private as well as published posts/pages in searches for the page/post containing the member card shortcode
* BUG: Didn't use valid WP_User object when adding u= query parameter.
* ENHANCEMENT: Add class to locate a post/page based on content (i.e. a shortcode)
* ENHANCEMENT: Use built-in shortcode search function has_shortcode()
* ENHANCEMENT: Use pmpro_posts_by_content::get() to search/find. Uses WP_Query & includes support for WP caching

= .3 =
* FEATURE: Added shortcode attribute for print_size. Specify small, medium, large or all. Default is: all.

= .2.1 =
* Fixed template loading bug.

= .2 =
* Generalized. Added readme.

= .1 =
* Initial version.
