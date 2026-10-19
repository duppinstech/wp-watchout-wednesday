# Plan

## Current

- Version 1.3.0 is the current GitHub Release, tag `v1.3.0`. The plugins screen shows Settings, Sponsor, View details, and Support. Updates install from GitHub Releases.
- Local install is a copy into `wp-content/plugins/watch-out-wednesday`. Sites install the GitHub Release zip. Zips stay untracked.
- CI runs `php -l` and `vendor/bin/phpunit` on PHP 7.4.
- `uninstall.php` deletes plugin options and the weekly cron. Generated posts stay.
- The GitHub updater is `includes/class-wow-github-updater.php` (standard v1.1.0). Disable it with `WOW_DISABLE_GITHUB_UPDATER`.

## Open

- Run the smoke test in `.claude/reviews/2026-10-18-0153-full-plugin-review-appendix.md` on a staging WordPress site before the first `v1.2.0` tag. (ops, 2026-10-18)
- Counsel can still review the Wordfence Intelligence terms. The post now shows the feed’s copyright field, source links, a Defiant attribution line, and the accuracy sentence Odell accepted. (legal, 2026-10-18)

## Decisions needed (Odell)

None. The 2026-10-18 review decisions were accepted and built in 1.2.0.

## Done

- [x] Release workflow. Decision 2026-10-18: ship the free plugin as a GitHub Release zip. An updater comes later.
- [x] P0 and the accepted recommendations from the 2026-10-18 department review. Shipped in 1.2.0. Notes: `.claude/reviews/2026-10-18-0153-full-plugin-review.md`.

## Declined

- Raising “Requires at least” to 6.2. `WP_Query` `'title'` has existed since WordPress 4.4. (2026-10-18)
- Caching the full Wordfence body in a transient. (2026-10-18)
- Tagging `v1.1.0` or `v1.2.0` in the same session as the fixes. A release waits for the staging smoke test. (2026-10-18)
