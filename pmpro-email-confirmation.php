<?php
/*
Plugin Name: PMPro Email Confirmation
Plugin URI: http://www.paidmembershipspro.com/addons/pmpro-email-confirmation/
Description: Require email confirmation before certain levels are enabled for members.
Version: .1.1
Author: Stranger Studios
Author URI: http://www.strangerstudios.com
*/
/*
	Sample use case: You have a free level but want people to use a real email address when signing up.	
*/

/*
	Set this array to the include the levels which should require email confirmation.
*/
global $pmpro_email_confirmation_levels;
$pmpro_email_confirmation_levels = array(1);

/*
	Functions
*/
//Check if a level id requires an invite code or should generate one
function pmproec_isEmailConfirmationLevel($level_id)
{
	global $pmpro_email_confirmation_levels;
		
	return in_array($level_id, $pmpro_email_confirmation_levels);		
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
	Add validation lik to confirmation email.
*/
function pmproec_pmpro_email_body($body, $email)
{
	//must be a confirmation email and checkout template
	if(pmproec_isEmailConfirmationLevel($email->data['membership_id']) && strpos($email->template, "checkout") !== false)
	{
		//get user
		$user = get_user_by("login", $email->data['user_login']);
		
		//need validation?
		$validated = $user->pmpro_email_confirmation_key;
		if(empty($validated) || $validated != "validated")
		{		
			$url = home_url("?ui=" . $user->ID . "&validate=" . $validated);
			$body = "<p><strong>IMPORTANT! You must follow this link to confirm your email addredss before your membership is fully activated:<br /><a href='" . $url . "'>" . $url . "</a></strong></p><hr />" . $body;
			$body = str_replace("Your membership account is now active.", "", $body);
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
			
			if(is_user_logged_in())			
				wp_redirect(home_url());
			else
				wp_redirect(login_url());
			
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
