<?php
/**
 * Ten-week backfill through the real post functions.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_run_bulk_backfill
 */
class Test_Bulk extends Wow_Test_Case {
	/**
	 * Stub the WordPress functions a backfill touches.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wow_posts'] = array();

		Functions\when( 'wp_timezone' )->justReturn( new DateTimeZone( 'UTC' ) );
		Functions\when( 'wp_date' )->alias( array( $this, 'format_wp_date' ) );
		Functions\when( 'get_option' )->justReturn(
			array(
				'email'      => 'owner@example.com',
				'intro_text' => '',
				'outro_text' => '',
			)
		);
		Functions\when( 'sanitize_email' )->alias(
			function ( $value ) {
				$clean = filter_var( $value, FILTER_SANITIZE_EMAIL );
				return is_string( $clean ) ? $clean : '';
			}
		);
		Functions\when( 'is_email' )->justReturn( true );
		Functions\when( 'sanitize_title_with_dashes' )->returnArg();
		Functions\when( 'wp_slash' )->returnArg();
		Functions\when( 'get_current_user_id' )->justReturn( 1 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.test/?p=1' );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'set_post_thumbnail' )->justReturn( true );
		Functions\when( 'attachment_url_to_postid' )->justReturn( 0 );
		Functions\when( 'wp_mail' )->justReturn( true );
		Functions\when( 'wp_insert_post' )->alias(
			function ( $post ) {
				$id                        = count( $GLOBALS['wow_posts'] ) + 1;
				$GLOBALS['wow_posts'][ $id ] = $post;
				return $id;
			}
		);
		Functions\when( 'wp_update_post' )->alias(
			function ( $post ) {
				$id                        = (int) $post['ID'];
				$GLOBALS['wow_posts'][ $id ] = $post;
				return $id;
			}
		);
		Functions\when( 'get_post_status' )->alias(
			function ( $post_id ) {
				$post_id = (int) $post_id;
				if ( ! isset( $GLOBALS['wow_posts'][ $post_id ] ) ) {
					return false;
				}
				return $GLOBALS['wow_posts'][ $post_id ]['post_status'];
			}
		);
	}

	/**
	 * Ten Wednesdays are created from one feed, including an odd record shape.
	 */
	public function test_backfill_of_ten_weeks_publishes_without_crashing() {
		$feed = array(
			array(
				'title'     => array( 'not-a-string' ),
				'published' => '2026-10-08 12:00:00',
				'software'  => array(
					array(
						'type'             => 'plugin',
						'name'             => 'Broken',
						'patched_versions' => array(
							array( 'from_version' => '9.9.9' ),
						),
						'remediation'      => array( 'nested' ),
					),
				),
			),
			array(
				'title'      => 'October flaw',
				'published'  => '2026-10-15 12:00:00',
				'copyright'  => 'Copyright example',
				'references' => array( 'https://example.test/advisory' ),
				'software'   => array(
					array(
						'type'             => 'plugin',
						'name'             => 'Alpha',
						'patched_versions' => array( '2.0' ),
						'remediation'      => 'Update Alpha',
					),
					array(
						'type'             => 'theme',
						'name'             => 'Beta',
						'patched_versions' => array(
							array( 'from_version' => '3.1' ),
						),
						'remediation'      => 'Update Beta',
					),
				),
			),
		);

		$result = wow_run_bulk_backfill( '2026-10-21', 10, $feed );

		$this->assertSame( 'success', $result['type'] );
		$this->assertStringContainsString( 'Published: 2', $result['message'] );
		$this->assertStringContainsString( 'Drafts with no vulnerabilities: 8', $result['message'] );
		$this->assertStringContainsString( 'Failed: 0', $result['message'] );
		$this->assertCount( 10, $GLOBALS['wow_posts'] );

		$published = 0;
		$drafts    = 0;
		$combined  = '';
		foreach ( $GLOBALS['wow_posts'] as $post ) {
			$combined .= $post['post_content'];
			if ( 'publish' === $post['post_status'] ) {
				$published++;
			} else {
				$drafts++;
				$this->assertSame( 'draft', $post['post_status'] );
			}
		}

		$this->assertSame( 2, $published );
		$this->assertSame( 8, $drafts );
		$this->assertStringContainsString( 'October flaw', $combined );
		$this->assertStringContainsString( 'Plugin: Alpha', $combined );
		$this->assertStringContainsString( 'Theme: Beta', $combined );
		$this->assertStringContainsString( '3.1', $combined );
		$this->assertStringContainsString( 'Untitled vulnerability', $combined );
		$this->assertStringContainsString( '9.9.9', $combined );
	}
}
