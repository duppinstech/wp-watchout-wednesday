# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.3.0] - 2026-10-18

### Added
- The plugins screen shows Settings before Deactivate, then Sponsor, View details, and Support.
- View details shows a banner, an icon, and Description, Installation, FAQ, Screenshots, and Changelog from `readme.txt`.
- Updates install from GitHub Releases. WordPress.org cannot replace this plugin.

## [1.2.1] - 2026-10-18

### Fixed
- **A ten-week backfill no longer ends in a blank server error.** The run stops with a settings message when PHP is out of memory or time, and a feed record with an unexpected shape is skipped instead of crashing the request.

## [1.2.0] - 2026-10-18

### Added
- Site-local Wednesday titles and post dates. A rerun still finds a post saved under the old off-by-one title.
- `uninstall.php`. Deleting the plugin removes its settings, the stored API key, and the weekly cron. Generated posts stay.
- Attribution on every generated post, including a record’s copyright notice and source links when the feed provides them.
- One summary email for a bulk backfill, instead of one email per week.
- PHPUnit tests for the feed, settings merge, Wednesday dates, and manual-run checks. Run `composer install` and `vendor/bin/phpunit`.

### Changed
- **Enable weekly posts can be turned off.** Saving the Content tab with the switch unchecked unschedules the weekly cron.
- **A week with no vulnerabilities is saved as a draft.** It is not published.
- **The weekly cron publishes the Wednesday that was due**, including when WP-Cron runs late.
- The post lists every affected plugin or theme on a vulnerability, not only the first.
- The Wordfence API key is not shown on the settings screen. Leave the field blank to keep the saved key.
- The saved feed URL must be a public https address. Requests do not follow redirects.
- Settings fields have labels, and the weekly-post switch can be focused from the keyboard.
- “Run Current Week Now” is the primary button. A backfill of more than one week asks for confirmation.

### Security
- Settings saves and manual runs require a logged-in administrator and a valid request.
- Report vulnerabilities to support@duppinstech.com.

## [1.1.0] - 2026-04-28

### Added
- Manual run, missed-Wednesday rerun, and bulk backfill on the settings screen.

### Changed
- **The settings screen is under Settings → Watch Out Wednesday.**

### Security
- Settings saves and manual runs require a logged-in administrator and a valid request.
