<?php
/**
 * Form shortcode class.
 *
 */

namespace P4FB\Form_Builder;

use Timber\Post;

class Form_Shortcode {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Shortcode
	 */
	static $instance;

	/**
	 * Create singleton instance.
	 *
	 * @return Form_Shortcode
	 */
	public static function get_instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Set up our hooks.
	 */
	function load() {
		add_action( 'init', [ $this, 'shortcode_ui_detection' ] ); // Check to see if Shortcake is running, show an admin notice if not.
		add_action( 'init', [ $this, 'register_shortcode' ] ); // Register the shortcode.
		add_action( 'register_shortcode_ui', [ $this, 'register_shortcode_ui' ] ); // Register the Shortcode UI setup for the shortcode.
	}


	/**
	 * If Shortcake isn't active, then add an administration notice.
	 */
	function shortcode_ui_detection() {
		if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_need_shortcake' ] );
		}
	}

	/**
	 * Display an administration notice if the user can activate plugins.
	 */
	function admin_notice_need_shortcake() {
		if ( current_user_can( 'activate_plugins' ) ) {
			?>
			<div class="error message">
				<p><?php esc_html_e( 'Shortcode UI plugin must be active for Planet4 Form Shortcode UI to function.', 'planet4-form-builder' ); ?></p>
			</div>
			<?php
		}
	}

	/**
	 * Register our shortcode.
	 *
	 * This registration is done independently of any UI that might be associated with the shortcode, so it always happens, even if
	 * Shortcake is not active.
	 *
	 */
	function register_shortcode() {
		add_shortcode( P4FB_FORM_SHORTCODE, [ $this, 'shortcode_ui_dev_shortcode' ] );
	}


	/**
	 * Shortcode UI setup for the Planet4 Form shortcode.
	 *
	 */
	function register_shortcode_ui() {
		$fields = [
			[
				'label'    => esc_html__( 'Select Form', 'planet4-form-builder' ),
				'attr'     => 'form',
				'type'     => 'post_select',
				'query'    => [ 'post_type' => P4FB_FORM_CPT ],
				'multiple' => true,
			],
		];

		/*
		 * Define the Shortcode UI arguments.
		 */
		$shortcode_ui_args = [
			'label'         => esc_html__( 'Planet 4 Form', 'planet4-form-builder' ),
			'listItemImage' => 'dashicons-feedback',
			'post_type'     => [ 'post', 'page' ],
			'attrs'         => $fields,
		];

		shortcode_ui_register_for_shortcode( P4FB_FORM_SHORTCODE, $shortcode_ui_args );
	}




	/*
	 * 3. Define the callback for the advanced shortcode.
	 */

	/**
	 * Callback for the shortcake_dev shortcode.
	 *
	 * It renders the shortcode based on supplied attributes.
	 */
	function shortcode_ui_dev_shortcode( $attr, $content, $shortcode_tag ) {

		$attr       = shortcode_atts( [
			'form' => '',
		], $attr, $shortcode_tag );
		$form_title = ! empty( $attr['form'] ) ? get_the_title( $attr['form'] ) : '';

		ob_start();
		?>
		<section class="pullquote" style="padding: 20px; background: rgba(0, 0, 0, 0.1);">
			<p style="margin:0; padding: 0;">

				<?php if ( ! empty( $attr['form'] ) && is_admin() ) : ?>
					<strong><?php esc_html_e( 'Form:', 'planet4-form-builder' ); ?></strong> <?php echo esc_html( $form_title ); ?><br>
				<?php endif; ?>
				<?php
				add_filter( 'timber/context', function ( array $context ) use ( $attr ) : array {
					if ( isset( $attr['form'] ) ) {
						$context['post'] = new Post( $attr['form'] );
					}

					return $context;
				} );
				Form_Builder::$template_loader->get_template_part( 'single', P4FB_FORM_CPT, true );
				?>
			</p>
		</section>
		<?php

		return ob_get_clean();
	}


}
