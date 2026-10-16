# CLAUDE.md — AppSec Platform (Laravel)

Internal AppSec CTF training platform, being migrated from a custom PHP MVC (`../Git/backend_project_private`) to Laravel. Students log in with Google, launch a Docker lab, exploit an **intentionally vulnerable** app, and submit the flag. The plan lives in `docs/MIGRATION-PLAN.md` and the facts about the old code in `docs/CODEBASE-INVENTORY.md`.

The contracts below are fixed. Only the orchestrator changes them. If one looks wrong, stop and report it; do not work around it.

## Hard rules

- `labs/{slug}/` apps are intentionally vulnerable. Do not fix, harden or refactor anything inside them except their `docker/docker-compose.yml`, and only when the task says so.
- Implementation agents never edit `tests/`. A test that seems wrong is reported to the orchestrator.
- The lab slug, the Blade view `resources/views/labs/{slug}.blade.php`, the folder `labs/{slug}/` and `labs.slug` in the DB must match exactly.
- Docker is invoked only through `App\Contracts\LabRunner`. No `exec`, `shell_exec`, `system`, `passthru` or backticks anywhere in `app/`.
- Every query that touches progress is scoped to a user (defect D1).
- One commit per defect or feature: `fix(D1): …`, `feat(admin): …`, `test: …`, `docs: …`.
- Definition of done: assigned tests green, `php artisan test` no worse, `vendor/bin/phpstan` and `vendor/bin/pint --test` pass, docs for the track updated, report written to `docs/agent-log/phase-N-<track>.md`.

## Commands

```bash
docker compose up -d                       # app, nginx, mysql, redis, queue, scheduler
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test                  # CI suite
docker compose exec app php artisan test --group=docker   # lab solvability, local only
docker compose exec app vendor/bin/phpstan analyse
docker compose exec app vendor/bin/pint --test
```

## Schema

| Table | Columns |
|---|---|
| `users` | `id`, `google_id` string unique, `email` string unique, `name` string, `role` enum(`user`,`admin`) default `user`, `last_login_at` timestamp null, timestamps |
| `labs` | `id`, `slug` string unique, `name` string, `category` enum(`frontend`,`backend`), `sort` unsigned int default 0, timestamps |
| `lab_user` | `id`, `user_id` FK cascade, `lab_id` FK cascade, `status` enum(`started`,`completed`), `started_at` timestamp, `completed_at` timestamp null, timestamps; unique(`user_id`,`lab_id`) |
| `flag_attempts` | `id`, `user_id` FK, `lab_id` FK, `is_correct` bool, `created_at` |
| `admin_actions` | `id`, `admin_id` FK users, `target_user_id` FK users, `lab_id` FK null, `action` enum(`progress_reset`,`progress_complete`), `reason` text, `meta` json null, `created_at` |

`LabSeeder` data (10 labs, slug/name/category/sort) is in `docs/MIGRATION-PLAN.md` → Phase 0. The original DB is lost, so that table is the source of truth.

`lab_user.started_at` is set on the **first launch** (not on page view) and is never overwritten by later launches. It drives the hint schedule.

## Models

- `User`: `labs()` belongsToMany `Lab` via `LabProgress`, withPivot(`status`,`started_at`,`completed_at`); `flagAttempts()` hasMany; `isAdmin(): bool`.
- `Lab`: route key `slug`; `users()` belongsToMany; `flagAttempts()` hasMany.
- `LabProgress`: pivot model for `lab_user`.
- `FlagAttempt`, `AdminAction`: plain models, no `updated_at`.
- `Gate::define('admin', fn (User $u) => $u->isAdmin())`.

## Routes

