<?php
/**
 * Base form builder class.
 */

namespace P4FB\Form_Builder;

//use CMB2_Field;

class Form_Builder {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Builder
	 */

	static $instance;

	const P4FB_FORM_CPT = 'p4_form';

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Builder
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register CPT. Set up our hooks.
	 */
	function load() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'cmb2_init', [ $this, 'add_fields' ] );
		add_filter( 'enter_title_here', [ $this, 'filter_enter_title_here' ], 10, 2 );
	}

	function filter_enter_title_here( $title, $post ) {
		if ( self::P4FB_FORM_CPT === $post->post_type ) {
			return __( 'Enter form name' );
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

		register_post_type( self::P4FB_FORM_CPT, $args );
	}

	public function add_fields() {
		// Fields meta box
		$prefix = 'p4_form_';

		$cmb_form_mb = new_cmb2_box( [
			'id'           => $prefix . 'form_metabox',
			'title'        => esc_html__( 'Form Details', 'planet4-form-builder' ),
			'object_types' => [ self::P4FB_FORM_CPT ],
		] );

		$cmb_form_mb->add_field(
			[
				'id'      => $prefix . 'form_type',
				'name'    => esc_html__( 'CMS type', 'planet4-form-builder' ),
				'type'    => 'select',
				'options' => [
					'en'  => esc_html__( 'Engaging Networks', 'planet4-form-builder' ),
					'sf'  => esc_html__( 'Salesforce', 'planet4-form-builder' ),
					'hs'  => esc_html__( 'Hubspot', 'planet4-form-builder' ),
					'bsd' => esc_html__( 'BSD', 'planet4-form-builder' ),
				],
			]
		);

		/**
		 * Repeatable Field Groups
		 */
		$cmb_fields_mb = new_cmb2_box( [
			'id'           => $prefix . 'fields_metabox',
			'title'        => esc_html__( 'Form Fields', 'planet4-form-builder' ),
			'object_types' => [ self::P4FB_FORM_CPT ],
		] );

		// $group_field_id is the field id string, so in this case: $prefix . 'fields'
		$group_field_id = $cmb_fields_mb->add_field( [
			'id'          => $prefix . 'fields',
			'type'        => 'group',
			'description' => esc_html__( 'Generates reusable form entries', 'planet4-form-builder' ),
			'options'     => [
				'group_title'   => esc_html__( 'Field {#}', 'planet4-form-builder' ), // The {#} gets replaced by row number.
				'add_button'    => esc_html__( 'Add Another Field', 'planet4-form-builder' ),
				'remove_button' => esc_html__( 'Remove Field', 'planet4-form-builder' ),
				'sortable'      => true,
			],
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name' => esc_html__( 'Field name', 'planet4-form-builder' ),
			'id'   => 'name',
			'type' => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name'        => esc_html__( 'Description', 'planet4-form-builder' ),
			'description' => esc_html__( 'Write a short description for this entry', 'planet4-form-builder' ),
			'id'          => 'description',
			'type'        => 'textarea_small',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name'             => esc_html__( 'Field type', 'planet4-form-builder' ),
			'id'               => 'type',
			'type'             => 'select',
			'show_option_none' => true,
			'options'          => [
				'text'     => __( 'Text field', 'planet4-form-builder' ),
				'textarea' => __( 'Text area', 'planet4-form-builder' ),
				'select'   => __( 'Dropdown select', 'planet4-form-builder' ),
				'checkbox' => __( 'Checkbox', 'planet4-form-builder' ),
				'radio'    => __( 'Radio button', 'planet4-form-builder' ),
			],
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name' => esc_html__( 'Field label', 'planet4-form-builder' ),
			'id'   => 'label',
			'type' => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name' => esc_html__( 'Field default value', 'planet4-form-builder' ),
			'id'   => 'default',
			'type' => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name'        => esc_html__( 'Field options', 'planet4-form-builder' ),
			'id'          => 'options',
			'description' => esc_html__(
				'Used for drop down select. Enter each choice on a new line. For more control, you may specify both a value and label like this: red : Red',
				'planet4-form-builder'
			),
			'type'        => 'textarea',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name'        => esc_html__( 'Field value', 'planet4-form-builder' ),
			'id'          => 'value',
			'description' => esc_html__( 'used for checkbox, radio, and hidden fields.', 'planet4-form-builder' ),
			'type'        => 'text',
		] );

		$cmb_fields_mb->add_group_field( $group_field_id, [
			'name'        => esc_html__( 'Field class', 'planet4-form-builder' ),
			'id'          => 'class',
			'description' => esc_html__( 'add any abitrary classes you need to affect the display.', 'planet4-form-builder' ),
			'type'        => 'text',
		] );

		if ( function_exists( 'cmb2_ajax' ) ) {
			cmb2_ajax();
		}

	}
}