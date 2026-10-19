# Plan

## Current

- Version 1.3.1 is the current GitHub Release, tag `v1.3.1`. A 158 MB feed is read on a 768M memory limit. Updates install from GitHub Releases.
- Local install is a copy into `wp-content/plugins/watch-out-wednesday`. Sites install the GitHub Release zip. Zips stay untracked.
- CI runs `php -l` and `vendor/bin/phpunit` on PHP 7.4.
- `uninstall.php` deletes plugin options and the weekly cron. Generated posts stay.
- The GitHub updater is `includes/class-wow-github-updater.php` (standard v1.1.0). Disable it with `WOW_DISABLE_GITHUB_UPDATER`.

## Open

None.

## Decisions needed (Odell)

None. The 2026-10-18 review decisions were accepted and built in 1.2.0.

## Done

- [x] Release workflow. Decision 2026-10-18: ship the free plugin as a GitHub Release zip. An updater comes later.
- [x] P0 and the accepted recommendations from the 2026-10-18 department review. Shipped in 1.2.0. Notes: `.claude/reviews/2026-10-18-0153-full-plugin-review.md`.
- [x] Release-zip check from the 2026-10-18 smoke test. The archive root is `watch-out-wednesday/watch-out-wednesday.php`. `.github`, `.claude`, `AGENTS.md`, and `bin/` are absent. The “before the first `v1.2.0` tag” gate is closed: `v1.2.0` through `v1.3.1` are already published. The WordPress admin steps in that checklist were not run from this repository. The local compose project named in `CLAUDE.local.md` is not on this machine, and the repo has no Wordfence key.
- [x] Wordfence attribution. Posts include the feed copyright field, source links, the Defiant line, and the accuracy sentence accepted on 2026-10-18. A lawyer has not reviewed the Intelligence terms page. That reading is outside this repository and is not unfinished plugin work.

## Declined

- Raising “Requires at least” to 6.2. `WP_Query` `'title'` has existed since WordPress 4.4. (2026-10-18)
- Caching the full Wordfence body in a transient. (2026-10-18)
- Tagging `v1.1.0` or `v1.2.0` in the same session as the fixes. A release waits for the staging smoke test. (2026-10-18)
