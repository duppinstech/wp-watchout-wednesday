<?php
/**
 * Wednesday date tests.
 *
 * @package Watch_Out_Wednesday
 */

use Brain\Monkey\Functions;

/**
 * @covers wow_wednesday_post_schedule
 */
class Test_Schedule extends Wow_Test_Case {
	/**
	 * Use a US timezone where UTC midnight is the previous evening.
	 */
	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_timezone' )->justReturn( new DateTimeZone( 'America/New_York' ) );
		Functions\when( 'wp_date' )->alias( array( $this, 'format_wp_date' ) );
	}

	/**
	 * The title and post date stay on the site-local Wednesday.
	 */
	public function test_titles_the_post_with_the_site_local_wednesday() {
		$schedule = wow_wednesday_post_schedule( '2026-10-21' );

		$this->assertSame( 'Watch Out Wednesday - October 21, 2026', $schedule['title'] );
		$this->assertSame( 'Watch Out Wednesday - October 20, 2026', $schedule['legacy_title'] );
		$this->assertSame( '2026-10-21 00:00:00', $schedule['post_date'] );
		$this->assertSame( '2026-10-21 04:00:00', $schedule['post_date_gmt'] );
	}

	/**
	 * Bulk dates stay on Wednesdays and reject other weekdays.
	 */
	public function test_bulk_dates_walk_backward_by_wednesdays() {
		$this->assertSame(
			array( '2026-10-21', '2026-10-14', '2026-10-07' ),
			wow_bulk_wednesday_dates( '2026-10-21', 3 )
		);
		$this->assertSame( array(), wow_bulk_wednesday_dates( '2026-10-20', 2 ) );
	}

	/**
	 * A private post with the same title is not updated.
	 */
	public function test_lookup_does_not_return_a_private_post() {
		$GLOBALS['wow_query_posts'] = array( 42 );
		Functions\when( 'get_post_status' )->justReturn( 'private' );

		$this->assertSame( 0, wow_find_existing_post_id_by_title( 'Watch Out Wednesday - October 21, 2026' ) );
		$this->assertNotContains( 'private', $GLOBALS['wow_last_query']['post_status'] );
	}
}
