# Project context

_Shared, committed memory. Keep it factual and current. If the repo is public, so is this file._

- **Product:** Watch Out Wednesday, a WordPress plugin. Owner: Odell Duppins / Duppins Technology.
- **Repo:** https://github.com/oduppinsjr/wp-watchout-wednesday (public). GitHub's canonical URL is https://github.com/duppinstech/wp-watchout-wednesday.
- **Current release:** 1.1.0, documented 2026-04-28 in `CHANGELOG.md`. No git tag yet.
- **Distribution:** GitHub Release zip on `v*` tags (`release.yml`, `bin/build-zip.sh`). Not published on WordPress.org. `readme.txt` is kept in WordPress.org form. No updater in this tree yet.
- **Key dependencies:** None. No `composer.json` and no `package.json`.
- **Integrations:** Wordfence Intelligence v3. Default endpoint `https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production`. The bearer key is `wow_settings['wordfence_api_key']`, entered on the Authentication tab by the person configuring the site. It is not hardcoded. Mail goes through `wp_mail`. Posts also write Yoast meta `_yoast_wpseo_metadesc` and `_yoast_wpseo_focuskw`.
- **CI:** `php -l` on PHP 7.4 (`.github/workflows/ci.yml`). No test suite. Releases are built by `release.yml` on `v*` tags.

## Review watchlist
Known items each department re-checks on every review until fixed. Remove lines when they're resolved.
- **Security:** none yet
- **Legal:** none yet
- **Finance:** none yet
- **Product:** none yet
- **Marketing:** none yet
- **Support:** none yet
- **Ops:** No updater in the tree yet. Sites get the GitHub Release zip; the updater is added after this scaffold. No `uninstall.php`. Deactivation only clears `wow_weekly_cron_event`. Options `wow_settings` and `wow_last_run` are left in place.
- **UX:** none yet
