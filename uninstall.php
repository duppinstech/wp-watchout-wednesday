<?php
/**
 * Remove plugin options, the GitHub updater cache, and the weekly cron. Generated posts stay.
 *
 * @package Watch_Out_Wednesday
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete this plugin's options on one site.
 */
function wow_uninstall_site() {
	delete_option( 'wow_settings' );
	delete_option( 'wow_last_run' );
	delete_transient( 'wow_admin_notice' );
	delete_transient( 'wow_autoload_checked' );
	wp_clear_scheduled_hook( 'wow_weekly_cron_event' );
}

if ( is_multisite() ) {
	$wow_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);
	foreach ( $wow_site_ids as $wow_site_id ) {
		switch_to_blog( (int) $wow_site_id );
		wow_uninstall_site();
		restore_current_blog();
	}
} else {
	wow_uninstall_site();
}

delete_site_option( 'wow_github_release' );
