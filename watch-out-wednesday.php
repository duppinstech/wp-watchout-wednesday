<?php
/**
 * Plugin Name: Watch Out Wednesday
 * Plugin URI: https://github.com/duppinstech/wp-watchout-wednesday
 * Update URI: https://github.com/duppinstech/wp-watchout-wednesday
 * Description: Publishes a weekly Watch Out Wednesday post from the Wordfence Intelligence vulnerability feed. You supply the API key.
 * Version: 1.3.0
 * Author: Duppins Technology
 * Author URI: https://duppinstech.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: watch-out-wednesday
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Copyright (C) 2026 Duppins Technology
 *
 * @package Watch_Out_Wednesday
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WOW_VERSION', '1.3.0' );

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wow-github-updater.php';

// Updates from GitHub Releases. Booted at load (not admin_init) so WP-Cron and background auto-updates see releases.
// Disable with define( 'WOW_DISABLE_GITHUB_UPDATER', true ).
Wow_GitHub_Updater::boot(
	array(
		'file'    => __FILE__,
		'version' => WOW_VERSION,
		'owner'   => 'duppinstech',
		'repo'    => 'wp-watchout-wednesday',
		'prefix'  => 'wow',
	)
);

require_once plugin_dir_path( __FILE__ ) . 'includes/class-watch-out-wednesday-plugin-info.php';

// Plugins screen links and the View details modal. No public guide URL, so Help is omitted.
Wow_Plugin_Info::boot(
	array(
		'file'         => __FILE__,
		'settings_url' => 'options-general.php?page=wow-settings',
		'help_url'     => '',
		'support_url'  => 'mailto:support@duppinstech.com',
	)
);

require_once plugin_dir_path( __FILE__ ) . 'includes/email-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/post-generator.php';

add_filter( 'cron_schedules', 'wow_add_weekly_schedule' );
add_action( 'init', 'wow_load_textdomain', 0 );
add_action( 'init', 'wow_schedule_weekly_cron' );
add_action( 'init', 'wow_disable_option_autoload', 20 );
add_action( 'wow_weekly_cron_event', 'wow_run_weekly_task' );
add_action( 'admin_enqueue_scripts', 'wow_enqueue_admin_assets' );
add_action( 'admin_init', 'wow_add_privacy_policy' );
add_action( 'admin_post_wow_manual_run', 'wow_handle_manual_run_request' );
add_action( 'admin_notices', 'wow_render_admin_notices' );
register_activation_hook( __FILE__, 'wow_schedule_weekly_cron' );
register_deactivation_hook( __FILE__, 'wow_clear_scheduled_event' );

/**
 * Load translations from languages/ on GitHub installs.
 */
function wow_load_textdomain() {
	load_plugin_textdomain( 'watch-out-wednesday', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Privacy policy text for the Wordfence request and the stored notification email.
 */
function wow_add_privacy_policy() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$content = '<p>' . esc_html__( 'This plugin requests the Wordfence Intelligence v3 feed at the endpoint on the Authentication tab. The default is https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production. Each request sends that host the API key you entered. The connecting server’s IP address is visible to Wordfence as part of a normal HTTPS request. No visitor personal data is sent.', 'watch-out-wednesday' ) . '</p>';
	$content .= '<p>' . esc_html__( 'Wordfence Intelligence terms: https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/ Wordfence privacy policy: https://www.wordfence.com/privacy-policy/', 'watch-out-wednesday' ) . '</p>';
	$content .= '<p>' . esc_html__( 'The notification email is stored in the WordPress option wow_settings and is used only to send the generated post or an error through this site’s mail setup. It remains until you change the setting or delete the plugin. Deactivating the plugin does not delete it.', 'watch-out-wednesday' ) . '</p>';

	wp_add_privacy_policy_content(
		'Watch Out Wednesday',
		wp_kses_post( $content )
	);
}

/**
 * Keep the API key out of the autoloaded options cache.
 */
function wow_disable_option_autoload() {
	if ( get_transient( 'wow_autoload_checked' ) ) {
		return;
	}

	$values = array();
	if ( false !== get_option( 'wow_settings', false ) ) {
		$values['wow_settings'] = false;
	}
	if ( false !== get_option( 'wow_last_run', false ) ) {
		$values['wow_last_run'] = false;
	}

	if ( ! empty( $values ) && function_exists( 'wp_set_option_autoload_values' ) ) {
		wp_set_option_autoload_values( $values );
	} elseif ( ! empty( $values ) ) {
		global $wpdb;
		foreach ( array_keys( $values ) as $wow_option_name ) {
			$wpdb->update(
				$wpdb->options,
				array( 'autoload' => 'no' ),
				array( 'option_name' => $wow_option_name )
			);
		}
		wp_cache_delete( 'alloptions', 'options' );
	}

	set_transient( 'wow_autoload_checked', 1, DAY_IN_SECONDS );
}

/**
 * @param array $schedules Cron schedules.
 * @return array
 */
function wow_add_weekly_schedule( $schedules ) {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => did_action( 'init' ) ? __( 'Once Weekly', 'watch-out-wednesday' ) : 'Once Weekly',
		);
	}
	return $schedules;
}

