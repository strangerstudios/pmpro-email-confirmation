=== Paid Memberships Pro - Email Confirmation Add On ===

Contributors: strangerstudios, messica
Tags: pmpro, paid memberships pro, email, confirmation, validate, validation, confirm, customize, member, membership, subscription, addon
Requires at least: 3.5
Tested up to: 6.6
Stable tag: 0.8


== Description ==
Addon for Paid Memberships Pro that will include a validation link in the confirmation email sent to users signing up for certain levels on your site. They will still be members, but the pmpro_has_membership_access_filter will return false until they validate their email or an admin validates for them through the dashboard.

== Installation ==
1. Upload the `pmpro-email-confirmation` directory to the `/wp-content/plugins/` directory of your site.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Edit your Paid Memberships Pro levels and check the "require email validation for this level" checkbox.

== Frequently Asked Questions ==
= I found a bug in the plugin. =
  * Please post it in the issues section of GitHub and we'll fix it as soon as we can. Thanks for helping. https://github.com/strangerstudios/pmpro-email-confirmation/issues

= How do I manually validate a user's email address? =
  * Find the user in the Users list or Members List in your WP dashboard, hover over their username in the list and click "Validate User".

= How do I submit a translation for next release =
* Please add your .po and .mo files to the languages folder and create a pull request (https://github.com/strangerstudios/pmpro-email-confirmation/pulls) or reach out to www.paidmembershipspro.com/contact

  
== Changelog ==
= 0.8 - 2024-07-18 =
* ENHANCEMENT: Updated the frontend UI for compatibility with PMPro v3.1. #53 (@dparker1005, @kimcoleman)

= 0.7 - 2023-08-21 =
* ENHANCEMENT: Improved integration with BuddyPress directories when using the PMPro BuddyPress Add On. #46 (@JarrydLong)
* ENHANCEMENT: Updating `<h3>` tags to `<h2>` tags for better accessibility. #51 (@michaelbeil)
* BUG FIX/ENHANCEMENT: Now allowing users without a confirmed email address to cancel their membership. #45 (@dparker1005)
* BUG FIX/ENHANCEMENT: Improved compatibility with PMPro Multiple Memberships Per User Add On. #47 (@dparker1005)
* REFACTOR: No longer pulling checkout level from `$_REQUEST` variable. #49 (@dparker1005)
* REFACTOR: Now using the function `get_option()` instead of `pmpro_getOption()`. #50 (@dwanjuki)

= 0.6 - 2020-09-10 =
* SECURITY: Escaped text on front-end.
* BUG FIX: Fixed issue where manually added members were validated but treated as unvalidated members on the front-end.
* BUG FIX: Fixed grammar (spacing) issue for the email confirmation message. 
* ENHANCEMENT: Added localization functionality, includes Afrikaans and English (UK) translations
* ENHANCEMENT: Added new filters to handle validation redirects for logged-in and logged-out users: `pmproec_logged_in_validate_redirect` and `pmproec_logged_out_validate_redirect` respectively.
* ENHANCEMENT: Added 'Resend Confirmation Email' to the Membership Account page.

= .5 - 2018-09-13 =
* ENHANCEMENT: Added filter pmproec_extra_query_args to allow developers to add extra query args to the email confirmation link.
* ENHANCEMENT: Option added to revalidate user's if they change their email address - if an admin changes a user email user's won't need to validate their email again.
* ENHANCEMENT: Admins and users are able to resend the email confirmation at any point while the user's email is not validated.
* ENHANCEMENT: Resend email confirmation link added to the user's membership account page.
* ENHANCEMENT: Custom HTML email template for resending email confirmation requests.
* ENHANCEMENT: Integrates with Email Templates Admin Editor and uses !!validation_link!! shortcode available in the resend confirmation email template.
* ENHANCEMENT: Adjusted the method used to generate validation keys.
* BUG FIX: Removed PHP Notice error log entry.
* ENHANCEMENT: Support localization, includes master POT file.

= .4 =
* ENHANCEMENT: Now also filtering pmpro_has_membership_level to users who aren't confirmed won't see content hidden via shortcodes or pmpro_hasMembershipLevel().

= .3 =
* If you have !!validation_link!! in your email body, the validation link will be inserted there instead of at the top of the email. (Thanks, Thomas Sjolshagen)

= .2.2 =
* Added pmproec_after_validate_user hook to execute custom code after validation.

= .2.1 =
* Fixed typo in text added to email. (Thanks, Jiks)
* BUG: Fixed login URL destination when users are logged out

= .2 =
* Added a checkbox to the edit level page to check if a level should require email confirmation.
* Added a "Validate User" link to the user admin to force validate a user.

= .1 =
* This is the initial version of the plugin.

