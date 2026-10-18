---
paths:
  - "watch-out-wednesday.php"
  - "includes/**/*.php"
  - "js/**/*.js"
  - "css/**/*.css"
---

# Testing standard

## Minimum (every change)
1. `php -l` passes on every changed PHP file (the PostToolUse hook does this).
2. Manual smoke test on a WordPress site with `WP_DEBUG` on:
   - The plugin activates without notices or errors.
   - Settings → Watch Out Wednesday: Content, Manual Run, and Authentication tabs load, and both settings forms save.
   - With a Wordfence key saved on the Authentication tab, a manual run creates or updates that Wednesday's post.
   - The generated post is the front-end output. Bulk backfill updates a post that already has the same title.
3. If you touched the updater, test the Check Again flow against a draft release.

## Automated tests
`composer install`, then `vendor/bin/phpunit`. See `tests/unit/`.
- Location, once a runner is chosen: `tests/unit/` (Brain Monkey) and `tests/integration/` (wp-phpunit). Naming: `Test_<Class_Name>.php`, methods `test_<behavior>_<condition>`.
- Every bug fix ships with a regression test that fails before the fix. Unit tests never hit the network.
- CI runs `php -l` on PHP 7.4 (`.github/workflows/ci.yml`).

## Priority test targets
- `wow_handle_manual_run_request` in `watch-out-wednesday.php` — capability, nonce, and the current-week, single-week, and bulk admin-post actions.
- `wow_fetch_vulnerabilities` in `includes/post-generator.php` — Wordfence request, bearer key from settings, and HTTP error handling.
- `wow_sanitize_settings` in `includes/admin-settings.php` — sanitization of the API key, endpoint, and the other `wow_settings` fields.
- `wow_generate_post` in `includes/post-generator.php` — creates or updates the Wednesday post and sends mail.
