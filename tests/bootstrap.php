<?php
/**
 * Load the plugin without WordPress.
 *
 * @package Watch_Out_Wednesday
 */

require dirname( __DIR__ ) . '/vendor/autoload.php';
require __DIR__ . '/wp-stubs.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

use Brain\Monkey;
use Brain\Monkey\Functions;

Monkey\setUp();

Functions\when( 'add_action' )->justReturn( true );
Functions\when( 'add_filter' )->justReturn( true );
Functions\when( 'apply_filters' )->returnArg( 2 );
Functions\when( 'register_activation_hook' )->justReturn( true );
Functions\when( 'register_deactivation_hook' )->justReturn( true );
Functions\when( 'plugin_dir_path' )->alias(
	function ( $file ) {
		return dirname( $file ) . DIRECTORY_SEPARATOR;
	}
);
Functions\when( 'plugin_dir_url' )->justReturn( 'https://example.test/wp-content/plugins/watch-out-wednesday/' );
Functions\when( 'plugin_basename' )->alias(
	function ( $file ) {
		return 'watch-out-wednesday/' . basename( $file );
	}
);

require dirname( __DIR__ ) . '/watch-out-wednesday.php';
require __DIR__ . '/unit/Wow_Test_Case.php';

Monkey\tearDown();
