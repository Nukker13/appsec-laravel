# Phase 1 - Foundation (agent report)

## Files created (by area)
- Skeleton: Laravel 13.x skeleton (`composer.json` adds `laravel/socialite`; dev: `larastan/larastan`, `laravel/boost` 2.10, `laravel/pint`), `.mcp.json` (Boost MCP only), `.gitignore` additions (`.env`, `client_credentials.json`, `storage/app/instances/`, `flag.txt`, `.phpunit.cache`).
- Docker: `docker-compose.yml`, `docker/php/{Dockerfile,php.ini}`, `docker/nginx/default.conf.template`, `docker/mysql/init.sql`, `.env.example`, `config/labs.php`, `config/services.php` (google), `config/hints.php` (`[]`).
- Schema: 6 migrations (users+sessions, failed_jobs, labs, lab_user, flag_attempts, admin_actions); models `User`, `Lab`, `LabProgress`, `FlagAttempt`, `AdminAction`; `UserFactory` (+`admin()`), `LabFactory`; `LabSeeder`, `DatabaseSeeder`.
- Stubs: `Contracts/LabRunner`, `Exceptions/LabRunnerException`, `Services/{DockerLabRunner,InstanceResolver,FlagService,HintService}`, `Testing/FakeLabRunner` (implemented), `Jobs/{LaunchLab,StopLab}`, `Console/Commands/{ReapLabs,PromoteUser,DemoteUser}`, controllers (`HomeController`, `Auth/GoogleController`, `LabController`, `LabInstanceController`, `HintController`, `FlagController`, `Admin/{ProgressController,LabAnalyticsController,UserController,ProgressOverrideController}`), `routes/web.php`, `routes/console.php` (schedule), `AppServiceProvider` (gate, `LabRunner` binding, `flag-check` limiter), `bootstrap/app.php` (guest -> `home`, authed -> `labs.index`), `layouts/app.blade.php`, `home.blade.php`.
- Quality/CI: `phpstan.neon` (level 6, no baseline), `pint.json`, `.github/workflows/ci.yml`, `phpunit.xml` (MySQL `testing`).
- Docs: `docs/ARCHITECTURE.md`, ADRs 0001-0003.

## Verification (all run in the `app` container unless noted)
| Command | Result |
|---|---|
| `docker compose up -d --build` | OK; all 7 services up (mysql/redis healthy) |
| `docker compose exec app php artisan migrate:fresh --seed` | OK, 6 migrations, `LabSeeder` DONE |
| `select slug,... from appsec.labs` | 10 rows, matching Phase 0 table |
| `php artisan test` | `No tests found.`, exit 0 |
| `vendor/bin/phpstan analyse` | `[OK] No errors` (needs >128M; image sets 512M) |
| `vendor/bin/pint --test` | `PASS ... 57 files` |
| `php artisan route:list` | 15 routes, names and middleware as in CLAUDE.md (guest on login/callback; auth on the rest; `throttle:flag-check` on `labs.flag`; `can:admin` on admin) |
| `curl http://localhost:8080/` | 200 |
| `curl http://localhost:8080/labs`, `/admin` | 302 -> `http://localhost:8080/` |
| `grep -rnE '\b(exec\|shell_exec\|system\|passthru\|proc_open)\s*\(\|`' app/` | no matches |
| `cmp CLAUDE.md <copy>` after Boost | identical; no AGENTS.md |
| Throwaway tests (deleted afterwards, not committed) | DB is `testing`, `admin()` factory state, `array` cache, Auth login/logout without `remember_token`, guest `POST /logout` redirects home |
| In `queue`: `docker version`, `docker compose version` | client 29.9.0 / server 29.7.2, compose 5.6.0, cwd = host path |
| `ci.yml` syntax | parsed with Ruby YAML (OK). **Not run on GitHub** (no remote). |

## Deviations
- `HomeController::index` returns the `home` view instead of throwing (needed for the 200 check; no business logic).
- Mount the **whole project** at the host path (not only `labs/` and `storage/app/instances/`); see ADR 0001.
- `HOST_PROJECT_PATH` defaults inside `docker-compose.yml` (`${HOST_PROJECT_PATH:-${PWD}}`); in `.env.example` it is a commented line.
- Boost: official (`github.com/laravel/boost`, laravel org). Installed as dev dependency; the installer was not run, `.mcp.json` written by hand (`php artisan boost:mcp`, runs on the host PHP).
- PHP extensions are installed with `mlocati/php-extension-installer`; plain `pecl install redis` failed to build on PHP 8.5.
- `User::$fillable` excludes `role` (ADR 0003). `lab_user` relations use `->using(LabProgress::class)->withTimestamps()` in addition to the contract's `withPivot`.
- Added `LABS_MAX_INSTANCES=4`, `config/labs.php.max_instances` default 4 (contract gave no default).
- `phpstan` needs `--memory-limit=1G` outside the Docker image (CI passes it).
- The scheduler runs `labs:reap` every minute and will log `LogicException` until the Runtime track lands.

## Contract issues
- None blocking. Notes: the CLAUDE.md `flag-check` limiter reads route param `lab`, but `throttle` can run before route-model binding; the limiter accepts either a bound `Lab` or a raw slug. `users` has no `remember_token`, so "remember me" is unsupported.

## Open items
- Real Google credentials are needed in `.env` for end-to-end login.
- Linux hosts: the UID of the bind-mounted project vs `www-data` (storage permissions) is not handled.
- Lab compose files must join external network `labs_proxy`; `labs/` does not exist yet (phase 3).
- `DockerLabRunner` talks to the daemon as root in `queue`; web workers cannot reach the socket by design.
