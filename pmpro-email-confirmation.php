<?php
/**
 * Plugin Name: Paid Memberships Pro - Email Confirmation Add On
 * Plugin URI: https://www.paidmembershipspro.com/add-ons/email-confirmation-add-on/
 * Description: Require email confirmation before certain levels are enabled for members.
 * Version: 0.8
 * Author: Paid Memberships Pro
 * Author URI: https://www.paidmembershipspro.com/
 * Text Domain: pmpro-email-confirmation
 * Domain Path: /languages
 */

/**
 * Sample use case: You have a free level but want people to use a real email address when signing up.	
 */

/**
 * [Deprecated] Set this array to the include the levels which should require email confirmation.
 *
 * global $pmpro_email_confirmation_levels;
 * $pmpro_email_confirmation_levels = array(6);
 * Use the checkbox on the edit levels page instead.
 */

function pmproec_load_plugin_text_domain() {
	load_plugin_textdomain( 'pmpro-email-confirmation', false, basename( dirname( __FILE__ ) ) . '/languages' ); 
}
add_action( 'init', 'pmproec_load_plugin_text_domain' ); 

/*
	Add checkbox to edit level page to set if level requires email confirmation.
*/
//show the checkbox on the edit level page
function pmproec_pmpro_membership_level_after_other_settings() {	
	$level_id = intval($_REQUEST['edit']);
	if ( $level_id > 0 ) {
		$email_confirmation = get_option('pmproec_email_confirmation_' . $level_id);
		$reset_email_confirmation = get_option( 'pmproec_reset_email_confirmation_' . $level_id );	
	} else {
		$email_confirmation = false;
		$reset_email_confirmation = false;
	}

?>
<h2 class="topborder"><?php esc_html_e( 'Email Confirmation', 'pmpro-email-confirmation' ); ?></h2>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="email_confirmation"><?php esc_html_e('Email Confirmation', 'pmpro-email-confirmation');?>:</label></th>

		<td>
			<input type="checkbox" id="email_confirmation" name="email_confirmation" value="1" <?php checked($email_confirmation, 1);?> />
			<label for="email_confirmation"><?php esc_html_e('Check this to require email validation for this level', 'pmpro-email-confirmation');?>.</label>
		</td>
	</tr>
	<tr id="pmproec_reset_confirmation" <?php if(!$email_confirmation){ ?> style="display:none;" <?php } ?> >
	<th scope="row" valign="top"><label for="reset_email_confirmation"><?php esc_html_e('Reset Email Confirmation', 'pmpro-email-confirmation');?>:</label></th>
		<td>
			<input type="checkbox" id="reset_email_confirmation" name="reset_email_confirmation" value="1" <?php checked($reset_email_confirmation, 1);?> />
			<label for="reset_email_confirmation"><?php esc_html_e('Check this to require email validation when a user updates their email address', 'pmpro-email-confirmation');?>.</label>
		</td>
	</tr>
</tbody>
</table>

<!-- PMPro Email Confirmation -->
<script type="text/javascript">
	jQuery(document).ready(function(){
		jQuery('#email_confirmation').click(function(){
			jQuery('#pmproec_reset_confirmation').toggle();
		});
			
		
	});
</script>
<?php
}
add_action( 'pmpro_membership_level_after_other_settings', 'pmproec_pmpro_membership_level_after_other_settings' );

//save email_confirmation setting when the level is saved/added
function pmproec_pmpro_save_membership_level( $level_id ) {

	if ( isset( $_REQUEST['email_confirmation'] ) ) {
		$email_confirmation = intval($_REQUEST['email_confirmation']);
	} else {
		$email_confirmation = 0;
	}

	if ( isset( $_REQUEST['reset_email_confirmation'] ) ) {
		$reset_email_confirmation = intval($_REQUEST['reset_email_confirmation']);
	} else {
		$reset_email_confirmation = 0;
	}

	//Failsafe, if user selects reset email but not email confirmation. Set the reset option to 0.
	if ( isset( $_REQUEST['reset_email_confirmation'] ) && !isset( $_REQUEST['email_confirmation'] ) ) {
		$reset_email_confirmation = 0;
	}

	update_option( 'pmproec_email_confirmation_' . $level_id, $email_confirmation );
	update_option( 'pmproec_reset_email_confirmation_' . $level_id, $reset_email_confirmation );

}

