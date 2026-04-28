<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wow_get_relevant_wednesday( $base_timestamp = null ) {
	$timestamp = $base_timestamp ? (int) $base_timestamp : time();
	$local     = wp_date( 'Y-m-d', $timestamp );
	$weekday   = wp_date( 'w', $timestamp ); // 0=Sun ... 3=Wed.

	if ( 3 === (int) $weekday ) {
		return $local;
	}

	return wp_date( 'Y-m-d', strtotime( 'next wednesday', strtotime( $local ) ) );
}

function wow_generate_post( $target_wednesday = null, $vulnerabilities = null ) {
	$options            = get_option( 'wow_settings', array() );
	$email_address      = isset( $options['email'] ) ? sanitize_email( $options['email'] ) : get_option( 'admin_email' );
	$featured_image_url = isset( $options['image_url'] ) ? esc_url_raw( $options['image_url'] ) : '';
	$post_category      = isset( $options['category_id'] ) ? absint( $options['category_id'] ) : 0;

	$relevant_wednesday = $target_wednesday ? gmdate( 'Y-m-d', strtotime( $target_wednesday ) ) : wow_get_relevant_wednesday();
	$start_of_week      = gmdate( 'Y-m-d', strtotime( $relevant_wednesday . ' -7 days' ) );
	$end_of_week        = gmdate( 'Y-m-d', strtotime( $relevant_wednesday . ' -1 days' ) );
	$post_title         = 'Watch Out Wednesday - ' . wp_date( 'F j, Y', strtotime( $relevant_wednesday ) );

	$post_data = wow_generate_vuln_post_content( $start_of_week, $end_of_week, $vulnerabilities );
	if ( is_wp_error( $post_data ) ) {
		wow_send_error_email( $post_data->get_error_message(), $email_address );
		return $post_data;
	}

	$total_vulns  = absint( $post_data['total_vulns'] );
	$post_content = wp_kses_post( $post_data['content'] );

	$existing_id = wow_find_existing_post_id_by_title( $post_title );
	$post_arr = array(
		'post_title'    => $post_title,
		'post_name'     => sanitize_title_with_dashes( $post_title ),
		'post_content'  => $post_content,
		'post_status'   => 'publish',
		'post_type'     => 'post',
		'post_author'   => get_current_user_id() ? get_current_user_id() : 1,
		'post_date'     => gmdate( 'Y-m-d H:i:s', strtotime( $relevant_wednesday ) ),
		'post_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( $relevant_wednesday ) ),
	);

	if ( $post_category > 0 ) {
		$post_arr['post_category'] = array( $post_category );
	}

	if ( $existing_id > 0 ) {
		$post_arr['ID'] = $existing_id;
		$post_id        = wp_update_post( wp_slash( $post_arr ), true );
	} else {
		$post_id = wp_insert_post( wp_slash( $post_arr ), true );
	}

	if ( is_wp_error( $post_id ) ) {
		wow_send_error_email( $post_id->get_error_message(), $email_address );
		return $post_id;
	}

	if ( ! empty( $featured_image_url ) ) {
		wow_set_post_thumbnail_for_post( $post_id, $featured_image_url );
	}

	wow_define_meta_information( $post_id );
	wow_send_success_email( $post_title, $post_content, $post_id, $email_address );

	update_option(
		'wow_last_run',
		array(
			'date'                => $relevant_wednesday,
			'post_id'             => $post_id,
			'post_url'            => get_permalink( $post_id ),
			'vulnerability_count' => $total_vulns,
		),
		false
	);

	return $post_id;
}

function wow_send_success_email( $post_title, $post_content, $post_id, $to ) {
	$recipient = sanitize_email( $to );
	if ( empty( $recipient ) ) {
		return;
	}

	$subject = sprintf( 'Post Published: %s', $post_title );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$message = '<h1>' . esc_html( $post_title ) . '</h1>' . wp_kses_post( $post_content );
	$message .= '<p><a href="' . esc_url( get_permalink( $post_id ) ) . '">View post</a></p>';

	wp_mail( $recipient, $subject, $message, $headers );
}

function wow_send_error_email( $error_message, $to = '' ) {
	$recipient = sanitize_email( $to );
	if ( empty( $recipient ) ) {
		$recipient = get_option( 'admin_email' );
	}

	$subject = 'Error Occurred in Watch Out Wednesday Process';
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$message = '<p>' . esc_html( $error_message ) . '</p>';

	wp_mail( $recipient, $subject, $message, $headers );
}

function wow_define_meta_information( $post_id ) {
	$meta_description = "Stay informed about potential security risks with WordPress plugins and core updates. Find out more in this week's Watch Out Wednesday post.";
	$focus_keyphrase = 'WordPress vulnerability';

	update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
	update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyphrase );
}

function wow_set_post_thumbnail_for_post( $post_id, $image_url ) {
	$image_id = wow_get_image_id( $image_url );

	if ( $post_id && $image_id ) {
		set_post_thumbnail( $post_id, $image_id );
	}
}

