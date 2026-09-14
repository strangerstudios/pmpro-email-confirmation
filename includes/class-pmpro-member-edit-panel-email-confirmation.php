<?php
/**
 * Email Confirmation panel for Edit Member screen
 *
 * @since TBD
 */
class PMProEC_Member_Edit_Panel_Email_Confirmation extends PMPro_Member_Edit_Panel {
	/**
	 * Set up the panel.
	 */
	public function __construct() {
		$this->slug = 'email-confirmation';
		$this->title = __( 'Email Confirmation', 'pmpro-email-confirmation' );
		$this->submit_text = __( 'Save Changes', 'pmpro-email-confirmation' );
	}

	/**
	 * Display the panel contents.
	 */
	protected function display_panel_contents() {
		$user = self::get_user();

		// Get confirmation key
		$validation_key = get_user_meta( $user->ID, 'pmpro_email_confirmation_key', true );

		// Determine status. Uses core's .pmpro_tag classes so the badges match the rest of PMPro's admin.
		if ( empty( $validation_key ) ) {
			$status = __( 'Not Required', 'pmpro-email-confirmation' );
			$status_class = 'pmpro_tag pmpro_tag-info';
		} elseif ( $validation_key === 'validated' ) {
			$status = __( 'Confirmed', 'pmpro-email-confirmation' );
			$status_class = 'pmpro_tag pmpro_tag-has_icon pmpro_tag-success';
		} else {
			$status = __( 'Pending', 'pmpro-email-confirmation' );
			$status_class = 'pmpro_tag pmpro_tag-has_icon pmpro_tag-alert';
		}

		// Get member's levels
		$member_levels = pmpro_getMembershipLevelsForUser( $user->ID );
		$confirmation_required_levels = array();
		$confirmation_not_required_levels = array();

		if ( ! empty( $member_levels ) ) {
			foreach ( $member_levels as $level ) {
				if ( pmproec_isEmailConfirmationLevel( $level->id ) ) {
					$confirmation_required_levels[] = $level;
				} else {
					$confirmation_not_required_levels[] = $level;
				}
			}
		}

		?>
		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Status', 'pmpro-email-confirmation' ); ?></label>
					</th>
					<td>
						<span class="<?php echo esc_attr( $status_class ); ?>">
							<?php echo esc_html( $status ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Email Address', 'pmpro-email-confirmation' ); ?></label>
					</th>
					<td>
						<?php echo esc_html( $user->user_email ); ?>
					</td>
				</tr>
				<?php if ( ! empty( $confirmation_required_levels ) ) { ?>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Levels Requiring Confirmation', 'pmpro-email-confirmation' ); ?></label>
						</th>
						<td>
							<ul class="ul-disc">
								<?php foreach ( $confirmation_required_levels as $level ) { ?>
									<li><?php echo esc_html( $level->name ); ?></li>
								<?php } ?>
							</ul>
						</td>
					</tr>
				<?php } ?>
				<?php if ( ! empty( $confirmation_not_required_levels ) ) { ?>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Levels Not Requiring Confirmation', 'pmpro-email-confirmation' ); ?></label>
						</th>
						<td>
							<ul class="ul-disc">
								<?php foreach ( $confirmation_not_required_levels as $level ) { ?>
									<li><?php echo esc_html( $level->name ); ?></li>
								<?php } ?>
							</ul>
						</td>
					</tr>
				<?php } ?>
				<?php if ( ! empty( $confirmation_required_levels ) ) { ?>
					<tr>
						<th scope="row">
							<label><?php esc_html_e( 'Actions', 'pmpro-email-confirmation' ); ?></label>
						</th>
						<td>
							<?php if ( $validation_key !== 'validated' && ! empty( $validation_key ) ) { ?>
								<input type="submit" name="pmproec_validate_now" class="button" value="<?php esc_attr_e( 'Validate Now', 'pmpro-email-confirmation' ); ?>" />
								<input type="submit" name="pmproec_resend_email" class="button" value="<?php esc_attr_e( 'Resend Confirmation Email', 'pmpro-email-confirmation' ); ?>" />
							<?php } elseif ( $validation_key === 'validated' ) { ?>
								<input type="submit" name="pmproec_require_reconfirmation" class="button" value="<?php esc_attr_e( 'Require Re-confirmation', 'pmpro-email-confirmation' ); ?>" />
							<?php } else { ?>
								<input type="submit" name="pmproec_send_confirmation" class="button" value="<?php esc_attr_e( 'Send Confirmation Email', 'pmpro-email-confirmation' ); ?>" />
							<?php } ?>
							<p class="description">
								<?php
								if ( $validation_key !== 'validated' && ! empty( $validation_key ) ) {
									esc_html_e( 'Validate Now: Mark this user as confirmed without requiring them to click the confirmation link.', 'pmpro-email-confirmation' );
									echo '<br>';
									esc_html_e( 'Resend Confirmation Email: Send a new confirmation email to the user.', 'pmpro-email-confirmation' );
								} elseif ( $validation_key === 'validated' ) {
									esc_html_e( 'Require Re-confirmation: Generate a new confirmation key and require the user to confirm their email again.', 'pmpro-email-confirmation' );
								} else {
									esc_html_e( 'Send Confirmation Email: Generate a confirmation key and send the confirmation email to the user.', 'pmpro-email-confirmation' );
								}
								?>
							</p>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Save the panel.
	 */
	public function save() {
		// Core has already verified the panel nonce and the edit member capability before calling save().
		$user_id = self::get_user()->ID;

		// Handle validate now action
		if ( isset( $_POST['pmproec_validate_now'] ) ) {
			$validation_key = get_user_meta( $user_id, 'pmpro_email_confirmation_key', true );
			update_user_meta( $user_id, 'pmpro_email_confirmation_key', 'validated' );

			// Fire the same hook as a confirmation link click so integrations see admin validations too.
			do_action( 'pmproec_after_validate_user', $user_id, $validation_key );

			pmpro_setMessage( __( 'User has been validated.', 'pmpro-email-confirmation' ), 'pmpro_success' );
		}

		// Handle resend email action
		if ( isset( $_POST['pmproec_resend_email'] ) ) {
			pmproec_resend_confirmation_email( $user_id );
			pmpro_setMessage( __( 'Confirmation email has been resent.', 'pmpro-email-confirmation' ), 'pmpro_success' );
		}

		// Handle require re-confirmation action
		if ( isset( $_POST['pmproec_require_reconfirmation'] ) ) {
			pmproec_generateNewKey( $user_id );
			pmproec_resend_confirmation_email( $user_id );
			pmpro_setMessage( __( 'A new confirmation key has been generated and the confirmation email has been sent.', 'pmpro-email-confirmation' ), 'pmpro_success' );
		}

		// Handle send confirmation action (for users who never had one)
		if ( isset( $_POST['pmproec_send_confirmation'] ) ) {
			pmproec_generateNewKey( $user_id );
			pmproec_resend_confirmation_email( $user_id );
			pmpro_setMessage( __( 'Confirmation email has been sent.', 'pmpro-email-confirmation' ), 'pmpro_success' );
		}
	}

	/**
	 * Only show this panel if at least one of the user's levels requires confirmation.
	 */
	public function should_show() {
		// First check parent capability
		if ( ! parent::should_show() ) {
			return false;
		}

		$user = self::get_user();

		// Don't show when creating a new user, since there is no user ID yet.
		if ( empty( $user->ID ) ) {
			return false;
		}

		return pmproec_user_requires_confirmation( $user->ID );
	}
}