add_action( 'pmpro_save_membership_level', 'pmproec_pmpro_save_membership_level' );

/*
	Functions
*/
//Check if a level id requires an invite code or should generate one
function pmproec_isEmailConfirmationLevel( $level_id ) {
	global $pmpro_email_confirmation_levels;

	//get value from options
	$email_confirmation = get_option( 'pmproec_email_confirmation_' . $level_id, false );	
	
	//check option and global var
	return ( ! empty( $email_confirmation ) || ! empty( $pmpro_email_confirmation_levels ) && in_array( $level_id, $pmpro_email_confirmation_levels ) );
}

/**
 * Generate a unique validation key for users.
 * @param int $user_id The user's ID.
 */
function pmproec_getValidationKey( $user_id ) {
	
	$key = md5( $user_id . AUTH_KEY . $user_id . time() );
	
	if ( strlen( $key ) > 16 ) {
		$key = substr( $key, 0, 16 );
	}
		
	return $key;
}

/**
 * Generate a validation key after checkout if the level requires email confirmation.
 * @param int $user_id The user ID of the user that just signed up for a membership.
 */
function pmproec_pmpro_after_checkout($user_id)
{
	global $pmpro_level;
	
	if(!empty($pmpro_level) && pmproec_isEmailConfirmationLevel($pmpro_level->id))
	{
		//already validated?
		$oldkey = get_user_meta($user_id, "pmpro_email_confirmation_key", true);
		if($oldkey != "validated")
		{
			//nope? give them a key
			$key = pmproec_getValidationKey($user_id);
			update_user_meta($user_id, "pmpro_email_confirmation_key", $key);
		}
	}
}
add_action("pmpro_after_checkout", "pmproec_pmpro_after_checkout");

/**
 * Restrict user's access until they validate their email address.
 * Reference: https://www.paidmembershipspro.com/hook/pmpro_has_membership_access_filter/
 */
function pmproec_pmpro_has_membership_access_filter( $hasaccess, $mypost, $myuser, $post_membership_levels ) {

	//if they don't have access, ignore this
	if ( ! $hasaccess ) {
		return $hasaccess;
	}
		
	//if this isn't locked by level, ignore this
	if ( empty( $post_membership_levels ) ) {
		return $hasaccess;
	}
	
	//if they've validated already, continue
	$validation_key = get_user_meta($myuser->ID, "pmpro_email_confirmation_key", true);
	if ( empty( $validation_key ) || $validation_key === 'validated' ) {
		return $hasaccess;
	}
	
	//let's check if one of the required levels requires validation
	$user_membership_levels = pmpro_getMembershipLevelsForUser($myuser->ID);
	foreach( $post_membership_levels as $post_membership_level ) {
		foreach( $user_membership_levels as $user_membership_level ) {
			if( $post_membership_level->id == $user_membership_level->id ) {
				if ( ! pmproec_isEmailConfirmationLevel( $post_membership_level->id ) ) {
					// The user has one of the required levels,
					// which doesn't require email confirmation.
					$hasaccess = true;
					return $hasaccess;
				} else {
					$hasaccess = false;
				}
			}
		}
	}
	
	return $hasaccess;
}
add_filter( "pmpro_has_membership_access_filter", "pmproec_pmpro_has_membership_access_filter", 10, 4 );

/**
 * Restrict access when content is restricted using shortcodes such as [membership].
 * Reference: https://www.paidmembershipspro.com/hook/pmpro_has_membership_level/
 */
function pmproec_pmpro_has_membership_level( $haslevel, $user_id, $levels ) {
	global $pmpro_pages;

	//if they don't have the level, ignore this
	if ( ! $haslevel ) {
		return $haslevel;
	}
		
	//if not checking for a level, ignore this
	if ( empty( $levels ) ) {
		return $haslevel;
	}

	// If the user is trying to cancel, let them.
	if ( is_page( $pmpro_pages['cancel'] ) ) {
		return $haslevel;
	}
	
	//if they've validated already, continue
	$validation_key = get_user_meta($user_id, "pmpro_email_confirmation_key", true);

	if ( empty( $validation_key ) || $validation_key === 'validated' ) {
		return $haslevel;
	}
	
	//let's check if one of the required levels requires validation
	$user_membership_levels = pmpro_getMembershipLevelsForUser($user_id);
	foreach( $levels as $level_id ) {
		foreach( $user_membership_levels as $user_membership_level ) {
			if( $level_id == $user_membership_level->id ) {
				if ( ! pmproec_isEmailConfirmationLevel( $level_id ) ) {
					// The user has one of the levels,
					// which doesn't require email confirmation.
					$haslevel = true;
					return $haslevel;
				} else {
					$haslevel = false;
				}
			}
		}
	}
		
	return $haslevel;
}
add_action( 'pmpro_has_membership_level', 'pmproec_pmpro_has_membership_level', 10, 3 );