| Method | URI | Name | Middleware | Response |
|---|---|---|---|---|
| GET | `/` | `home` | — | view |
| GET | `/login` | `login` | guest | redirect to Google |
| GET | `/auth/google/callback` | `auth.callback` | guest | redirect `labs.index` (or `home` with error) |
| POST | `/logout` | `logout` | auth | redirect `home` |
| GET | `/labs` | `labs.index` | auth | view; `?category=frontend\|backend` (validated) |
| GET | `/labs/{lab:slug}` | `labs.show` | auth | view |
| POST | `/labs/{lab:slug}/launch` | `labs.launch` | auth | 202 JSON `{status}` |
| GET | `/labs/{lab:slug}/status` | `labs.status` | auth | JSON `{status, url?}` |
| GET | `/labs/{lab:slug}/hints` | `labs.hints` | auth | JSON `{hints: [{n, text}], next_at?}` |
| POST | `/labs/{lab:slug}/flag` | `labs.flag` | auth, throttle:flag-check | JSON `{correct: bool}`; 429 when throttled |
| GET | `/admin` | `admin.progress` | auth, can:admin | view: progress matrix |
| GET | `/admin/labs` | `admin.labs` | auth, can:admin | view: lab analytics |
| GET | `/admin/users/{user}` | `admin.users.show` | auth, can:admin | view: user progress + override forms |
| POST | `/admin/users/{user}/labs/{lab:slug}/reset` | `admin.progress.reset` | auth, can:admin | redirect back; `reason` required |
| POST | `/admin/users/{user}/labs/{lab:slug}/complete` | `admin.progress.complete` | auth, can:admin | redirect back; `reason` required |

Rate limiter `flag-check`: 10 per minute, keyed `user_id:lab_id`.

## Services and contracts

```php
namespace App\Contracts;

interface LabRunner
{
    /** Start compose project $project from labs/{$slug}/docker/docker-compose.yml. Throws LabRunnerException. */
    public function up(string $slug, string $project): void;

    /** Stop and remove compose project $project and clear labs/{$slug} storage. Idempotent. */
    public function down(string $slug, string $project): void;

    public function isRunning(string $project): bool;
}
```

- `App\Services\DockerLabRunner implements LabRunner` uses Symfony Process with an argument array, and rejects a slug that is not in `labs` (throws `App\Exceptions\LabRunnerException` before any process is created). Constructor: `__construct(?\Closure $processFactory = null)`; the factory is `fn (array $command, ?string $cwd, array $env): Process` and defaults to `new Process($command, $cwd, $env)`. Every process is created through it (test seam). `Process::fromShellCommandline` is never used.
- `App\Testing\FakeLabRunner implements LabRunner` records calls in memory; bound in tests.
- `App\Services\InstanceResolver::project(User $user, Lab $lab): string` returns `lab_{slug with - → _}` in phase 3 and `lab_{slug}_u{user_id}` in phase 6. Everything instance-related is keyed by this project name, so phase 6 only changes this method.
- `App\Services\FlagService`:
  - `issue(string $project, Lab $lab): void`: random 32-byte hex flag, writes the plaintext to the instance flag file, stores the sha256 in cache.
  - `verify(string $project, string $submitted): bool`: `hash_equals` against the stored hash.
  - `forget(string $project): void`.
  - `isIssued(string $project): bool`: a hash is stored for the project. `LaunchLab` re-issues when the instance runs but no flag is issued (expired TTL, earlier failure).
  - `flagFile(string $project, Lab $lab): string`: absolute path of the instance flag file. Phase 3: `labs/{slug}/flag.txt` (what the labs read today). Phase 6: `storage/app/instances/{project}/flag.txt`. Tests read the plaintext through this method, never through a hard-coded path.
- `App\Services\HintService::due(LabProgress $progress): array`: reads `config/hints.php` (`[slug => [['after' => 2, 'text' => '…'], …]]`, minutes after `started_at`).

## Lab instance routing

- Lab compose files publish **no host ports** and have no `container_name`. The entry service (`nginx`, or `app` for `ssrf`) joins the external network `labs_proxy` and carries Traefik labels for `Host(\`${LAB_HOST}\`)`. **Only** the entry service joins `labs_proxy`, and no platform service except `traefik` is ever on it (phase 1 review: php-fpm reachable from a lab shell = host root via `docker.sock`).
- `DockerLabRunner::up()` sets env `COMPOSE_PROJECT_NAME={project}`, `LAB_HOST={project}.labs.localhost` (with `-` instead of `_`), and `LAB_FLAG_FILE` (= `FlagService::flagFile()`, absolute; set from phase 3, labs start reading it in phase 6).
- `labs.status` returns `url = http://{LAB_HOST}` once the status is `ready`.

