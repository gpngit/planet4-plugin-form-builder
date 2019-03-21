<?php
declare( strict_types=1 );
/**
 * Entry handler class.
 * This class hooks to the 'p4fb_post_save_form' action and enqueues the form entry to be sent off to a CRM.
 * It responds to it's own enqueued jobs then triggers an action to allow a plugin CRM to do the communication part.
 * It will expect an success/error response from the CRM handler, and will deal with re-queueing on error, and deleting the entry on success.
 *
 * @package P4FB\Form_Builder
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

/**
 * Class Entry_Handler
 *
 * @package P4FB\Form_Builder
 */
class Entry_Handler {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Entry_Handler
	 */
	private static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Entry_Handler
	 */
	public static function get_instance() :Entry_Handler {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set up our hooks.
	 */
	public function load() {
		add_action( 'p4fb_post_save_form', [ $this, 'entry_handler' ], 10, 3 );
		add_action( P4FB_KEY_PREFIX . 'queued_entry', [ $this, 'send_entry' ] );
	}

	/**
	 * Post process the form entry. Queue ready to send to a CRM.
	 * Calleds from the generic hook for all form types on 'p4fb_post_save_form'
	 *
	 * @param \WP_Post $form      The CRM form.
	 * @param array    $form_data The form submission data.
	 * @param int      $entry_id  The entry reference.
	 */
	public function entry_handler( \WP_Post $form, array $form_data, int $entry_id ) {var_dump($form_data);
		$mapping_id  = get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'mapping_id', true );
		$mapped_data = $this->get_mapped_data( $entry_id );
		$form_type   = get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'form_type', true );

		// Send all the mapped data.
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
	 * Combine the form entry data according to the mapping to produce the data to be sent.
	 *
	 * @param int $entry_id The post id of the entry
	 *
	 * @return array The mapped data
	 */
	protected function get_mapped_data( int $entry_id ) : array {
		$mapped_data = [];
		$form_id     = get_post_meta( $entry_id, P4FB_KEY_PREFIX . 'form_id', true );
		$mapping_id  = get_post_meta( $form_id, P4FB_KEY_PREFIX . 'mapping_id', true );
		$entry       = get_post( $entry_id );
		$form_data   = json_decode( $entry->post_content, true );
		if ( $mapping_id ) {
			$fields = get_post_meta( $form_id, P4FB_KEY_PREFIX . 'fields', true );
			foreach ( $fields as $field ) {
				$mapped_field = get_post_meta( $mapping_id, 'form_field_' . $field['name'], true );
				if ( $mapped_field ) {
					$mapped_data[ $mapped_field ] = $form_data[ $field['name'] ];
				}
			}
		}

		return $mapped_data;
	}

	/**
	 * Schedule the form entry data to be sent to the CRM.
	 *
	 * @param array $data The data to send.
	 */
	public function schedule_send_entry( array $data ) {
		// we need a minimum of the entry id in the data.
		if ( ! isset( $data['entry_id'] ) ) {
			// not enough data to schedule.
			return;
		}

		// do we need to load the data ourselves (.e.g. from re-queue)?
		if ( ! isset( $data['form_type'] ) ) {
			$data['form_type']   = get_post_meta( $data['entry_id'], P4FB_KEY_PREFIX . 'form_type', true );
			$data['form_id']     = get_post_meta( $data['entry_id'], P4FB_KEY_PREFIX . 'form_id', true );
			$data['mapping_id']  = get_post_meta( $data['form_id'], P4FB_KEY_PREFIX . 'mapping_id', true );
			$data['mapped_data'] = $this->get_mapped_data( $data['entry_id'] );
		}

		// Don't bother scheduling if we are not configured.
		$configured = apply_filters( P4FB_KEY_PREFIX . 'crm_is_configured_' . $data['form_type'], false );
		if ( ! $configured ) {
			// @Todo: Not configured. How should this be alerted?
			return;
		}

		// Trigger the job.
		wp_schedule_single_event(
			time() + DEBUG_DELAY,
			P4FB_KEY_PREFIX . 'queued_entry',
			[ $data ]
		);
		if ( add_post_meta( $data['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_QUEUED, true ) === false ) {
			update_post_meta( $data['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_QUEUED );
		}
	}

	/**
	 * Trigger the send entry action.
	 * Respond to success or failure response to requeue a number of retries.
	 *
	 * @param array $args The id of the entry, mapped fields, and other supporting data.
	 */
	public function send_entry( array $args ) {
		// @Todo: Should retries be handled?
		$retries       = 0;
		$form_type     = $args['form_type'];
		$general_hook  = P4FB_KEY_PREFIX . 'send_entry';
		$specific_hook = $general_hook . '_' . $form_type;
		$response      = [];
		// Do the send via a do_action hook. This allows plugins to handle specific form types (CRMs) differently.
		if ( has_action( $specific_hook ) ) {
			if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_PROCESS, true ) === false ) {
				update_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_PROCESS );
			}
			do_action_ref_array( $specific_hook, [ $args, &$response ] );
		} elseif ( has_action( $general_hook ) ) {
			if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_PROCESS, true ) === false ) {
				update_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_PROCESS );
			}
			do_action_ref_array( $general_hook, [ $args, &$response ] );
		} else {
			if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_ERROR, true ) === false ) {
				update_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_ERROR );
			}

			return;
		}

		$transmission_success = false;

		if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
			$response_code = $response['code'];
			if ( $response_code === 'success' ) {
				$transmission_success = true;
			}
		}

		if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_RESPONSE_META_KEY, $response['response'], true ) === false ) {
			update_post_meta( $args['entry_id'], P4FB_ENTRY_RESPONSE_META_KEY, $response['response'] );
		}

		if ( ! $transmission_success ) {
			$retry_count = $args['retry_count'] ?? 0;

			if ( $retry_count <= $retries ) {
				$args['retry_count'] = ++ $retry_count;
				$this->schedule_send_entry( $args );
			} else {

				// We have run out of retries or irrecoverable error. Log error. Notify.
				if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_ERROR, true ) === false ) {
					update_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_ERROR );
				}
			}
		} else {

			// Everything went fine!
			if ( add_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_SENT, true ) === false ) {
				update_post_meta( $args['entry_id'], P4FB_ENTRY_STATUS_META_KEY, P4FB_ENTRY_STATUS_SENT );
			}
			// and delete it??
			wp_trash_post( $args['entry_id'] );

		}
	}
}
