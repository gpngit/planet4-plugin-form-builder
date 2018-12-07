<?php
/**
 * Part of the Planet4 Form Builder.
 */

namespace P4FB\Form_Builder;

/**
 * Class P4_Form_Builder_Settings_Page
 */
class Form_Builder_Settings_Page {

	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Builder_Settings_Page
	 */
	protected static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Builder_Settings_Page
	 */
	public static function get_instance() : Form_Builder_Settings_Page {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Form_Builder_Settings_Page constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'init_settings' ] );
	}

	/**
	 * Add our settings page under the CRM Forms menu
	 */
	public function add_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=' . P4FB_FORM_CPT,
			esc_html__( 'CRM Settings', 'planet4-form-builder' ),
			esc_html__( 'CRM Settings', 'planet4-form-builder' ),
			'manage_options',
			'p4fb-settings-slug',
			[ $this, 'page_layout' ]
		);
	}

	/**
	 * Register our settings, sections and fields.
	 */
	public function init_settings() {

		register_setting(
			'p4fb_settings_group',
			'p4fb_settings',
			[
				'type'              => 'string',
				'description'       => 'CRM settings',
				'sanitize_callback' => [ $this, 'sanitize_cb' ],
			]
		);

		add_settings_section(
			'p4fb_settings_section',
			'',
			'__return_empty_string',
			'p4fb_settings'
		);

		add_settings_field(
			'p4fb-crm_type',
			__( 'CRM Type', 'planet4-form-builder' ),
			[ $this, 'render_crm_type_field' ],
			'p4fb_settings',
			'p4fb_settings_section'
		);

		add_settings_field(
			'p4fb-api_key',
			__( 'API Key', 'planet4-form-builder' ),
			[ $this, 'render_api_key_field' ],
			'p4fb_settings',
			'p4fb_settings_section'
		);

		add_settings_field(
			'p4fb-api_secret',
			__( 'API Secret', 'planet4-form-builder' ),
			[ $this, 'render_api_secret_field' ],
			'p4fb_settings',
			'p4fb_settings_section'
		);

	}

	/**
	 * Render the CRM settings page.
	 */
	public function page_layout() {

		// Check required user capability
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'planet4-form-builder' ) );
		}

		wp_enqueue_media();
		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'p4fb_settings_group' );
				do_settings_sections( 'p4fb_settings' );
				submit_button( __( 'Save Settings', 'planet4-form-builder' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the CRM Type field.
	 */
	function render_crm_type_field() {

		// Get current value.
		$options       = get_option( 'p4fb_settings' );
		$current_value = isset( $options['p4fb-crm_type'] ) ? $options['p4fb-crm_type'] : '';

		?>
		<select name="p4fb_settings[p4fb-crm_type]" class="regular-text p4fb-crm_type_field">
			<option value=""><?php echo __( 'Select CRM Type', 'planet4-form-builder' ); ?></option>
			<?php
			foreach ( Form_Builder::get_instance()->get_crm_type_options() as $val => $option ) {
				?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $current_value, true ); ?>><?php echo $option; ?></option>
				<?php
			}
			?>
		</select>
		<p class="description"><?php esc_html_e( 'Select the CRM type', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Render the API Key text field.
	 */
	function render_api_key_field() {

		// Get current value.
		$options       = get_option( 'p4fb_settings' );
		$current_value = isset( $options['p4fb-api_key'] ) ? $options['p4fb-api_key'] : '';

		?>
		<input type="text" name="p4fb_settings[p4fb-api_key]" class="regular-text p4fb-api_key_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the API key', 'planet4-form-builder' ); ?></p>
		<?php
	}

	/**
	 * Render the API secret text field.
	 */
	function render_api_secret_field() {

		// Get current value.
		$options       = get_option( 'p4fb_settings' );
		$current_value = isset( $options['p4fb-api_secret'] ) ? $options['p4fb-api_secret'] : '';

		?>
		<input type="text" name="p4fb_settings[p4fb-api_secret]" class="regular-text p4fb-api_secret_field" value="<?php echo esc_attr( $current_value ); ?>">
		<p class="description"><?php esc_html_e( 'Enter the API secret', 'planet4-form-builder' ); ?></p>
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
	function sanitize_cb( array $input ) : array {
		if (
			! isset( $input['p4fb-crm_type'] ) ||
			! in_array( $input['p4fb-crm_type'], array_keys( Form_Builder::get_instance()->get_crm_type_options() ), true )
		) {
			$input['p4fb-crm_type'] = '';
		}

		if ( isset( $input['p4fb-api_key'] ) ) {
			$input['p4fb-api_key'] = wp_strip_all_tags( $input['p4fb-api_key'] );
		}

		if ( isset( $input['p4fb-api_secret'] ) ) {
			$input['p4fb-api_secret'] = wp_strip_all_tags( $input['p4fb-api_secret'] );
		}

		return $input;
	}

}
