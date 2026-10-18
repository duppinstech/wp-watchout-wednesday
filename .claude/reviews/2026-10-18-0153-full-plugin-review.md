# Department review: full plugin evaluation
**When:** 2026-10-18 01:53 EDT · **Scope:** whole plugin at `chore/scaffold` (1.1.0, no git tag), not only the scaffold diff · **Chair:** main session · **Owner:** Odell Duppins

## Attendance and verdicts

| Department | Verdict | Findings | Summary |
|---|---|---|---|
| Security | fix-before-merge | 1 High, 3 Medium, 4 Low | Enable cannot be turned off. The saved endpoint is requested with the API key. Lookup can republish a private post that already has the Wednesday title. |
| Legal | fix-before-merge | 1 High, 2 Medium, 2 Low | Feed text is republished without the attribution the v3 docs describe. Attorney review before the next release that publishes posts. |
| Finance | ok | 2 Medium, 1 Low | No cost to Duppins Technology. The operator’s key and host pay. Price stays free. |
| Product | fix-before-merge | 2 High, 4 Medium, 4 Low | Wednesday titles shift a day west of UTC. Enable does not stick. `PLAN.md` still denied the release workflow. |
| Marketing | fix-before-merge | 4 High, 4 Medium, 4 Low | Do not announce 1.1.0. The public README denies the release zip, and the plugin link is the wrong GitHub URL. |
| Support | fix-before-merge | 3 High, 8 Medium, 5 Low | Docs disagree with the screen and with `release.yml`. Chair overrode this department’s block; see Decisions. |
| Operations | fix-before-merge | 2 High, 3 Medium, 5 Low | No `uninstall.php`. Plugin header omits the WordPress and PHP floors. Chair overrode this department’s block; see Decisions. |
| UX | fix-before-merge | 5 High, 8 Medium, 5 Low | Settings fields are unlabeled, the enable switch has no accessible name or focus ring, and post headings skip a level. |
| Tests | ok | gaps only | No suite. Runner choice stays open. Highest-value tests are the fetch, the settings merge, and create-or-update. |
| Research | fix-before-merge | 1 P1 needs Odell, 3 auto | The production v3 URL and bearer header match current Wordfence docs. v2 ended 9 March 2026. `WP_Query` `'title'` dates from 4.4, not 6.2. |

**Chair verdict:** fix-before-merge. Do not tag `v1.1.0` and do not deploy this tree as a release. There is no `block` after the false positive below is removed. Attorney review does not block a code merge unless Odell says so.

## Highlights

- Do not ship 1.1.0 as it stands. Turning Enable off does not save, so a site cannot stop the weekly publish from Settings. West of UTC, the post title is the day before Wednesday, and a later correction will not find the old post.
- Security, Support, and Operations reported that WordPress 6.0 and 6.1 ignore `WP_Query` `'title'` and that a run overwrites the site’s newest post. That claim is declined. The parameter has existed since WordPress 4.4. The lookup issue that remains is narrower and is in PLAN.
- The Wordfence production URL, bearer header, and lack of query parameters match the current v3 docs. Nothing in cron, HTTP, mail, media, or the settings API used here is deprecated through WordPress 6.9. PHP 7.4 syntax is clean.
- Duppins Technology is not on the request path. The price stays free. A bulk run still sends one email per week and downloads the full feed once.
- Generated posts copy feed fields without the attribution Wordfence’s v3 help page describes. That is with Odell, as attorney review, before a public release of those posts.

## Findings and recommendations by department

### Security

