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

## Local development

1. Clone this repository.
2. Copy the directory into a local WordPress site as `wp-content/plugins/watch-out-wednesday`.
3. WordPress 6.0+ and PHP 7.4+ are required (`readme.txt`).
4. Activate **Watch Out Wednesday** and open `Settings -> Watch Out Wednesday`.

There is no Composer or npm install.

## Tests

No test suite is checked in. There is no `phpunit` or `npm test` command. CI runs `php -l` on each `*.php` file.

## Release / deploy

The plugin is shipped as this source tree. There is no build step and no release workflow. Do not commit `*.zip`. When a package is needed, zip the plugin directory locally and tag the version that matches the Stable tag in `readme.txt`.
