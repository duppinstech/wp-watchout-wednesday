<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default Wordfence Intelligence v3 production feed.
 *
 * @return string
 */
function wow_default_wordfence_endpoint() {
	return 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production';
}

/**
 * Site-local midnight for a Y-m-d value.
 *
 * @param string $ymd Date.
 * @return DateTimeImmutable|null
 */
function wow_date_from_ymd( $ymd ) {
	$ymd = is_string( $ymd ) ? trim( $ymd ) : '';
	if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $ymd ) ) {
		return null;
	}

	$date = date_create_immutable_from_format( '!Y-m-d', $ymd, wp_timezone() );
	if ( ! $date instanceof DateTimeImmutable ) {
		return null;
	}

	$errors = DateTime::getLastErrors();
	if ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) {
		return null;
	}

	return $date;
}

/**
 * Whether a Y-m-d string is a Wednesday in the site timezone.
 *
 * @param string $ymd Date.
 * @return bool
 */
function wow_is_wednesday_ymd( $ymd ) {
	$date = wow_date_from_ymd( $ymd );
	if ( ! $date ) {
		return false;
	}

	return 3 === (int) $date->format( 'w' );
}

/**
 * The current or upcoming Wednesday in the site timezone.
 *
 * @param int|null $base_timestamp Unix timestamp.
 * @return string
 */
function wow_get_relevant_wednesday( $base_timestamp = null ) {
	$timestamp = $base_timestamp ? (int) $base_timestamp : time();
	$local     = wp_date( 'Y-m-d', $timestamp );
	$weekday   = (int) wp_date( 'w', $timestamp );

	if ( 3 === $weekday ) {
		return $local;
	}

	$date = wow_date_from_ymd( $local );
	if ( ! $date ) {
		return $local;
	}

	return $date->modify( 'next wednesday' )->format( 'Y-m-d' );
}

/**
 * The Wednesday a late weekly cron should publish.
 *
 * @return string
 */
function wow_most_recent_wednesday_ymd() {
	$now = new DateTimeImmutable( 'now', wp_timezone() );
	if ( 3 === (int) $now->format( 'w' ) ) {
		return $now->format( 'Y-m-d' );
	}

	return $now->modify( 'last wednesday' )->format( 'Y-m-d' );
}

/**
 * Next Wednesday at 00:00 in the site timezone, as a UTC timestamp.
 *
 * @return int
 */
function wow_next_wednesday_timestamp() {
	$now  = new DateTimeImmutable( 'now', wp_timezone() );
	$next = $now->modify( 'next wednesday' )->setTime( 0, 0, 0 );

	return $next->getTimestamp();
}

/**
 * Title, legacy title, and post dates for one Wednesday.
 *
 * @param string $ymd Site-local Wednesday.
 * @return array|null
 */
function wow_wednesday_post_schedule( $ymd ) {
	$date = wow_date_from_ymd( $ymd );
	if ( ! $date ) {
		return null;
	}

	$title_date = wp_date( 'F j, Y', $date->getTimestamp(), wp_timezone() );
	$legacy_utc = strtotime( $date->format( 'Y-m-d' ) . ' UTC' );
	$end_local  = $date->modify( '-1 day' )->setTime( 23, 59, 59 );

	return array(
		'ymd'           => $date->format( 'Y-m-d' ),
		'title'         => sprintf(
			/* translators: %s: Wednesday date, for example October 21, 2026. */
			__( 'Watch Out Wednesday - %s', 'watch-out-wednesday' ),
			$title_date
		),
		'legacy_title'  => 'Watch Out Wednesday - ' . wp_date( 'F j, Y', $legacy_utc ),
		'post_date'     => $date->format( 'Y-m-d H:i:s' ),
		'post_date_gmt' => $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ),
		'start'         => $date->modify( '-7 days' )->setTimezone( new DateTimeZone( 'UTC' ) ),
		'end'           => $end_local->setTimezone( new DateTimeZone( 'UTC' ) ),
	);
}

/**
 * Wednesday dates walking backward from an ending Wednesday.
 *
 * @param string $end_ymd Ending Wednesday.
 * @param int    $count   How many weeks.
 * @return array
 */
