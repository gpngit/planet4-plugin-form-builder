<?php
// Core settings.
define( 'WP_DEFAULT_THEME', 'twentyseventeen' );

// Set path to MU Plugins.
defined( 'WP_CONTENT_URL' ) || define( 'WP_CONTENT_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/content' );
defined( 'WPMU_PLUGIN_DIR' ) || define( 'WPMU_PLUGIN_DIR', '/chassis/content/plugins-mu' );
defined( 'WPMU_PLUGIN_URL' ) || define( 'WPMU_PLUGIN_URL', WP_CONTENT_URL . '/plugins-mu' );
