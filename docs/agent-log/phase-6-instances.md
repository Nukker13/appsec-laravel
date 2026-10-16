# Phase 6: per-user instances

## Files
- `app/Services/InstanceResolver.php` (`lab_{slug}_u{id}`), `FlagService.php` (`flagFile` under `labs.instances_path`).
- `app/Services/DockerLabRunner.php`: `up()` copies the lab (Laravel Filesystem, no shell) and runs compose from the copy; `down()` runs `down -v --remove-orphans --rmi local` and deletes the instance directory; the old "empty labs storage" code is gone.
- `app/Jobs/LaunchLab.php`: issue flag, then `up`; on failure best-effort `down`, `forget`, `failed`.
- `app/Http/Controllers/LabInstanceController.php`: 503 `{status: capacity}`.
- `docs/ARCHITECTURE.md` (runtime section, diagram, labs notes).
- `.gitignore` already ignores `/storage/app/instances/`. `tests/` untouched.

## Suite
Before: 23 failed, 144 passed. After the first commit: 2 failed (the capacity tests), 165 passed. After the second: 167 passed, 0 failed. phpstan and pint pass; no process functions or backticks in `app/`.

## Deviations and decisions
- `down()` with a missing copy falls back to the original compose file with `-p {project}` and cwd `labs/{slug}/docker` (read only), so it stays idempotent.
- The copy is considered present when `docker/docker-compose.yml` exists in it (the flag file alone, issued before `up`, does not count).
- The copy skips only `docker/storage/*` and `storage/*` contents; symlinks are copied as the files they point to. A half-finished copy is completed by the next `up`.
- Capacity uses `InstanceRegistry::running()`; a failed instance stays registered until the reaper or a retry and counts against capacity.
- `labs.launch` capacity check is not atomic with the registration done in `LaunchLab`; two simultaneous first launches can exceed the cap by one.

## Open issues
- Real docker not exercised here (orchestrator does live acceptance).
- `labs.instances_path` must be under the host-path mount (ADR 0001).
