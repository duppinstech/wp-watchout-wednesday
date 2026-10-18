# Contributing

## Branch naming

Branch from `main`. Do not commit on `main`.

Name branches `type/short-description`, for example `fix/cron-skip` or `docs/local-install`.

Types in use: `feat`, `fix`, `docs`, `chore`, `ci`.

## Commit style

Use a conventional commit subject:

```
type: short explanation
```

Examples: `fix: skip the cron run when the plugin is disabled`, `docs: describe local install`.

## Pull requests

1. Open a pull request into `main`.
2. Fill in the pull request template.
3. CI runs `php -l` and `vendor/bin/phpunit` on PHP 7.4.
4. Do not push tags or publish a zip from the pull request. A tag that matches `v*` (for example `v1.2.0`) runs the release workflow and publishes the GitHub Release zip. Push that tag from a release, not from a pull request.
