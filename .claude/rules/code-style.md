---
paths:
  - "watch-out-wednesday.php"
  - "includes/**/*.php"
  - "js/**/*.js"
  - "css/**/*.css"
---

# Code style

## PHP
- Target **PHP 7.4**. For PHP 7.4 that means no `match`, enums, `readonly`, constructor promotion, `?->`, union types, or `str_contains` without a polyfill.
- WordPress Coding Standards: spaces inside parentheses, Yoda conditions for literals; match the file's array syntax and indentation. Don't reformat lines you didn't change.
- Prefix every global function, option, meta key, transient, hook and AJAX action with `wow_`. Text domain `watch-out-wednesday` on every translatable string.
- Docblock on every public method and every hook the plugin fires.
- No namespace, no classes, and no autoloader. There is no `composer.json`. Global `wow_*` functions live in `watch-out-wednesday.php`, `includes/admin-settings.php`, and `includes/post-generator.php`.
- The main file loads those includes and registers hooks. There is no module class and no `init()` pattern.
- No field library. The settings page uses `register_setting()` on `wow_settings` and the render functions in `includes/admin-settings.php`.
- `includes/email-handler.php` is a legacy include with no functions. Mail is sent from `includes/post-generator.php`.

## JavaScript
- Data from PHP arrives through `wp_localize_script` or `wp_add_inline_script`; never inline secrets.
- jQuery, enqueued only on `settings_page_wow-settings`, with no build step. `js/admin-script.js` switches the settings tabs and opens the WordPress media frame. It does not receive localized data.

## CSS
- Scope front-end CSS under a plugin wrapper or body class so it doesn't leak into themes or wp-admin.
- Don't edit vendored libraries (`*.min.*`, third-party bundles).

## Comments
- Explain *why*, not *what*.