- Enable stays on after it is unchecked, because an omitted checkbox never updates `wow_settings['enabled']`. → Hidden `0` on the Content form only. **Outcome:** Approved → PLAN (P0)
- Saved endpoint is requested with the bearer token, and `wp_remote_get` will follow that URL. → Require https, reject non-global hosts, use `wp_safe_remote_get` with redirects off and a size cap. **Outcome:** Approved → PLAN (P0)
- Security issue in existing-post lookup. The searched statuses include `private`, and the update sets `publish`. The report also said WordPress 6.0 ignores `'title'` and overwrites an unrelated post. → Keep the private-post fix. **Outcome:** Approved → PLAN (P0) for the private-post part. The unrelated-post part is **Declined** (false positive; `'title'` since WordPress 4.4).
- API key is printed into the password field. → Stop echoing it; keep the stored value when the field is empty. **Outcome:** Approved → PLAN (P1)
- No `uninstall.php`, so the key remains after delete. → Delete plugin options and the notice transient. Do not delete posts. **Outcome:** Approved → PLAN (P1). Security marked this “needs Odell” because the key is removed; uninstall coverage is routine, and generated posts stay.
- `wow_settings` is autoloaded. → Turn autoload off. **Outcome:** Approved → PLAN (P2)
- Featured-image preview is built from an HTML string. → Set the image URL with jQuery `.attr()`. **Outcome:** Approved → PLAN (P2)
- Feed dates passed to `DateTime` can throw, and the response size is uncapped. → Skip bad records and cap the body. Merged with Research. **Outcome:** Approved → PLAN (P2) for parsing; size cap is part of the P0 request fix.
- `.distignore` names `.env` exactly. → Ignore `.env*` and fail the release job if a secret file is packed. **Outcome:** Approved → PLAN (P1)

### Legal

- Generated posts and the notification email copy Intelligence fields with no Defiant designation, license text, or per-record licensor notice. The terms page was behind a bot wall. → Attorney review before the next publishing release. **Outcome:** Forwarded → Odell (P0)
- Posts state a recommended action with no accuracy limit. → Draft sentence in the appendix, after counsel. **Outcome:** Forwarded → Odell (P1)
- Wordfence is named with no non-affiliation line. → Draft in the appendix. **Outcome:** Forwarded → Odell (P1)
- No External services section and no privacy-policy content for the API key, the server IP on that request, or the stored notification email. → Appendix wording. **Outcome:** Approved → PLAN (P1)
- No copyright line on the plugin header. → `Copyright (C) 2026 Duppins Technology`. Do not edit `LICENSE`. **Outcome:** Approved → PLAN (P1)
- Plugin URI points at `oduppinsjr/watch-out-wednesday`, which is not this repository. → Canonical `duppinstech/wp-watchout-wednesday`. **Outcome:** Approved → PLAN (P1). Marketing had marked the public URL change as needing Odell; it is a factual correction, so it is approved.

### Finance

- Cost impact: none for Duppins Technology. The operator’s Wordfence key is the credential. Front-end page views do not call the API. A backfill downloads the feed once.
- Pricing recommendation: no change.
- Bulk backfill sends one success email per week, up to 260. → One summary email. **Outcome:** Forwarded → Odell (P1). Finance marked it routine; Product marked the mail-behavior change as Odell’s.
- Every cron and manual submit downloads the full feed with no cache. → Do not put the body in a transient. Raise the timeout and document memory. A second request inside 30 minutes can still receive HTTP 429, which the plugin already explains. **Outcome:** Approved → PLAN (P1) for timeout and docs. Full-body cache **Declined**.
- The Authentication tab does not say who is rate-limited. → Short help text. **Outcome:** Approved → PLAN (P2)
- Business context still lacks the feed’s published price (free), the published default limit (1 request / 30 minutes), a measured feed size, and mail cost. Those figures in this review are from Wordfence’s 2 February 2026 notice, via secondary copies where the help page was blocked. Hosting and support time remain TBD.

### Product

