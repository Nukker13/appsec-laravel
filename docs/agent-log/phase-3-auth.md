# Phase 3 - Auth track

## Files changed
- `app/Http/Controllers/Auth/GoogleController.php` (redirect, callback, logout)
- `app/Console/Commands/PromoteUser.php`, `DemoteUser.php`
- `resources/views/home.blade.php` (login link kept, `session('error')` shown)
- `docs/ARCHITECTURE.md` (Auth section)

## Tests
- Before: 137 failed, 18 passed. After: 129 failed, 26 passed (205 assertions).
- Green: all of `AuthTest` and `UserCommandsTest` (20 tests, 107 assertions; some guest-redirect cases already passed).
- phpstan: no errors. pint: pass. `git diff main -- tests/`: empty.

## Deviations
- None. `routes/web.php` unchanged.

## Decisions
- Known email with a different `google_id`: login refused, redirect home with `error`, nothing written (see ARCHITECTURE.md, Auth). No ADR written.
- Any `Throwable` from the Socialite `user()` call is caught, reported and flashed; not only the OAuth exception types.

## Open issues
- `redirect()` is not covered by a test.
- A user whose Google account changed id cannot log in until an admin fixes the row.

## Review finding: email_verified
The callback trusted the email without `email_verified`. Fixed: refuse unless the raw value is strictly `true` (false, missing, and the string "false" all refuse). See ARCHITECTURE.md, Auth.
