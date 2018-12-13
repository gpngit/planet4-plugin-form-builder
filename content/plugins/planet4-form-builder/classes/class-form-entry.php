<?php
/**
 * Base form entry class.
 */

namespace P4FB\Form_Builder;

use WP_Post;

class Form_Entry {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Entry
	 */
	static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Entry
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
		add_action( 'p4fb_save_form_submission', [ $this, 'save_form_submission' ], 10, 3 );
	}

	/**
	 * Register Custom Post Type
	 */
	public function register_post_type() {

		$labels = [
			'name'                  => _x( 'Entries', 'Post Type General Name', 'planet4-form-builder' ),
			'singular_name'         => _x( 'Entry', 'Post Type Singular Name', 'planet4-form-builder' ),
			'menu_name'             => __( 'Form Entries', 'planet4-form-builder' ),
			'name_admin_bar'        => __( 'Entry', 'planet4-form-builder' ),
			'archives'              => __( 'Entry Archives', 'planet4-form-builder' ),
			'attributes'            => __( 'Entry Attributes', 'planet4-form-builder' ),
			'parent_item_colon'     => __( 'Parent form:', 'planet4-form-builder' ),
			'all_items'             => __( 'All Entries', 'planet4-form-builder' ),
			'add_new_item'          => __( 'Add New Entry', 'planet4-form-builder' ),
			'add_new'               => __( 'Add New', 'planet4-form-builder' ),
			'new_item'              => __( 'New Entry', 'planet4-form-builder' ),
			'edit_item'             => __( 'Edit Entry', 'planet4-form-builder' ),
			'update_item'           => __( 'Update Entry', 'planet4-form-builder' ),
			'view_item'             => __( 'View Entry', 'planet4-form-builder' ),
			'view_items'            => __( 'View Entries', 'planet4-form-builder' ),
			'search_items'          => __( 'Search Entry', 'planet4-form-builder' ),
			'not_found'             => __( 'Not found', 'planet4-form-builder' ),
			'not_found_in_trash'    => __( 'Not found in Trash', 'planet4-form-builder' ),
			'featured_image'        => __( 'Featured Image', 'planet4-form-builder' ),
			'set_featured_image'    => __( 'Set featured image', 'planet4-form-builder' ),
			'remove_featured_image' => __( 'Remove featured image', 'planet4-form-builder' ),
			'use_featured_image'    => __( 'Use as featured image', 'planet4-form-builder' ),
			'insert_into_item'      => __( 'Insert into Entry', 'planet4-form-builder' ),
			'uploaded_to_this_item' => __( 'Uploaded to this Entry', 'planet4-form-builder' ),
			'items_list'            => __( 'Entries list', 'planet4-form-builder' ),
			'items_list_navigation' => __( 'Entries list navigation', 'planet4-form-builder' ),
			'filter_items_list'     => __( 'Filter forms list', 'planet4-form-builder' ),
		];

		$args = [
			'label'               => __( 'Entry', 'planet4-form-builder' ),
			'description'         => __( 'Planet 4 Form Entries custom post type.', 'planet4-form-builder' ),
			'labels'              => $labels,
			'supports'            => [ 'title' ],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
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

		register_post_type( P4FB_ENTRY_CPT, $args );
	}

	/**
	 * Save away the form submission..
	 * Returns errors array updated with a saved reference (post id), or an error indication.
	 *
	 * @param array   $errors    (passed by reference).
	 * @param WP_Post $form      The CRM form.
	 * @param array   $form_data The form submission data.
	 *
	 */
	public function save_form_submission( array &$errors, WP_Post $form, array $form_data ) {
		// Create entry post
		$entry_id = wp_insert_post( [
			'post_type' => P4FB_ENTRY_CPT,
			// translators: %d is replaced with the current unix timestamp.
			'post_title'   => sprintf( __( 'form entry %d', 'planet4-form-builder' ), time() ),
			'post_content' => wp_json_encode( $form_data ),
			'post_status' => 'publish',
		] );

		if ( ! is_wp_error( $entry_id ) ) {
			add_post_meta( $entry_id, 'p4_form_id', $form->ID, true );
			add_post_meta( $entry_id, 'p4_form_name', $form->post_title, true );
			add_post_meta( $entry_id, 'p4_form_type', get_post_meta( $form->ID, 'p4_form_form_type', true ), true );
			$errors['id'] = $entry_id;
		} else {
			$errors['error'] = $entry_id;
		}
	}
}
