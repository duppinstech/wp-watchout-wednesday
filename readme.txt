=== Watch Out Wednesday ===
Contributors: duppinstech
Tags: security, vulnerabilities, wordfence, cron, automation
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically generates weekly Watch Out Wednesday posts by pulling vulnerability intelligence from Wordfence.

== Description ==

Watch Out Wednesday helps security-conscious WordPress sites publish a weekly vulnerability recap post.

Features:

* Weekly cron generation (when enabled in settings).
* Manual one-click run for the current reporting week.
* Missed-week rerun by selecting a specific Wednesday date.
* Bulk backfill mode to regenerate multiple historical weeks.
* Configurable intro text, outro text, post category, featured image, and notification email.

== Installation ==

1. Upload plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Navigate to `Settings -> Watch Out Wednesday`.
4. Configure options and save.
5. Use Manual Runs section for immediate, specific, or bulk backfill generation.

== Frequently Asked Questions ==

= Does bulk backfill create duplicates? =

No. Existing posts with the same generated title are updated.

= Which date should I pick for missed weeks? =

Use the Wednesday date for the reporting week you want to publish.

== Changelog ==

= 1.1.0 =
* Added manual run, specific-week rerun, and bulk backfill controls.
* Moved menu under Settings.
* Hardened nonce validation, escaping, and settings sanitization.
* Added plugin license metadata and project documentation files.