function wow_bulk_wednesday_dates( $end_ymd, $count ) {
	if ( ! wow_is_wednesday_ymd( $end_ymd ) ) {
		return array();
	}

	$end   = wow_date_from_ymd( $end_ymd );
	$count = max( 1, min( 260, (int) $count ) );
	$dates = array();

	for ( $i = 0; $i < $count; $i++ ) {
		$dates[] = $end->modify( '-' . ( 7 * $i ) . ' days' )->format( 'Y-m-d' );
	}

	return $dates;
}

/**
 * Create or update the Wednesday post.
 *
 * @param string|null $target_wednesday Y-m-d. Null uses the current or upcoming Wednesday.
 * @param array|null  $vulnerabilities  Feed records, or null to fetch them.
 * @param bool        $send_email       Whether this single post sends its own email.
 * @return int|WP_Error
 */
function wow_generate_post( $target_wednesday = null, $vulnerabilities = null, $send_email = true ) {
	$options            = get_option( 'wow_settings', array() );
	$email_address      = isset( $options['email'] ) ? sanitize_email( $options['email'] ) : get_option( 'admin_email' );
	$featured_image_url = isset( $options['image_url'] ) ? esc_url_raw( $options['image_url'] ) : '';
	$post_category      = isset( $options['category_id'] ) ? absint( $options['category_id'] ) : 0;
	$ymd                = $target_wednesday ? $target_wednesday : wow_get_relevant_wednesday();
	$schedule           = wow_wednesday_post_schedule( $ymd );

	if ( ! is_array( $schedule ) ) {
		$error = new WP_Error(
			'wow_invalid_date',
			__( 'Enter a Wednesday date in YYYY-MM-DD format.', 'watch-out-wednesday' )
		);
		if ( $send_email ) {
			wow_send_error_email( $error->get_error_message(), $email_address );
		}
		return $error;
	}

	$post_data = wow_generate_vuln_post_content( $schedule['start'], $schedule['end'], $vulnerabilities );
	if ( is_wp_error( $post_data ) ) {
		if ( $send_email ) {
			wow_send_error_email( $post_data->get_error_message(), $email_address );
		}
		return $post_data;
	}

	$total_vulns  = absint( $post_data['total_vulns'] );
	$post_content = wp_kses_post( $post_data['content'] );
	$post_status  = $total_vulns > 0 ? 'publish' : 'draft';
	$existing_id  = wow_find_wednesday_post_id( $schedule['title'], $schedule['legacy_title'] );

	$post_arr = array(
		'post_title'    => $schedule['title'],
		'post_name'     => sanitize_title_with_dashes( $schedule['title'] ),
		'post_content'  => $post_content,
		'post_status'   => $post_status,
		'post_type'     => 'post',
		'post_author'   => get_current_user_id() ? get_current_user_id() : 1,
		'post_date'     => $schedule['post_date'],
		'post_date_gmt' => $schedule['post_date_gmt'],
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
		if ( $send_email ) {
			wow_send_error_email( $post_id->get_error_message(), $email_address );
		}
		return $post_id;
	}

	if ( ! empty( $featured_image_url ) ) {
		wow_set_post_thumbnail_for_post( $post_id, $featured_image_url );
	}

	wow_define_meta_information( $post_id );

	if ( $send_email ) {
		wow_send_success_email( $schedule['title'], $post_content, $post_id, $email_address, $post_status );
	}

	update_option(
		'wow_last_run',
		array(
			'date'                => $schedule['ymd'],
			'post_id'             => $post_id,
			'post_url'            => get_permalink( $post_id ),
			'vulnerability_count' => $total_vulns,
			'status'              => $post_status,
		),
		false
	);

	return $post_id;
}

/**
 * Find a post this plugin can update. Private posts are left alone.
 *
 * @param string $title        Current title.
 * @param string $legacy_title Title saved before the timezone fix.
 * @return int
 */
function wow_find_wednesday_post_id( $title, $legacy_title ) {
	$existing_id = wow_find_existing_post_id_by_title( $title );
	if ( $existing_id > 0 ) {
		return $existing_id;
	}

	if ( is_string( $legacy_title ) && '' !== $legacy_title && $legacy_title !== $title ) {
		return wow_find_existing_post_id_by_title( $legacy_title );
	}

	return 0;
}

/**
 * @param string $post_title Post title.
 * @param string $post_content HTML content.
 * @param int    $post_id Post ID.
 * @param string $to Recipient.
 * @param string $post_status publish or draft.
 */