- Wednesday title and post date use UTC midnight, then the site timezone, so US sites title the post Tuesday. → Site timezone, and match the old title on rerun. **Outcome:** Approved → PLAN (P0)
- Enable does not persist off. → Same fix as Security. **Outcome:** Already tracked (P0)
- A late weekly cron calls the generator with no date, so it can target the next Wednesday. → Pass the Wednesday the event was for. **Outcome:** Approved → PLAN (P1)
- Backfill of up to 260 weeks is one request, and the notice does not name failures. → Chunk it and name the weeks. **Outcome:** Approved → PLAN (P1)
- One slow Wordfence response drops the week, and cron errors are easy to miss on the settings screen. → Timeout, no full-body transient, surface the error. **Outcome:** Already tracked (P1)
- README and `readme.txt` still describe a hand-made zip. → Document the Release zip. **Outcome:** Already tracked (P1)
- No git tag yet. → Do not tag until the P0s are fixed. **Outcome:** Forwarded → Odell
- “Enable Script” and no visible next run. → “Enable weekly posts” and show the next event. **Outcome:** Approved → PLAN (P2)
- Featured image cannot be cleared. → Remove control and a notice. **Outcome:** Already tracked (P2)
- Changelog heading does not match `release.yml`. → `## [1.1.0]`. **Outcome:** Already tracked (P1)
- A week with no matches still publishes. → Skip or draft. **Outcome:** Forwarded → Odell (P2)
- One email per backfill week. → **Outcome:** Already tracked under Decisions
- Only `software[0]` is written. → List each item later. **Outcome:** Forwarded → Odell (P2)
- `PLAN.md` still said the release workflow was out of scope. → Marked done. **Outcome:** Approved → PLAN

### Marketing

- Story: Watch Out Wednesday publishes a weekly vulnerability recap from the Wordfence Intelligence feed, and an operator can rerun one missed Wednesday or backfill older weeks. Free, no Pro, no upgrade pitch.
- Public README says there is no release workflow. → Replace that section. Do not announce a download until `v1.1.0` exists. **Outcome:** Already tracked (P1)
- Plugin URI is the wrong repository. → **Outcome:** Already tracked (P1)
- Install docs never say the operator must supply a Wordfence key. → FAQ and install step in the appendix. **Outcome:** Already tracked (P1)
- Changelog heading will not be extracted. → **Outcome:** Already tracked (P1)
- Description says the plugin generates and emails “plugin vulnerabilities.” → Recap wording in the appendix. **Outcome:** Approved → PLAN (P2)
- Yoast meta description talks about core updates. → Recap sentence. **Outcome:** Already tracked (P2)
- `readme.txt` tag `security`, and the changelog line “Hardened plugin security”, sound like the plugin protects the site. → Drop `security` before any directory submission. Soften the changelog bullet now. **Outcome:** Changelog bullet Approved → PLAN (P1). Tag change Forwarded → Odell only if a WordPress.org listing is started; there is no listing now, so the tag edit waits with that decision. Recorded under the changelog item for the bullet, and the tag stays until a listing is in scope. **Outcome for the tag itself:** Declined for this release (no WordPress.org submission).
- “Enable Script”, intro/outro labels, and Manual Run copy. → “Enable weekly posts”, “Intro”, “Outro”, and the Manual Run line in the appendix. **Outcome:** Approved → PLAN (P2)
- GitHub landing page does not say Duppins Technology. → One-line opening in the appendix. **Outcome:** Approved → PLAN (P2), folded into the README item.
- No screenshots in the repo. **Outcome:** Declined for this release (distribution stays GitHub-only).

### Support

