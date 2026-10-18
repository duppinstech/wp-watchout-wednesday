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
3. CI runs `php -l` on PHP files. There is no test suite yet.
4. Do not push tags or publish a zip from the pull request.
