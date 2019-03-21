<?php
declare( strict_types=1 );
/**
 * Base form builder class.
 */

namespace P4FB\Form_Builder;

use Timber\Timber;

/**
 * Class Form_Builder
 * @package P4FB\Form_Builder
 */
class Form_Builder {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Builder
	 */
	private static $instance;

	/**
	 *  Store the Template Loader instance
	 *
	 * @var  Form_Template_Loader
	 */
	public static $template_loader;


	/**
	 * Create singleton instance.
	 *
	 * @return Form_Builder
	 */
	public static function get_instance() :Form_Builder {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register CPT. Set up our hooks.
	 */
	public function load() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		self::$template_loader = new Form_Template_Loader();
		add_filter( 'template_include', [ $this, 'template_include' ] );

		add_action( 'cmb2_init', [ $this, 'add_fields' ] );
		add_filter( 'enter_title_here', [ $this, 'filter_enter_title_here' ], 10, 2 );

		/* Default sanitization */
		add_filter( 'p4fb_sanitize_field_text', [ $this, 'sanitize_field_text' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_email', [ $this, 'sanitize_field_email' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_tel', [ $this, 'sanitize_field_tel' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_textarea', [ $this, 'sanitize_field_textarea' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_select', [ $this, 'sanitize_field_select' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_checkbox', [ $this, 'sanitize_field_checkbox' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_checkbox-group', [ $this, 'sanitize_field_checkbox_group' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_radio-group', [ $this, 'sanitize_field_radio_group' ], 10, 3 );
		add_filter( 'p4fb_sanitize_field_hidden', [ $this, 'sanitize_field_text' ], 10, 3 );

		/* Default validation */
		add_filter( 'p4fb_validate_field_text', [ $this, 'validate_field_text' ], 10, 3 );
		add_filter( 'p4fb_validate_field_textarea', [ $this, 'validate_field_textarea' ], 10, 3 );
		add_filter( 'p4fb_validate_field_select', [ $this, 'validate_field_select' ], 10, 3 );
		add_filter( 'p4fb_validate_field_checkbox', [ $this, 'validate_field_checkbox' ], 10, 3 );
		add_filter( 'p4fb_validate_field_checkbox-group', [ $this, 'validate_field_checkbox_group' ], 10, 3 );
		add_filter( 'p4fb_validate_field_radio-group', [ $this, 'validate_field_radio_group' ], 10, 3 );
		add_filter( 'p4fb_validate_field_hidden', [ $this, 'validate_field_text' ], 10, 3 );
		add_filter( 'p4fb_validate_field_email', [ $this, 'validate_field_email' ], 10, 3 );
		add_filter( 'p4fb_validate_field_tel', [ $this, 'validate_field_tel' ], 10, 3 );

		Timber::$locations = [ P4FB_PLUGIN_DIR . '/templates/views' ];
	}

	/**
	 * Set up a different prompt for the Title field.
	 *
	 * @param string   $title The current prompt.
	 * @param \WP_Post $post  THe current post.
	 *
	 * @return string The updated string.
	 */
	public function filter_enter_title_here( string $title, \WP_Post $post ) : string {
		if ( P4FB_FORM_CPT === $post->post_type ) {
			return __( 'Enter form name', 'planet4-form-builder' );
		}

		return $title;
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_post_type() {

		$labels = [
			'name'                  => _x( 'Forms', 'Post Type General Name', 'planet4-form-builder' ),
			'singular_name'         => _x( 'Form', 'Post Type Singular Name', 'planet4-form-builder' ),
			'menu_name'             => __( 'CRM Forms', 'planet4-form-builder' ),
			'name_admin_bar'        => __( 'Form', 'planet4-form-builder' ),
			'archives'              => __( 'Form Archives', 'planet4-form-builder' ),
			'attributes'            => __( 'Form Attributes', 'planet4-form-builder' ),
			'parent_item_colon'     => __( 'Parent form:', 'planet4-form-builder' ),
			'all_items'             => __( 'All Forms', 'planet4-form-builder' ),
			'add_new_item'          => __( 'Add New Form', 'planet4-form-builder' ),
			'add_new'               => __( 'Add New', 'planet4-form-builder' ),
			'new_item'              => __( 'New Form', 'planet4-form-builder' ),
			'edit_item'             => __( 'Edit Form', 'planet4-form-builder' ),
			'update_item'           => __( 'Update Form', 'planet4-form-builder' ),
			'view_item'             => __( 'View Form', 'planet4-form-builder' ),
			'view_items'            => __( 'View Forms', 'planet4-form-builder' ),
			'search_items'          => __( 'Search Form', 'planet4-form-builder' ),
			'not_found'             => __( 'Not found', 'planet4-form-builder' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'planet4-form-builder' ),
			'featured_image'        => __( 'Featured Image', 'planet4-form-builder' ),
			'set_featured_image'    => __( 'Set featured image', 'planet4-form-builder' ),
			'remove_featured_image' => __( 'Remove featured image', 'planet4-form-builder' ),
			'use_featured_image'    => __( 'Use as featured image', 'planet4-form-builder' ),
			'insert_into_item'      => __( 'Insert into Form', 'planet4-form-builder' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Form', 'planet4-form-builder' ),
			'items_list'            => __( 'Forms list', 'planet4-form-builder' ),
			'items_list_navigation' => __( 'Forms list navigation', 'planet4-form-builder' ),
			'filter_items_list'     => __( 'Filter forms list', 'planet4-form-builder' ),
		];

		$rewrite = [
			'slug'       => 'form',
			'with_front' => true,
			'pages'      => true,
			'feeds'      => false,
		];

		$args = [
			'label'               => __( 'Form', 'planet4-form-builder' ),
			'description'         => __( 'Planet 4 Forms custom post type.', 'planet4-form-builder' ),
			'labels'              => $labels,
			'supports'            => [ 'title', 'author' ],
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 160,
			'menu_icon'           => 'dashicons-feedback',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => 'forms',
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => $rewrite,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => 'forms',
		];

		register_post_type( P4FB_FORM_CPT, $args );
	}

	/**
	 * Return list of supported CRM types.
	 * The array should be in the form of 'crm_abbrev' => 'CRM name'.
	 * The name should be html escaped ready for use in a form etc.
	 *
	 * @return array The list of types.
	 */
	public function get_crm_type_options() : array {
		return apply_filters( 'p4fb_get_crm_options', [
			//'en'  => esc_html__( 'Engaging Networks', 'planet4-form-builder' ),
			//'sf'  => esc_html__( 'Salesforce', 'planet4-form-builder' ),
			'hs'  => esc_html__( 'Hubspot', 'planet4-form-builder' ),
			'bsd' => esc_html__( 'BSD', 'planet4-form-builder' ),
		] );
	}

	/**
	 * Check the pased form type is an allowed value.
	 *
	 * @param mixed $form_type
	 *
	 * @return bool
	 */
	public function validate_form_type( $form_type ) {
		$form_type = sanitize_text_field( $form_type );
		if ( array_key_exists( $form_type, $this->get_crm_type_options() ) ) {
			return $form_type;
		}

		return '';
	}

	/**
	 * Add the required CMB2 meta boxes and fields.
	 */
	public function add_fields() {
		// Fields meta box.
		$cmb_form_mb = new_cmb2_box( [
			'id'           => P4FB_KEY_PREFIX . 'form_metabox',
			'title'        => esc_html__( 'Form details', 'planet4-form-builder' ),
			'object_types' => [ P4FB_FORM_CPT ],
		] );

		$cmb_form_mb->add_field( [
			'id'          => P4FB_KEY_PREFIX . 'description',
			'name'        => esc_html__( 'Description', 'planet4-form-builder' ),
			'description' => esc_html__( 'Write a short description for this form', 'planet4-form-builder' ),
			'type'        => 'textarea_small',
		] );

		$cmb_form_mb->add_field(
			[
				'id'          => P4FB_KEY_PREFIX . 'form_type',
				'name'        => esc_html__( 'CRM type', 'planet4-form-builder' ),
				'description' => esc_html__( 'Which CRM does this form map to?', 'planet4-form-builder' ),
				'type'        => 'select',
				'options'     => $this->get_crm_type_options(),
			]
		);

		$cmb_form_mb->add_field( [
			'id'          => P4FB_KEY_PREFIX . 'submit_text',
			'name'        => esc_html__( 'Submit button', 'planet4-form-builder' ),
			'description' => esc_html__( 'What should the submit button say?', 'planet4-form-builder' ),
			'type'        => 'text',
			'default'     => esc_html__( 'Submit', 'planet4-form-builder' ),
		] );

		/**
		 * Repeatable Field Groups
		 */
		$cmb_fields_mb = new_cmb2_box( [
			'id'           => P4FB_KEY_PREFIX . 'fields_metabox',
			'title'        => esc_html__( 'Form Fields', 'planet4-form-builder' ),
			'object_types' => [ P4FB_FORM_CPT ],
		] );

		// $group_field_id is the field id string, so in this case: P4FB_KEY_PREFIX . 'fields'
		$group_field_id = $cmb_fields_mb->add_field( [
			'id'      => P4FB_KEY_PREFIX . 'fields',
			'type'    => 'group',
			'options' => [
				'group_title'   => esc_html__( 'Field {#}', 'planet4-form-builder' ), // The {#} gets replaced by row number.
				'add_button'    => esc_html__( 'Add Another Field', 'planet4-form-builder' ),
				'remove_button' => esc_html__( 'Remove Field', 'planet4-form-builder' ),
				'sortable'      => true,
			],
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'              => 'name',
			'name'            => esc_html__( 'Field ID', 'planet4-form-builder' ),
			'type'            => 'text',
			'sanitization_cb' => [ $this, 'sanitize_name' ],
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'          => 'description',
			'name'        => esc_html__( 'Description', 'planet4-form-builder' ),
			'description' => esc_html__( 'Write a short description for this entry, if needed', 'planet4-form-builder' ),
			'type'        => 'textarea_small',
		] );

		$cmb_fields_mb->add_group_field(
			$group_field_id,
			[
				'id'      => 'type',
				'name'    => esc_html__( 'Field type', 'planet4-form-builder' ),
				'type'    => 'select',
				'options' => [
					'text'     => __( 'Text field', 'planet4-form-builder' ),
					'email'    => __( 'Email field', 'planet4-form-builder' ),
					'tel'      => __( 'Telephone field', 'planet4-form-builder' ),
					'textarea' => __( 'Text area', 'planet4-form-builder' ),
					'select'   => __( 'Dropdown select', 'planet4-form-builder' ),
					'checkbox' => __( 'Checkbox', 'planet4-form-builder' ),
					'hidden'   => __( 'Hidden value', 'planet4-form-builder' ),
					'date'     => __( 'Date', 'planet4-form-builder' ),
				],
			]
		);

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'   => 'label',
			'name' => esc_html__( 'Label', 'planet4-form-builder' ),
			'type' => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'          => 'options',
			'name'        => esc_html__( 'Field options', 'planet4-form-builder' ),
			'description' => esc_html__(
				'Used for drop down select, multiple checkboxes or radio buttons. Enter each choice on a new line. For more control, you may specify both a value and label like this: "red:Red"',
				'planet4-form-builder'
			),
			'type'        => 'textarea',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'          => 'value',
			'name'        => esc_html__( 'Field default value', 'planet4-form-builder' ),
			'description' => esc_html__( 'Default value for a text field or the value for a single checkbox.', 'planet4-form-builder' ),
			'type'        => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'          => 'required',
			'name'        => esc_html__( 'Required', 'planet4-form-builder' ),
			'description' => esc_html__( 'Is this field required?', 'planet4-form-builder' ),
			'type'        => 'checkbox',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'id'          => 'class',
			'name'        => esc_html__( 'HTML class', 'planet4-form-builder' ),
			'description' => esc_html__( 'add any arbitrary classes you need to affect the display.', 'planet4-form-builder' ),
			'type'        => 'text',
		] );

		if ( function_exists( 'cmb2_ajax' ) ) {
			cmb2_ajax();
		}

	}

	/**
	 * Make sure the name will work as a form field name.
	 *
	 * @param string $value The field name to be sanitized.
	 *
	 * @return string The sanitized field name.
	 */
	public function sanitize_name( $value ) {
		return sanitize_title( $value );
	}

	/**
	 * Optionally return our form template.
	 *
	 * @param string $original_template The current calculated template.
	 *
	 * @return string Our template or the original.
	 */
	public function template_include( string $original_template ) : string {
		if ( P4FB_FORM_CPT === get_post_type() ) {
			return self::$template_loader->get_template_part( 'single', P4FB_FORM_CPT, false );
		}

		return $original_template;
	}

	/**
	 * Simple sanitization for text field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_field_text( $value ) :string {
		return sanitize_text_field( $value );
	}

	/**
	 * Simple sanitization for date field.
	 *
	 * @param string $value The value from the form submission or empty string.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_field_date( $value ) :string {
		return $value;
	}

	/**
	 * Simple sanitization for email field.
	 *
	 * @param string $value The email address to sanitize.
	 *
	 * @return string
	 */
	public function sanitize_field_email( $value ) :string {
		return sanitize_email( $value );
	}

	/**
	 * Sanitize telephone number.
	 *
	 * @param string $value The phone to sanitize.
	 *
	 * @return string
	 */
	public function sanitize_field_telephone( $value ) :string {
		// Requirement:  99-99999999 or 99-9999999 (area code - 8 or 9 digits).
		if ( preg_match( '/^\d\d\-\d\d\d\d\d\d\d\d\d?$/', $value ) ) {
			return $value;
		}
		return '';
	}

	/**
	 * Simple sanitization for textarea field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_field_textarea( $value ) :string {
		return sanitize_textarea_field( $value );
	}

	/**
	 * Simple sanitization for select field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_field_select( $value ) :string {
		return sanitize_text_field( $value );
	}

	/**
	 * Simple sanitization for checkbox field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string The sanitized value.
	 */
	public function sanitize_field_checkbox( $value ) :string {
		return sanitize_text_field( $value );
	}

	/**
	 * Simple sanitization for checkbox group field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string|array The sanitized value.
	 */
	public function sanitize_field_checkbox_group( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Simple sanitization for radio button fields.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 *
	 * @return string|array The sanitized value.
	 */
	public function sanitize_field_radio_group( $value ) {
		if ( is_array( $value ) ) {
			return sanitize_text_field( $value[0] );
		}

		return sanitize_text_field( $value );
	}

	/**
	 * Simple validation for text field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error.
	 */
	public function validate_field_text( $value, \WP_Post $form, array $field ) {
		if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
			return __( 'Required field missing.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Simple validation for date field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error.
	 */
	public function validate_field_date( $value, \WP_Post $form, array $field ) {
		if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
			return __( 'Required field missing.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Simple validation for email field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error
	 */
	public function validate_field_email( $value, \WP_Post $form, array $field ) {
		if ( ! empty( $field['required'] ) && empty( $value ) ) {
			return __( 'Required field missing.', 'planet4-form-builder' );
		}
		return false;
	}

	/**
	 * Simple validation for telephone field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error
	 */
	public function validate_field_tel( $value, \WP_Post $form, array $field ) {
		if ( ! empty( $field['required'] ) && empty( $value ) ) {
			return __( 'Required field missing.', 'planet4-form-builder' );
		}
		return false;
	}

	/**
	 * Simple validation for textarea field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error.
	 */
	public function validate_field_textarea( $value, \WP_Post $form, array $field ) {
		if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
			return __( 'Required field missing.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Simple validation for select field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error.
	 */
	public function validate_field_select( $value, \WP_Post $form, array $field ) {
		$options = $this->get_options( $field );
		if ( in_array( $value, array_keys( $options ), true ) || in_array( $value, $options, true ) ) {
			return false;
		}

		return __( 'You must select an option.', 'planet4-form-builder' );
	}

	/**
	 * Simple validation for checkbox field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|string False if no error. Error message if there is an error.
	 */
	public function validate_field_checkbox( $value, \WP_Post $form, array $field ) {
		if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
			return __( 'You must check the box.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Simple validation for checkbox group field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|array The current error. False if no error. An array [ 'field_name' => 'error message' ] is there is an error.
	 */
	public function validate_field_checkbox_group( $value, \WP_Post $form, array $field ) {
		if ( isset( $field['required'] ) && $field['required'] && empty( $value ) ) {
			return __( 'You must check an option.', 'planet4-form-builder' );
		}

		$options = $this->get_options( $field );
		if ( ! array_intersect( (array) $value, array_keys( $options ) ) && ! array_intersect( (array) $value, $options ) ) {
			return __( 'You must check an option.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Simple validation for radio group field.
	 *
	 * @param string|array $value The value from the form submission or empty string.
	 * @param \WP_Post     $form  The CRM form.
	 * @param array        $field The field definition.
	 *
	 * @return boolean|array The current error. False if no error. An array [ 'field_name' => 'error message' ] is there is an error.
	 */
	public function validate_field_radio_group( $value, \WP_Post $form, array $field ) {
		$options = $this->get_options( $field );
		if ( ! in_array( $value, array_keys( $options ), true ) && ! in_array( $value, $options, true ) ) {
			return __( 'You must choose an option.', 'planet4-form-builder' );
		}

		return false;
	}

	/**
	 * Retrieve the field options.
	 *
	 * @param array $field The field definition.
	 *
	 * @return array The parsed options as [ 'option_value' => 'option' ] or [ 'option', 'option', 'option'...]
	 */
	public function get_options( array $field ) :array {
		$options     = $field['options'];
		$options     = explode( "\n", $options );
		$new_options = [];
		foreach ( $options as $option ) {
			if ( empty( trim( $option ) ) ) {
				continue;
			}
			$parts = explode( '|', $option, 2 );
			if ( count( $parts ) > 1 ) {
				$new_options[ trim( $parts[0] ) ] = trim( $parts[1] );
			} else {
				$new_options[ trim( $option ) ] = trim( $option );
			}
		}

		return $new_options;
	}

}