/**
 * Schedule or clear the weekly event from the saved Enable value.
 */
function wow_schedule_weekly_cron() {
	$options = get_option( 'wow_settings', array() );
	$enabled = ! empty( $options['enabled'] );

	if ( ! $enabled ) {
		wow_clear_scheduled_event();
		return;
	}

	if ( ! wp_next_scheduled( 'wow_weekly_cron_event' ) ) {
		wp_schedule_event( wow_next_wednesday_timestamp(), 'weekly', 'wow_weekly_cron_event' );
	}
}

/**
 * Clear every scheduled weekly run.
 */
function wow_clear_scheduled_event() {
	wp_clear_scheduled_hook( 'wow_weekly_cron_event' );
}

/**
 * Publish the Wednesday that was due, including when WP-Cron runs late.
 *
 * @return int|false|WP_Error
 */
function wow_run_weekly_task() {
	$options = get_option( 'wow_settings', array() );
	if ( empty( $options['enabled'] ) ) {
		return false;
	}

	try {
		return wow_generate_post( wow_most_recent_wednesday_ymd() );
	} catch ( Exception $exception ) {
		wow_send_error_email( $exception->getMessage() );
		return new WP_Error( 'wow_run_failed', $exception->getMessage() );
	} catch ( Error $exception ) {
		wow_send_error_email( $exception->getMessage() );
		return new WP_Error( 'wow_run_failed', $exception->getMessage() );
	}
}

/**
 * Turn an unexpected crash into a settings notice instead of a blank server error.
 *
 * @param Exception|Error $exception Caught failure.
 * @return array
 */
function wow_run_exception_notice( $exception ) {
	return array(
		'type'    => 'error',
		'message' => sprintf(
			/* translators: %s: error message returned by PHP. */
			__( 'The run stopped before it finished: %s', 'watch-out-wednesday' ),
			$exception->getMessage()
		),
	);
}

/**
 * @param string $hook Current admin page hook.
 */
function wow_enqueue_admin_assets( $hook ) {
	if ( 'settings_page_wow-settings' !== $hook ) {
		return;
	}

	$style_path  = plugin_dir_path( __FILE__ ) . 'css/admin-style.css';
	$script_path = plugin_dir_path( __FILE__ ) . 'js/admin-script.js';

	wp_enqueue_media();
	wp_enqueue_style( 'wow-admin-style', plugin_dir_url( __FILE__ ) . 'css/admin-style.css', array(), file_exists( $style_path ) ? filemtime( $style_path ) : '1.3.0' );
	wp_enqueue_script( 'wow-admin-script', plugin_dir_url( __FILE__ ) . 'js/admin-script.js', array( 'jquery' ), file_exists( $script_path ) ? filemtime( $script_path ) : '1.3.0', true );
	wp_localize_script(
		'wow-admin-script',
		'wowAdmin',
		array(
			'mediaTitle'  => __( 'Select Featured Image', 'watch-out-wednesday' ),
			'mediaButton' => __( 'Use this image', 'watch-out-wednesday' ),
			'imageAlt'    => __( 'Selected featured image', 'watch-out-wednesday' ),
			'bulkConfirm' => __( 'Publish %d Watch Out Wednesday posts? Each week is created from the current Wordfence feed.', 'watch-out-wednesday' ),
		)
	);
}

/**
 * admin-post handler. The work lives in wow_process_manual_run() so tests can call it.
 */
function wow_handle_manual_run_request() {
	try {
		$result = wow_process_manual_run( wp_unslash( $_POST ) );
	} catch ( Exception $exception ) {
		$result = wow_run_exception_notice( $exception );
	} catch ( Error $exception ) {
		$result = wow_run_exception_notice( $exception );
	}

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ) );
	}

	if ( is_array( $result ) ) {
		set_transient(
			'wow_admin_notice',
			array(
				'type'    => isset( $result['type'] ) ? $result['type'] : 'info',
				'message' => isset( $result['message'] ) ? $result['message'] : '',
			),
			60
		);
	}

	wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
	exit;
}

/**
 * Run the requested manual action.
 *
 * @param array $post Unslashed request body.
 * @return array|WP_Error
 */
