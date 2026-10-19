=== Watch Out Wednesday ===
Contributors: duppinstech
Tags: vulnerabilities, wordfence, posts, cron
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Publishes a weekly Watch Out Wednesday post from the Wordfence Intelligence vulnerability feed. You supply the API key.

== Description ==

Watch Out Wednesday, by Duppins Technology, publishes a weekly vulnerability recap from the Wordfence Intelligence feed. You can rerun one missed Wednesday or backfill older weeks.

Wordfence and Wordfence Intelligence are trademarks of Defiant, Inc. Watch Out Wednesday is not affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence.

This plugin does not scan, patch, or protect the site. It publishes a recap.

= Features =

* Weekly post when weekly posts are turned on.
* Run the current week, one missed Wednesday, or a backfill.
* A week that already has a post is updated.
* A week with no vulnerabilities is saved as a draft.
* Intro, outro, category, featured image, and one notification email.

= Support =

Support: [support@duppinstech.com](mailto:support@duppinstech.com). Made by [Duppins Technology](https://duppinstech.com).

== Installation ==

1. Download `watch-out-wednesday.zip` from the latest GitHub Release. This plugin is not on WordPress.org.
2. In wp-admin, go to Plugins → Add New → Upload Plugin, choose the zip, and click Install Now.
3. Activate Watch Out Wednesday.
4. Open Settings → Watch Out Wednesday → Authentication, paste your Wordfence Intelligence v3 API key, and save. Watch Out Wednesday does not include a key.
5. On the Content tab, set the intro, outro, category, featured image, and notification email. Turn on Enable weekly posts for the Wednesday run and save.
6. Use the Manual Run tab to publish the current week, one missed Wednesday, or a backfill.

The feed is one full download. Wordfence’s February 2026 notice described it at about 123 MB. A PHP memory limit of 128 MB can fail a run. Give the site enough memory and time for that download. The plugin does not store the feed.

= Updates =

New releases appear under Dashboard → Updates.

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

= How do I update the plugin? =

Open Dashboard → Updates. A newer GitHub Release appears there. This plugin is not on WordPress.org, so WordPress.org cannot replace it.

= What happens if I deactivate or delete the plugin? =

Deactivating stops the weekly cron. It does not delete posts or settings. Deleting the plugin removes its settings, including the API key, and the stored last run. Generated posts stay.

== External services ==

This plugin requests the Wordfence Intelligence v3 feed at the endpoint on the Authentication tab (default: https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production). Each request sends that host the API key you entered. The connecting server’s IP address is visible to Wordfence as part of a normal HTTPS request. No visitor personal data is sent. Wordfence Intelligence terms: https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/ Wordfence privacy policy: https://www.wordfence.com/privacy-policy/

The notification email is stored in the WordPress option wow_settings and is used only to send the generated post or an error through this site’s mail setup. It remains until you change the setting or delete the plugin. Deactivating the plugin does not delete it.

== Screenshots ==

1. Content settings for the weekly post, including the intro, outro, category, featured image, and notification email.
2. Manual Run for the current week, one missed Wednesday, or a backfill.
3. Authentication, where the Wordfence Intelligence API key is saved.

== Changelog ==

= 1.3.1 =
* A 158 MB feed on a 768M memory limit is read instead of being refused.
* When memory really is too low, the message names a limit above the one already set.

= 1.3.0 =
* The plugins screen has a Settings link, plus Sponsor, View details, and Support.
* View details shows the description, installation, questions, screenshots, and changelog.
* A newer release appears under Dashboard → Updates. WordPress.org cannot replace this plugin.

= 1.2.1 =
* A bulk backfill that cannot finish reports the error on the settings screen instead of a blank server error.
* The Wordfence download stops with a clear message when PHP does not have enough memory or time.
* A feed record with an unexpected shape is skipped instead of crashing the run.

= 1.2.0 =
* Weekly posts can be turned off. Saving the Content tab with the switch unchecked stops the weekly cron.
* The Wednesday title and post date use the site timezone. A rerun still finds a post saved under the old title.
* The weekly cron publishes the Wednesday that was due, including when the cron runs late.
* A week with no vulnerabilities is saved as a draft. It is not published.
* A backfill sends one summary email.
* The post lists every affected plugin or theme, and shows Wordfence attribution.
* The API key is not displayed on the settings screen. Leave the field blank to keep the saved key.
* The saved feed URL must be a public https address.
* Settings fields have labels, and the weekly-post switch can be focused from the keyboard.
* Run Current Week Now is the primary button. A backfill of more than one week asks for confirmation.
* Deleting the plugin removes its settings, the stored API key, and the weekly cron. Generated posts stay.
* Settings saves and manual runs require a logged-in administrator and a valid request.

= 1.1.0 =
* Added manual run, missed-Wednesday rerun, and bulk backfill.
* The settings screen is under Settings → Watch Out Wednesday.
* Settings saves and manual runs require a logged-in administrator and a valid request.

== Upgrade Notice ==

= 1.3.1 =
A site that already has enough memory for the Wordfence feed is no longer told to raise the limit to 512M.

= 1.3.0 =
Newer releases appear under Dashboard → Updates, and the plugins screen includes Settings and View details.

= 1.2.1 =
A backfill that runs out of memory or time now stops with a settings message instead of a blank server error.
