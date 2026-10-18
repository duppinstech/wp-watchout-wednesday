<?php
/**
 * Manual run handler tests.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_process_manual_run
 */
class Test_Manual_Run extends Wow_Test_Case {
	/**
	 * A user who cannot manage options never reaches the feed.
	 */
	public function test_unauthorized_request_does_not_fetch() {
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\expect( 'wp_safe_remote_get' )->never();

		$result = wow_process_manual_run( array( 'wow_run_now' => '1' ) );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'wow_unauthorized', $result->get_error_code() );
	}

	/**
	 * A missing key becomes an error notice and does not generate a post.
	 */
	public function test_missing_key_returns_an_error_notice() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( array() );

		$result = wow_process_manual_run( array( 'wow_run_now' => '1' ) );

		$this->assertSame( 'error', $result['type'] );
		$this->assertStringContainsString( 'Wordfence API key is missing', $result['message'] );
	}

	/**
	 * A Tuesday is rejected before a post is written.
	 */
	public function test_rejects_a_date_that_is_not_wednesday() {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'check_admin_referer' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( array( 'wordfence_api_key' => 'secret-key' ) );
		Functions\when( 'wp_safe_remote_get' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{}' );
		Functions\when( 'wp_timezone' )->justReturn( new DateTimeZone( 'UTC' ) );

		$result = wow_process_manual_run(
			array(
				'wow_run_specific'      => '1',
				'wow_target_wednesday'  => '2026-10-20',
			)
		);

		$this->assertSame( 'error', $result['type'] );
		$this->assertStringContainsString( 'Wednesday', $result['message'] );
	}
}