/**
 * Add a validation link to the confirmation email sent by Paid Memberships Pro.
 * Reference: https://www.paidmembershipspro.com/hook/pmpro_email_body/
 */
function pmproec_pmpro_email_body( $body, $email ) {
	//must be a confirmation email and checkout template
	if ( ! empty( $email->data['membership_id'] ) && pmproec_isEmailConfirmationLevel( $email->data['membership_id'] ) && strpos( $email->template, "checkout" ) !== false ) {
		//get user
		$user = get_user_by("login", $email->data['user_login']);

		$validated = $user->pmpro_email_confirmation_key;

		$url = home_url( "?ui=" . $user->ID . "&validate=" . $validated );

		//add a filter to allow users to add extra arguments to the validation URL.
		$pmpro_extra_query_args = apply_filters( 'pmproec_extra_query_args', array() );

		//update validation URL to include additional query args.
		$url = ( add_query_arg( $pmpro_extra_query_args, $url ) );

		//need validation?
		if ( empty( $validated ) || $validated != "validated" ) {
			//use validation_link substitute?
			if ( false === stripos( $body, "!!validation_link!!" ) ) {
				$body = "<p><strong>" . esc_html__("IMPORTANT! You must follow this link to confirm your email address before your membership is fully activated", "pmpro-email-confirmation") . ":<br /><a href='" . esc_url( $url ) . "'>" . esc_url( $url ) . "</a></strong></p><hr />" . $body;
				$body = str_replace( esc_html__('Your membership account is now active', 'pmpro-email-confirmation') . '.', "", $body );
			} else
				$body = str_ireplace( "!!validation_link!!", $url, $body );
		}
	}
	
	return $body;
}
add_filter( "pmpro_email_body", "pmproec_pmpro_email_body", 10, 2 );

/**
 * Validate the user's email confirmation.
 */
function pmproec_init_validate() {
	if ( ! empty( $_REQUEST['validate'] ) && ! empty( $_REQUEST['ui'] ) ) {
		$validate = $_REQUEST['validate'];
		$ui = $_REQUEST['ui'];
		$user = get_userdata( $ui );
		if ( $validate == $user->pmpro_email_confirmation_key ) {
			//validate!
			update_user_meta( $user->ID, "pmpro_email_confirmation_key", "validated" );

			do_action( 'pmproec_after_validate_user', $user->ID, $validate );
			
			if ( is_user_logged_in() ) {		
				wp_redirect( apply_filters( 'pmproec_logged_in_validate_redirect', home_url() ) );
			} else {
				wp_redirect( apply_filters( 'pmproec_logged_out_validate_redirect', wp_login_url() ) );
			}
			
			exit;
		}
	}
}
add_action("init", "pmproec_init_validate");

/*
	Update confirmation page to mention validation email if needed.
*/
function pmproec_pmpro_confirmation_message($message)
{
	//must be an email confirmation level
	$level = pmpro_getLevelAtCheckout();
	if ( ! empty( $level->id ) && pmproec_isEmailConfirmationLevel( intval( $level->id ) ) )
	{
		global $current_user;
		if($current_user->pmpro_email_confirmation_key != "validated")
		{
			$message = str_replace( "is now active", esc_html__( "will be activated as soon as you confirm your email address", "pmpro-email-confirmation" ) . ". <strong>" . sprintf( esc_html__( "Important! You must click on the confirmation URL sent to %s before you gain full access to your membership", "pmpro-email-confirmation" ), $current_user->user_email ) . "</strong>", $message );
		}
	}
	
	return $message;
}
add_filter("pmpro_confirmation_message", "pmproec_pmpro_confirmation_message");

