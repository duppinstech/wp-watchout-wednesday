# Watch Out Wednesday

WordPress plugin that pulls vulnerability intelligence from Wordfence and publishes a weekly "Watch Out Wednesday" post.

## Features

- Weekly scheduled automation using WP-Cron.
- Manual "Run current week now" trigger.
- Missed-week regeneration for a specific Wednesday date.
- Bulk backfill mode for multiple historical weeks.
- Configurable intro/outro text, category, featured image, and notification email.

## Installation

1. Copy the plugin into `wp-content/plugins/watch-out-wednesday`.
2. Activate **Watch Out Wednesday** in WordPress Admin.
3. Go to `Settings -> Watch Out Wednesday`.
4. Configure options and save.
5. Use manual run controls for immediate generation or backfill.

## Notes

- Bulk backfill updates existing posts by title if they already exist for a Wednesday.
- Weekly automation only runs when the plugin setting **Enable Script** is turned on.

## Development

- Main bootstrap: `watch-out-wednesday.php`
- Admin settings UI: `includes/admin-settings.php`
- Post generation and API handling: `includes/post-generator.php`

## License

GPL-2.0-or-later
