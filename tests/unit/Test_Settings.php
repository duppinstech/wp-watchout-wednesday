<?php
/**
 * Settings merge tests.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_sanitize_settings
 */
class Test_Settings extends Wow_Test_Case {
	/**
	 * Stub sanitizers around one stored settings array.
	 *
	 * @param array $existing Stored option.
	 */
	private function stub_existing( $existing ) {
		Functions\when( 'get_option' )->justReturn( $existing );
		Functions\when( 'sanitize_email' )->alias(
			function ( $value ) {
				$clean = filter_var( $value, FILTER_SANITIZE_EMAIL );
				return is_string( $clean ) ? $clean : '';
			}
		);
		Functions\when( 'is_email' )->alias(
			function ( $value ) {
				return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
			}
		);
	}

	/**
	 * An omitted Enable key must not turn the schedule off.
	 */
	public function test_keeps_enabled_when_the_authentication_form_omits_it() {
		$this->stub_existing(
			array(
				'enabled'            => 1,
				'wordfence_api_key'  => 'stored-key',
				'intro_text'         => 'Hello',
			)
		);

		$output = wow_sanitize_settings(
			array(
				'wordfence_api_key'      => 'new-key',
				'wordfence_api_endpoint' => 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production',
			)
		);

		$this->assertSame( 1, $output['enabled'] );
		$this->assertSame( 'Hello', $output['intro_text'] );
		$this->assertSame( 'new-key', $output['wordfence_api_key'] );
	}

	/**
	 * The Content form sends a hidden 0 when the checkbox is unchecked.
	 */
	public function test_stores_enabled_zero_when_the_key_is_present_and_empty() {
		$this->stub_existing( array( 'enabled' => 1 ) );

		$output = wow_sanitize_settings( array( 'enabled' => '0' ) );

		$this->assertSame( 0, $output['enabled'] );
	}

	/**
	 * A blank key field keeps the stored key.
	 */
	public function test_keeps_the_api_key_when_the_field_is_blank() {
		$this->stub_existing( array( 'wordfence_api_key' => 'stored-key' ) );

		$output = wow_sanitize_settings( array( 'wordfence_api_key' => '' ) );

		$this->assertSame( 'stored-key', $output['wordfence_api_key'] );
	}

	/**
	 * The clear control removes the key.
	 */
	public function test_clears_the_api_key_when_asked() {
		$this->stub_existing( array( 'wordfence_api_key' => 'stored-key' ) );

		$output = wow_sanitize_settings(
			array(
				'wordfence_api_key'       => 'stored-key',
				'wordfence_api_key_clear' => '1',
			)
		);

		$this->assertSame( '', $output['wordfence_api_key'] );
	}

	/**
	 * A non-https endpoint is rejected and the previous value stays.
	 */
	public function test_rejects_a_non_https_endpoint() {
		$this->stub_existing(
			array(
				'wordfence_api_endpoint' => 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production',
			)
		);

		$output = wow_sanitize_settings( array( 'wordfence_api_endpoint' => 'http://127.0.0.1/feed' ) );

		$this->assertSame( 'https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production', $output['wordfence_api_endpoint'] );
	}

	/**
	 * An invalid email does not wipe the saved address.
	 */
	public function test_keeps_the_previous_email_when_the_new_one_is_invalid() {
		$this->stub_existing( array( 'email' => 'owner@example.com' ) );

		$output = wow_sanitize_settings( array( 'email' => 'not-an-email' ) );

		$this->assertSame( 'owner@example.com', $output['email'] );
	}
}
