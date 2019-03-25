<?php
declare( strict_types=1 );
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
			P4FB_SETTINGS_OPTION_NAME,
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
			P4FB_SETTINGS_OPTION_NAME
		);

		add_settings_field(
			'crm_type',
			__( 'CRM Type', 'planet4-form-builder' ),
			[ $this, 'render_crm_type_field' ],
			P4FB_SETTINGS_OPTION_NAME,
			'p4fb_settings_section'
		);

		/**
		 * Action P4 Form builder add settings section.
		 * A CRM specific add-on can add it's own section and fields here.
		 *
		 * @param string $settings_option The option key for the settings.
		 */
		do_action( P4FB_KEY_PREFIX . 'add_settings_section', P4FB_SETTINGS_OPTION_NAME );

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
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'p4fb_settings_group' );
				do_settings_sections( P4FB_SETTINGS_OPTION_NAME );
				submit_button( __( 'Save Settings', 'planet4-form-builder' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render the CRM Type field.
	 */
	public function render_crm_type_field() {

		// Get current value.
		$options       = get_option( P4FB_SETTINGS_OPTION_NAME );
		$current_value = isset( $options['crm_type'] ) ? $options['crm_type'] : '';

		?>
		<select name="p4fb_settings[crm_type]" class="regular-text crm_type_field">
			<option value=""><?php esc_html_e( 'Select CRM Type', 'planet4-form-builder' ); ?></option>
			<?php
			foreach ( Form_Builder::get_instance()->get_crm_type_options() as $val => $option ) {
				?>
				<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $val, $current_value ); ?>><?php echo esc_html( $option ); ?></option>
				<?php
			}
			?>
		</select>
		<p class="description"><?php esc_html_e( 'Select the CRM type', 'planet4-form-builder' ); ?></p>
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
	public function sanitize_cb( array $input ) : array {
		if (
			! isset( $input['crm_type'] ) ||
			! array_key_exists( $input['crm_type'], Form_Builder::get_instance()->get_crm_type_options() )
		) {
			$input['crm_type'] = '';
		}

		$input = apply_filters( P4FB_KEY_PREFIX . 'sanitize_callback', $input );

		return $input;
	}

}
