<?php
/**
 * Email template for the "resend confirmation" email sent by the Email Confirmation Add On.
 *
 * @since TBD
 */
class PMPro_Email_Template_PMProEC_Resend_Confirmation extends PMPro_Email_Template {

	/**
	 * The user who needs to confirm their email address.
	 *
	 * @var WP_User
	 */
	protected $user;

	/**
	 * The link the user must visit to confirm their email address.
	 *
	 * @var string
	 */
	protected $validation_link;

	/**
	 * Constructor.
	 *
	 * @since TBD
	 *
	 * @param WP_User $user            The user who needs to confirm their email address.
	 * @param string  $validation_link The link the user must visit to confirm their email address.
	 */
	public function __construct( WP_User $user, string $validation_link ) {
		$this->user            = $user;
		$this->validation_link = $validation_link;
	}

	/**
	 * Get the email template slug.
	 *
	 * Kept as "resend_confirmation" so subjects and bodies that sites saved
	 * through the legacy template editor continue to apply.
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
		return esc_html__( 'This email is sent to a member when a confirmation email is resent to them, either from their account page or by an administrator.', 'pmpro-email-confirmation' );
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
		if ( ! class_exists( 'PMPro_Liquid_Renderer' ) ) {
			// Running a version of PMPro before liquid email rendering was available.
			return wp_kses_post( __( '<p>Please click the following link to confirm your email address: !!validation_link!!</p>', 'pmpro-email-confirmation' ) );
		}
		return wp_kses_post( __( '<p>Please click the following link to confirm your email address: {{ validation_link }}</p>', 'pmpro-email-confirmation' ) );
	}

	/**
	 * Get the email template variables for the email paired with a description of the variable.
	 *
	 * @since TBD
	 *
	 * @return array The email template variables for the email (key => value pairs).
	 */
	public static function get_email_template_variables_with_description() {
		if ( ! class_exists( 'PMPro_Liquid_Renderer' ) ) {
			// Running a version of PMPro before liquid email rendering was available.
			return array(
				'!!validation_link!!' => esc_html__( 'The link the member must visit to confirm their email address.', 'pmpro-email-confirmation' ),
				'!!display_name!!'    => esc_html__( 'The display name of the member.', 'pmpro-email-confirmation' ),
				'!!user_login!!'      => esc_html__( 'The username of the member.', 'pmpro-email-confirmation' ),
				'!!user_email!!'      => esc_html__( 'The email address of the member.', 'pmpro-email-confirmation' ),
			);
		}
		return array(
			'{{ validation_link }}' => esc_html__( 'The link the member must visit to confirm their email address.', 'pmpro-email-confirmation' ),
			'{{ display_name }}'    => esc_html__( 'The display name of the member.', 'pmpro-email-confirmation' ),
			'{{ user_login }}'      => esc_html__( 'The username of the member.', 'pmpro-email-confirmation' ),
			'{{ user_email }}'      => esc_html__( 'The email address of the member.', 'pmpro-email-confirmation' ),
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
		return array(
			'validation_link' => $this->validation_link,
			'display_name'    => $this->user->display_name,
			'user_login'      => $this->user->user_login,
			'user_email'      => $this->user->user_email,
		);
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

	/**
	 * Returns the arguments used to send a test email from the email templates settings page.
	 *
	 * @since TBD
	 *
	 * @return array The constructor arguments for a test email.
	 */
	public static function get_test_email_constructor_args() {
		global $current_user;
		$test_link = home_url( '?ui=' . $current_user->ID . '&validate=' . rawurlencode( 'TESTKEY' ) );
		return array( $current_user, $test_link );
	}
}

/**
 * Register the email template with PMPro.
 *
 * @since TBD
 *
 * @param array $email_templates The email templates (template slug => email template class name).
 * @return array The modified email templates array.
 */
function pmproec_register_email_template( $email_templates ) {
	$email_templates['resend_confirmation'] = 'PMPro_Email_Template_PMProEC_Resend_Confirmation';
	return $email_templates;
}
add_filter( 'pmpro_email_templates', 'pmproec_register_email_template' );
