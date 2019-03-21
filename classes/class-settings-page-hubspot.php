<?php
/**
 * Handles the hubspot settings page logic.
 *
 * @package P4FB\Form_Builder
 */

declare( strict_types=1 );

namespace P4FB\Form_Builder;

/**
 * Class Settings_Page_Hubspot
 *
 * @package P4FB\Form_Builder
 */
class Settings_Page_Hubspot {

	/**
	 *  Store the singleton instance
	 *
	 * @var  Settings_Page_Hubspot
	 */
	protected static $instance;

	/**
	 *  Settings section name.
	 *
	 * @var  string
	 */
	protected $section_name;

	/**
	 *  Settings option key.
	 *
	 * @var  string
	 */
	protected $option_key;

	/**
	 * Create singleton instance.
	 *
	 * @return Settings_Page_Hubspot
	 */
	public static function get_instance() : Settings_Page_Hubspot {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Form_Builder_Settings_Page constructor.
	 */
	public function __construct() {
		$this->section_name = 'hubspot_settings_section';
		add_action( P4FB_KEY_PREFIX . 'add_settings_section', [ $this, 'add_settings_section' ] );
		add_filter( P4FB_KEY_PREFIX . 'sanitize_callback', [ $this, 'sanitize_callback' ] );
	}

	/**
	 * Hook for action P4 Form builder add settings section.
	 *
	 * @param string $option_key The option key for the settings.
	 */
	public function add_settings_section( string $option_key ) {
		$this->option_key = $option_key;
		add_settings_section(
			$this->section_name,
			'Hubspot Settings',
			'__return_empty_string',
			$option_key
		);
		add_settings_field(
			'portal_id',
			__( 'Portal ID', 'planet4-form-builder' ),
			[ $this, 'render_portal_id_field' ],
			$option_key,
			$this->section_name
		);

		add_settings_field(
			'form_guid',
			__( 'Form GUID', 'planet4-form-builder' ),
			[ $this, 'render_form_guid_field' ],
			$option_key,
			$this->section_name
		);
	}

	/**
	 * Render the Base URL text field.
	 */
	public function render_portal_id_field() {

		// Get current value.
		$options       = get_option( $this->option_key );
		$current_value = $options['portal_id'] ?? '';

		?>
		<input type="text" name="p4fb_settings[portal_id]" class="regular-text portal_id_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the portal ID', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Render the Base URL text field.
	 */
	public function render_form_guid_field() {

		// Get current value.
		$options       = get_option( $this->option_key );
		$current_value = $options['form_guid'] ?? '';

		?>
		<input type="text" name="p4fb_settings[form_guid]" class="regular-text form_guid_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the form GUID', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Validate input.
	 *
	 * @param array $input The POST-ed values.
	 *
	 * @return array The sanitized input.
	 */
	public function sanitize_callback( array $input ) : array {
		if ( isset( $input['portal_id'] ) ) {
			$input['portal_id'] = sanitize_text_field( $input['portal_id'] );
		}
		if ( isset( $input['form_guid'] ) ) {
			$input['form_guid'] = sanitize_text_field( $input['form_guid'] );
		}
		return $input;
	}

}
