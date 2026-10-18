# AGENTS.md

## Commands

Install. No dependency manifest and no install script. Copy this directory into a WordPress site:

`wp-content/plugins/watch-out-wednesday`

Requires WordPress 6.0+ and PHP 7.4+ (`readme.txt`).

Lint. No linter and no formatter are configured. Do not add one unless asked.

Syntax check, same command as CI:

`find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l`

Test. No test command. Do not invent `phpunit` or `npm test`.

Build. No build command. Do not create or commit a zip.

## Conventions

- `watch-out-wednesday.php` and `includes/post-generator.php` are tab-indented. `includes/admin-settings.php`, `js/admin-script.js`, and `css/admin-style.css` use 4-space indent. Do not reformat a file to make those match.
- Line endings in git are LF (`.gitattributes`). `*.ps1`, `*.bat`, and `*.cmd` are CRLF.
- When the version changes, update the plugin header, the Stable tag in `readme.txt`, and `CHANGELOG.md` together.
- Branch from `main` using `type/short-description`. Commit subjects are conventional: `type: explanation`.

## Do not touch

- Application behavior, unless the task asks for it.
- `.env`, `.env.*`, `CLAUDE.local.md`, and `.claude/settings.local.json`.
- `vendor/`, `node_modules/`, `dist/`, `build/`, `*.zip`, and `*.log` (do not commit them).
- `SECURITY.md` and `LICENSE` (leave existing text in place).
- Push, tag, release, and deploy, unless the task asks for that explicitly.
