# Phase 4 - Admin

## Files
- New: `app/Services/AdminMetrics.php`, `resources/views/admin/{progress,labs,user}.blade.php`, `docs/ADMIN.md`.
- Changed: `app/Http/Controllers/Admin/*` (all four), `routes/web.php` (`withoutScopedBindings()` on the admin group), `docs/ARCHITECTURE.md` (Admin section).

## Suite
Before: 14 failed / 145 passed (all AdminTest). After: 159 passed, 0 failed. phpstan: no errors. pint: pass. `git diff main -- tests/` empty.

## Deviations / decisions
- Route binding: `/admin/users/{user}/labs/{lab:slug}` implicitly scopes `lab` to `user->labs()`, giving 404 when the user has no progress (test `override_without_previous_progress`). Fixed with `withoutScopedBindings()` on the admin group; URIs and names are unchanged.
- Hints aggregate: average hints released per completed user (the test has a single solver, so 12). Documented in `docs/ADMIN.md`.
- Commits: the matrix and analytics landed in the first commit (shared service), not two.

## Open issues
- The matrix loads all users, labs and attempts without pagination; fine for a class-sized dataset.
- Views use bare tables; `app.css` has no table styles.
