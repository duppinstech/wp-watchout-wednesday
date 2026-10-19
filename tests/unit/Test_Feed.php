<?php
/**
 * Wordfence request tests.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_fetch_vulnerabilities
 */
class Test_Feed extends Wow_Test_Case {
	/**
	 * A missing key never calls Wordfence.
	 */
	public function test_missing_key_does_not_call_the_feed() {
		Functions\when( 'get_option' )->justReturn( array() );
		Functions\expect( 'wp_safe_remote_get' )->never();

		$result = wow_fetch_vulnerabilities();

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'wow_wordfence_missing_key', $result->get_error_code() );
	}

	/**
	 * The default production URL is requested with the bearer key and no redirects.
	 */
	public function test_sends_the_bearer_key_to_the_default_endpoint() {
		Functions\when( 'get_option' )->justReturn( array( 'wordfence_api_key' => 'secret-key' ) );
		Functions\expect( 'wp_safe_remote_get' )->once()->withArgs(
			function ( $url, $args ) {
				return false !== strpos( $url, '/api/intelligence/v3/vulnerabilities/production' )
					&& isset( $args['headers']['Authorization'] )
					&& 'Bearer secret-key' === $args['headers']['Authorization']
					&& 0 === $args['redirection']
					&& $args['timeout'] > 30;
			}
		)->andReturn( array( 'body' => '{}' ) );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{"uuid-1":{"title":"Example"}}' );

		$result = wow_fetch_vulnerabilities();

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'uuid-1', $result );
	}

	/**
	 * HTTP 401 and 429 keep their own error codes.
	 */
	public function test_maps_unauthorized_and_rate_limit_responses() {
		Functions\when( 'get_option' )->justReturn( array( 'wordfence_api_key' => 'secret-key' ) );
		Functions\when( 'wp_safe_remote_get' )->justReturn( array( 'body' => '' ) );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '' );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 401 );

		$unauthorized = wow_fetch_vulnerabilities();
		$this->assertSame( 'wow_wordfence_unauthorized', $unauthorized->get_error_code() );

		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 429 );
		$limited = wow_fetch_vulnerabilities();
		$this->assertSame( 'wow_wordfence_rate_limited', $limited->get_error_code() );
	}

	/**
	 * Private and loopback addresses are rejected.
	 */
	public function test_rejects_non_global_addresses() {
		$this->assertTrue( wow_ip_is_non_global( '127.0.0.1' ) );
		$this->assertTrue( wow_ip_is_non_global( '10.1.1.1' ) );
		$this->assertTrue( wow_ip_is_non_global( '192.168.0.5' ) );
		$this->assertTrue( wow_ip_is_non_global( '169.254.1.1' ) );
		$this->assertFalse( wow_ip_is_non_global( '8.8.8.8' ) );
		$this->assertTrue( wow_feed_exceeds_memory( 120 * 1048576, 256 * 1048576, 40 * 1048576 ) );
		$this->assertFalse( wow_feed_exceeds_memory( 1024, 256 * 1048576, 40 * 1048576 ) );
		$this->assertFalse( wow_feed_exceeds_memory( 120 * 1048576, 0, 40 * 1048576 ) );
		$this->assertFalse( wow_feed_exceeds_memory( 158 * 1048576, 768 * 1048576, 400 * 1048576 ) );
		$this->assertTrue( wow_feed_exceeds_memory( 158 * 1048576, 768 * 1048576, 700 * 1048576 ) );
		$this->assertGreaterThan( 768, (int) wow_feed_suggested_limit_label( 158 * 1048576, 768 * 1048576 ) );
		$this->assertNotSame( '512M', wow_feed_suggested_limit_label( 158 * 1048576, 768 * 1048576 ) );
		$this->assertSame( 268435456, wow_ini_bytes( '256M' ) );
		$this->assertSame( 0, wow_ini_bytes( '-1' ) );
		$this->assertFalse( wow_is_public_https_url( 'http://www.wordfence.com/api/intelligence/v3/vulnerabilities/production' ) );
		$this->assertFalse( wow_is_public_https_url( 'https://127.0.0.1/feed' ) );
		$this->assertTrue( wow_is_public_https_url( 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production' ) );
	}
}
