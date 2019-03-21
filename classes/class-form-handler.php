<?php
declare( strict_types=1 );
/**
 * Base form handler class.
 */

namespace P4FB\Form_Builder;

class Form_Handler {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Handler
	 */
	private static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Handler
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set up our hooks.
	 */
	public function load() {
		$form_action = P4FB_FORM_ACTION;
		add_action( "admin_post_nopriv_{$form_action}", [ $this, 'form_handler' ] );
		add_action( "admin_post_{$form_action}", [ $this, 'form_handler' ] );
	}

	/**
	 * Handle the form submission.
	 * Process, sanitize, and validate fields.
	 * Call action to store submission.
	 * Call action to post-process submission (e.g. send to CRM).
	 */
	public function form_handler() {
		$form_id      = (int) $_POST['p4_form_id'];
		$nonce_action = P4FB_FORM_ACTION . '-' . $form_id;
		$nonce_name   = P4FB_FORM_NONCE;

		// Check everything is legit...
		if ( ! wp_verify_nonce( $_POST[ $nonce_name ], $nonce_action ) ) {
			echo __( 'Error: There was a problem with your form submission.', 'planet4-form-builder' ); // phpcs:ignore WordPress.Security.EscapeOutput
			exit;
		}

		$form_type = Form_Builder::get_instance()->validate_form_type( $_POST['p4_form_type'] );
		// Get the form details
		$form      = get_post( $form_id );
		$form_data = [];
		$errors    = [];

		$fields = get_post_meta( $form_id, 'p4_form_fields', true );
		foreach ( $fields as $field ) {
			$field_name = $field['name'];

			/**
			 * Sanitize the value submitted for the specific field type.
			 *
			 * @param string|array $value The value from the form submission or empty string.
			 * @param WP_Post      $form  The CRM form.
			 * @param array        $field The field definition.
			 *
			 * @return string|array The sanitized value.
			 */
			$value = apply_filters( "p4fb_sanitize_field_{$field['type']}", $_POST[ $field_name ] ?? '', $form, $field );

			/**
			 * Validate the value submitted for the specific field type.
			 *
			 * @param string|array $value The sanitized value from the form submission or empty string.
			 * @param WP_Post      $form  The CRM form.
			 * @param array        $field The field definition.
			 * @param bool|array   $error The current error condition.
			 *
			 * @return boolean|string The current error. False if no error. Error message if there is an error.
			 *
			 */
			$error = apply_filters( "p4fb_validate_field_{$field['type']}", $value ?? '', $form, $field, false );
			if ( $error !== false ) {
				$errors[ $field_name ] = $error;
			}
			$form_data[ $field_name ] = $value;
		}

		// save record and trigger actions
		if ( empty( $errors ) ) {
			/**
			 * Save away the form submission. May only be temporary.
			 * The action should update the errors array with a saved reference (usually a post id) indexed by 'id',
			 *      or an error indication indexed by 'error'.
			 *
			 * @param array   The arguments
			 *                       array $errors (passed by reference).
			 *                       \WP_Post  $form The CRM form.
			 *                       array $form_data The form submission data.
			 *
			 */

			do_action_ref_array( 'p4fb_save_form_submission', [
				&$errors,
				$form,
				$form_data,
			] );

			/**
			 * Post process the form entry. Likely send to a CRM or queue it to be sent.
			 * Use a generic hook for all form types on 'p4fb_post_save_form' or
			 * a form type(CRM)-specific hook on "p4fb_post_save_form_{$form_type}".
			 *
			 * @param WP_Post $form      The CRM form.
			 * @param array   $form_data The form submission data.
			 * @param string  $entry     The entry reference.
			 *
			 */
			if ( isset( $errors['id'] ) ) {
				do_action( "p4fb_post_save_form_{$form_type}", $form, $form_data, $errors['id'] );
				do_action( 'p4fb_post_save_form', $form, $form_data, $errors['id'] );
			}
		} else {
			// Handle errors (likely redisplay the same page)
			// @Todo: echo 'errors are ' . var_export( $errors, true );
		}

	}

}
