<?php
declare( strict_types=1 );
/**
 * BSD Form handler class.
 * This class hooks to the 'P4FB_KEY_PREFIX_send_entry_bsd' action and sends the form entry to the BSD CRM.
 *
 * @package P4FB\Form_Builder
 */

namespace P4FB\Form_Builder;

/**
 * Class Entry_Handler_BSD
 *
 * @package P4FB\Form_Builder
 */
class Entry_Handler_BSD {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Entry_Handler_BSD
	 */
	private static $instance;

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
	 * @return mixed Whether we have our set up or not.
	 */
	public function crm_is_configured() : bool {
		$options  = get_option( P4FB_SETTINGS_OPTION_NAME );
		$base_url = $options['base_url'] ?? '';
		$source   = $options['source'] ?? '';

		return ! ( empty( $base_url ) || empty( $source ) );
	}

	/**
	 * Send the form entry details to the API endpoint.
	 *
	 * @param array $data            The id of the entry, mapped fields, and other supporting data.
	 * @param array $passed_response Fill in the response details.
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

		$options = get_option( P4FB_SETTINGS_OPTION_NAME );
		$base_url       = $options['base_url'] ?? '';
		$source         = $options['source'] ?? '';
		$args['source'] = $source;
		if ( isset( $data['mapped_data']['subsource'] ) ) {
			$args['subsource'] = $data['mapped_data']['subsource'];
		}
		$url = add_query_arg( $args, $base_url );

		$response = wp_remote_post(
			esc_url_raw( $url ),
			[
				'body'        => $data['mapped_data'],
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 1,
				'httpversion' => '1.1',
				'blocking'    => true,
				'headers'     => [
					'x-sender'     => 'planet4-form-builder',
					'Content-Type' => 'application/x-www-form-urlencoded',
				],
			]
		);

		$transmission_success = false;
		if ( ! empty( $response ) && ! is_wp_error( $response ) ) {
			$response_code = (int) wp_remote_retrieve_response_code( $response );
			if ( $response_code < 400 ) {
				$transmission_success = true;
			}
			$response_body = json_decode( wp_remote_retrieve_body( $response ), true );
		}

		if ( ! $transmission_success ) {
			$passed_response['code']     = $response_code;
			$passed_response['response'] = $response_body;
		} else {
			$passed_response['code']     = 'success';
			$passed_response['response'] = $response_body;
		}
	}
}
