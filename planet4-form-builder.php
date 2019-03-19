<?php
/**
 * Plugin Name: Planet4 - Form Builder
 * Description: A simple form builder. The plugin handles form submissions and provides hooks to send them off to CRM or other external systems.
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

// Exit if accessed directly.
defined( 'ABSPATH' ) or die( 'Direct access is forbidden!' );
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/classes/class-form-builder.php';
require_once __DIR__ . '/classes/class-form-mapping.php';
require_once __DIR__ . '/classes/class-form-handler.php';
require_once __DIR__ . '/classes/class-form-entry.php';
require_once __DIR__ . '/classes/class-entry-handler.php';
require_once __DIR__ . '/classes/class-entry-handler-bsd.php';
require_once __DIR__ . '/classes/class-form-shortcode.php';
require_once __DIR__ . '/classes/class-form-template-loader.php';

add_action( 'plugins_loaded', function () {
	Form_Builder::get_instance()->load();
	Form_Mapping::get_instance()->load();
	Form_Handler::get_instance()->load();
	Form_Entry::get_instance()->load();
	Entry_Handler::get_instance()->load();
	Entry_Handler_BSD::get_instance()->load();
	Form_Shortcode::get_instance()->load();
} );

if ( is_admin() ) {
	require_once __DIR__ . '/classes/class-form-builder-settings-page.php';
	require_once __DIR__ . '/classes/class-settings-page-bsd.php';
	Form_Builder_Settings_Page::get_instance();
	Settings_Page_BSD::get_instance();
}
