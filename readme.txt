=== Paid Memberships Pro - Email Confirmation Add On ===

Contributors: strangerstudios, messica
Tags: pmpro, paid memberships pro, email, confirmation, validate, validation, confirm, customize, member, membership, subscription, addon
Requires at least: 3.5
Tested up to: 4.3.1
Stable tag: .2.1

== Description ==
Addon for Paid Memberships Pro that will include a validation link in the confirmation email sent to users signing up for certain levels on your site. They will still be members, but the pmpro_has_membership_access_filter will return false until they validate their email or an admin validates for them through the dashboard.

== Installation ==
1. Upload the `pmpro-email-confirmation` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit your Paid Memberships Pro levels and check the "require email validation for this level" checkbox.

== Frequently Asked Questions ==
* I found a bug in the plugin.
  * Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-email-confirmation/issues

* How do I manually validate a user's email address?
  * Find the user in the Users list or Members List in your WP dashboard, hover over their username in the list and click "Validate User".
  
== Changelog ==
= .2.1 =
* Fixed typo in text added to email. (Thanks, Jiks)
* BUG: Fixed login URL destination when users are logged out

= .2 =
* Added a checkbox to the edit level page to check if a level should require email confirmation.
* Added a "Validate User" link to the user admin to force validate a user.

= .1 =
* This is the initial version of the plugin.

