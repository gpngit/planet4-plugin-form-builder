<?php
declare( strict_types=1 );
/**
 * Form shortcode class.
 *
 */

namespace P4FB\Form_Builder;

use Timber\Post;
use Timber\Timber;

class Form_Shortcode {
	/**
	 *  Store the singleton instance
	 *
	 * @var  Form_Shortcode
	 */
	private static $instance;

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
	public function load() {
		add_action( 'init', [ $this, 'shortcode_ui_detection' ] ); // Check to see if Shortcake is running, show an admin notice if not.
		add_action( 'init', [ $this, 'register_shortcode' ] ); // Register the shortcode.
		add_action( 'register_shortcode_ui', [ $this, 'register_shortcode_ui' ] ); // Register the Shortcode UI setup for the shortcode.
	}


	/**
	 * If Shortcake isn't active, then add an administration notice.
	 */
	public function shortcode_ui_detection() {
		if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			add_action( 'admin_notices', [ $this, 'admin_notice_need_shortcake' ] );
		}
	}

	/**
	 * Display an administration notice if the user can activate plugins.
	 */
	public function admin_notice_need_shortcake() {
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
	public function register_shortcode() {
		add_shortcode( P4FB_FORM_SHORTCODE, [ $this, 'shortcode_ui_dev_shortcode' ] );
	}

	/**
	 * Shortcode UI setup for the Planet4 Form shortcode.
	 */
	public function register_shortcode_ui() {
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
	public function shortcode_ui_dev_shortcode( $attr, $content, $shortcode_tag ) {

		$attr       = shortcode_atts( [ 'form' => '' ], $attr, $shortcode_tag );
		$form_title = ! empty( $attr['form'] ) ? get_the_title( $attr['form'] ) : '';

		wp_enqueue_style( 'p4fb' );
		ob_start();
		?>
		<div class="p4fb">
			<h2>
				<?php if ( ! empty( $attr['form'] ) ) : ?>
					<?php echo esc_html( $form_title ); ?><br>
				<?php endif; ?>
			</h2>
			<p>
				<?php
				$desc = get_post_meta( $attr['form'], 'p4_form_description', true );
				if ( $desc ) {
					echo esc_html( $desc );
				}
				?>
			</p>
			<?php
			set_query_var( 'form_id', $attr['form'] );
			Form_Builder::$template_loader->get_template_part( 'single', P4FB_FORM_CPT );
			?>
		</div>
		<?php

		return ob_get_clean();
	}
}