function wow_process_manual_run( $post ) {
	if ( ! is_array( $post ) ) {
		$post = array();
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return new WP_Error( 'wow_unauthorized', __( 'Unauthorized request.', 'watch-out-wednesday' ) );
	}

	check_admin_referer( 'wow_manual_run_action', 'wow_manual_run_nonce' );

	$vulnerabilities = wow_fetch_vulnerabilities();
	if ( is_wp_error( $vulnerabilities ) ) {
		return array(
			'type'    => 'error',
			'message' => $vulnerabilities->get_error_message(),
		);
	}

	$run_now      = isset( $post['wow_run_now'] ) ? $post['wow_run_now'] : null;
	$run_specific = isset( $post['wow_run_specific'] ) ? $post['wow_run_specific'] : null;
	$run_bulk     = isset( $post['wow_run_bulk'] ) ? $post['wow_run_bulk'] : null;
	$target       = isset( $post['wow_target_wednesday'] ) ? sanitize_text_field( $post['wow_target_wednesday'] ) : '';
	$bulk_count   = isset( $post['wow_bulk_count'] ) ? absint( $post['wow_bulk_count'] ) : 0;
	$bulk_end     = isset( $post['wow_bulk_end_wednesday'] ) ? sanitize_text_field( $post['wow_bulk_end_wednesday'] ) : '';

	if ( null !== $run_now ) {
		$result = wow_generate_post( wow_get_relevant_wednesday(), $vulnerabilities, true );
		return wow_notice_for_post( $result );
	}

	if ( null !== $run_specific && '' !== $target ) {
		if ( ! wow_is_wednesday_ymd( $target ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'The date must be a Wednesday.', 'watch-out-wednesday' ),
			);
		}
		$result = wow_generate_post( $target, $vulnerabilities, true );
		return wow_notice_for_post( $result );
	}

	if ( null !== $run_bulk && $bulk_count > 0 ) {
		$end = '' !== $bulk_end ? $bulk_end : wow_get_relevant_wednesday();
		if ( ! wow_is_wednesday_ymd( $end ) ) {
			return array(
				'type'    => 'error',
				'message' => __( 'The ending date must be a Wednesday.', 'watch-out-wednesday' ),
			);
		}

		return wow_run_bulk_backfill( $end, $bulk_count, $vulnerabilities );
	}

	return array(
		'type'    => 'error',
		'message' => __( 'No run was started. Use Run Current Week Now, Run Selected Week, or Run Bulk Backfill.', 'watch-out-wednesday' ),
	);
}

/**
 * Publish a batch of Wednesdays and stop before the request runs out of time.
 *
 * @param string $end_ymd Ending Wednesday.
 * @param int    $count How many weeks.
 * @param array  $vulnerabilities Feed records.
 * @return array
 */
function wow_run_bulk_backfill( $end_ymd, $count, $vulnerabilities ) {
	$dates     = wow_bulk_wednesday_dates( $end_ymd, $count );
	$prepared  = wow_normalize_feed( $vulnerabilities );
	unset( $vulnerabilities );
	$published = 0;
	$drafts    = 0;
	$failed    = array();
	$not_run   = array();
	$options   = get_option( 'wow_settings', array() );
	$email     = isset( $options['email'] ) ? $options['email'] : '';

	foreach ( $dates as $index => $ymd ) {
		if ( $index > 0 && wow_seconds_remaining() < 5 ) {
			$not_run = array_slice( $dates, $index );
			break;
		}

		$result = wow_generate_post( $ymd, $prepared, false );
		if ( is_wp_error( $result ) ) {
			$failed[] = $ymd;
			continue;
		}

		$status = get_post_status( (int) $result );
		if ( 'draft' === $status ) {
			$drafts++;
		} else {
			$published++;
		}
	}

	$summary = array(
		'published' => $published,
		'drafts'    => $drafts,
		'failed'    => $failed,
		'not_run'   => $not_run,
	);

	wow_send_bulk_summary_email( $email, $summary );

	return array(
		'type'    => empty( $failed ) ? 'success' : 'warning',
		'message' => wow_bulk_summary_text( $summary ),
	);
}

/**
 * @param int|WP_Error $result Post ID or error.
 * @return array
 */
function wow_notice_for_post( $result ) {
	if ( is_wp_error( $result ) ) {
		return array(
			'type'    => 'error',
			'message' => $result->get_error_message(),
		);
	}

	$status = get_post_status( (int) $result );
	if ( 'draft' === $status ) {
		$message = __( 'No vulnerabilities were found for this Wednesday, so the post was saved as a draft.', 'watch-out-wednesday' );
		$type    = 'warning';
	} elseif ( 'future' === $status ) {
		$message = __( 'The post is scheduled and will be public on that Wednesday.', 'watch-out-wednesday' );
		$type    = 'success';
	} else {
		$message = __( 'The post was published.', 'watch-out-wednesday' );
		$type    = 'success';
	}

	$post_link = get_edit_post_link( (int) $result );
	if ( $post_link ) {
		$message .= ' <a href="' . esc_url( $post_link ) . '">' . esc_html__( 'Edit post', 'watch-out-wednesday' ) . '</a>.';
	}

	return array(
		'type'    => $type,
		'message' => $message,
	);
}

/**
 * Print the stored admin notice.
 */
function wow_render_admin_notices() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = get_transient( 'wow_admin_notice' );
	if ( empty( $notice ) || ! is_array( $notice ) ) {
		return;
	}
	delete_transient( 'wow_admin_notice' );

	$type    = isset( $notice['type'] ) ? sanitize_html_class( $notice['type'] ) : 'info';
	$message = isset( $notice['message'] ) ? $notice['message'] : '';
	if ( '' === $message ) {
		return;
	}

	echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p><strong>' . esc_html__( 'Watch Out Wednesday:', 'watch-out-wednesday' ) . '</strong> ' . wp_kses_post( $message ) . '</p></div>';
}
