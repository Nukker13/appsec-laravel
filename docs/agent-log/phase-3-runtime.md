# Phase 3: Runtime and flag (D1, D2, D3, D4, D8, D10, D11)

## Commits
`fix(D2)` flag service and resolver, `fix(D11)` docker runner, `fix(D4,D3)` launch job, status, lock, `fix(D10)` stop job and reaper, `fix(D1,D8)` flag check, `docs`.

## Files changed
- `app/Services/InstanceResolver.php`, `FlagService.php`, `DockerLabRunner.php`; new `app/Services/InstanceRegistry.php` (status, last_seen and the `labs:running` map in one place).
- `app/Jobs/LaunchLab.php`, `StopLab.php`, `app/Console/Commands/ReapLabs.php`.
- `app/Http/Controllers/LabInstanceController.php`, `FlagController.php`.
- `docs/ARCHITECTURE.md` (runtime section, old planned diagram replaced by a pointer), `docs/decisions/0004-flag-in-redis.md`.
- `tests/` untouched.

## Test results (docker/wt.sh test)
- Before: 137 failed, 18 passed. After: 83 failed, 72 passed. phpstan OK, pint OK, no process functions or backticks in `app/`.
- Green: `InstanceResolverTest`, `FlagServiceTest`, `DockerLabRunnerTest`, `RuntimeTest` (all), `Integration/ConcurrentLaunchTest`, `Integration/RedisIntegrationTest`, `FlagTest` except two.

## Cross-track reds (not my code)
- `FlagTest::test_viewing_lab_page_does_not_change_flag` and `test_viewing_a_lab_that_was_never_launched_issues_no_flag`: both fail at `get(route('labs.show'))` with `LogicException: not implemented` in `LabController::show` (catalog track). Everything after that line in those tests is untested until then.
- `HintsTest`, `CatalogTest`, `AdminTest`, `AuthTest`, lab invariants (missing `labs/` folders) fail for the other tracks' reasons.

## Contract gaps I decided
- Failure path: on `LabRunnerException` from `up`, `LaunchLab` logs, sets status `failed`, issues no flag, creates no `lab_user` row, keeps the project registered and touched so the reaper cleans partial containers. No retry inside the job; the student relaunches.
- `isRunning()` throws `LabRunnerException` when `docker ps` fails, which `LaunchLab` also turns into `failed`.
- `StopLab` also takes `lab:{project}:lock` (block 10 s, then release) so a stop cannot interleave with a start.
- `labs:running` is a project => slug map in one cache entry, guarded by `labs:running:lock`, not a Redis set. A registry entry without `last_seen` is treated as expired.
- Correct flag updates `lab_user` only where `status = started`, so a repeat solve keeps the first `completed_at`.
- `flag` validation also has `max:255`.
- `launch` returns the status after dispatch, so on a sync queue it can already be `ready`.
- `down` does not remove `labs/{slug}/flag.txt` (the hash is forgotten, so it is dead); phase 6 moves the file anyway.

## Open issues
- Phase 3 shares one instance per lab: a correct flag from one student stops the lab for others and any student can submit the shared flag. Intended until phase 6.
- Real `docker compose` calls are unverified here (no `labs/`, no docker.sock in the test stack); the `--wait` flag assumes the labs' compose files start cleanly.
- `down` ignores files it cannot delete (for example root-owned files written by containers).

## Follow-up: never ready without a valid flag (`fix(D2,D4)`)
- `up` failure now also runs a best-effort `down` (exception logged, not rethrown) before status `failed`.
- `LaunchLab` re-issues a flag when the instance is running and `FlagService::isIssued($project)` is false (new method, PHPDoc'd).
- Scenarios verified by hand with a throwaway script (not committed) using `FakeLabRunner` through the container: (1) `failNextUp()` then relaunch: `downCalls` has the cleanup, status `failed`, second launch starts it and issues a flag; (2) instance running, flag key forgotten (TTL stand-in), relaunch: no second `up`, a new flag verifies, status `ready`. The existing suite also covers the unchanged paths.