## Jobs, commands, cache keys

- `App\Jobs\LaunchLab(User, Lab)`: under `Cache::lock("lab:{project}:lock", 120)`: if the instance is not running → status `starting` → `LabRunner::up` → `FlagService::issue` → status `ready`; sets `started_at` on first launch; touches `last_seen`.
- `App\Jobs\StopLab(slug, project)`: `LabRunner::down`, `FlagService::forget`, status `stopped`.
- `labs:reap` (scheduled every minute): `StopLab` for instances whose `last_seen` is older than `LAB_IDLE_MINUTES` (default 15).
- `user:promote {email}`, `user:demote {email}`.

Cache keys (Redis store, logical names):

| Key | Value | TTL |
|---|---|---|
| `lab:{project}:status` | `pending\|starting\|ready\|stopped\|failed` | none (cleared on stop) |
| `lab:{project}:flag` | sha256 of the flag | `LAB_MAX_MINUTES` (default 120) |
| `lab:{project}:last_seen` | unix timestamp | none |
| `lab:{project}:lock` | atomic lock | 120 s |
| `labs:running` | set of running project names (for the reaper) | none |

## Observable behaviour (fixed 2026-10-16 for the phase 2 tests)

- Error flash key is `error` (`redirect()->route('home')->with('error', …)`).
- OAuth callback: users are matched by `google_id` only. It refuses (redirect `home` + `error`, nothing written) when Google's raw `email_verified` is not `true`, and when the email already belongs to a user with a different `google_id`. `user:promote`/`user:demote` look users up by email, so an unverified email must never reach the table.
- `labs.launch` always dispatches `LaunchLab` (it refreshes `last_seen` and the user's progress even when the instance runs), sets status `pending` if the instance is not already `starting|ready`, and returns 202 `{status}` with the current status. It does not create `lab_user`; `LaunchLab` does, for the launching user, even when the instance is already running.
- `labs.status`: missing status key → `stopped`. `url` is present only when the status is `ready`.
- `labs.hints`: no `lab_user` row (never launched) → `{hints: []}`. `next_at` is ISO 8601 and is omitted when every hint is released.
- `labs.flag`: request field `flag` (required string, else 422). Every non-throttled submission writes one `flag_attempts` row. A correct flag sets the user's `lab_user` to `completed` + `completed_at`, then dispatches `StopLab`. No issued flag → `{correct: false}`.
- `LaunchLab` when `up` fails: best-effort `down`, status `failed`, no flag, no `lab_user` row.
- `LaunchLab` when the lock is held by another owner: it never calls `LabRunner::up`; it waits with `block()` up to 10 s, then releases itself back to the queue.
- `user:promote` / `user:demote`: exit code 0 on success, 1 when the email is unknown.
- Admin override `reset` deletes the user's `lab_user` row (flag attempts are kept as history). `complete` upserts `status=completed`, `completed_at=now`, `started_at` kept or `now` if absent. Both write `meta` = the previous pivot state (or `null`). Order inside the one transaction: progress change first, then the audit row through the `AdminAction` model (so a failing audit insert must roll the change back).
- Unknown `?category` on `labs.index` → validation error (redirect with errors / 422 for JSON). Missing category = all labs.

## Admin metrics

- Solve time = `completed_at − started_at`.
- Attempts = count of `flag_attempts` per user+lab up to and including the first correct one.
- Hints released at completion = hints from `config/hints.php` whose `after` ≤ solve time.
- Override actions run in one DB transaction together with the `admin_actions` insert.

## Documentation map

`docs/ARCHITECTURE.md`, `docs/decisions/` (ADRs), `docs/agent-log/`, `docs/labs/{slug}.md`, `docs/ADMIN.md`, `README.md`, `CHANGELOG.md`. The owner and purpose of each are in `docs/MIGRATION-PLAN.md` → Documentation.
