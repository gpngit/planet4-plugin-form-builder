<?php
// DB.
if ( file_exists( __DIR__ . '/chassis/local-config-db.php' ) ) {
	include __DIR__ . '/chassis/local-config-db.php';
} else {
	define( 'DB_NAME', 'wordpress' );
	define( 'DB_USER', 'wordpress' );
	define( 'DB_PASSWORD', 'vagrantpassword' );
	define( 'DB_HOST', 'localhost' );

	if ( defined( 'HM_DEV' ) && HM_DEV ) {
		define( 'DOMAIN_CURRENT_SITE', 'planet4.local' );
	}
}

// Core settings.
define( 'AUTOMATIC_UPDATER_DISABLED', true );
define( 'SAVEQUERIES', true );
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEFAULT_THEME', 'twentyseventeen' );
define( 'WP_HOME', 'http://' . $_SERVER['HTTP_HOST'] );
define( 'WP_SITEURL', 'http://' . $_SERVER['HTTP_HOST'] . '/wordpress' );

// HM vars. for Platform plugin.
define( 'ELASTICSEARCH_HOST', '127.0.0.1' );
define( 'ELASTICSEARCH_PORT', 9200 );

if ( ! defined( 'EP_HOST' ) ) {
	define(
		'EP_HOST',
		sprintf(
			'%s://%s:%d',
			ELASTICSEARCH_PORT === 443 ? 'https' : 'http',
			ELASTICSEARCH_HOST,
			ELASTICSEARCH_PORT
		)
	);
}

// Load Chassis extensions.
if ( file_exists( __DIR__ . '/chassis/local-config-extensions.php' ) ) {
	include __DIR__ . '/chassis/local-config-extensions.php';
}

global $hm_platform;
$hm_platform = [
	'elasticsearch' => false,  // Disable Platform ES (workaround for environment detection).
];
