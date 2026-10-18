# Architectural decisions

Append-only log. Format: `## YYYY-MM-DD — Title`, then **Context** / **Decision** / **Consequences**. Owner waivers of High findings are recorded here too.

## 2026-04-28 — Call Wordfence from settings

**Context:** The initial commit (`467b56f`) is procedural PHP with no Composer stack. Vulnerability data comes from the Wordfence Intelligence v3 feed.

**Decision:** Store options in `wow_settings`. The bearer token is `wordfence_api_key` on that option, saved from the Authentication tab. The default endpoint is `https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production`.

**Consequences:** The key is not in source. A missing key returns `WP_Error` `wow_wordfence_missing_key`.

## 2026-04-28 — Generate the post from cron or from settings

**Context:** Changelog 1.1.0, in the same initial commit, adds operator control beyond the weekly cron.

**Decision:** When the plugin is enabled, `wow_weekly_cron_event` is scheduled weekly from the next Wednesday. Settings → Watch Out Wednesday can run the current week, one Wednesday, or a bulk backfill of 1 to 260 weeks. An existing post with the same title is updated.

**Consequences:** The menu is under Settings. Manual runs post to `admin-post.php` with the `wow_manual_run` nonce and `manage_options`.

## 2026-04-28 — Keep email-handler.php as a legacy include

**Context:** `includes/email-handler.php` says the email helpers moved.

**Decision:** Keep the file so `watch-out-wednesday.php` can keep its `require_once`. `wow_send_success_email` and `wow_send_error_email` live in `includes/post-generator.php` and call `wp_mail`.

**Consequences:** The legacy file defines no functions.

## 2026-10-17 — Install by copy and lint with php -l

**Context:** `a965dae` and `ba55f1a` document how to run the plugin. `PLAN.md` leaves the test runner unset.

**Decision:** Install is a copy into `wp-content/plugins/watch-out-wednesday`. CI runs `php -l` on PHP 7.4. There is no PHPUnit suite and no npm test.

**Consequences:** Do not invent a test command.

## 2026-10-18 — Ship the free plugin as a GitHub Release zip

**Context:** The plugin is not listed on WordPress.org. No updater is in the tree. The person who configures a site supplies the Wordfence key.

**Decision:** Sites receive updates from the GitHub Release zip built on `v*` tags. The price is free. `readme.txt` stays in WordPress.org form. The API key stays a settings field. An updater is added after this scaffold.

**Consequences:** `release.yml` and `bin/build-zip.sh` produce the zip. Zips are not committed. There is no WordPress.org listing.

## 2026-10-18 — Accept the department-review recommendations

**Context:** The full review of 1.1.0 is in `.claude/reviews/2026-10-18-0153-full-plugin-review.md`. Odell accepted the recommendations and asked for the fixes before any release tag.

**Decision:** Build them in 1.2.0. Do not publish an empty week. Send one summary email for a bulk backfill. List every affected software item. Add the accuracy sentence, the non-affiliation line, and feed attribution (copyright field, source links, Defiant line). Keep “Run current week now” dated on the coming Wednesday and say when WordPress has scheduled it. Make that button primary. Report vulnerabilities to support@duppinstech.com. Do not tag a release in this change.

**Consequences:** Version 1.2.0. A `v1.2.0` tag is a later step, after the staging smoke test.
