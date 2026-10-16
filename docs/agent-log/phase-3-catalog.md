# Phase 3: Catalog & hints

## Files
- `app/Services/HintService.php` (`due()`, new `nextAt()`), `app/Http/Controllers/HintController.php`
- `app/Http/Controllers/LabController.php`, `resources/views/labs-index.blade.php`, `resources/views/lab.blade.php`
- `resources/views/layouts/app.blade.php`, `public/js/lab.js`
- `docs/ARCHITECTURE.md` (Catalog and hints section)

## Suite
Before: 35 failed, 124 passed. After: 21 failed, 138 passed. phpstan and pint pass; `git diff main -- tests/` empty.

Green now: CatalogTest (reject category, filter, per-user status, lab pages for ssrf/oauth-ato/rbac/path-trav/type-juggle/insec-deser), HintServiceTest (both), HintsTest (before launch, per-user start).

## Still red, caused by labs-track views (not fixed, out of scope)
`resources/views/labs/{osci,dom-xss,reflect-xss,stored-xss}.blade.php` contain raw `<?php` / `<?=` inside `@verbatim`. Blade does not protect those, so the compiled view has a parse error (500). It also leaves raw HTML tags (`<div>`, `<html>`) unescaped inside `<code>`. This keeps red: 4 CatalogTest lab-page cases, `test_hints_are_released_at_2_4_6_minutes_after_start`, `test_lab_page_html_contains_no_hint_text`, FlagTest `test_viewing_lab_page_does_not_change_flag`. Suggested fix (labs track): HTML-escape `& < >` inside the code samples of those four partials. I tried this and the permission classifier denied the edit, so I left it. The remaining 14 reds are AdminTest (phase 4).

## Deviations
- Added `HintService::nextAt()` (CLAUDE.md only lists `due()`).
- Catalog view is `labs-index.blade.php` at the views root, keeping `views/labs/` for slug partials only.

## Open issues
- Traefik warm-up is a fixed 2.5 s delay before showing the link.
