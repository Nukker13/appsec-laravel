# Phase 7 fixes, batch 1

## #7 Atomic capacity reservation
- `InstanceRegistry::reserve(project, slug, max): bool` checks and registers under `labs:running:lock`. A project that is already registered is re-registered and returns true. Otherwise it returns false at capacity, without adding it.
- `LabInstanceController::launch()` calls `reserve()` once and returns 503 `{status: "capacity"}` on false. After a successful reserve it calls `touch()`, so the reaper can act on a reservation whose job never runs.

## #5 Hints anchored to the current launch
- `InstanceRegistry`: `markLaunched`, `launchedAt`, `clearLaunched` (cache key `lab:{project}:launched_at`).
- `HintService::due/nextAt` take `$since` and release hints at `$since + after` minutes.
- `LaunchLab` calls `markLaunched` only on a fresh bring-up that succeeded. The failure path calls `clearLaunched`.
- `StopLab` calls `clearLaunched`.
- `HintController` returns `{hints: []}` when there is no progress row or no `launched_at`.

## Results
Full suite 176 passed, phpstan OK, pint OK.
