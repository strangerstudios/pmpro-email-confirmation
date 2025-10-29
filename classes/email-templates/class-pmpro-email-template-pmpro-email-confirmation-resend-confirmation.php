<?php

class PMPro_Email_Template_Resend_Confirmation extends PMPro_Email_Template {

	/**
	 * The parent user.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The validation link.
	 *
	 * @var String
	 */
	protected $validation_link;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user The user will receive the email.
	 * @param String $validation_link The validation link.
	 *
	 */
	public function __construct( WP_User $user, String $validation_link ) {
		$this->user = $user;
		$this->validation_link = $validation_link;
	}

	/**
	 * Get the email template slug.
	 *
	 * @since TBD
	 *
	 * @return string The email template slug.
	 */
	public static function get_template_slug() {
		return 'resend_confirmation';
	}

	/**
	 * Get the "nice name" of the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "nice name" of the email template.
	 */
	public static function get_template_name() {
		return esc_html__( 'Email Confirmation - Resend Confirmation', 'pmpro-email-confirmation' );
	}

	/**
	 * Get "help text" to display to the admin when editing the email template.
	 *
	 * @since TBD
	 *
	 * @return string The "help text" to display to the admin when editing the email template.
	 */
	public static function get_template_description() {
		return  esc_html__( 'This email is sent to users when they need to confirm their email address.', 'pmpro-email-confirmation' );
	}

	/**
	 * Get the default subject for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default subject for the email.
	 */
	public static function get_default_subject() {
		return esc_html__( 'Confirm Your Email Address', 'pmpro-email-confirmation' );
	}

	/**
	 * Get the default body content for the email.
	 *
	 * @since TBD
	 *
	 * @return string The default body content for the email.
	 */
	public static function get_default_body() {
		return wp_kses_post( __( '<p>Please click the following link to confirm your email address: !!validation_link!!</p>', 'pmpro-email-confirmation' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		return array(
			// '!!display_name!!' => esc_html__( 'The display name of the user need to confirm the email', 'pmpro-email-confirmation' ),
			// '!!user_login!!' => esc_html__( 'The login name of the user need to confirm the email', 'pmpro-email-confirmation' ),
			// '!!user_email!!' => esc_html__( 'The email address of the user need to confirm the email', 'pmpro-email-confirmation' ),
			'!!validation_link!!' => esc_html__( 'The link to the validation page.', 'pmpro-email-confirmation' ),
		);
	}

	/**
	 * Get the email template variables for the email.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public function get_email_template_variables() {
		$user = $this->user;
		$email_template_variables = array(
			'display_name' => $user->display_name,
			'user_login' => $user->user_login,
			'user_email' => $user->user_email,
			'validation_link' => $this->validation_link,
		);

		return $email_template_variables;
	}

	/**
	 * Get the email address to send the email to.
	 *
	 * @since TBD
	 *
	 * @return string The email address to send the email to.
	 */
	public function get_recipient_email() {
		return $this->user->user_email;
	}

	/**
	 * Get the name of the email recipient.
	 *
	 * @since TBD
	 *
	 * @return string The name of the email recipient.
	 */
	public function get_recipient_name() {
		
		return $this->user->display_name;
	}
}
/**
 * Register the email template.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name)
 * @return array The modified email templates array.
 */
function pmpro_email_template_pmpro_email_confirmation_resend_confirmation( $email_templates ) {
	$email_templates['resend_confirmation'] = 'PMPro_Email_Template_Resend_Confirmation';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmpro_email_template_pmpro_email_confirmation_resend_confirmation' );
