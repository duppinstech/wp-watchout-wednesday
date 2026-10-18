# Plan

## Current

- Latest documented version is 1.1.0 (`CHANGELOG.md` and the Stable tag in `readme.txt`).
- Install is a copy into `wp-content/plugins/watch-out-wednesday`.
- Pull requests run `php -l` on PHP files.
- There is no test suite and no release workflow. Zip packages stay untracked.

## Open

- Choose a test runner before adding tests. PHPUnit and wp-env are both unset.
- A release workflow stays out of scope until a zip build is part of how this plugin ships.
