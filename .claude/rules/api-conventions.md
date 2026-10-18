---
paths:
  - "watch-out-wednesday.php"
  - "includes/**/*.php"
---

# API conventions (REST, AJAX, admin-post, hooks)

## REST
- Register in `rest_api_init` under a `watch-out-wednesday/v1`-style namespace. Every route has a real `permission_callback`; never `__return_true` on routes that write.
- Validate and sanitize every argument with `args` → `sanitize_callback` / `validate_callback`. Return `WP_REST_Response` or `WP_Error`; never `echo`.

## admin-ajax
- Actions are prefixed: `wp_ajax_wow_*`.
- First lines of the handler: `check_ajax_referer()`, then the capability check. Respond with `wp_send_json_success()` / `wp_send_json_error()`.

## admin-post
- `admin_post_wow_*` handlers call `check_admin_referer()` **and** `current_user_can()` before reading `$_POST` or `$_FILES`, and finish with `wp_safe_redirect()` then `exit`.

## Outbound HTTP
- `wp_remote_get()` / `wp_remote_post()` with a timeout; cache responses in transients where sensible. Keys come from settings; never log them. The Wordfence bearer token is `wow_settings['wordfence_api_key']` from the Authentication tab. Do not hard-code it.

## Hooks we expose
- New hooks are prefixed `wow_`, documented with a docblock, and listed here. Renaming or removing a public hook is a breaking change; deprecate first.
- None yet. The plugin does not call `apply_filters()` or `do_action()`. WP-Cron runs the scheduled event `wow_weekly_cron_event`.
