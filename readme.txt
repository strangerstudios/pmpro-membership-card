=== Paid Memberships Pro - Membership Card Add On ===
Contributors: strangerstudios
Tags: paid memberships pro, pmpro, membership, card, membership card, members, badge, logo
Requires at least: 5.0
Tested up to: 6.1
Stable tag: 1.1.2

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
= 1.1.2 - 2023-02-02 =
* ENHANCEMENT: Added compatibility for using the [pmpro_membership_card] in custom post types (@JarrydLong)
* BUG FIX: Fixed an issue when qr_data attribute was set to an invalid option.

= 1.1.1 - 2022-10-27 =
* BUG FIX: Fixed an issue where it would always show "None" as the level even if a user had a valid level.

= 1.1 - 2022-10-19 =
* ENHANCEMENT: Improved support for Multiple Memberships Per User Add On. This now shows multiple levels within the card view. (@dparker1005)
* ENHANCEMENT: Added the ID 'pmpro_membership_card_member_since' to the 'since' element on the membership card. (@kimwhite)
* BUG FIX/ENHANCEMENT: Fixed an issue where the "View and Print Membership Card" link would not generate a URL correctly if a page wasn't set with the [pmpro_membership_card] shortcode. (@andrewlimaza)
* DEPRECATED: Deprecated functions (pmpro_membership_card_get_levels_for_user and pmpro_membership_card_return_level_name) in place of the new function pmpro_membership_card_output_levels_for_user. (@dparker1005)

= 1.0 - 2020-01-04 =
* ENHANCEMENT: Added QR Code functionality and QR data. Attributes: `qr_code="true"` and `qr_data="email"` for example. Please see documentation for more information. Thanks @jarrydlong.
* ENHANCEMENT: Added filter to allow custom data to be added: `pmpro_membership_card_after_card`. 
* ENHANCEMENT: Added filter for QR code if `"other"` is set in attribute: `pmpro_membership_card_qr_data_other` and `pmpro_membership_card_qr_code_size`.
* ENHANCEMENT: Support Multiple Memberships Per User Add On. Thanks @ronalfy
* BUG FIX/ENHANCEMENT: Fixed notices and warnings, general improvements.

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
