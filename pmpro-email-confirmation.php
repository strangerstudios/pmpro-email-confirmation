<?php
/*
Plugin Name: Paid Memberships Pro - Email Confirmation Add On
Plugin URI: http://www.paidmembershipspro.com/addons/pmpro-email-confirmation/
Description: Require email confirmation before certain levels are enabled for members.
Version: .3
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/
/*
	Sample use case: You have a free level but want people to use a real email address when signing up.	
*/

/*
	[Deprecated] Set this array to the include the levels which should require email confirmation.

	global $pmpro_email_confirmation_levels;
	$pmpro_email_confirmation_levels = array(6);
	
	Use the checkbox on the edit levels page instead.
*/

/*
	Add checkbox to edit level page to set if level requires email confirmation.
*/
//show the checkbox on the edit level page
function pmproec_pmpro_membership_level_after_other_settings()
{	
	$level_id = intval($_REQUEST['edit']);
	if($level_id > 0)
		$email_confirmation = get_option('pmproec_email_confirmation_' . $level_id);	
	else
		$email_confirmation = false;
?>
<h3 class="topborder">Email Confirmation</h3>
<table>
<tbody class="form-table">
	<tr>
		<th scope="row" valign="top"><label for="email_confirmation"><?php _e('Email Confirmation:', 'pmpro');?></label></th>
		<td>
			<input type="checkbox" id="email_confirmation" name="email_confirmation" value="1" <?php checked($email_confirmation, 1);?> />
			<label for="email_confirmation"><?php _e('Check this to require email validation for this level.', 'pmpro');?></label>
		</td>
	</tr>
</tbody>
</table>
<?php
}
add_action('pmpro_membership_level_after_other_settings', 'pmproec_pmpro_membership_level_after_other_settings');

//save email_confirmation setting when the level is saved/added
function pmproec_pmpro_save_membership_level($level_id)
{
	if(isset($_REQUEST['email_confirmation']))
		$email_confirmation = intval($_REQUEST['email_confirmation']);
	else
		$email_confirmation = 0;
	delete_option('pmproec_email_confirmation_' . $level_id);
	add_option('pmproec_email_confirmation_' . $level_id, $email_confirmation, '', 'no');
}
add_action("pmpro_save_membership_level", "pmproec_pmpro_save_membership_level");

/*
	Functions
*/
//Check if a level id requires an invite code or should generate one
function pmproec_isEmailConfirmationLevel($level_id)
{
	global $pmpro_email_confirmation_levels;

	//get value from options
	$email_confirmation = get_option('pmproec_email_confirmation_' . $level_id, false);	
	
	//check option and global var
	return (!empty($email_confirmation) || !empty($pmpro_email_confirmation_levels) && in_array($level_id, $pmpro_email_confirmation_levels));
}

//generate a key from a user id
function pmproec_getValidationKey($user_id)
{
	$key = md5($user_id . AUTH_KEY . $user_id);
	if(strlen($key) > 16)
		$key = substr($key, 0, 16);
		
	return $key;
}