- Reported a block because `'title'` would miss on WordPress 6.0 and overwrite another post. → **Declined** as a false positive. See Decisions.
- Enable does not save off. → **Outcome:** Already tracked (P0)
- “Run current week now” can leave a scheduled future post while the notice says the run completed. The title can also show the previous day. → Date fix is Already tracked (P0). The publish-now versus scheduled choice is **Forwarded → Odell**.
- README and PLAN denied the release workflow. → **Outcome:** Already tracked (P1)
- Changelog heading and an unbolded menu move. → Draft in the appendix. **Outcome:** Already tracked (P1)
- Install steps omit the key, the zip, and what Enable controls. → Appendix. **Outcome:** Already tracked (P1)
- Manual Run does not explain the Wednesday window, the single current feed, or empty weeks. → Help text in the appendix. **Outcome:** Approved → PLAN (P1)
- Several notices are English-only and do not say what to do next. → Translate with the text domain and name the next step. **Outcome:** Approved → PLAN (P1)
- Category `0` is labeled Uncategorized but means the site default. → **Outcome:** Already tracked (P1)
- `SECURITY.md` says to email the maintainer in the plugin header, and the header has no email. → **Forwarded → Odell**
- Deactivation and delete behavior are undocumented. → README note. **Outcome:** Already tracked with uninstall (P1)
- Intro, outro, email, featured image, Yoast, Plugin URI, and “Manual Run” naming. → **Outcome:** Approved → PLAN (P2) or already tracked
- Upgrade note: no tagged release exists. Existing sites already moved to Settings → Watch Out Wednesday. After the Enable fix, an operator who thinks the job is off must open Content and save once. Deactivate stops the cron until then. Deleting the plugin does not remove posts or settings until `uninstall.php` exists.

### Operations

- Reported a block on the same WordPress 6.0 title claim. → **Declined.** See Decisions.
- Enable cannot stop the cron. → **Outcome:** Already tracked (P0)
- Header has no Requires at least or Requires PHP, so an older site can activate. `wp_date()` runs only when a post is generated. `php -l` passed on the machine that ran the review (PHP 8.2). CI remains the PHP 7.4 syntax gate. No enums, `readonly`, `match`, constructor promotion, `?->`, union types, or `str_contains`. → Add the two header lines. **Outcome:** Approved → PLAN (P1)
- No `uninstall.php`. Deactivation clears only the next cron timestamp, not the hook via `wp_clear_scheduled_hook()`. → Uninstall item. **Outcome:** Already tracked (P1)
- Unguarded `DateTime` and a 260-week request can fatal after a partial publish. → Parse guard and chunked backfill. **Outcome:** Already tracked (P2 and P1)
- Cron timestamp and both post date columns ignore the site offset. → **Outcome:** Already tracked (P0)
- Release notes extractor looks for `## [1.1.0]`. → **Outcome:** Already tracked (P1)
- README tells operators to zip the clone, which would pack `.github` and `.claude`. The release workflow’s zip does not. → **Outcome:** Already tracked (P1)
- `bin/build-zip.sh` packs a worktree. A dirty local build can include paths `.distignore` does not name. → **Outcome:** Already tracked (P1)
- No updater, by decision. A `v*` tag does not update live sites. Rollback is a folder replace; options are a flat array. → **Outcome:** Already tracked (decision 2026-10-18). Updater stays later and needs Security when it is added.

### UX

- Enable never saves off. → **Outcome:** Already tracked (P0)
- The switch has no accessible name. The input is removed from the hit area and has no focus style. Off-track contrast is about 1.4:1. Intro, outro, category, email, key, and endpoint have no `for`/`id`, and both tables are `role="presentation"`. → Label, focus, and contrast fixes that keep the current layout. **Outcome:** Approved → PLAN (P1)
- Tabs do not expose which panel is selected. Inactive panels are `display:none` in HTML, so a script failure hides the API key and Manual Run. → ARIA on the existing tabs, and hide inactive panels from script. **Outcome:** Approved → PLAN (P1)
- A bad notification email is stored as empty with no `settings_errors()`. → Keep the previous value and show the error. **Outcome:** Already tracked (P1)
- Vulnerability headings are `h3` with only the software name, so the outline skips from the title and repeats. Intro and outro are not wrapped. The preview image has no `alt`. → `h2`, title in the heading, `wpautop()`, alt text. **Outcome:** Approved → PLAN (P1) for headings; alt is part of the featured-image P2 item.
- Bulk backfill uses the same button weight as one week and has no confirm. → Confirm, and primary versus secondary is Odell’s. **Outcome:** Confirm Approved → PLAN (P2). Button weight **Forwarded → Odell**
- Category `0` label. → **Outcome:** Already tracked (P1)
- A non-Wednesday date is still published as Watch Out Wednesday. → Help text that the date must be a Wednesday, and reject other weekdays. **Outcome:** Approved → PLAN (P1), with the Manual Run help.
- Switch target, reduced motion, field width, new-tab warning, and the “Enable Script” words. → **Outcome:** Approved → PLAN (P2). The label is approved as help text, not a redesign.

