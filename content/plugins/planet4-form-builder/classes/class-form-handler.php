<?php
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
	static $instance;

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
	function load() {
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
		$form_id      = intval( $_POST['p4_form_id'] );
		$nonce_action = P4FB_FORM_ACTION . '-' . $form_id;
		$nonce_name   = P4FB_FORM_NONCE;

		// Check everything is legit...
		if ( ! wp_verify_nonce( $_POST[ $nonce_name ], $nonce_action ) ) {
			echo __( 'Error: There was a problem with your form submission.', 'planet4-form-builder' );
			exit;
		}

		$form_type = Form_Builder::get_instance()->sanitize_form_type( $_POST['p4_form_type'] );
		// Get the form details
		$form      = get_post( $form_id );
		$form_data = [];
		$errors    = [];

		$fields = get_post_meta( $form_id, 'p4_form_fields', true );
		foreach ( $fields as $field ) {
			$field_name = $field['name'];
			$value      = apply_filters( 'p4fb_sanitize_field', $_POST[ $field_name ] ?? '', $form, $field );
			$error      = apply_filters( 'p4fb_validate_field', $_POST[ $field_name ] ?? '', $form, $field );
			if ( $error !== true ) {
				$errors[ $field_name ] = $error;
			}
			$form_data[ $field_name ] = $value;
		}
		// save record and trigger post action
		if ( empty( $errors ) ) {
			do_action( 'p4fb_save_form_submission', $form, $form_data );
			do_action( 'p4fb_post_save_form', $form, $form_data );
			do_action( "p4fb_post_save_form_{$form_type}", $form, $form_data );
		}
	}

}
