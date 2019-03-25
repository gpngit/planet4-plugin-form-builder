<?php
declare( strict_types=1 );
/**
 * Base form mapping class.
 */

namespace P4FB\Form_Builder;

use WP_Query;

class Form_Mapping {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Mapping
	 */
	private static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Mapping
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
	public function load() {
		add_action( 'init', [ $this, 'register_post_type' ] );

		add_action( 'cmb2_init', [ $this, 'add_fields' ] );
		add_filter( 'enter_title_here', [ $this, 'filter_enter_title_here' ], 10, 2 );
		// Add dynamic fields during normal view.
		add_action( 'cmb2_init_hookup_' . P4FB_MAPPING_KEY_PREFIX . 'metabox', [ $this, 'add_fields_dynamically_to_box' ] );
		// Add dynamic fields during save process.
		add_action( 'cmb2_post_process_fields_' . P4FB_MAPPING_KEY_PREFIX . 'metabox', [ $this, 'add_fields_dynamically_to_box' ] );
		// Save this mapping to the selected form
		add_action( 'cmb2_save_post_fields_' . P4FB_MAPPING_KEY_PREFIX . 'form_metabox', [ $this, 'add_mapping_to_form' ], 10, 3 );

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
		if ( P4FB_MAPPING_CPT === $post->post_type ) {
			return __( 'Enter mapping name', 'planet4-form-builder' );
		}

		return $title;
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_post_type() {

		$labels = [
			'name'                  => _x( 'Mappings', 'Post Type General Name', 'planet4-form-builder' ),
			'singular_name'         => _x( 'Mapping', 'Post Type Singular Name', 'planet4-form-builder' ),
			'menu_name'             => __( 'CRM Mappings', 'planet4-form-builder' ),
			'name_admin_bar'        => __( 'Mapping', 'planet4-form-builder' ),
			'archives'              => __( 'Mapping Archives', 'planet4-form-builder' ),
			'attributes'            => __( 'Mapping Attributes', 'planet4-form-builder' ),
			'parent_item_colon'     => __( 'Parent form:', 'planet4-form-builder' ),
			'all_items'             => __( 'All Mappings', 'planet4-form-builder' ),
			'add_new_item'          => __( 'Add New Mapping', 'planet4-form-builder' ),
			'add_new'               => __( 'Add New', 'planet4-form-builder' ),
			'new_item'              => __( 'New Mapping', 'planet4-form-builder' ),
			'edit_item'             => __( 'Edit Mapping', 'planet4-form-builder' ),
			'update_item'           => __( 'Update Mapping', 'planet4-form-builder' ),
			'view_item'             => __( 'View Mapping', 'planet4-form-builder' ),
			'view_items'            => __( 'View Mappings', 'planet4-form-builder' ),
			'search_items'          => __( 'Search Mapping', 'planet4-form-builder' ),
			'not_found'             => __( 'Not found', 'planet4-form-builder' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'planet4-form-builder' ),
			'featured_image'        => __( 'Featured Image', 'planet4-form-builder' ),
			'set_featured_image'    => __( 'Set featured image', 'planet4-form-builder' ),
			'remove_featured_image' => __( 'Remove featured image', 'planet4-form-builder' ),
			'use_featured_image'    => __( 'Use as featured image', 'planet4-form-builder' ),
			'insert_into_item'      => __( 'Insert into Mapping', 'planet4-form-builder' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Mapping', 'planet4-form-builder' ),
			'items_list'            => __( 'Mappings list', 'planet4-form-builder' ),
			'items_list_navigation' => __( 'Mappings list navigation', 'planet4-form-builder' ),
			'filter_items_list'     => __( 'Filter forms list', 'planet4-form-builder' ),
		];

		$args = [
			'label'               => __( 'Mapping', 'planet4-form-builder' ),
			'description'         => __( 'Planet 4 Form Mappings custom post type.', 'planet4-form-builder' ),
			'labels'              => $labels,
			'supports'            => [ 'title', 'author' ],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => 'edit.php?post_type=' . P4FB_FORM_CPT,
			'menu_position'       => 160,
			'menu_icon'           => 'dashicons-feedback',
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'rewrite'             => false,
			'capability_type'     => 'post',
			'show_in_rest'        => true,
			'rest_base'           => 'mappings',
		];

		register_post_type( P4FB_MAPPING_CPT, $args );
	}

	/**
	 * Add the required CMB2 meta boxes and fields.
	 */
	public function add_fields() {
		// Fields meta box
		$cmb_mapping_mb = new_cmb2_box( [
			'id'           => P4FB_MAPPING_KEY_PREFIX . 'form_metabox',
			'title'        => esc_html__( 'Mapping details', 'planet4-form-builder' ),
			'object_types' => [ P4FB_MAPPING_CPT ],
		] );

		$cmb_mapping_mb->add_field( [
			'id'          => P4FB_MAPPING_KEY_PREFIX . 'description',
			'name'        => esc_html__( 'Description', 'planet4-form-builder' ),
			'description' => esc_html__( 'Write a short description for this mapping', 'planet4-form-builder' ),
			'type'        => 'textarea_small',
		] );

		$cmb_mapping_mb->add_field( [
			'id'          => P4FB_MAPPING_KEY_PREFIX . 'form_id',
			'name'        => esc_html__( 'Form ', 'planet4-form-builder' ),
			'description' => esc_html__( 'Which CRM form does this mapping apply to?', 'planet4-form-builder' ),
			'type'        => 'select',
			'options'     => $this->get_crm_form_options(),
		] );

		/**
		 * Field Mappings
		 */
		$cmb_fields_mb = new_cmb2_box( [
			'id'           => P4FB_MAPPING_KEY_PREFIX . 'metabox',
			'title'        => esc_html__( 'Field Mapping', 'planet4-form-builder' ),
			'object_types' => [ P4FB_MAPPING_CPT ],
		] );

		// the sub fields are added dynamically

		if ( function_exists( 'cmb2_ajax' ) ) {
			cmb2_ajax();
		}

	}

	/**
	 * Add CMB2 fileds dynmically based on the chosen fom definition.
	 *
	 * @param $cmb The CMB2 object.
	 */
	public function add_fields_dynamically_to_box( $cmb ) {
		if ( $cmb->object_id() ) {
			// Loop through however many fields are in the associated form
			$form_id = get_post_meta( $cmb->object_id(), P4FB_MAPPING_KEY_PREFIX . 'form_id', true );
			if ( ! $form_id ) {
				return;
			}
			$fields = get_post_meta( $form_id, 'p4_form_fields', true );

			foreach ( $fields as $field ) {
				$cmb->add_field( [
					'id'          => 'form_field_' . $field['name'],
					'name'        => $field['name'],
					/* translators: %s is replaced by the name of the form field. */
					'description' => esc_html( sprintf( __( "Remote field ID for '%s'", 'planet4-form-builder' ), $field['label'] ) ),
					'type'        => 'text',
				] );
			}
		}
	}

	/**
	 * Generate an option list of all the from definitions.
	 *
	 * @return array
	 */
	public function get_crm_form_options() : array {
		$my_query = new WP_Query( [
			'post_type'      => P4FB_FORM_CPT,
			'posts_per_page' => 99,
		] );
		$forms    = wp_list_pluck( $my_query->posts, 'post_title', 'ID' );
		$forms    = array_map( 'esc_html', $forms );

		return apply_filters( 'p4fb_get_form_list_options', $forms );
	}

	/**
	 * Add the Mapping id to the Form
	 *
	 * @param int    $object_id   The ID of the current object
	 */
	public function add_mapping_to_form( int $object_id ) {
		// Check whether the form_id is set/changed
		$form_id = get_post_meta( $object_id, P4FB_MAPPING_KEY_PREFIX . 'form_id', true );
		if ( $form_id ) {
			if ( false === add_post_meta( $form_id, P4FB_KEY_PREFIX . 'mapping_id', $object_id, true ) ) {
				add_post_meta( $form_id, P4FB_KEY_PREFIX . 'mapping_id', $object_id );
			}
		}
	}

}
