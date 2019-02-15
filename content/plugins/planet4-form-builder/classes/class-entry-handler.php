<?php
/**
 * Entry handler class.
 * This class hooks to the 'p4fb_post_save_form' action and enqueues the form entry to be sent off to a CRM.
 * It responds to it's own enqueued jobs then triggers an action to allow a plugin CRM to do the communication part.
 * It will expect an success/error response from the CRM handler, and will deal with re-queueing on error, and deleting the entry on success.
 *
 */

namespace P4FB\Form_Builder;

/*
 * Debug delay during dev only to give time to check it got queued.
 * e.g. `wp cron event list`
 */
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
	define( 'DEBUG_DELAY', 10 );
} else {
	define( 'DEBUG_DELAY', 0 );
}

class Entry_Handler {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Entry_Handler
	 */
	static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Entry_Handler
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
		add_action( 'p4fb_post_save_form', [ $this, 'entry_handler' ], 10, 3 );
		add_action( P4FB_KEY_PREFIX . 'queued_entry', [ $this, 'send_entry' ] );

	}

	/**
	 * Post process the form entry. Queue ready to send to a CRM.
	 * Calleds from the generic hook for all form types on 'p4fb_post_save_form'
	 *
	 * @param WP_Post $form      The CRM form.
	 * @param array   $form_data The form submission data.
	 * @param int     $entry     The entry reference.
	 */
	public function entry_handler( \WP_Post $form, array $form_data, int $entry_id ) {
		//zed1_debug( __FILE__ . ':' . __LINE__ ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		//zed1_debug( 'form=', $form ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		//zed1_debug( 'form data=', $form_data ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		//zed1_debug( 'entry_id=', $entry_id ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		$mapped_data = [];
		$entry       = get_post( $entry_id );
		//zed1_debug( 'entry=', $entry ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		$mapping_id = get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'mapping_id', true );
		//zed1_debug( 'mapping id=', $mapping_id ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		if ( $mapping_id ) {
			$fields = get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'fields', true );
			//zed1_debug( 'fields =', $fields ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			foreach ( $fields as $field ) {
				//zed1_debug( 'mapped data=', $mapped_data ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				$mapped_field = get_post_meta( $mapping_id, 'form_field_' . $field['name'], true );
				//zed1_debug( 'mapped field', $mapped_field ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				if ( $mapped_field ) {
					//zed1_debug( 'data =', $form_data[ $field['name'] ] ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
					$mapped_data[ $mapped_field ] = $form_data[ $field['name'] ];
				}
			}
			//zed1_debug( 'mapped data=', $mapped_data ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		}

		$form_type = get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'form_type', true );

		// Send all the mapped data
		$data = [
			'form_type'   => $form_type,
			'form_id'     => $form->ID,
			'mapping_id'  => $mapping_id,
			'entry_id'    => $entry_id,
			'mapped_data' => $mapped_data,
		];

		$this->schedule_send_entry( $data );

	}

	/**
	 * Schedule the form entry data to be sent to the CRM.
	 *
	 * @param array $data The data to send.
	 */
	public function schedule_send_entry( array $data ) {
		// Don't bother scheduling if we are not configured.
		$configured = apply_filters( P4FB_KEY_PREFIX . 'crm_is_configured_' . $data['form_type'], false );
		if ( ! $configured ) {
			//zed1_debug( 'not configured' ); //phpcs:ignore Squiz.PHP.CommentedOutCode.Found

			return;
		}

		// Trigger the job.
		wp_schedule_single_event(
			time() + DEBUG_DELAY,
			P4FB_KEY_PREFIX . 'queued_entry',
			[ $data ]
		);
	}

	/**
	 * Trigger the send entry action.
	 * Respond to success or failure response to requeue a number of retries.
	 *
	 * @param array $args The id of the entry, mapped fields, and other supporting data.
	 */
	public function send_entry( array $args ) {
		$retries       = 0;
		$form_type     = $args['form_type'];
		$general_hook  = P4FB_KEY_PREFIX . 'send_entry';
		$specific_hook = $general_hook . '_' . $form_type;
		$response      = [];

		// Do the send via a do_action hook. This allows plugins to handle specific form types (CRMs) differently.
		if ( has_action( $specific_hook ) ) {

			do_action_ref_array( $specific_hook, [ $args, &$response ] );
		} elseif ( has_action( $general_hook ) ) {
			do_action_ref_array( $general_hook, [ $args, &$response ] );
		}

		//zed1_debug( 'Response from scheduled send is', $response ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
		$transmission_success = false;

		if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
			$response_code = $response['code'];
			if ( $response_code === 'success' ) {
				//zed1_debug( 'sent successfully. code=', $response_code ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
				$transmission_success = true;
			}
		}

		if ( ! $transmission_success ) {
			//zed1_debug( 'Send failure' ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$retry_count = $args['retry_count'] ?? 0;

			if ( $retry_count <= $retries ) {
				$args['retry_count'] = ++ $retry_count;
				$this->schedule_send_entry( $args );

				$notification_post_id = $args['entry_id'];
				$notification_type    = 'warning';
			} else {

				// We have run out of retries or irrecoverable error. Log error. Notify.
				$notification_post_id = $args['entry_id'];
				$notification_type    = 'error';
			}
		} else {

			// Everything went fine!
			$notification_post_id = $args['entry_id'];
			$notification_type    = 'success';
		}

	}
}
