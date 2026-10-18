# Appendix: 2026-10-18 full plugin review

Drafts for the PLAN items. Attribution and the accuracy sentence stay drafts until Odell and counsel accept them.

## Changelog

```markdown
## [1.1.0] - 2026-04-28

### Added
- Manual run, missed-Wednesday rerun, and bulk backfill on the settings screen.
- GitHub Release zip. A `v*` tag builds `watch-out-wednesday.zip`. There is no WordPress.org listing and no automatic updater.

### Changed
- **The settings screen is under Settings → Watch Out Wednesday.**

### Security
- Settings saves and manual runs now require a logged-in administrator and a valid request.
```

## Plugin header

```text
Plugin URI: https://github.com/duppinstech/wp-watchout-wednesday
Description: Publishes a weekly Watch Out Wednesday post from the Wordfence Intelligence vulnerability feed. You supply the API key.
Requires at least: 6.0
Requires PHP: 7.4
Copyright (C) 2026 Duppins Technology
```

## README release section

Install the GitHub Release zip for Watch Out Wednesday into `wp-content/plugins/watch-out-wednesday`. Releases are attached to `v*` tags. There is no automatic updater. Do not announce a download until the `v1.1.0` release exists.

You need your own Wordfence Intelligence API key. Open Settings → Watch Out Wednesday → Authentication, paste the key, and save. Watch Out Wednesday does not include a key.

Deactivating the plugin stops the weekly cron. It does not delete posts, settings, or the stored API key. There is no `uninstall.php` yet, so deleting the plugin leaves those in place too.

## readme.txt external services

```text
== External services ==

This plugin requests the Wordfence Intelligence v3 feed at the endpoint on the Authentication tab (default: https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production). Each request sends that host the API key you entered. The connecting server’s IP address is visible to Wordfence as part of a normal HTTPS request. No visitor personal data is sent. Wordfence Intelligence terms: https://www.wordfence.com/wordfence-intelligence-terms-and-conditions/ Wordfence privacy policy: https://www.wordfence.com/privacy-policy/

The notification email is stored in the WordPress option wow_settings and is used only to send the generated post or an error through this site’s mail setup. It remains until you change the setting or delete the option. Deactivating the plugin does not delete it.
```

Use the same text for `wp_add_privacy_policy_content()`.

## Drafts that wait for Odell

Non-affiliation, for `readme.txt` and `README.md`:

> Wordfence and Wordfence Intelligence are trademarks of Defiant, Inc. Watch Out Wednesday is not affiliated with, endorsed by, or sponsored by Defiant, Inc. or Wordfence.

Accuracy sentence, for the generated post, only after counsel:

> Vulnerability details come from the Wordfence Intelligence vulnerability database. They may be incomplete, outdated, or later corrected. This post does not confirm that this site is affected.

## Settings help

- Enable weekly posts: “Turn on to publish the Watch Out Wednesday post every Wednesday.”
- Intro: “Shown at the top of every generated post. Basic HTML is kept.”
- Outro: “Shown at the bottom of every generated post. Basic HTML is kept.”
- Email: “Sent when a run publishes or updates a post. Leave blank to skip that message. If a run fails, the error is still emailed to the site admin address.”
- Featured image: “Choose an image from the Media Library. A URL that is not in the library is ignored.”
- Category value 0: “Site default category”. Help: “Site default category uses the category WordPress assigns to new posts.”
- Manual Run: “The weekly run is the next Wednesday at 00:00 in the site timezone, then every 7 days. A post dated a Wednesday includes vulnerabilities published or updated from the previous Wednesday through that Tuesday, using the current Wordfence feed. Each run publishes the post. The date you enter must be a Wednesday.”
- Bulk: “Walks backward one Wednesday at a time from the ending date. Each week is filled from the current Wordfence feed, not from a snapshot taken that week. Start with a small count. A large backfill runs in one request and can stop when PHP hits its time limit.”
- API key: “From your Wordfence account. Duppins Technology is not billed. The published default limit is one request per 30 minutes.”

## FAQ

**Where does the API key go?**
Open Settings → Watch Out Wednesday → Authentication, paste a Wordfence Intelligence v3 key, and save. The plugin does not ship with a key. Manual Run works while weekly posts are off. The weekly cron runs only when Enable is on and saved.

**I turned Enable off and it came back on.**
This build does not store the off position. Deactivate the plugin to stop the cron. After the update, open the Content tab, turn it off, and save once.

**The title date is the day before the Wednesday I picked.**
Sites west of UTC format UTC midnight in the local timezone, so the title can show the previous day. The post for a Wednesday covers vulnerabilities from the previous Wednesday through that Tuesday, from the current feed. A backfill does not restore what the feed looked like on that past day.

## Smoke test

Run this on a staging site from the zip `bash bin/build-zip.sh` produces, not from a zip of the git clone. Use PHP 7.4. Repeat the post checks on WordPress 6.0 and on 6.2 or newer.

1. The archive root is `watch-out-wednesday/watch-out-wednesday.php`. `.github`, `.claude`, `AGENTS.md`, and `bin/` are absent.
2. Activate. wp-admin loads. Open Settings → Watch Out Wednesday and switch Content, Manual Run, and Authentication.
3. Publish an unrelated post. Run the current week. That post is unchanged. The new post title is the site-local Wednesday, not the day before.
4. Run the same week again. The same Watch Out Wednesday post is updated. A second post is not created.
5. If a private post already has that exact title, it stays private.
6. Save an API key and endpoint, then save Content. The key is still stored. Save Authentication. The intro is still stored.
7. Turn the schedule on, save, and confirm `wow_weekly_cron_event` exists. Turn it off, save, and confirm `enabled` is `0` and the event is gone.
8. Use a bad API key. The settings screen shows an error. The front end stays up.
9. With a site timezone west of UTC, the title date and the post’s displayed date are the same Wednesday.
10. Deactivate and confirm the cron hook is gone and `wow_settings` still holds the key. After `uninstall.php` exists, delete the plugin and confirm `wow_settings` and `wow_last_run` are gone and the generated posts remain.