/*
	Save validation key in user meta after checkout.
*/
function pmproec_pmpro_after_checkout($user_id)
{
	global $pmpro_level;
	
	if(pmproec_isEmailConfirmationLevel($pmpro_level->id))
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

/*
	If a user hasn't validated yet and needs it, don't give them access.
*/
function pmproec_pmpro_has_membership_access_filter($hasaccess, $mypost, $myuser, $post_membership_levels)
{
	//if they don't have access, ignore this
	if(!$hasaccess)
		return $hasaccess;
		
	//if this isn't locked by level, ignore this
	if(empty($post_membership_levels))
		return $hasaccess;
	
	//does this user have a level that requires confirmation?
	$user_membership_level = pmpro_getMembershipLevelForUser($myuser->ID);
	if(pmproec_isEmailConfirmationLevel($user_membership_level->id))
	{
		//if they still have a validation key, they haven't clicked on the validation link yet
		$validation_key = get_user_meta($myuser->ID, "pmpro_email_confirmation_key", true);
		
		if(!empty($validation_key) && $validation_key != "validated")
			$hasaccess = false;
	}
	
	return $hasaccess;
}
add_filter("pmpro_has_membership_access_filter", "pmproec_pmpro_has_membership_access_filter", 10, 4);

/*
	If a user hasn't validated yet, restrict access via shortcodes or pmpro_hasMembershipLevel
*/
function pmproec_pmpro_has_membership_level($haslevel, $user_id, $levels) {
	//if they don't have the level, ignore this
	if(!$haslevel)
		return $haslevel;
		
	//if not checking for a level, ignore this
	if(empty($levels))
		return $haslevel;
	
	//does this user have a level that requires confirmation?
	$user_membership_level = pmpro_getMembershipLevelForUser($user_id);	
	if(pmproec_isEmailConfirmationLevel($user_membership_level->id))
	{
		//if they still have a validation key, they haven't clicked on the validation link yet
		$validation_key = get_user_meta($user_id, "pmpro_email_confirmation_key", true);
				
		if(!empty($validation_key) && $validation_key != "validated")
			$haslevel = false;
	}
		
	return $haslevel;
}
add_action('pmpro_has_membership_level', 'pmproec_pmpro_has_membership_level', 10, 3);

/*
	Add validation lik to confirmation email.
*/
function pmproec_pmpro_email_body($body, $email)
{
	//must be a confirmation email and checkout template
	if(!empty($email->data['membership_id']) && pmproec_isEmailConfirmationLevel($email->data['membership_id']) && strpos($email->template, "checkout") !== false)
	{
		//get user
		$user = get_user_by("login", $email->data['user_login']);

		$validated = $user->pmpro_email_confirmation_key;
		$url = home_url("?ui=" . $user->ID . "&validate=" . $validated);

		//need validation?
		if(empty($validated) || $validated != "validated")
		{
			//use validation_link substitute?
			if(false === stripos($body, "!!validation_link!!"))
			{
				$body = "<p><strong>IMPORTANT! You must follow this link to confirm your email address before your membership is fully activated:<br /><a href='" . $url . "'>" . $url . "</a></strong></p><hr />" . $body;
				$body = str_replace("Your membership account is now active.", "", $body);
			} else
				$body = str_ireplace("!!validation_link!!", $url, $body);
		}
	}
	
	return $body;
}
add_filter("pmpro_email_body", "pmproec_pmpro_email_body", 10, 2);

/*
	Process validation links.
*/
function pmproec_init_validate()
{
	if(!empty($_REQUEST['validate']) && !empty($_REQUEST['ui']))
	{
		$validate = $_REQUEST['validate'];
		$ui = $_REQUEST['ui'];
		$user = get_userdata($ui);
		if($validate == $user->pmpro_email_confirmation_key)
		{
			//validate!
			update_user_meta($user->ID, "pmpro_email_confirmation_key", "validated");

			do_action('pmproec_after_validate_user', $user->ID, $validate);
			
			if(is_user_logged_in())			
				wp_redirect(home_url());
			else
				wp_redirect(wp_login_url());
			
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
	if(!empty($_REQUEST['level']) && pmproec_isEmailConfirmationLevel(intval($_REQUEST['level'])))
	{
		global $current_user;
		if($current_user->pmpro_email_confirmation_key != "validated")
		{
			$message = str_replace("is now active", "will be activated as soon as you confirm your email address. <strong>Important! You must click on the confirmation URL sent to " . $current_user->user_email . " before you gain full access to your membership</strong>", $message);
		}
	}
	
	return $message;
}
add_filter("pmpro_confirmation_message", "pmproec_pmpro_confirmation_message");

/*
Function to add links to the plugin row meta
*/
function pmproec_plugin_row_meta($links, $file) {
	if(strpos($file, 'pmpro-email-confirmation.php') !== false)
	{
		$new_links = array(
			'<a href="' . esc_url('http://paidmembershipspro.com/support/') . '" title="' . esc_attr( __( 'Visit Customer Support Forum', 'pmpro' ) ) . '">' . __( 'Support', 'pmpro' ) . '</a>',
		);
		$links = array_merge($links, $new_links);
	}
	return $links;
}
add_filter('plugin_row_meta', 'pmproec_plugin_row_meta', 10, 2);

/**
 * Add link to the user action links to validate a user
 *
 * Use the pmproec_validate_user_cap filter to change the capability required to see this.
 */	
function pmproec_user_row_actions($actions, $user) {	
	$cap = apply_filters('pmproec_validate_user_cap', 'edit_users');
	if(current_user_can($cap))
	{
		//check if they still have a validation key
		$validation_key = get_user_meta($user->ID, "pmpro_email_confirmation_key", true);		
		if(!empty($validation_key) && $validation_key != "validated")
		{		
			$url = admin_url("users.php?pmproecvalidate=" . $user->ID);
			if(!empty($_REQUEST['s']))
				$url .= "&s=" . esc_attr($_REQUEST['s']);
			if(!empty($_REQUEST['paged']))
				$url .= "&paged=" . intval($_REQUEST['paged']);
			$url = wp_nonce_url($url, 'pmproecvalidate_' . $user->ID);
			$actions[] = '<a href="' . $url . '">Validate User</a>';
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
 *	 
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
			$pmproec_msg = 'Could not reset sessions. User not found.';
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
				$pmproec_msg = 'You do not have permission to validate users.';
				$pmproec_msgt = 'error';
			}
			else
			{				
				//validate!
				update_user_meta($user_id, "pmpro_email_confirmation_key", "validated");
				
				//show success message
				$pmproec_msg = $user->user_email . ' has been validated.';
				$pmproec_msgt = 'updated';
			}
		}						
	}
}
add_action('admin_init', 'pmproec_validate_user');

/**
 * Show any messages generated by PMPro Email Confirmations
 */	
function pmproec_admin_notices() 
{
	global $pmproec_msg, $pmproec_msgt;
	if(!empty($pmproec_msg))
		echo "<div class=\"$pmproec_msgt\"><p>$pmproec_msg</p></div>"; 
}
add_action('admin_notices', 'pmproec_admin_notices');
