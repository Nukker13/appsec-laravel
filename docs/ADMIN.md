# Admin

Access: `auth` + `can:admin` (role `admin`). Promotion is `php artisan user:promote {email}`; there is no user management UI.
Code: `app/Http/Controllers/Admin/*`, `app/Services/AdminMetrics.php`, views in `resources/views/admin/`.

## Screens

### Progress matrix (`/admin`, `admin.progress`)
Rows: all users, by name. Columns: labs by `sort`. A cell shows, for an existing `lab_user` row: status (`started`/`completed`), solve time (completed only) and attempt count. No row shows `-`. The user name links to the user page.

### Lab analytics (`/admin/labs`, `admin.labs`)
One row per lab (by `sort`):

| Column | Definition |
|---|---|
| Started | number of `lab_user` rows for the lab (completed users also count as started) |
| Completed | rows with `status = completed` |
| Completion | completed / started x 100, 1 decimal trimmed (`60%`); `-` when nobody started |
| Median solve time | median of solve times of completed rows; for an even count the mean of the two middle values; `-` when none |
| Avg attempts | mean attempt count of completed users only; `-` when none |
| Avg hints released | see below |

### User page (`/admin/users/{user}`, `admin.users.show`)
Per lab: status, solve time, attempts, and two forms (reset, mark complete). Each POSTs with `@csrf` and a required `reason` textarea. Validation errors and the success flash are shown at the top.

## Metric definitions
- **Solve time** = `completed_at - started_at` in seconds; shown as `N min S s`. Only for `completed` rows; a started row shows no time.
- **Attempts** = `flag_attempts` of that user and lab, ordered by id, counted up to and including the first correct one. If none is correct yet, all attempts are counted. No attempts = 0. Attempts after the first correct one are ignored.
- **Hints released at completion**: for each completed user, the number of entries in `config('hints.<slug>')` with `after * 60 <= solve seconds`. The lab figure is the **average per completed user** (1 decimal, trailing `.0` dropped). A lab with no hints, or no completions, shows `0` / `-`.
- Users with an override-completed row have solve time `completed_at - started_at` like anyone else (a never-launched user completed by an admin gets 0 s and 0 hints).

## Overrides
- `POST /admin/users/{user}/labs/{lab}/reset` and `/complete`. `reason` is validated first (`required|string`; blank or whitespace-only is rejected, the input is trimmed by the framework).
- Everything runs in one `DB::transaction`: first the progress change, then `AdminAction::create(...)`. If the audit insert throws, the change is rolled back.
- **reset** deletes the target user's `lab_user` row for that lab. `flag_attempts` are kept. Other users are untouched.
- **complete** upserts `status = completed`, `completed_at = now`; `started_at` is kept if a row existed, otherwise `now`.
- `admin_actions`: `admin_id`, `target_user_id`, `lab_id`, `action` (`progress_reset`/`progress_complete`), `reason`, `meta` = previous state `{status, started_at, completed_at}` (ISO 8601) or `null` when there was no row.
- Response: redirect back with a `status` flash. All queries are scoped to the target user and lab.
- Routes use `withoutScopedBindings()`: `{lab:slug}` must resolve globally, not through the user's labs.
