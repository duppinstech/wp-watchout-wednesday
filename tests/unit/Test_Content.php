<?php
/**
 * Generated post body tests.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_generate_vuln_post_content
 */
class Test_Content extends Wow_Test_Case {
	/**
	 * Window covering 14 October 2026 04:00 UTC through 21 October 2026 03:59:59 UTC.
	 *
	 * @return array
	 */
	private function window() {
		return array(
			new DateTimeImmutable( '2026-10-14 04:00:00', new DateTimeZone( 'UTC' ) ),
			new DateTimeImmutable( '2026-10-21 03:59:59', new DateTimeZone( 'UTC' ) ),
		);
	}

	/**
	 * Every affected software item is listed, and a bad date does not throw.
	 */
	public function test_lists_every_software_item_and_skips_a_bad_date() {
		Functions\when( 'get_option' )->justReturn( array( 'intro_text' => 'Intro', 'outro_text' => 'Outro' ) );
		list( $start, $end ) = $this->window();

		$result = wow_generate_vuln_post_content(
			$start,
			$end,
			array(
				array(
					'title'      => 'Broken record',
					'published'  => 'not-a-date',
					'software'   => array(
						array( 'type' => 'plugin', 'name' => 'Should Skip' ),
					),
				),
				array(
					'title'      => 'Example flaw',
					'published'  => '2026-10-15 12:00:00',
					'copyright'  => 'Copyright example licensor',
					'references' => array( 'https://example.test/advisory' ),
					'software'   => array(
						array(
							'type'              => 'plugin',
							'name'              => 'Alpha',
							'patched_versions'  => array( '2.0' ),
							'remediation'       => 'Update Alpha',
						),
						array(
							'type'             => 'theme',
							'name'             => 'Beta',
							'patched_versions' => array( '3.1' ),
							'remediation'      => 'Update Beta',
						),
					),
				),
				array(
					'title'     => 'Old flaw',
					'published' => '2026-01-01 00:00:00',
					'software'  => array(
						array( 'type' => 'plugin', 'name' => 'Old' ),
					),
				),
			)
		);

		$this->assertSame( 1, $result['total_vulns'] );
		$this->assertStringContainsString( '<h2>Example flaw</h2>', $result['content'] );
		$this->assertStringContainsString( 'Plugin: Alpha', $result['content'] );
		$this->assertStringContainsString( 'Theme: Beta', $result['content'] );
		$this->assertStringContainsString( 'Copyright example licensor', $result['content'] );
		$this->assertStringContainsString( 'https://example.test/advisory', $result['content'] );
		$this->assertStringNotContainsString( 'Should Skip', $result['content'] );
		$this->assertStringNotContainsString( 'Old flaw', $result['content'] );
		$this->assertStringContainsString( 'does not confirm that this site is affected', $result['content'] );
	}

	/**
	 * An empty week still explains that nothing matched.
	 */
	public function test_empty_window_says_nothing_was_found() {
		Functions\when( 'get_option' )->justReturn( array() );
		list( $start, $end ) = $this->window();

		$result = wow_generate_vuln_post_content( $start, $end, array() );

		$this->assertSame( 0, $result['total_vulns'] );
		$this->assertStringContainsString( 'No vulnerabilities found in this period.', $result['content'] );
	}
}
