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


/**
 * Followed WordPress plugins best practices from https://developer.wordpress.org/plugins/the-basics/best-practices/
 * Followed WordPress-Core coding standards https://make.wordpress.org/core/handbook/best-practices/coding-standards/php/
 * Followed WordPress-VIP coding standards https://vip.wordpress.com/documentation/code-review-what-we-look-for/
 * Added namespacing and PSR-4 auto-loading.
 */

// Exit if accessed directly. defined( 'ABSPATH' ) or die( 'Direct access is forbidden !' );

require_once __DIR__ . '/classes/class-form-builder.php';
add_action( 'plugins_loaded', function () {
	P4FB\Form_Builder\Form_Builder::get_instance()->load();
} );
