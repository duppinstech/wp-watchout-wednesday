<?php
/**
 * Shared Brain Monkey setup.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Base test case.
 */
abstract class Wow_Test_Case extends PHPUnit\Framework\TestCase {
	/**
	 * Start Brain Monkey and stub escaping helpers.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'esc_html_e' )->returnArg();
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'esc_textarea' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'wp_kses' )->returnArg();
		Functions\when( 'wpautop' )->returnArg();
		Functions\when( 'wp_parse_url' )->alias( 'parse_url' );
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'add_settings_error' )->justReturn( null );
	}

	/**
	 * Tear down Brain Monkey.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Site-timezone wp_date() stand-in.
	 *
	 * @param string            $format Format.
	 * @param int|null          $timestamp Timestamp.
	 * @param DateTimeZone|null $timezone Timezone.
	 * @return string
	 */
	public function format_wp_date( $format, $timestamp = null, $timezone = null ) {
		if ( null === $timestamp ) {
			$timestamp = time();
		}
		if ( ! $timezone instanceof DateTimeZone ) {
			$timezone = wp_timezone();
		}

		return ( new DateTimeImmutable( '@' . (int) $timestamp ) )->setTimezone( $timezone )->format( $format );
	}
}