function wow_get_image_id( $image_url ) {
	if ( empty( $image_url ) ) {
		return null;
	}

	$image_id = attachment_url_to_postid( $image_url );
	return $image_id ? (int) $image_id : null;
}

function wow_find_existing_post_id_by_title( $post_title ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'title'                  => $post_title,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	return ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
}

function wow_fetch_vulnerabilities() {
	$options  = get_option( 'wow_settings', array() );
	$url      = ! empty( $options['wordfence_api_endpoint'] ) ? esc_url_raw( $options['wordfence_api_endpoint'] ) : 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production';
	$api_key  = isset( $options['wordfence_api_key'] ) ? trim( (string) $options['wordfence_api_key'] ) : '';

	if ( empty( $api_key ) ) {
		return new WP_Error( 'wow_wordfence_missing_key', 'Wordfence API key is missing. Add it in Settings > Watch Out Wednesday > Authentication.' );
	}

	$response = wp_remote_get(
		$url,
		array(
			'timeout' => 30,
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Accept'        => 'application/json',
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status_code   = (int) wp_remote_retrieve_response_code( $response );
	$response_body = (string) wp_remote_retrieve_body( $response );

	if ( 200 !== (int) $status_code ) {
		if ( 401 === (int) $status_code ) {
			return new WP_Error(
				'wow_wordfence_unauthorized',
				'Wordfence API returned HTTP 401. Verify your v3 API key in Settings > Watch Out Wednesday > Authentication and confirm the endpoint is /api/intelligence/v3/vulnerabilities/production.'
			);
		}

		if ( 429 === (int) $status_code ) {
			return new WP_Error(
				'wow_wordfence_rate_limited',
				'Wordfence API returned HTTP 429 (rate limit). Wait and retry later, or request a higher limit from wfi-support@wordfence.com.'
			);
		}

		return new WP_Error( 'wow_wordfence_http', 'Wordfence API returned HTTP ' . $status_code );
	}

	$data          = json_decode( $response_body, true );

	if ( ! is_array( $data ) ) {
		return new WP_Error( 'wow_wordfence_parse', 'Wordfence API returned an unexpected response format.' );
	}

	// v3 feeds can be wrapped in a `data` key.
	if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
		return $data['data'];
	}

	return $data;
}

function wow_generate_vuln_post_content( $start_date, $end_date, $vulnerabilities = null ) {
	$options    = get_option( 'wow_settings', array() );
	$intro_text = isset( $options['intro_text'] ) ? wp_kses_post( $options['intro_text'] ) : '';
	$outro_text = isset( $options['outro_text'] ) ? wp_kses_post( $options['outro_text'] ) : '';

	if ( null === $vulnerabilities ) {
		$vulnerabilities = wow_fetch_vulnerabilities();
	}

	if ( is_wp_error( $vulnerabilities ) ) {
		return $vulnerabilities;
	}

	$post_content    = $intro_text;
	$start_date_obj  = new DateTime( $start_date );
	$end_date_obj    = new DateTime( $end_date . ' 23:59:59' );
	$total_vulns     = 0;

	foreach ( $vulnerabilities as $vuln ) {
		$published_obj = isset( $vuln['published'] ) ? new DateTime( $vuln['published'] ) : null;
		$updated_obj   = isset( $vuln['updated'] ) ? new DateTime( $vuln['updated'] ) : null;

		$matches_published = $published_obj && $published_obj >= $start_date_obj && $published_obj <= $end_date_obj;
		$matches_updated   = $updated_obj && $updated_obj >= $start_date_obj && $updated_obj <= $end_date_obj;

		if ( ! $matches_published && ! $matches_updated ) {
			continue;
		}

		$software = isset( $vuln['software'][0] ) && is_array( $vuln['software'][0] ) ? $vuln['software'][0] : array();
		if ( empty( $software ) ) {
			continue;
		}

		$total_vulns++;
		$type            = isset( $software['type'] ) ? ucfirst( sanitize_text_field( $software['type'] ) ) : 'Software';
		$name            = isset( $software['name'] ) ? sanitize_text_field( $software['name'] ) : 'Unknown';
		$patched_version = isset( $software['patched_versions'][0] ) ? sanitize_text_field( $software['patched_versions'][0] ) : 'N/A';
		$remediation     = isset( $software['remediation'] ) ? sanitize_text_field( $software['remediation'] ) : 'No remediation info';
		$title           = isset( $vuln['title'] ) ? sanitize_text_field( $vuln['title'] ) : 'Untitled vulnerability';

		$post_content .= '<h3>' . esc_html( $type . ': ' . $name ) . '</h3>';
		$post_content .= '<p><strong>Vulnerability:</strong> ' . esc_html( $title ) . '<br />';
		$post_content .= '<strong>Patched Version:</strong> ' . esc_html( $patched_version ) . '<br />';
		$post_content .= '<strong>Recommended Action:</strong> ' . esc_html( $remediation ) . '</p>';
	}

	if ( 0 === $total_vulns ) {
		$post_content .= '<p>No vulnerabilities found in this period.</p>';
	}

	$post_content .= $outro_text;

	return array(
		'content'     => $post_content,
		'total_vulns' => $total_vulns,
	);
}
