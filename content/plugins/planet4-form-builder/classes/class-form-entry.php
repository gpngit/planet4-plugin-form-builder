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
		if ( is_admin() ) {
			add_filter( 'manage_' . P4FB_ENTRY_CPT . '_posts_columns', [ $this, 'manage_posts_columns' ], 10 );
			add_action( 'manage_' . P4FB_ENTRY_CPT . '_posts_custom_column', [ $this, 'manage_posts_custom_column' ], 10, 2 );
			add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );
			add_action( 'post_action_requeue', [ $this, 'post_action_requeue_entry_handler' ] );
		}
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
			'post_type'    => P4FB_ENTRY_CPT,
			// translators: %d is replaced with the current unix timestamp as a unique id.
			'post_title'   => sprintf( __( 'form entry %d', 'planet4-form-builder' ), time() ),
			'post_content' => wp_json_encode( $form_data ),
			'post_status'  => 'publish',
		] );

		if ( ! is_wp_error( $entry_id ) ) {
			add_post_meta( $entry_id, P4FB_KEY_PREFIX . 'form_id', $form->ID, true );
			add_post_meta( $entry_id, P4FB_KEY_PREFIX . 'form_name', $form->post_title, true );
			add_post_meta( $entry_id, P4FB_KEY_PREFIX . 'form_type', get_post_meta( $form->ID, P4FB_KEY_PREFIX . 'form_type', true ), true );
			$errors['id'] = $entry_id;
		} else {
			$errors['error'] = $entry_id;
		}
	}


	/**
	 * Add our extra post columns.
	 *
	 * @param array $columns The current set of columns.
	 *
	 * @return array The modified list.
	 */
	public function manage_posts_columns( array $columns ) : array {
		$columns[ P4FB_ENTRY_STATUS_META_KEY ]   = __( 'Status', 'planet4-form-builder' );
		$columns[ P4FB_ENTRY_RESPONSE_META_KEY ] = __( 'Response', 'planet4-form-builder' );

		return $columns;
	}

	/**
	 * Output the information for each of our columns.
	 *
	 * @param string $column_name The column to output.
	 * @param int    $post_id     The post in question.
	 */
	public function manage_posts_custom_column( $column_name, $post_id ) {
		$send_stati = [
			P4FB_ENTRY_STATUS_QUEUED  => __( 'Queued', 'planet4-form-builder' ),
			P4FB_ENTRY_STATUS_PROCESS => __( 'Processing', 'planet4-form-builder' ),
			P4FB_ENTRY_STATUS_SENT    => __( 'Sent', 'planet4-form-builder' ),
			P4FB_ENTRY_STATUS_ERROR   => __( 'Error', 'planet4-form-builder' ),
		];

		switch ( $column_name ) {
			case P4FB_ENTRY_STATUS_META_KEY:
				$send_status = get_post_meta( $post_id, P4FB_ENTRY_STATUS_META_KEY, true );
				if ( in_array( $send_status, array_keys( $send_stati ), true ) ) {
					echo $send_stati[ $send_status ];
				} else {
					echo __( 'Unknown', 'planet4-form-builder' );
				}
				break;
			case P4FB_ENTRY_RESPONSE_META_KEY:
				$response = get_post_meta( $post_id, P4FB_ENTRY_RESPONSE_META_KEY, true );
				if ( ! empty( $response ) ) {
					printf( '<span class="entry-response"><abbr title="%s">%s</abbr></span>',
						esc_attr( var_export( $response, true ) ),
						__( 'Hover mouse to see last response', 'planet4-form-builder' )
					);
				}
				break;
		}
	}

	/**
	 * Add our specific action item to the hover menu.
	 *
	 * @param array    $actions The current array of actions.
	 * @param \WP_Post $post    The current post.
	 *
	 * @return array The modified list.
	 */
	public function post_row_actions( array $actions, \WP_Post $post ) : array {
		if ( P4FB_ENTRY_CPT !== $post->post_type ) {
			return $actions;
		}

		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
			return $actions;
		}

		// Add re-queue action if errored.
		$status = get_post_meta( $post->ID, P4FB_ENTRY_STATUS_META_KEY, true );
		if ( P4FB_ENTRY_STATUS_ERROR === $status ) {
			$action             = 'requeue';
			$link               = add_query_arg( 'action', $action, admin_url( sprintf( $post_type_object->_edit_link, $post->ID ) ) );
			$link               = wp_nonce_url( $link, "$action-post_{$post->ID}" );
			$actions['requeue'] = sprintf(
				'<a href="%s" class="" aria-label="%s">%s</a>',
				$link,
				esc_attr( __( 'Re-queue entry', 'planet4-form-builder' ) ),
				_x( 'Re-queue', 'verb', 'planet4-form-builder' )
			);
		}

		return $actions;
	}

	/**
	 * Handle the requeue action from the post_edit screen.
	 *
	 * @param int $post_id The entry to requeue.
	 */
	public function post_action_requeue_entry_handler( int $post_id ) {
		global $post_type;
		// check everything is legit...
		check_admin_referer( "requeue-post_{$post_id}" );
		Entry_Handler::get_instance()->schedule_send_entry( [ 'entry_id' => $post_id ] );
		$sendback = admin_url( 'edit.php' );
		$sendback = add_query_arg( 'post_type', $post_type, $sendback );
		$sendback = add_query_arg( [
			'enqueued' => 1,
			'ids'      => $post_id,
		], $sendback );
		wp_redirect( $sendback );
		exit();
	}

}
