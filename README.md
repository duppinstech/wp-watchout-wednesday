# Watch Out Wednesday

Watch Out Wednesday, by Duppins Technology, publishes a weekly vulnerability recap from the Wordfence Intelligence feed.

Wordfence and Wordfence Intelligence are trademarks of Defiant, Inc. Watch Out Wednesday is not affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence.

Copyright (C) 2026 Duppins Technology

## Features

- Weekly post when **Enable weekly posts** is on.
- Run the current week, one missed Wednesday, or a backfill.
- A week that already has a post is updated.
- A week with no vulnerabilities is saved as a draft.
- Intro, outro, category, featured image, and one notification email.

## Installation

1. Install the GitHub Release zip into `wp-content/plugins/watch-out-wednesday`. This plugin is not on WordPress.org. A newer GitHub Release shows under Dashboard → Updates.
2. For local development, copy this directory into a WordPress site as `wp-content/plugins/watch-out-wednesday`.
3. Activate **Watch Out Wednesday**.
4. Open Settings → Watch Out Wednesday → Authentication, paste your Wordfence Intelligence v3 API key, and save. The plugin does not include a key.
5. On the Content tab, set the intro, outro, category, featured image, and notification email. Turn on **Enable weekly posts** and save.
6. Use the Manual Run tab to publish the current week, one missed Wednesday, or a backfill.

Each run downloads the full Wordfence feed. Wordfence’s February 2026 notice described that file at about 123 MB. A 128 MB PHP memory limit can fail the run. The feed is not stored in WordPress.

## Notes

- The weekly run is the next Wednesday at 00:00 in the site timezone, then every 7 days. Turning weekly posts on during Wednesday waits until the following Wednesday.
- A Wednesday post includes vulnerabilities published or updated from the previous Wednesday through that Tuesday, from the current feed.
- Before Wednesday, **Run Current Week Now** targets the coming Wednesday. WordPress holds that post as scheduled until then.
- A bulk backfill sends one summary email. It can stop early so the request can finish. The notice names failed weeks and how to run the rest.
- Each run writes the Yoast meta description and focus keyphrase on that post. A rerun replaces both. The focus keyphrase is `WordPress vulnerability`.
- Deactivating the plugin stops the weekly cron. It does not delete posts, settings, or the API key.
- Deleting the plugin removes `wow_settings` and `wow_last_run`. Generated posts stay.

## Development

- Main bootstrap: `watch-out-wednesday.php`
- Admin settings UI: `includes/admin-settings.php`
- Post generation and API handling: `includes/post-generator.php`
- WordPress 6.0+ and PHP 7.4+ (`readme.txt`)

There is no runtime Composer install. Development tests use Composer:

```bash
composer install
vendor/bin/phpunit
```

## Tests

`vendor/bin/phpunit` runs the unit suite. CI runs that command on PHP 7.4 after `php -l`. There is no `npm test`.

## Release

Releases are the GitHub Release zip (`watch-out-wednesday.zip`), built by `bin/build-zip.sh` and published by `.github/workflows/release.yml` when a `v*` tag is pushed. The tag must match the Version header, the `WOW_VERSION` constant, and the Stable tag in `readme.txt`. The release notes are written by `bin/release-notes.sh`. Installed sites receive that zip through the GitHub updater on Dashboard → Updates. Do not commit `*.zip`.

## License

GPL-2.0-or-later. Copyright (C) 2026 Duppins Technology.