/**
 * Add a link on user's account page to resend the confirmation email.
 * Reference: https://www.paidmembershipspro.com/hook/pmpro_member_action_links/
 *
 * @param array $action_links Array of action links for the Membership Account page.
 * @param int   $level_id    ID of the level the action links are being shown for.
 */
function pmproec_add_resend_email_link_to_account( $action_links, $level_id ) {
	global $current_user;

	$user = get_user_by( 'ID', $current_user->ID );
	$validated = $user->pmpro_email_confirmation_key;

	// If none of the user's levels require confirmation, bail.
	$requires_confirmation = false;
	if ( pmproec_isEmailConfirmationLevel( $level_id ) ) {
		$requires_confirmation = true;
	}

	if ( ! $requires_confirmation ) {
		return $action_links;
	}

	// If user is already validated, bail.
	if ( empty( $validated ) || $validated == 'validated' ){
		return $action_links;
	}

	// Add a nonce here.
	$url = add_query_arg( 
		array(
			'resendconfirmation'	=>	1,
		)
	);

	// Add the "Resend Confirmation Email" action link.
	$action_links['resend_confirmation_email'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Resend Confirmation Email', 'pmpro-email-confirmation' ) . '</a>';

	return $action_links;
}
add_action( 'pmpro_member_action_links', 'pmproec_add_resend_email_link_to_account', 10, 2 );

/**
 * Resend validation email for the user.
 */
function pmproec_resend_the_confirmation_email() {
	global $current_user;

	if ( ! empty( $_REQUEST['user_id'] ) && ! empty( $_REQUEST['resendconfirmation'] ) ){
		$user_id = (int) $_REQUEST['user_id'];

		//check if nonce is valid for admin
		check_admin_referer( 'resendconfirmation_' . $user_id );

		pmproec_resend_confirmation_email( $user_id );

	} elseif ( !empty( $_REQUEST['resendconfirmation'] ) ) {

		pmproec_resend_confirmation_email();	
	}
}

add_action( 'init', 'pmproec_resend_the_confirmation_email', 20 );

/**
 * Function to create a confirmation email for a user.
 */
function pmproec_resend_confirmation_email( $user_id = NULL ) {
		global $current_user, $pmproec_msg, $pmproec_msgt;

		//Fallback to current_user if the user's ID is blank.
		if ( empty( $user_id ) ) {
			$user_id = $current_user->ID;
		}

		$user = get_user_by( 'ID', $user_id );
		$validated = $user->pmpro_email_confirmation_key;

		//Do not go any further if user is validated.
		if ( $validated == 'validated' ) {
			return;
		}

		$body = file_get_contents( dirname( __FILE__ ) . "/email/resend_confirmation.html" );

		//filter to allow additional query arguments.
		$pmpro_query_args = apply_filters( 'pmproec_query_args', array() );

		$url = home_url( "?ui=" . $user->ID . "&validate=" . $validated );

		//add query arguments to the URL (on top of existing args)
		$url = ( add_query_arg(
			$pmpro_query_args,
			$url
			));

		if ( empty( $validated ) || $validated != "validated" ) {

			//use validation_link substitute?
			if ( false === stripos( $body, "!!validation_link!!" ) ) {
				$body = "<p><strong>" . esc_html__("IMPORTANT! You must follow this link to confirm your email address before your membership is fully activated", "pmpro-email-confirmation") . ":<br /><a href='" . esc_url( $url ) . "'>" . esc_url( $url ) . "</a></strong></p><hr />" . $body;
			} else {
				$body = str_ireplace( "!!validation_link!!", $url, $body );
			}

			//Setup the new email.
			$pmpro_email = new PMProEmail();
			//Setup the email data
			$pmpro_email->body = $body;
			$pmpro_email->subject = esc_html__( 'Confirm Your Email Address', 'pmpro-email-confirmation' );
			$pmpro_email->email = $user->user_email;
			$pmpro_email->data = array( 
				"display_name"          => $user->display_name,
				"user_login"			=> $user->user_login,
				"user_email"            => $user->user_email,
				"sitename"              => get_option( "blogname" ),
				"siteemail"             => get_option( "pmpro_from_email" ),
				"login_link"            => wp_login_url(),
				"validation_link"		=> $url
			);
			$pmpro_email->template = 'resend_confirmation';
			$pmpro_email->sendEmail();

			$pmproec_msg = esc_html__( 'A confirmation email has been sent to', 'pmpro-email-confirmation' ) . ' ' . $user->user_email;
			$pmproec_msgt = 'updated';
		}

}

/**
 * Function to show Resend Email Notification on Account Page
 * @since 0.6
 */
function pmproec_show_account_email_notification( $content ) {
	global $pmproec_msg, $pmproec_msgt, $pmpro_pages;
	if ( ! empty( $pmproec_msg ) && is_page( $pmpro_pages['account'] ) ) {
		$new_content = "<div class='pmpro_message pmpro_success'><p>" . esc_html( $pmproec_msg ) . "</p></div>";
		$new_content .= $content;
		$content = $new_content;
	}

	return $content;
}
add_filter( 'the_content', 'pmproec_show_account_email_notification' );



/**
 * Add link to the user action links to validate a user.
 * Use the pmproec_validate_user_cap filter to change the capability required to see this.
 */	
function pmproec_user_row_actions( $actions, $user ) {	
	$cap = apply_filters( 'pmproec_validate_user_cap', 'edit_users' );
	if ( current_user_can( $cap ) ) {
		//check if they still have a validation key
		$validation_key = get_user_meta( $user->ID, "pmpro_email_confirmation_key", true );		
		if ( ! empty( $validation_key ) && $validation_key != "validated" )	{		
			$url = admin_url( "users.php?pmproecvalidate=" . $user->ID );

			if ( ! empty( $_REQUEST['s'] ) ) {
				$url .= "&s=" . esc_attr($_REQUEST['s']);
			}

			if ( ! empty( $_REQUEST['paged'] ) ) {
				$url .= "&paged=" . intval($_REQUEST['paged']);
			}

			$url = wp_nonce_url( $url, 'pmproecvalidate_' . $user->ID );
			$actions[] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Validate User', 'pmpro-email-confirmation' ) . '</a>';

			//Add a resend email for admins or users that have $cap pmproec_validate_user_cap.
			$resend_url = admin_url("users.php?user_id=" . $user->ID . "&resendconfirmation=1");
			$resend_url = wp_nonce_url( $resend_url, 'resendconfirmation_'.$user->ID );
			$actions[] = '<a href="' . $resend_url . '">' . esc_html__( "Resend Confirmation Email", "pmpro-email-confirmation" ) . '</a>';
		}
		else
			$actions[] = 'Validated';
	}
	
	return $actions;
}
add_filter('user_row_actions', 'pmproec_user_row_actions', 10, 2);
add_filter('pmpro_memberslist_user_row_actions', 'pmproec_user_row_actions', 10, 2);

/**
 * Manually validate a user. Runs on admin init. Checks for pmproecvalidate and nonce and validates that user. 
 */	
function pmproec_validate_user()
{
	if(!empty($_REQUEST['pmproecvalidate']))
	{
		global $pmproec_msg, $pmproec_msgt;
		
		//get user id
		$user_id = intval($_REQUEST['pmproecvalidate']);
		$user = get_userdata($user_id);
					
		//no user?
		if(empty($user))
		{
			//user not found error
			$pmproec_msg = __( 'Could not reset sessions. User not found.', 'pmpro-email-confirmation' );
			$pmproec_msgt = 'error';
		}			
		else
		{				
			//check nonce
			check_admin_referer( 'pmproecvalidate_'.$user_id);
			
			//check caps
			$cap = apply_filters('pmproec_validate_user_cap', 'edit_users');
			if(!current_user_can($cap))
			{
				//show error message
				$pmproec_msg = esc_html__( 'You do not have permission to validate users.', 'pmpro-email-confirmation' );
				$pmproec_msgt = 'error';
			}
			else
			{				
				//validate!
				update_user_meta($user_id, "pmpro_email_confirmation_key", "validated");
				
				//show success message
				$pmproec_msg = $user->user_email . ' ' . esc_html__( 'has been validated.', 'pmpro-email-confirmation' );
				$pmproec_msgt = 'updated';
			}
		}						
	}
}
add_action('admin_init', 'pmproec_validate_user');

function pmproec_buddypress_sql( $sql_parts, $levels_included ) {

	global $wpdb;

		$sql_parts['JOIN'] .= " LEFT JOIN {$wpdb->usermeta} ecumm 
			ON ecumm.meta_key = 'pmpro_email_confirmation_key' 			
			AND m.user_id = ecumm.user_id ";

		$sql_parts['WHERE'] .= " AND ( ecumm.meta_value = 'validated' OR ecumm.meta_value IS NULL OR ecumm.meta_value = '' ) ";
	
		return $sql_parts;

}
add_filter( 'pmpro_bp_directory_sql_parts', 'pmproec_buddypress_sql', 10, 2 );

/**
 * Filter the header message for the no access message.
 *
 * @since TBD
 *
 * @param string $header The header message for the no access message.
 * @return string The filtered header message for the no access message.
 */
function pmproec_no_access_message_header( $header ) {
	global $current_user;

	// We are running PMPro v3.1+, so make sure that deprecated filters don't run later.
	remove_filter( 'pmpro_non_member_text_filter', 'pmproec_pmpro_text_filter' );
	remove_filter( 'pmpro_not_logged_in_text_filter', 'pmproec_pmpro_text_filter' );

	// If a user does not have a membership level, return default text.
	if ( ! pmpro_hasMembershipLevel() ) {
		return $header;
	}

	// User doesn't have access. To check whether it is because their email is not validated,
	// we can unhook our pmpro_has_membership_access_filter and check if the user would then have access.
	remove_filter( 'pmpro_has_membership_access_filter', 'pmproec_pmpro_has_membership_access_filter', 10 );
	$hasaccess = pmpro_has_membership_access();
	add_filter( 'pmpro_has_membership_access_filter', 'pmproec_pmpro_has_membership_access_filter', 10, 4 );

	if ( ! $hasaccess ) {
		// User still does not have access. Email confirmations is not the preventing factor. Return the original text.
		return $header;
	}

	// User does not have access because their email is not validated.
	// Let's just double check that they are not validated.
	$validated = $current_user->pmpro_email_confirmation_key;
	if ( empty( $validated ) || $validated == 'validated' ) {
		// User is validated. Return the original text.
		return $header;
	}
	
	// User is not validated. Let's overwrite the header message.
	return __( 'Email Confirmation Required', 'pmpro-email-confirmation' );
}
add_filter( 'pmpro_no_access_message_header', 'pmproec_no_access_message_header' ); // PMPro 3.1+

/*
	Filter the message for users without access.
*/
function pmproec_pmpro_text_filter( $text )
{
	global $current_user;
	// User doesn't have access. To check whether it is because their email is not validated,
	// we can unhook our pmpro_has_membership_access_filter and check if the user would then have access.
	remove_filter('pmpro_has_membership_access_filter', 'pmproec_pmpro_has_membership_access_filter', 10);
	$hasaccess = pmpro_has_membership_access();
	add_filter('pmpro_has_membership_access_filter', 'pmproec_pmpro_has_membership_access_filter', 10, 4);

	if ( ! $hasaccess ) {
		// User still does not have access. Email confirmations is not the preventing factor. Return the original text.
		return $text;
	}

	// User does not have access because their email is not validated.
	// Let's just double check that they are not validated.
	$validated = $current_user->pmpro_email_confirmation_key;
	if ( empty( $validated ) || $validated == 'validated' ) {
		// User is validated. Return the original text.
		return $text;
	}
	
	// User is not validated. Let's show them a message.
	return '<p>' . esc_html__('Your membership will be activated as soon as you confirm your email address', 'pmpro-email-confirmation') . '.<strong> ' . sprintf( esc_html__('Important! You must click on the confirmation URL sent to %s before you gain full access to your membership', 'pmpro-email-confirmation'), $current_user->user_email) . '</strong>.</p>';
}
add_filter( 'pmpro_no_access_message_body', 'pmproec_pmpro_text_filter' ); // PMPro 3.1+
add_filter("pmpro_non_member_text_filter", "pmproec_pmpro_text_filter"); // Pre-PMPro 3.1
add_filter("pmpro_not_logged_in_text_filter", "pmproec_pmpro_text_filter"); // Pre-PMPro 3.1

/**
 * Show any messages generated by PMPro Email Confirmations
 */	
function pmproec_admin_notices() {
	global $pmproec_msg, $pmproec_msgt;
	if ( ! empty( $pmproec_msg ) ) {
		echo "<div class='$pmproec_msgt'><p>" . esc_html( $pmproec_msg ) . "</p></div>"; 
	}
}
add_action( 'admin_notices', 'pmproec_admin_notices' );


/**
 * Generate a new key for email confirmation - don't send the same key twice.
 * @since 0.5
 */
function pmproec_generateNewKey( $user_id = NULL ){
	global $current_user;

	if ( empty( $user_id ) ) {
		$user_id = $current_user->ID;
	}

	//remove the old key.
	delete_user_meta( $user_id, 'pmpro_email_confirmation_key' );

	//create a new key and assign it to the user meta.
	$newkey = pmproec_getValidationKey( $user_id );
	update_user_meta( $user_id, "pmpro_email_confirmation_key", $newkey );
}

/**
 * Require users to reconfirm their new email address if set in membership level.
 * @since 0.5
 */
function pmproec_profile_update( $user_id , $old_user_data ) {

	//if an admin edits a user's email, assume that they stay validated.
	if( current_user_can( 'manage_options' ) ){
		return;
	}

	//get levels for the user.
	$levels = pmpro_getMembershipLevelsForUser( $user_id );

	//if user does not have an active membership, don't carry on.
	if( empty( $levels ) ){
		return;
	}

	//get the resend confirmation email settings.
	$resend_confirmation_email = false;
	foreach ( $levels as $level ) {
		if ( ! pmproec_isEmailConfirmationLevel( $level->ID ) ) {
			continue;
		}
		if ( ! empty( get_option( 'pmproec_reset_email_confirmation_' . $level->ID) ) ) {
			$resend_confirmation_email = true;
		}
	}

	//if they don't have these settings, just quit.
	if ( ! $resend_confirmation_email ) {
   		return;
   	}

	$user = get_user_by( 'ID', $user_id );
	//check if email data was changed, generate a key and send the email again.

	if( $old_user_data->user_email != $user->user_email ) {
		pmproec_generateNewKey( $user_id );
		pmproec_resend_confirmation_email( $user_id );
	}

}

add_action( 'profile_update', 'pmproec_profile_update', 10, 2 );

/**
 * Integrate with Email Templates Admin Editor - 
 *
 */
function pmproec_email_templates( $templates ) {

	// Add the resend email confirmation template.
	$templates['resend_confirmation'] = array(
		'subject' => esc_html__( 'Please confirm your email address for', 'pmpro-email-confirmation' ) . ' !!sitename!!',
		'description' => esc_html__( 'Resend Email Confirmation', 'pmpro-email-confirmation' ),
		'body' => file_get_contents( dirname( __FILE__ ) . "/email/resend_confirmation.html" ),
	);


	return $templates;

}
add_filter( 'pmproet_templates', 'pmproec_email_templates', 10, 1 );

function pmproec_add_email_template( $templates, $page_name, $type = 'emails', $where = 'local', $ext = 'html' ) {
	$templates[] = dirname(__FILE__) . "/email/resend_confirmation.html";

	return $templates;
}
add_filter( 'pmpro_email_custom_template_path', 'pmproec_add_email_template', 10, 5 );

/**
 * Function to add links to the plugin row meta
 *
 * @param array  $links Array of links to be shown in plugin meta.
 * @param string $file Filename of the plugin meta is being shown for.
 */
function pmproec_plugin_row_meta( $links, $file ) {
	if ( strpos( $file, 'pmpro-email-confirmation.php' ) !== false ) {
		$new_links = array(
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/add-ons/email-confirmation-add-on/' ) . '" title="' . esc_attr( __( 'View Documentation', 'pmpro-email-confirmation' ) ) . '">' . esc_html__( 'Docs', 'pmpro-email-confirmation' ) . '</a>',
			'<a href="' . esc_url( 'https://www.paidmembershipspro.com/support/' ) . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro-email-confirmation' ) ) . '">' . esc_html__( 'Support', 'pmpro-email-confirmation' ) . '</a>',
		);
		$links = array_merge( $links, $new_links );
	}
	return $links;
}
add_filter( 'plugin_row_meta', 'pmproec_plugin_row_meta', 10, 2 );

