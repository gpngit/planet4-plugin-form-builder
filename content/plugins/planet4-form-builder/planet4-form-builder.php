<?php
/**
 * Plugin Name: Planet4 - Form Builder
 * Description: Simple form builder.
 * Plugin URI: http://github.com/greenpeace/
 * Version: 0.1.0
 * Php Version: 7.0
 *
 * Author: Human Made
 * Author URI: https://humanmade.com
 * Text Domain: planet4-form-builder
 *
 */

namespace P4FB\Form_Builder;

/**
 * Followed WordPress plugins best practices from https://developer.wordpress.org/plugins/the-basics/best-practices/
 * Followed WordPress-Core coding standards https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
 * Followed WordPress-VIP coding standards https://vip.wordpress.com/documentation/code-review-what-we-look-for/
 * Added namespacing and PSR-4 auto-loading.
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) or die( 'Direct access is forbidden !' );

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/classes/class-form-builder.php';
if ( ! class_exists( 'Gamajo_Template_Loader' ) ) {
	require plugin_dir_path( __FILE__ ) . 'class-gamajo-template-loader.php';
}
require_once __DIR__ . '/classes/class-form-template-loader.php';
add_action( 'plugins_loaded', function () {
	Form_Builder::get_instance()->load();
} );

if ( is_admin() ) {
	require_once __DIR__ . '/classes/class-form-builder-settings-page.php';
	Form_Builder_Settings_Page::get_instance();
}
