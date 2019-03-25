<?php
declare( strict_types=1 );
/**
 * Part of the Planet4 Form Builder.
 */

namespace P4FB\Form_Builder;

/**
 * Class Settings_Page_BSD
 */
class Settings_Page_BSD {

	/**
	 *  Store the singleton instance
	 *
	 * @var  Settings_Page_BSD
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
	 * @return Settings_Page_BSD
	 */
	public static function get_instance() : Settings_Page_BSD {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Form_Builder_Settings_Page constructor.
	 */
	public function __construct() {
		$this->section_name = 'bsd_settings_section';
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
			'BSD Settings',
			'__return_empty_string',
			$option_key
		);

		add_settings_field(
			'base_url',
			__( 'Base URL', 'planet4-form-builder' ),
			[ $this, 'render_base_url_field' ],
			$option_key,
			$this->section_name
		);

		add_settings_field(
			'source',
			__( 'Source value', 'planet4-form-builder' ),
			[ $this, 'render_source_field' ],
			$option_key,
			$this->section_name
		);

		add_settings_field(
			'api_retries',
			__( 'API Retries', 'planet4-form-builder' ),
			[ $this, 'render_api_retries_field' ],
			$option_key,
			$this->section_name
		);

	}

	/**
	 * Render the Base URL text field.
	 */
	public function render_base_url_field() {

		// Get current value.
		$options       = get_option( $this->option_key );
		$current_value = isset( $options['base_url'] ) ? $options['base_url'] : '';

		?>
		<input type="text" name="p4fb_settings[base_url]" class="regular-text base_url_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the base URL', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Render the API secret text field.
	 */
	public function render_source_field() {

		// Get current value.
		$options       = get_option( $this->option_key );
		$current_value = isset( $options['source'] ) ? $options['source'] : '';

		?>
		<input type="text" name="p4fb_settings[source]" class="regular-text source_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter value for the source query string', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Render the API retries text field.
	 */
	public function render_api_retries_field() {

		// Get current value.
		$options       = get_option( $this->option_key );
		$current_value = isset( $options['api_retries'] ) ? $options['api_retries'] : 0;

		?>
		<input type="text" name="p4fb_settings[api_retries]" class="regular-text api_retries_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the number of API retries to attempt', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Validate input.
	 * Check whether to action the create days checkboxes.
	 *
	 * @param array $input The POST-ed values.
	 *
	 * @return array The sanitized input.
	 */
	public function sanitize_callback( array $input ) : array {
		if ( isset( $input['base_url'] ) ) {
			$input['base_url'] = wp_strip_all_tags( $input['base_url'] );
		}

		if ( isset( $input['source'] ) ) {
			$input['source'] = wp_strip_all_tags( $input['source'] );
		}

		if ( isset( $input['api_retries'] ) ) {
			$input['api_retries'] = (int) $input['api_retries'];
		}

		return $input;
	}

}