function wow_send_success_email( $post_title, $post_content, $post_id, $to, $post_status = 'publish' ) {
	$recipient = sanitize_email( $to );
	if ( empty( $recipient ) ) {
		return;
	}

	if ( 'draft' === $post_status ) {
		$subject = sprintf(
			/* translators: %s: post title. */
			__( 'Watch Out Wednesday saved a draft: %s', 'watch-out-wednesday' ),
			$post_title
		);
	} else {
		$subject = sprintf(
			/* translators: %s: post title. */
			__( 'Post published: %s', 'watch-out-wednesday' ),
			$post_title
		);
	}

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$message = '<h1>' . esc_html( $post_title ) . '</h1>' . wp_kses_post( $post_content );
	$message .= '<p><a href="' . esc_url( get_permalink( $post_id ) ) . '">' . esc_html__( 'View post', 'watch-out-wednesday' ) . '</a></p>';

	wp_mail( $recipient, $subject, $message, $headers );
}

/**
 * One email for a bulk backfill.
 *
 * @param string $to Recipient.
 * @param array  $summary Counts and date lists.
 */
function wow_send_bulk_summary_email( $to, $summary ) {
	$recipient = sanitize_email( $to );
	if ( empty( $recipient ) ) {
		$recipient = sanitize_email( get_option( 'admin_email' ) );
	}
	if ( empty( $recipient ) ) {
		return;
	}

	$subject = __( 'Watch Out Wednesday bulk backfill finished', 'watch-out-wednesday' );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$message = '<p>' . esc_html( wow_bulk_summary_text( $summary ) ) . '</p>';

	wp_mail( $recipient, $subject, $message, $headers );
}

/**
 * @param array $summary Bulk result.
 * @return string
 */
function wow_bulk_summary_text( $summary ) {
	$published = isset( $summary['published'] ) ? absint( $summary['published'] ) : 0;
	$drafts    = isset( $summary['drafts'] ) ? absint( $summary['drafts'] ) : 0;
	$failed    = isset( $summary['failed'] ) && is_array( $summary['failed'] ) ? $summary['failed'] : array();
	$not_run   = isset( $summary['not_run'] ) && is_array( $summary['not_run'] ) ? $summary['not_run'] : array();

	$text = sprintf(
		/* translators: 1: published count, 2: draft count, 3: failed count. */
		__( 'Bulk backfill finished. Published: %1$d. Drafts with no vulnerabilities: %2$d. Failed: %3$d.', 'watch-out-wednesday' ),
		$published,
		$drafts,
		count( $failed )
	);

	if ( ! empty( $failed ) ) {
		$text .= ' ' . sprintf(
			/* translators: %s: comma-separated Wednesday dates. */
			__( 'Failed weeks: %s.', 'watch-out-wednesday' ),
			implode( ', ', array_map( 'sanitize_text_field', $failed ) )
		);
	}

	if ( ! empty( $not_run ) ) {
		$text .= ' ' . sprintf(
			/* translators: 1: remaining count, 2: next ending Wednesday. */
			__( 'Stopped early so the request could finish. %1$d weeks were not run. Set the ending Wednesday to %2$s and the count to %1$d, then run bulk again.', 'watch-out-wednesday' ),
			count( $not_run ),
			sanitize_text_field( (string) $not_run[0] )
		);
	}

	return $text;
}

/**
 * @param string $error_message Error text.
 * @param string $to Recipient.
 */
function wow_send_error_email( $error_message, $to = '' ) {
	$recipient = sanitize_email( $to );
	if ( empty( $recipient ) ) {
		$recipient = sanitize_email( get_option( 'admin_email' ) );
	}
	if ( empty( $recipient ) ) {
		return;
	}

	$subject = __( 'Watch Out Wednesday could not publish this week’s post', 'watch-out-wednesday' );
	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	$message = '<p>' . esc_html( $error_message ) . '</p>';

	wp_mail( $recipient, $subject, $message, $headers );
}

/**
 * @param int $post_id Post ID.
 */
function wow_define_meta_information( $post_id ) {
	$meta_description = __( 'This week’s Watch Out Wednesday recap of vulnerabilities from the Wordfence Intelligence feed.', 'watch-out-wednesday' );
	$focus_keyphrase  = __( 'WordPress vulnerability', 'watch-out-wednesday' );

	update_post_meta( $post_id, '_yoast_wpseo_metadesc', $meta_description );
	update_post_meta( $post_id, '_yoast_wpseo_focuskw', $focus_keyphrase );
}

