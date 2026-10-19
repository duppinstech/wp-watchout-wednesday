# AGENTS.md

## Plugin facts

- **Name:** Watch Out Wednesday
- **Slug:** `watch-out-wednesday`
- **Main file:** `watch-out-wednesday.php`
- **Prefix:** `wow_`
- **Text domain:** `watch-out-wednesday`
- **Floor:** WordPress 6.0, PHP 7.4 (`readme.txt`). Tested up to 6.9.
- **Version:** 1.2.1
- **License:** GPL-2.0-or-later
- **Repo:** public. `origin` is https://github.com/oduppinsjr/wp-watchout-wednesday. GitHub's canonical URL is https://github.com/duppinstech/wp-watchout-wednesday.
- **Distribution:** GitHub Release zip on `v*` tags. Not published on WordPress.org. `readme.txt` is kept in WordPress.org form.
- **Updater:** none in this tree. Sites install the GitHub Release zip. An updater comes after this scaffold.
- **Pro add-on:** none
- **Price:** free
- **Departments skipped:** none
- **Security-sensitive files:** `watch-out-wednesday.php` (`admin_post_wow_manual_run`), `includes/admin-settings.php` (settings and the API key field), `includes/post-generator.php` (Wordfence request)
- **Integrations:** Wordfence Intelligence v3. The key is `wow_settings['wordfence_api_key']` from the Authentication tab. It is not hardcoded.

## What this is

Watch Out Wednesday is a WordPress plugin that pulls vulnerability intelligence from Wordfence and publishes a weekly "Watch Out Wednesday" post.

It exists so a site can keep that recap on a schedule. Operators can also rerun one missed Wednesday or backfill older weeks from the settings screen.

This repository is the plugin. It has no runtime Composer dependencies. Development tests use Composer. It does not produce a compiled build.

## Architecture

- `watch-out-wednesday.php` — Bootstrap. Schedules the weekly cron when the plugin is enabled, handles `admin_post_wow_manual_run`, and prints admin notices.
- `includes/admin-settings.php` — Settings page at Settings → Watch Out Wednesday, with Content, Manual Run, and Authentication tabs. Registers `wow_settings`.
- `includes/post-generator.php` — Fetches Wordfence, writes or updates the Wednesday post, sets the featured image and Yoast meta, and sends mail with `wp_mail`.
- `includes/email-handler.php` — Legacy include. No functions. Mail lives in `post-generator.php`.
- `uninstall.php` — Deletes `wow_settings`, `wow_last_run`, the admin-notice transient, and `wow_weekly_cron_event`. Generated posts stay.
- `js/admin-script.js` — jQuery on the settings screen. Tab switching and the featured-image media frame.
- `css/admin-style.css` — Admin styles for the settings screen.

## Commands

Install. No dependency manifest and no install script. Copy this directory into a WordPress site:

`wp-content/plugins/watch-out-wednesday`

Requires WordPress 6.0+ and PHP 7.4+ (`readme.txt`).

Lint. No linter and no formatter are configured. Do not add one unless asked.

Syntax check, same command as CI:

`find . -name '*.php' -not -path './.git/*' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l`

CI runs that check, then `vendor/bin/phpunit`, on PHP 7.4 (`.github/workflows/ci.yml`).

Test. `composer install`, then `vendor/bin/phpunit`. There is no `npm test`.

Build. `bash bin/build-zip.sh <output.zip>` writes the release zip. `.github/workflows/release.yml` runs it on `v*` tags. Do not commit a zip.

## Conventions

- `watch-out-wednesday.php` and `includes/post-generator.php` are tab-indented. `includes/admin-settings.php`, `js/admin-script.js`, and `css/admin-style.css` use 4-space indent. Do not reformat a file to make those match.
- Line endings in git are LF (`.gitattributes`). `*.ps1`, `*.bat`, and `*.cmd` are CRLF.
- When the version changes, update the plugin header, the Stable tag in `readme.txt`, and `CHANGELOG.md` together.
- Branch from `main` using `type/short-description`. Commit subjects are conventional: `type: explanation`.

## Hard rules

- Application behavior, unless the task asks for it.
- `.env`, `.env.*`, `CLAUDE.local.md`, and `.claude/settings.local.json`.
- `vendor/`, `node_modules/`, `dist/`, `build/`, `*.zip`, and `*.log` (do not commit them).
- `SECURITY.md` and `LICENSE` (leave existing text in place).
- Push, tag, release, and deploy, unless the task asks for that explicitly.
- Do not hard-code the Wordfence API key. The person configuring the site enters it on the Authentication tab. It is stored in `wow_settings['wordfence_api_key']`.

## Agents

| Agent | Role |
| --- | --- |
| `security-reviewer` | Read-only audit of handlers, the updater, and superglobals. |
| `legal-reviewer` | Licensing, third-party terms, and privacy. |
| `finance-reviewer` | Running costs, free versus paid packaging, and pricing. |
| `product-reviewer` | `INTENT.md`, `PLAN.md`, scope, and the operator's experience. |
| `marketing-reviewer` | Customer-facing copy and release highlights. |
| `support-docs-reviewer` | `README.md`, `readme.txt`, `CHANGELOG.md`, admin messages, and i18n. |
| `release-ops-reviewer` | Release readiness: compatibility, zip contents, uninstall, and rollback. |
| `ux-reviewer` | Admin and front-end accessibility and layout. |
| `test-writer` | PHPUnit tests in `tests/unit/`. |
| `research` | WordPress, library, and third-party API questions. No code edits. |

Slash commands in `.claude/commands/`: `/review`, `/department-review`, `/deploy`, `/scaffold`.
