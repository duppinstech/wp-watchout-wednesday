=== Watch Out Wednesday ===
Contributors: duppinstech
Tags: vulnerabilities, wordfence, posts, cron
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publishes a weekly Watch Out Wednesday post from the Wordfence Intelligence vulnerability feed. You supply the API key.

== Description ==

Watch Out Wednesday, by Duppins Technology, publishes a weekly vulnerability recap from the Wordfence Intelligence feed. You can rerun one missed Wednesday or backfill older weeks.

Wordfence and Wordfence Intelligence are trademarks of Defiant, Inc. Watch Out Wednesday is not affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence.

This plugin does not scan, patch, or protect the site. It publishes a recap.

Features:

* Weekly post when weekly posts are turned on.
* Run the current week, one missed Wednesday, or a backfill.
* A week that already has a post is updated.
* A week with no vulnerabilities is saved as a draft.
* Intro, outro, category, featured image, and one notification email.

== Installation ==

1. Download `watch-out-wednesday.zip` from the GitHub Release for a tagged version. This plugin is not on WordPress.org and does not update itself. For local development, copy the `watch-out-wednesday` folder to `/wp-content/plugins/`.
2. Activate Watch Out Wednesday.
3. Open Settings → Watch Out Wednesday → Authentication, paste your Wordfence Intelligence v3 API key, and save. Watch Out Wednesday does not include a key.
4. On the Content tab, set the intro, outro, category, featured image, and notification email. Turn on Enable weekly posts for the Wednesday run and save.
5. Use the Manual Run tab to publish the current week, one missed Wednesday, or a backfill.

The feed is one full download. Wordfence’s February 2026 notice described it at about 123 MB. A PHP memory limit of 128 MB can fail a run. Give the site enough memory and time for that download. The plugin does not store the feed.

== Frequently Asked Questions ==

= Where does the API key go? =

Open Settings → Watch Out Wednesday → Authentication, paste a Wordfence Intelligence v3 key, and save. The plugin does not ship with a key. Manual Run works while weekly posts are off. The weekly cron runs only when Enable weekly posts is on and saved.

= I turned weekly posts off and they came back on. =

Versions before 1.2.0 did not store the off position. Deactivate the plugin to stop the cron. In 1.2.0, open the Content tab, turn Enable weekly posts off, and save once.

= Does bulk backfill create duplicates? =

No. The match is the full title, for example `Watch Out Wednesday - October 21, 2026`. An older post saved with the previous day’s title is updated to the corrected title.

= Which date should I pick for missed weeks? =

Use a Wednesday. The post for that Wednesday covers vulnerabilities published or updated from the previous Wednesday through that Tuesday, using the current Wordfence feed. A backfill does not restore what the feed looked like on that past day. Before Wednesday, Run Current Week Now targets the coming Wednesday, and WordPress holds the post as scheduled until then.

= What if a week has no vulnerabilities? =

The post is saved as a draft. It is not published.

= Does the plugin change Yoast fields? =

Each run writes the Yoast meta description and focus keyphrase on that post. A rerun replaces both values. The focus keyphrase is `WordPress vulnerability`.

= What happens if I deactivate or delete the plugin? =

Deactivating stops the weekly cron. It does not delete posts or settings. Deleting the plugin removes `wow_settings` (including the API key) and `wow_last_run`. Generated posts stay. There is no automatic update. Install a newer GitHub Release zip over the folder.

== External services ==

This plugin requests the Wordfence Intelligence v3 feed at the endpoint on the Authentication tab (default: https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production). Each request sends that host the API key you entered. The connecting server’s IP address is visible to Wordfence as part of a normal HTTPS request. No visitor personal data is sent. Wordfence Intelligence terms: https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/ Wordfence privacy policy: https://www.wordfence.com/privacy-policy/

The notification email is stored in the WordPress option wow_settings and is used only to send the generated post or an error through this site’s mail setup. It remains until you change the setting or delete the option. Deactivating the plugin does not delete it.

== Changelog ==

= 1.2.0 =
* Weekly posts can be turned off, and the Wednesday title uses the site timezone.
* Empty weeks are saved as drafts. A backfill sends one summary email.
* Generated posts list every affected software item and show Wordfence attribution.
* The API key is not displayed on the settings screen. Deleting the plugin removes its settings.

= 1.1.0 =
* Added manual run, missed-Wednesday rerun, and bulk backfill.
* **The settings screen is under Settings → Watch Out Wednesday.**
* Settings saves and manual runs require a logged-in administrator and a valid request.