/**
 * @param int    $post_id Post ID.
 * @param string $image_url Attachment URL.
 * @return bool
 */
function wow_set_post_thumbnail_for_post( $post_id, $image_url ) {
	$image_id = wow_get_image_id( $image_url );

	if ( $post_id && $image_id ) {
		set_post_thumbnail( $post_id, $image_id );
		return true;
	}

	return false;
}

/**
 * @param string $image_url Attachment URL.
 * @return int|null
 */
function wow_get_image_id( $image_url ) {
	if ( empty( $image_url ) ) {
		return null;
	}

	$image_id = attachment_url_to_postid( $image_url );
	return $image_id ? (int) $image_id : null;
}

/**
 * @param string $post_title Exact title.
 * @return int
 */
function wow_find_existing_post_id_by_title( $post_title ) {
	$query = new WP_Query(
		array(
			'post_type'              => 'post',
			'post_status'            => array( 'publish', 'draft', 'pending', 'future' ),
			'title'                  => $post_title,
			'posts_per_page'         => 1,
			'fields'                 => 'ids',
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	$post_id = ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
	if ( $post_id > 0 && 'private' === get_post_status( $post_id ) ) {
		return 0;
	}

	return $post_id;
}

/**
 * Whether an address is loopback, private, link-local, or otherwise non-global.
 *
 * @param string $ip IP address.
 * @return bool
 */
function wow_ip_is_non_global( $ip ) {
	if ( ! is_string( $ip ) || '' === $ip ) {
		return true;
	}

	return ! filter_var(
		$ip,
		FILTER_VALIDATE_IP,
		FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
	);
}

/**
 * HTTPS URL whose host is public. The official Wordfence host is allow-listed.
 *
 * @param string $url URL.
 * @return bool
 */
function wow_is_public_https_url( $url ) {
	$parts = wp_parse_url( (string) $url );
	if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['scheme'] ) ) {
		return false;
	}
	if ( 'https' !== strtolower( (string) $parts['scheme'] ) ) {
		return false;
	}
	if ( ! empty( $parts['user'] ) || ! empty( $parts['pass'] ) ) {
		return false;
	}

	$host = strtolower( (string) $parts['host'] );
	if ( 'www.wordfence.com' === $host ) {
		return true;
	}

	if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
		return ! wow_ip_is_non_global( $host );
	}

	$records = gethostbynamel( $host );
	if ( ! is_array( $records ) || empty( $records ) ) {
		return false;
	}

	foreach ( $records as $ip ) {
		if ( wow_ip_is_non_global( $ip ) ) {
			return false;
		}
	}

	return true;
}

/**
 * Fetch the Wordfence Intelligence feed.
 *
 * @return array|WP_Error
 */
