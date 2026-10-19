# Project context

_Shared, committed memory. Keep it factual and current. If the repo is public, so is this file._

- **Product:** Watch Out Wednesday, a WordPress plugin. Owner: Odell Duppins / Duppins Technology.
- **Repo:** https://github.com/oduppinsjr/wp-watchout-wednesday (public). GitHub's canonical URL is https://github.com/duppinstech/wp-watchout-wednesday.
- **Current release:** 1.3.0, tag `v1.3.0`, documented 2026-10-18 in `CHANGELOG.md`. Sites install the GitHub Release zip. Newer releases appear under Dashboard → Updates.
- **Distribution:** GitHub Release zip on `v*` tags (`release.yml`, `bin/build-zip.sh`). Not published on WordPress.org. `readme.txt` is kept in WordPress.org form. The updater is `includes/class-wow-github-updater.php` (standard v1.1.0).
- **Key dependencies:** None at runtime. `composer.json` is development-only (PHPUnit 9, Brain Monkey). No `package.json`.
- **Integrations:** Wordfence Intelligence v3. Default endpoint `https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production`. The bearer key is `wow_settings['wordfence_api_key']`, entered on the Authentication tab by the person configuring the site. It is not hardcoded and it is not printed back into the settings page. Mail goes through `wp_mail`. Posts also write Yoast meta `_yoast_wpseo_metadesc` and `_yoast_wpseo_focuskw`.
- **CI:** `php -l` and `vendor/bin/phpunit` on PHP 7.4 (`.github/workflows/ci.yml`). Releases are built by `release.yml` on `v*` tags.
- **Security contact:** support@duppinstech.com (`SECURITY.md`).

## Review watchlist
Known items each department re-checks on every review until fixed. Remove lines when they're resolved. Full notes: `.claude/reviews/2026-10-18-0153-full-plugin-review.md`.
- **Security:** none open from the 2026-10-18 review. Re-check the endpoint allow-list and the private-post lookup after the next change to those functions.
- **Legal:** Posts include the feed copyright field, source links, a Defiant line, and the accepted accuracy sentence. Counsel can still review the Intelligence terms. The terms page was not readable on 2026-10-18.
- **Finance:** none open. Price stays free. Do not cache the full feed body in a transient.
- **Product:** none open. Empty weeks are drafts. Bulk mail is one summary.
- **Marketing:** none open. Plugin URI is the canonical GitHub URL. The download is the `v1.3.0` GitHub Release zip.
- **Support:** none open. Vulnerability reports go to support@duppinstech.com.
- **Ops:** The GitHub updater installs tag `v1.3.0` and later from Dashboard → Updates. `uninstall.php` deletes `wow_settings`, `wow_last_run`, and `wow_github_release`, and clears `wow_weekly_cron_event`. Generated posts stay.
- **UX:** none open from the 2026-10-18 review.
