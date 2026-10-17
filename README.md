# AppSec CTF Training Platform

[![CI](https://github.com/<OWNER>/<REPO>/actions/workflows/ci.yml/badge.svg)](https://github.com/<OWNER>/<REPO>/actions/workflows/ci.yml)
<!-- TODO: replace <OWNER>/<REPO> once the remote is set -->

Internal application-security CTF training platform. Students sign in with Google, launch a Docker lab, exploit an **intentionally vulnerable** application, and submit the flag they find. Admins track progress per user and per lab. This repository is the Laravel rewrite (v2) of the original custom PHP MVC platform (v1); the defects found in v1 are listed in [Security defects fixed](#security-defects-fixed-v1--v2).

## ⚠️ Safety warning

- The applications under [`labs/`](labs/) are **deliberately vulnerable**. Never expose them to the public internet and never run them outside an isolated environment. Run the platform locally or on a controlled, isolated network only.
- The Docker socket is mounted into the `app`, `queue` and `scheduler` containers. Anyone who can execute code in those containers has root on the host. This is an accepted trust boundary; see [ADR 0002](docs/decisions/0002-docker-socket-trust-boundary.md).
- Only Traefik and each lab's entry service join the `labs_proxy` network. No other platform service may be attached to it, so a lab shell cannot reach the PHP runtime that holds the socket.
- Do not edit lab applications to "fix" them; their vulnerabilities are the exercise.

## Architecture

A Laravel application behind nginx, backed by MySQL and Redis, with a queue worker and scheduler. Labs are Docker Compose projects started on demand, one instance per user, routed by Traefik at `*.labs.localhost`. Flags are issued per instance launch and only a hash is kept in Redis, with a TTL.

| Service | Role |
|---|---|
| `nginx` | Serves the platform on `127.0.0.1:8080` |
| `app` | php-fpm (Laravel), Docker CLI + compose |
| `queue` | Runs `LaunchLab` / `StopLab` jobs |
| `scheduler` | Runs `labs:reap` every minute (stops idle instances) |
| `mysql` | Users, labs, progress, flag attempts, admin audit |
| `redis` | Cache, queue, locks, throttling, instance status, flag hashes |
| `traefik` | Routes `lab-{slug}-u{id}.labs.localhost` to the lab entry service |

Docker is only invoked through the `App\Contracts\LabRunner` contract. Details: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) and the ADRs:

- [0001 Host-path bind mounts](docs/decisions/0001-host-path-bind-mounts.md)
- [0002 Docker socket trust boundary](docs/decisions/0002-docker-socket-trust-boundary.md)
- [0003 Guarded role and no remember token](docs/decisions/0003-guarded-role-and-no-remember-token.md)
- [0004 Flag in Redis](docs/decisions/0004-flag-in-redis.md)
- [0005 Per-instance lab copy](docs/decisions/0005-per-instance-lab-copy.md)

## Requirements and setup

Requirements: Docker and Docker Compose.

```bash
cp .env.example .env
# set GOOGLE_CLIENT_ID and GOOGLE_CLIENT_SECRET (Web client from Google Cloud Console);
# GOOGLE_REDIRECT_URI defaults to http://localhost:8080/auth/google/callback

docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
```

Open <http://localhost:8080> and sign in with Google. Lab instances are served at `http://lab-{slug}-u{id}.labs.localhost`.

Promote (or demote) an admin by email, after that user has logged in once:

```bash
docker compose exec app php artisan user:promote <email>
docker compose exec app php artisan user:demote <email>
```

## Testing

```bash
docker compose exec app php artisan test              # CI suite
docker compose exec app vendor/bin/phpstan analyse
docker compose exec app vendor/bin/pint --test
```

The `--group=docker` lab solvability suite is **not implemented**: exploit-based solvability tests were skipped by owner decision and labs are verified by hand/QA (see [CHANGELOG](CHANGELOG.md), "Phase 5: skipped"). CI runs from [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

## Security defects fixed (v1 → v2)

| ID | v1 defect | v2 fix | Test |
|---|---|---|---|
| D1 | Completing a lab updated progress by lab only, marking it completed for every user | Progress is read and written through the current user's pivot only | `tests/Feature/FlagTest.php::test_correct_flag_completes_lab_for_current_user_only`, `tests/Feature/CatalogTest.php::test_catalog_shows_status_of_current_user_only` |
| D2 | One global flag, regenerated on every lab page view | Flag issued once per instance launch, hash stored in Redis with a TTL, page views never touch it | `tests/Feature/FlagTest.php::test_viewing_lab_page_does_not_change_flag` |
| D3 | Launching a lab stopped every other running lab, killing other users' sessions | No lab stops another; per-instance `Cache::lock`; per-user instances | `tests/Feature/RuntimeTest.php::test_launching_lab_does_not_stop_other_labs`, `tests/Integration/ConcurrentLaunchTest.php::test_concurrent_launches_start_instance_once` |
| D4 | Fixed `sleep(5)` then redirect, readiness never checked | Queued `LaunchLab` job plus a polled status endpoint | `tests/Feature/RuntimeTest.php::test_launch_job_marks_instance_ready` |
| D5 | No authentication on main-app routes | `auth` middleware on everything except home and the OAuth routes | `tests/Feature/AuthTest.php::test_guest_is_redirected_from_every_protected_route` |
| D6 | Admin role reset to `user` on every login | OAuth callback never writes `role` | `tests/Feature/AuthTest.php::test_admin_role_survives_relogin` |
| D7 | OAuth callback continued after redirect when `code` was missing | Socialite errors handled; redirect home with an error flash | `tests/Feature/AuthTest.php::test_callback_without_code_redirects_with_error` |
| D8 | Unlimited flag submissions | `throttle:flag-check`, 10 per minute per user and lab | `tests/Feature/FlagTest.php::test_flag_check_is_rate_limited` |
| D9 | Hints shipped in the page HTML (hidden only by CSS) with a client-side timer that reset on reload | Hints served by an endpoint on a server-side schedule, anchored to the running instance's launch time |  `tests/Feature/HintsTest.php::test_lab_page_html_contains_no_hint_text`, `tests/Feature/HintsTest.php::test_hints_are_released_at_2_4_6_minutes_after_launch` |
| D10 | A privileged `stopper` container with the Docker socket ran inside every lab | Removed; scheduled `labs:reap` stops expired instances; lab compose files may not mount the socket | `tests/Feature/LabDataInvariantsTest.php::test_no_lab_compose_file_mounts_docker_socket_or_defines_stopper`, `tests/Feature/RuntimeTest.php::test_reaper_stops_only_expired_instances` |
| D11 | Shell commands built by string concatenation | `DockerLabRunner` uses Symfony Process with an argument array; slug whitelisted against the DB | `tests/Unit/DockerLabRunnerTest.php::test_up_runs_compose_from_the_instance_copy_with_an_argument_array`, `tests/Unit/DockerLabRunnerTest.php::test_a_slug_that_is_not_in_the_labs_table_is_rejected_before_any_process_runs` |
| D12 | No schema or migrations in the repository | Migrations and `LabSeeder` are the source of truth | `tests/Feature/LabDataInvariantsTest.php::test_every_seeded_lab_has_view_and_lab_folder_with_compose_file` (the whole suite also runs on migrated schema) |
| D13 | No CSRF protection on login/logout | Laravel `web` middleware; logout requires a CSRF token | `tests/Feature/AuthTest.php::test_logout_requires_csrf_token` |

## Documentation map

- [CLAUDE.md](CLAUDE.md): fixed contracts (schema, routes, services, cache keys, observable behaviour)
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md): components, request flow, launch sequence, trust boundary
- [docs/ADMIN.md](docs/ADMIN.md): admin views, metrics and overrides
- [docs/decisions/](docs/decisions/): architecture decision records 0001-0005
- [docs/labs/](docs/labs/): per-lab documentation
- [docs/MIGRATION-PLAN.md](docs/MIGRATION-PLAN.md) and [docs/CODEBASE-INVENTORY.md](docs/CODEBASE-INVENTORY.md): migration plan and facts about the v1 code
- [CHANGELOG.md](CHANGELOG.md)