function wow_fetch_vulnerabilities() {
	$options = get_option( 'wow_settings', array() );
	$url     = ! empty( $options['wordfence_api_endpoint'] ) ? esc_url_raw( $options['wordfence_api_endpoint'] ) : wow_default_wordfence_endpoint();
	$api_key = isset( $options['wordfence_api_key'] ) ? trim( (string) $options['wordfence_api_key'] ) : '';

	if ( '' === $api_key ) {
		return new WP_Error(
			'wow_wordfence_missing_key',
			__( 'Wordfence API key is missing. Add it in Settings > Watch Out Wednesday > Authentication.', 'watch-out-wednesday' )
		);
	}

	if ( ! wow_is_public_https_url( $url ) ) {
		return new WP_Error(
			'wow_wordfence_endpoint',
			__( 'The Wordfence endpoint must be a public https URL. Open Settings > Watch Out Wednesday > Authentication and restore the default endpoint.', 'watch-out-wednesday' )
		);
	}

	$response = wp_safe_remote_get(
		$url,
		array(
			'timeout'             => 120,
			'redirection'         => 0,
			'limit_response_size' => defined( 'MB_IN_BYTES' ) ? 200 * MB_IN_BYTES : 209715200,
			'headers'             => array(
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
	unset( $response );

	if ( 200 !== $status_code ) {
		if ( 401 === $status_code ) {
			return new WP_Error(
				'wow_wordfence_unauthorized',
				__( 'Wordfence API returned HTTP 401. Verify your v3 API key in Settings > Watch Out Wednesday > Authentication and confirm the endpoint is the v3 vulnerabilities URL.', 'watch-out-wednesday' )
			);
		}

		if ( 429 === $status_code ) {
			return new WP_Error(
				'wow_wordfence_rate_limited',
				__( 'Wordfence API returned HTTP 429 (rate limit). The published default is one request per 30 minutes. Wait and try again, or request a higher limit from wfi-support@wordfence.com.', 'watch-out-wednesday' )
			);
		}

		return new WP_Error(
			'wow_wordfence_http',
			sprintf(
				/* translators: %d: HTTP status code. */
				__( 'Wordfence API returned HTTP %d. Check the endpoint on Settings > Watch Out Wednesday > Authentication, then try again.', 'watch-out-wednesday' ),
				$status_code
			)
		);
	}

	$body_length = strlen( $response_body );
	if ( wow_feed_exceeds_memory( $body_length, wow_ini_bytes( ini_get( 'memory_limit' ) ), memory_get_usage( true ) ) ) {
		$size_mb = max( 1, (int) round( $body_length / 1048576 ) );
		$limit   = ini_get( 'memory_limit' );
		unset( $response_body );
		return new WP_Error(
			'wow_wordfence_memory',
			sprintf(
				/* translators: 1: feed size in megabytes, 2: PHP memory_limit value. */
				__( 'The Wordfence feed is about %1$d MB. PHP memory_limit on this site is %2$s, which is too low to read that file. The run stopped instead of crashing. Raise memory_limit to at least 512M, then try again.', 'watch-out-wednesday' ),
				$size_mb,
				$limit ? $limit : __( 'unknown', 'watch-out-wednesday' )
			)
		);
	}

	$data = json_decode( $response_body, true );
	unset( $response_body );
	if ( ! is_array( $data ) ) {
		return new WP_Error(
			'wow_wordfence_parse',
			__( 'Wordfence API returned an unexpected response. Confirm the endpoint is the v3 vulnerabilities URL on the Authentication tab, then try again.', 'watch-out-wednesday' )
		);
	}

	if ( isset( $data['data'] ) && is_array( $data['data'] ) && 1 === count( $data ) ) {
		return $data['data'];
	}

	return $data;
}

/**
 * Parse a feed timestamp as UTC.
 *
 * @param mixed $value Feed value.
 * @return DateTimeImmutable|null
 */
function wow_parse_feed_datetime( $value ) {
	if ( ! is_string( $value ) || '' === trim( $value ) ) {
		return null;
	}

	$parsed = date_create_immutable_from_format( '!Y-m-d H:i:s', trim( $value ), new DateTimeZone( 'UTC' ) );
	if ( ! $parsed instanceof DateTimeImmutable ) {
		return null;
	}

	$errors = DateTime::getLastErrors();
	if ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) {
		return null;
	}

	return $parsed;
}

/**
 * Convert a php.ini size such as 256M into bytes. Zero means unlimited or unknown.
 *
 * @param mixed $value ini value.
 * @return int
 */
function wow_ini_bytes( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value || '-1' === $value ) {
		return 0;
	}

	$unit   = strtolower( substr( $value, -1 ) );
	$number = (int) $value;
	if ( 'g' === $unit ) {
		return $number * 1073741824;
	}
	if ( 'm' === $unit ) {
		return $number * 1048576;
	}
	if ( 'k' === $unit ) {
		return $number * 1024;
	}

	return (int) $value;
}

/**
 * Whether decoding this feed would exhaust the PHP memory limit.
 *
 * @param int $body_length JSON size in bytes.
 * @param int $memory_limit memory_limit in bytes, or 0 when unlimited.
 * @param int $memory_used Bytes already in use.
 * @return bool
 */
function wow_feed_exceeds_memory( $body_length, $memory_limit, $memory_used ) {
	if ( (int) $memory_limit <= 0 ) {
		return false;
	}

	$needed = ( (int) $body_length * 4 ) + 33554432;

	return ( (int) $memory_used + $needed ) >= (int) $memory_limit;
}

/**
 * Seconds left in this request before PHP is likely to abort it.
 *
 * @return int
 */
function wow_seconds_remaining() {
	$limit = (int) ini_get( 'max_execution_time' );
	$now   = time();
	if ( $limit <= 0 ) {
		return 45;
	}

	$started = isset( $_SERVER['REQUEST_TIME'] ) ? (int) $_SERVER['REQUEST_TIME'] : $now;

	return ( $started + $limit ) - $now - 8;
}

/**
 * Feed values are not always strings. Return text, or an empty string.
 *
 * @param mixed $value Field value.
 * @return string
 */
function wow_feed_text( $value ) {
	if ( is_int( $value ) || is_float( $value ) ) {
		$value = (string) $value;
	}
	if ( ! is_string( $value ) ) {
		return '';
	}

	return sanitize_text_field( $value );
}

/**
 * First patched version, whether the feed stored a string or a version range.
 *
 * @param array $software Software row.
 * @return string
 */
function wow_patched_version_text( $software ) {
	if ( ! isset( $software['patched_versions'] ) ) {
		return '';
	}

	$patched = $software['patched_versions'];
	if ( is_string( $patched ) || is_numeric( $patched ) ) {
		return wow_feed_text( $patched );
	}
	if ( ! is_array( $patched ) || ! isset( $patched[0] ) ) {
		return '';
	}

	$first = $patched[0];
	if ( is_string( $first ) || is_numeric( $first ) ) {
		return wow_feed_text( $first );
	}
	if ( is_array( $first ) ) {
		if ( isset( $first['version'] ) ) {
			return wow_feed_text( $first['version'] );
		}
		if ( isset( $first['from_version'] ) ) {
			return wow_feed_text( $first['from_version'] );
		}
	}

	return '';
}

/**
 * Copy the fields a post needs and drop the rest of each feed record.
 *
 * @param array $vulnerabilities Feed records.
 * @return array
 */
function wow_normalize_feed( &$vulnerabilities ) {
	if ( ! is_array( $vulnerabilities ) ) {
		return array();
	}

	$first = reset( $vulnerabilities );
	if ( is_array( $first ) && ! empty( $first['normalized'] ) ) {
		return $vulnerabilities;
	}

	$rows = array();
	foreach ( array_keys( $vulnerabilities ) as $key ) {
		$vuln = $vulnerabilities[ $key ];
		unset( $vulnerabilities[ $key ] );
		if ( ! is_array( $vuln ) ) {
			continue;
		}

		$software_list = array();
		if ( isset( $vuln['software'] ) && is_array( $vuln['software'] ) ) {
			foreach ( $vuln['software'] as $software ) {
				if ( ! is_array( $software ) ) {
					continue;
				}
				$name = wow_feed_text( isset( $software['name'] ) ? $software['name'] : '' );
				if ( '' === $name ) {
					continue;
				}
				$type = wow_feed_text( isset( $software['type'] ) ? $software['type'] : '' );
				$software_list[] = array(
					'type'        => '' !== $type ? ucfirst( $type ) : __( 'Software', 'watch-out-wednesday' ),
					'name'        => $name,
					'patched'     => wow_patched_version_text( $software ),
					'remediation' => wow_feed_text( isset( $software['remediation'] ) ? $software['remediation'] : '' ),
				);
			}
		}
		if ( empty( $software_list ) ) {
			continue;
		}

		$published = isset( $vuln['published'] ) ? wow_parse_feed_datetime( $vuln['published'] ) : null;
		$updated   = isset( $vuln['updated'] ) ? wow_parse_feed_datetime( $vuln['updated'] ) : null;
		if ( ! $published && ! $updated ) {
			continue;
		}

		$title = wow_feed_text( isset( $vuln['title'] ) ? $vuln['title'] : '' );
		$links = array();
		if ( ! empty( $vuln['references'] ) && is_array( $vuln['references'] ) ) {
			foreach ( $vuln['references'] as $reference ) {
				$text = wow_feed_text( $reference );
				if ( '' !== $text ) {
					$links[] = $text;
				}
			}
		}

		$rows[] = array(
			'normalized' => true,
			'published'  => $published ? $published->getTimestamp() : null,
			'updated'    => $updated ? $updated->getTimestamp() : null,
			'title'      => '' !== $title ? $title : __( 'Untitled vulnerability', 'watch-out-wednesday' ),
			'copyright'  => wow_feed_text( isset( $vuln['copyright'] ) ? $vuln['copyright'] : '' ),
			'references' => $links,
			'software'   => $software_list,
		);
	}

	return $rows;
}

/**
 * Build the post HTML for one Wednesday window.
 *
 * @param DateTimeImmutable $start_utc Window start in UTC.
 * @param DateTimeImmutable $end_utc Window end in UTC.
 * @param array|null        $vulnerabilities Feed records, or null to fetch.
 * @return array|WP_Error
 */
function wow_generate_vuln_post_content( $start_utc, $end_utc, $vulnerabilities = null ) {
	$options    = get_option( 'wow_settings', array() );
	$intro_text = isset( $options['intro_text'] ) ? wpautop( wp_kses_post( $options['intro_text'] ) ) : '';
	$outro_text = isset( $options['outro_text'] ) ? wpautop( wp_kses_post( $options['outro_text'] ) ) : '';

	if ( null === $vulnerabilities ) {
		$vulnerabilities = wow_fetch_vulnerabilities();
	}

	if ( is_wp_error( $vulnerabilities ) ) {
		return $vulnerabilities;
	}

	if ( ! is_array( $vulnerabilities ) ) {
		return new WP_Error(
			'wow_wordfence_parse',
			__( 'Wordfence API returned an unexpected response. Confirm the endpoint is the v3 vulnerabilities URL on the Authentication tab, then try again.', 'watch-out-wednesday' )
		);
	}

	$vulnerabilities = wow_normalize_feed( $vulnerabilities );
	$post_content    = $intro_text;
	$total_vulns     = 0;
	$start_ts        = $start_utc->getTimestamp();
	$end_ts          = $end_utc->getTimestamp();

	foreach ( $vulnerabilities as $vuln ) {
		$matches_published = is_int( $vuln['published'] ) && $vuln['published'] >= $start_ts && $vuln['published'] <= $end_ts;
		$matches_updated   = is_int( $vuln['updated'] ) && $vuln['updated'] >= $start_ts && $vuln['updated'] <= $end_ts;
		if ( ! $matches_published && ! $matches_updated ) {
			continue;
		}

		$total_vulns++;
		$post_content .= '<h2>' . esc_html( $vuln['title'] ) . '</h2>';

		foreach ( $vuln['software'] as $software ) {
			$patched_version = '' !== $software['patched'] ? $software['patched'] : __( 'N/A', 'watch-out-wednesday' );
			$remediation     = '' !== $software['remediation'] ? $software['remediation'] : __( 'No remediation info', 'watch-out-wednesday' );

			$post_content .= '<p><strong>' . esc_html( $software['type'] . ': ' . $software['name'] ) . '</strong><br />';
			$post_content .= esc_html__( 'Patched version:', 'watch-out-wednesday' ) . ' ' . esc_html( $patched_version ) . '<br />';
			$post_content .= esc_html__( 'Recommended action:', 'watch-out-wednesday' ) . ' ' . esc_html( $remediation ) . '</p>';
		}

		if ( '' !== $vuln['copyright'] ) {
			$post_content .= '<p>' . esc_html( $vuln['copyright'] ) . '</p>';
		}

		if ( ! empty( $vuln['references'] ) ) {
			$links = array();
			foreach ( $vuln['references'] as $reference ) {
				$links[] = '<a href="' . esc_url( $reference ) . '">' . esc_html( $reference ) . '</a>';
			}
			$post_content .= '<p><strong>' . esc_html__( 'Sources:', 'watch-out-wednesday' ) . '</strong><br />' . implode( '<br />', $links ) . '</p>';
		}
	}

	if ( 0 === $total_vulns ) {
		$post_content .= '<p>' . esc_html__( 'No vulnerabilities found in this period.', 'watch-out-wednesday' ) . '</p>';
	}

	$post_content .= '<p>' . esc_html__( 'Vulnerability details come from the Wordfence Intelligence vulnerability database. They may be incomplete, outdated, or later corrected. This post does not confirm that this site is affected.', 'watch-out-wednesday' ) . '</p>';
	$post_content .= '<p>' . wp_kses(
		__( 'Data is from the Wordfence Intelligence vulnerability database, published by Defiant, Inc. Some records include a copyright notice from another licensor, including MITRE. That notice is shown with the record when the feed provides it. <a href="https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/">Wordfence Intelligence terms</a>.', 'watch-out-wednesday' ),
		array(
			'a' => array(
				'href' => array(),
			),
		)
	) . '</p>';
	$post_content .= $outro_text;

	return array(
		'content'     => $post_content,
		'total_vulns' => $total_vulns,
	);
}
