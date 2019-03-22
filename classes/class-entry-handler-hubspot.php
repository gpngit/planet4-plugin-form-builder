<?php
declare( strict_types=1 );
/**
 * Hubspot Form handler class.
 * This class hooks to the 'P4FB_KEY_PREFIX_send_entry_hubspot' action and sends the form entry to the Hubspot CRM.
 *
 * @package P4FB\Form_Builder
 */

namespace P4FB\Form_Builder;

/**
 * Class Entry_Handler_Hubspot
 *
 * @package P4FB\Form_Builder
 */
class Entry_Handler_Hubspot {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Entry_Handler_Hubspot
	 */
	private static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Entry_Handler_Hubspot
	 */
	public static function get_instance(): Entry_Handler_Hubspot {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set up our hooks.
	 */
	public function load() {
		add_filter( P4FB_KEY_PREFIX . 'crm_is_configured_hubspot', [ $this, 'crm_is_configured' ] );
		add_action( P4FB_KEY_PREFIX . 'send_entry_hubspot', [ $this, 'send_entry' ], 10, 2 );
	}

	/**
	 * Check whether we have our settings configured.
	 *
	 * @return mixed Whether we have our set up or not.
	 */
	public function crm_is_configured(): bool {
		return true;
	}

	/**
	 * Send the form entry details to the API endpoint.
	 *
	 * @param array $data The id of the entry, mapped fields, and other supporting data.
	 * @param array $passed_response Fill in the response details.
	 */
	public function send_entry( array $data, array &$passed_response ) {
		if ( empty( $passed_response ) ) {
			$passed_response = [];
		}

		if ( ! $this->crm_is_configured() ) {
			$passed_response['code']  = 'error';
			$passed_response['error'] = __( 'Not configured', 'planet4-form-builder' );
			return;
		}

		$options    = get_option( P4FB_SETTINGS_OPTION_NAME );
		$form_guid  = $options['form_guid'] ?? '';
		$portal_id  = $options['portal_id'] ?? '';
		$hubspotutk = $_COOKIE['hubspotutk'] ?? ''; // grab the cookie from the visitors browser.
		$ip_addr    = $_SERVER['REMOTE_ADDR']; // IP address too.
		global $wp;
		$current_slug    = add_query_arg( [], $wp->request );
		$hs_context      = [
			'hutk'      => $hubspotutk,
			'ipAddress' => $ip_addr,
			'pageUrl'   => home_url( $current_slug ),
			'pageName'  => get_the_title(),
		];
		$hs_context_json = wp_json_encode( $hs_context );

		$body_fields[]             = $data['mapped_data'];
		$body_fields['hs_context'] = $hs_context_json;
		$post_data                 = http_build_query( $body_fields );
		$api_url                   = "https://forms.hubspot.com/uploads/form/v2/{$portal_id}/{$form_guid}";
		$response                  = wp_remote_post(
			esc_url_raw( $api_url ),
			[
				'body'    => $post_data,
				'headers' => [
					'Content-Type' => 'application/x-www-form-urlencoded',
				],
			]
		);

		$transmission_success = false;
		$response_code        = '';
		$response_body        = '';
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
