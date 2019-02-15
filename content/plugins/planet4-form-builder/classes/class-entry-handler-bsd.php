<?php
/**
 * BSD Form handler class.
 * This class hooks to the 'P4FB_KEY_PREFIX_send_entry_bsd' action and sends the form entry to the BSD CRM.
 *
 */

namespace P4FB\Form_Builder;

class Entry_Handler_BSD {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Entry_Handler_BSD
	 */
	static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Entry_Handler_BSD
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
		add_filter( P4FB_KEY_PREFIX . 'crm_is_configured_bsd', [ $this, 'crm_is_configured' ] );
		add_action( P4FB_KEY_PREFIX . 'send_entry_bsd', [ $this, 'send_entry' ], 10, 2 );
	}

	/**
	 * Check whether we have our settings configured.
	 *
	 * @param bool $status The current status
	 *
	 * @return mixed Whether we have our set up or not.
	 */
	public function crm_is_configured( bool $status ) : bool {
		$options  = get_option( P4FB_SETTINGS_OPTION_NAME );
		$base_url = $options['base_url'] ?? '';
		$source   = $options['source'] ?? '';
		if ( empty( $base_url ) || empty( $source ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Send the form entry details to the API endpoint.
	 *
	 * @param array $data     The id of the entry, mapped fields, and other supporting data.
	 * @param array $response Fill in the response details.
	 */
	public function send_entry( array $data, array &$passed_response ) {
		if ( empty( $passed_response ) ) {
			$passed_response = [];
		}

		if ( ! $this->crm_is_configured( false, P4FB_SETTINGS_OPTION_NAME ) ) {
			$passed_response['code']  = 'error';
			$passed_response['error'] = __( 'Not configured', 'planet4-form-builder' );

			return;
		}

		$options        = get_option( P4FB_SETTINGS_OPTION_NAME );
		$retries        = (int) $options['api_retries'] ?? 0;
		$base_url       = $options['base_url'] ?? '';
		$source         = $options['source'] ?? '';
		$args['source'] = $source;
		if ( isset( $data['mapped_data']['subsource'] ) ) {
			$args['subsource'] = $data['mapped_data']['subsource'];
		}
		$url = add_query_arg( $args, $base_url );

		// if it's a get request
		$try_get = false;
		if ( $try_get ) {
			$url = add_query_arg( $data['mapped_data'], $url );
			$response = wp_remote_get(
				esc_url_raw( $url ),
				[
					'timeout'     => 45,
					'redirection' => 1,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => [
						'x-sender' => 'planet4-form-builder',
					],
				]
			);
		} else {
			// try post

			// if it's a post request
			$response = wp_remote_post(
				esc_url_raw( $url ),
				[
					'body'        => wp_json_encode( $data, JSON_UNESCAPED_SLASHES ),
					'method'      => 'POST',
					'timeout'     => 45,
					'redirection' => 1,
					'httpversion' => '1.0',
					'blocking'    => true,
					'headers'     => [
						'Content-Type' => 'application/json',
						'x-sender'     => 'planet4-form-builder',
					],
				]
			);

		}

		$transmission_success = false;

		if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
			$response_code = (int) wp_remote_retrieve_response_code( $response );
			//zed1_debug( 'Sent. Response is ', $response ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			if ( $response_code < 400 ) {
				$transmission_success = true;
			}
		}

		if ( ! $transmission_success ) {
			//zed1_debug( 'send failure' ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$passed_response['code']  = $response_code;
			$passed_response['error'] = $response;
		} else {
			//zed1_debug( 'send success' ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$passed_response['code'] = 'success';
		}

		//zed1_debug( 'passed response is now ', $passed_response ); phpcs:ignore Squiz.PHP.CommentedOutCode.Found

		return;
	}
}
