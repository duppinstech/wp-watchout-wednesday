<?php
/**
 * Plugin Name: Watch Out Wednesday
 * Plugin URI: https://github.com/oduppinsjr/watch-out-wednesday
 * Description: Automatically generates and emails a "Watch Out Wednesday" WordPress post with plugin vulnerabilities.
 * Version: 1.1.0
 * Author: Duppins Technology
 * Author URI: https://duppinstech.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: watch-out-wednesday
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Include helper files
require_once plugin_dir_path(__FILE__) . 'includes/email-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/post-generator.php';

add_filter( 'cron_schedules', 'wow_add_weekly_schedule' );
add_action( 'init', 'wow_schedule_weekly_cron' );
add_action( 'wow_weekly_cron_event', 'wow_run_weekly_task' );
add_action( 'admin_enqueue_scripts', 'wow_enqueue_admin_assets' );
add_action( 'admin_post_wow_manual_run', 'wow_handle_manual_run_request' );
add_action( 'admin_notices', 'wow_render_admin_notices' );
register_activation_hook( __FILE__, 'wow_schedule_weekly_cron' );
register_deactivation_hook( __FILE__, 'wow_clear_scheduled_event' );

function wow_add_weekly_schedule( $schedules ) {
	if ( ! isset( $schedules['weekly'] ) ) {
		$schedules['weekly'] = array(
			'interval' => WEEK_IN_SECONDS,
			'display'  => __( 'Once Weekly', 'watch-out-wednesday' ),
		);
	}
	return $schedules;
}

function wow_schedule_weekly_cron() {
	$options = get_option( 'wow_settings', array() );
	$enabled = ! empty( $options['enabled'] );

	if ( ! $enabled ) {
		wow_clear_scheduled_event();
		return;
	}

	if ( ! wp_next_scheduled( 'wow_weekly_cron_event' ) ) {
		$first_run = strtotime( 'next wednesday 00:00:00' );
		wp_schedule_event( $first_run, 'weekly', 'wow_weekly_cron_event' );
	}
}

function wow_clear_scheduled_event() {
	$timestamp = wp_next_scheduled( 'wow_weekly_cron_event' );
	if ( $timestamp ) {
		wp_unschedule_event( $timestamp, 'wow_weekly_cron_event' );
	}
}

function wow_run_weekly_task() {
	$options = get_option( 'wow_settings', array() );
	$enabled = ! empty( $options['enabled'] );
	if ( ! $enabled ) {
		return false;
	}
	return wow_generate_post();
}

function wow_enqueue_admin_assets( $hook ) {
	if ( 'settings_page_wow-settings' !== $hook ) {
		return;
	}

	$style_path  = plugin_dir_path( __FILE__ ) . 'css/admin-style.css';
	$script_path = plugin_dir_path( __FILE__ ) . 'js/admin-script.js';

	wp_enqueue_media();
	wp_enqueue_style( 'wow-admin-style', plugin_dir_url( __FILE__ ) . 'css/admin-style.css', array(), file_exists( $style_path ) ? filemtime( $style_path ) : '1.0.0' );
	wp_enqueue_script( 'wow-admin-script', plugin_dir_url( __FILE__ ) . 'js/admin-script.js', array( 'jquery' ), file_exists( $script_path ) ? filemtime( $script_path ) : '1.0.0', true );
}

function wow_handle_manual_run_request() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized request.', 'watch-out-wednesday' ) );
	}

	check_admin_referer( 'wow_manual_run_action', 'wow_manual_run_nonce' );

	$vulnerabilities = wow_fetch_vulnerabilities();
	if ( is_wp_error( $vulnerabilities ) ) {
		set_transient( 'wow_admin_notice', array( 'type' => 'error', 'message' => $vulnerabilities->get_error_message() ), 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
		exit;
	}

	$run_now          = filter_input( INPUT_POST, 'wow_run_now' );
	$run_specific     = filter_input( INPUT_POST, 'wow_run_specific' );
	$run_bulk         = filter_input( INPUT_POST, 'wow_run_bulk' );
	$target_wednesday = filter_input( INPUT_POST, 'wow_target_wednesday', FILTER_SANITIZE_SPECIAL_CHARS );
	$bulk_count       = filter_input( INPUT_POST, 'wow_bulk_count', FILTER_VALIDATE_INT );
	$bulk_end         = filter_input( INPUT_POST, 'wow_bulk_end_wednesday', FILTER_SANITIZE_SPECIAL_CHARS );

	if ( null !== $run_now ) {
		$result = wow_generate_post( null, $vulnerabilities );
		wow_store_run_notice( $result, 'Current week run completed.' );
		wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
		exit;
	}

	if ( null !== $run_specific && ! empty( $target_wednesday ) ) {
		$target = sanitize_text_field( $target_wednesday );
		$result = wow_generate_post( $target, $vulnerabilities );
		wow_store_run_notice( $result, 'Selected week run completed.' );
		wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
		exit;
	}

	if ( null !== $run_bulk && ! empty( $bulk_count ) ) {
		$count = max( 1, min( 260, absint( $bulk_count ) ) );
		$end   = ! empty( $bulk_end ) ? sanitize_text_field( $bulk_end ) : wow_get_relevant_wednesday();
		$base  = strtotime( $end );

		$results = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$target_wednesday = gmdate( 'Y-m-d', strtotime( '-' . $i . ' week', $base ) );
			$results[]        = wow_generate_post( $target_wednesday, $vulnerabilities );
		}

		$successes = 0;
		$errors    = 0;
		foreach ( $results as $result ) {
			if ( is_wp_error( $result ) ) {
				$errors++;
			} else {
				$successes++;
			}
		}

		$message = sprintf( 'Bulk backfill completed. Success: %d, Errors: %d.', $successes, $errors );
		$type    = $errors > 0 ? 'warning' : 'success';
		set_transient( 'wow_admin_notice', array( 'type' => $type, 'message' => $message ), 60 );
		wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
		exit;
	}

	set_transient( 'wow_admin_notice', array( 'type' => 'error', 'message' => 'No valid run action was provided.' ), 60 );
	wp_safe_redirect( admin_url( 'options-general.php?page=wow-settings' ) );
	exit;
}

function wow_store_run_notice( $result, $success_message ) {
	if ( is_wp_error( $result ) ) {
		set_transient(
			'wow_admin_notice',
			array(
				'type'    => 'error',
				'message' => $result->get_error_message(),
			),
			60
		);
		return;
	}

	$post_link = get_edit_post_link( (int) $result );
	$message   = $success_message;
	if ( $post_link ) {
		$message .= ' <a href="' . esc_url( $post_link ) . '">Edit post</a>.';
	}

	set_transient(
		'wow_admin_notice',
		array(
			'type'    => 'success',
			'message' => $message,
		),
		60
	);
}

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
	if ( empty( $message ) ) {
		return;
	}

	echo '<div class="notice notice-' . esc_attr( $type ) . ' is-dismissible"><p><strong>Watch Out Wednesday:</strong> ' . wp_kses_post( $message ) . '</p></div>';
}