### Tests

- A PHPUnit 9 and Brain Monkey harness fits after the runner decision. No files were added. PHPUnit is not installed, so nothing was run.
- Already in PLAN: choose a test runner. **Outcome:** Already tracked, and it stays **Forwarded → Odell** because adding `composer.json` is a tooling change this repo does not have yet.
- Highest-value tests, once a runner exists: missing key does not call `wp_remote_get`; 401, 429, and bad JSON; Content and Authentication saves do not wipe each other’s fields; an omitted `enabled` stores `0` (fails on current code); unauthorized manual run does not fetch; same title updates and a new title inserts; dates inside the window are included. **Outcome:** Approved → PLAN (P1) as the test list under the runner item. Do not add the suite before the runner is chosen.
- Seams: `wow_handle_manual_run_request` calls `exit`, and it reads POST with `filter_input`, which a test cannot stub. **Outcome:** Approved → PLAN (P1), on the same runner item.
- `includes/email-handler.php` has no functions. `js/admin-script.js` stays on the manual smoke list.

### Research

- Keep `https://www.wordfence.com/api/intelligence/v3/vulnerabilities/production`, `Authorization: Bearer`, and no query or page arguments. v3 is the live feed as of the 2 February 2026 notice. v2 stopped returning data after 9 March 2026. Sources: Wordfence v3 help, and that notice.
- No WordPress 6.0–6.9 deprecation in the cron, HTTP, mail, media, or settings calls this plugin uses. `str_contains` is safe after WordPress loads, via the 5.9 polyfill. Do not use `array_is_list()` on the 6.0 floor. Do not use PHP 8-only syntax.
- `WP_Query` `'title'` was added in 4.4. This plugin does not call deprecated `get_page_by_title()`.
- Raise the 30-second timeout. Do not cache the body. The `data` unwrap is not in the current docs; it is harmless if no top-level key is the string `data`. **Outcome:** Already tracked (P1)
- MITRE and record attribution. **Outcome:** Already tracked under Attorney review
- Parse feed dates as UTC. **Outcome:** Already tracked (P2)
- Avoid translating the schedule label before `init`. `FILTER_SANITIZE_SPECIAL_CHARS` is valid on PHP 7.4 and deprecated on PHP 8.1. → `sanitize_text_field()`. **Outcome:** Approved → PLAN (P2)

## Decisions

- **Approved into PLAN.md:** Enable off switch; site-local Wednesday dates including a rerun match for the old title; private posts left private; endpoint request limits; API key not echoed; Requires header lines; `uninstall.php` without deleting posts; late-cron Wednesday; chunked backfill; timeout and feed-size note without a body cache; Plugin URI and install docs; changelog heading; settings accessibility; post heading level; i18n; external-services privacy text; field help; copyright line; `.distignore` and release zip checks; test-runner choice left open with the test list attached.
- **Forwarded to Odell:** Tag `v1.1.0` now? Attorney review of Intelligence terms? Accuracy sentence on posts? Non-affiliation line? Publish an empty week? One summary email for backfill? List every affected software item? Which security mailbox? Publish immediately on “Run current week now,” or keep the post scheduled until Wednesday? Make the current-week button primary?
- **Declined:** WordPress 6.0 ignoring `WP_Query` `'title'` and overwriting an unrelated newest post. Full-feed transient cache. WordPress.org tag cleanup and screenshots, because there is no directory listing in this release.

## Gate

Support and Operations returned `block` on the declined title claim. With that claim removed, nothing blocks a merge of later fixes. `v1.1.0` does not ship until the P0 items are fixed. High findings that remain open are the private-post update and the endpoint request. They are approved work, not waivers. The legal High finding is attorney review and waits for Odell. It does not block a merge by itself.
