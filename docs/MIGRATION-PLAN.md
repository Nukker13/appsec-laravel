# AppSec Platform — Laravel Migration Plan

Source: `../../Git/backend_project_private` (custom PHP MVC). Facts: [`CODEBASE-INVENTORY.md`](CODEBASE-INVENTORY.md) (read its section 8 verification notes first). Contracts every agent must follow: [`../CLAUDE.md`](../CLAUDE.md).

**Goal:** port the platform shell (auth, catalog, progress, lab launcher, flag check) to Laravel, fix the platform defects, and add a minimal admin area. Redis is used only where it fixes a defect.

**Out of scope:** the labs' vulnerable apps. Each `labs/{slug}/` stays plain PHP in its own containers, and its intended vulnerabilities stay untouched. Only their compose files change.

## Decisions log

- 2026-10-16 [stated] All code is written by agents; the owner wrote the original platform and reviews. Orchestrator: Opus. Routine: Haiku. Independent modules: Sonnet.
- 2026-10-16 [stated] Tests first: tests are written from the spec (D-table + contracts) before the implementation; implementation is built to make them pass. Implementation agents may not edit tests.
- 2026-10-16 [stated] Admin v1 = progress matrix, lab analytics, manual progress override with audit log. Deferred: running-instances view, lab enable/disable, user management, CSV export.
- 2026-10-16 No new end-user features beyond the defect fixes and admin v1.
- 2026-10-16 [stated] The original DB is lost. Slugs = file names in `views/labs/`. Names and categories are recovered from each view's `<h1>` and category badge (table in phase 0).
- 2026-10-16 [stated] Phase 6 (per-user instances) ships in the first public version.
- 2026-10-16 [stated] No lab solutions in the docs; the labs are elementary. `docs/labs/{slug}.md` describes the vulnerability class and the setup only.
- 2026-10-16 Phase 1 review: platform services stay off `labs_proxy` (php-fpm was reachable from lab containers → docker.sock → host root). Only Traefik and lab entry services join it.
- 2026-10-16 Before phase 2 the orchestrator fixed the observable behaviour the tests rely on (CLAUDE.md → "Observable behaviour"), added `FlagService::flagFile()` so tests never hard-code the flag path, and added a process-factory seam to `DockerLabRunner` for D11 unit tests.
- 2026-10-16 Every lab publishes host port 80 (XSS/OAuth labs also 3307), so two different labs can't run at once. That collision is the real reason v1 stops other labs. Host ports are therefore replaced with Traefik routing already in phase 3, not in phase 6.

---

## Defects to fix

| # | Defect (source) | Fix in Laravel | Redis |
|---|---|---|---|
| D1 | `Lab::labDone()` updates `user_lab_progress` by `lab_id` only, so completing a lab marks it completed for every user | Progress updated through the pivot of the current user only | — |
| D2 | Flag is global and regenerated on every `/lab/{slug}` view | Flag issued once per instance launch; hash in Redis with TTL; viewing a page never touches it | yes |
| D3 | `DockerController::run()` runs `compose down` on every other running lab, killing other users' sessions | No lab ever stops another; `Cache::lock` per instance; per-user instances in phase 6 | yes (lock) |
| D4 | `sleep(5)` and then a redirect to `localhost`, readiness never checked | `LaunchLab` queued job + polled instance status | yes (queue + status) |
| D5 | No auth on any main-app route | `auth` middleware on everything except home and the OAuth routes | — |
| D6 | Admin role reset to `user` on every login (`role = VALUES(role)`) | OAuth callback never writes `role` | — |
| D7 | `callback()` has no `exit` after the redirect when `code` is missing | Socialite errors handled → redirect home with error flash | — |
| D8 | Unlimited flag submissions | `throttle:flag-check` (10/min per user+lab) | yes |
| D9 | Hints sit in the HTML with `display:none`, and the timer resets on reload | Hints served by an endpoint according to the server-side `started_at` | — |
| D10 | `stopper` container with `docker.sock` mounted runs inside every lab | Removed; scheduled `labs:reap` stops expired instances | — |
| D11 | Shell commands built by string concatenation (not exploitable today) | `DockerLabRunner` uses Symfony Process with an argument array; slug whitelisted against the DB | — |
| D12 | No schema or migrations in the repo | Migrations + `LabSeeder` as the source of truth | — |
| D13 | No CSRF on login/logout | Laravel `web` middleware | — |

Not ported: `docker_loader.php`, `spatie/docker` (unused), `helpers/Config.php`, commented-out code.

---

## Target stack

| Layer | Source | Laravel |
|---|---|---|
| Framework | none, `switch` router | Laravel, latest stable at `create-project` time (local PHP 8.5) |
| DB | Doctrine DBAL, raw SQL, MySQL 8 | Eloquent + migrations, MySQL 8 |
| Auth | `google/apiclient` + `$_SESSION` | `laravel/socialite` (Google) + session guard; `Gate::define('admin')` |
| Views | PHP includes | Blade layout + components |
| Docker control | `exec('cd … && docker compose …')` | `LabRunner` interface → `DockerLabRunner` (Symfony Process) / `FakeLabRunner` (tests) |
| Cache / queue / locks / throttle | — | Redis |
| Lab auto-stop | `stopper` container | `labs:reap` on the scheduler |
| Tests / quality | none | PHPUnit, Larastan, Pint, GitHub Actions |

---

## Agents

| Agent | Model | Owns | Gets as context |
|---|---|---|---|
| Orchestrator | Opus | Contracts (`CLAUDE.md`), phase order, briefing other agents, reviewing every diff, running the full suite, verifying agent claims against code | everything |
| Routine | Haiku | Copying `labs/`, compose-file edits, porting the 10 instruction views to Blade, extracting hints to `config/hints.php` | `CLAUDE.md`, inventory §6–7, one finished Blade example |
| Foundation | Sonnet | Phase 1: skeleton, Docker setup, migrations, models, stubs, CI | `CLAUDE.md`, inventory §1, §4, §9 |
| Test writer | Sonnet | Phase 2: all tests from the spec, before implementation | `CLAUDE.md`, this file (D-table + test list); **no access to implementation briefs** |
| Auth | Sonnet | D5, D6, D7, D13 | `CLAUDE.md`, inventory §5 |
| Catalog & hints | Sonnet | Catalog, lab pages, hints endpoint (D9) | `CLAUDE.md`, inventory §6 |
| Runtime & flag | Sonnet | `LabRunner`, `InstanceResolver`, `FlagService`, jobs, `labs:reap`, flag check (D2, D3, D4, D8, D10, D11) | `CLAUDE.md`, inventory §3, §7 |
| Admin | Sonnet | Admin v1 | `CLAUDE.md`, admin section of this file |

Laravel knowledge comes from: `CLAUDE.md` (project conventions, read automatically by every agent), Laravel Boost MCP installed in phase 1 (version-matched docs; verify it is still the official tool before installing), and the PhpStorm Laravel Idea MCP that the orchestrator uses for verification (routes, models, views).

**Rules for every agent**
1. Follow the contracts in `CLAUDE.md`. If a contract looks wrong, stop and report to the orchestrator; do not deviate.
2. Implementation agents never edit `tests/`. A test that seems wrong is reported, not changed.
3. Work in a separate git worktree per agent; one commit per defect or feature (`fix(D1): …`, `feat(admin): …`).
4. Finish only when the agent's assigned tests are green, Larastan and Pint pass, the rest of the suite is no worse than before, and the docs listed for the track are updated (see Documentation).
5. The final report goes to `docs/agent-log/` and lists files changed, tests turned green, and anything left unresolved. The orchestrator checks it with `git diff` before merging (and `git diff tests/` must be empty for implementation branches).

---

## Phases

### Phase 0 — Inputs (resolved 2026-10-16)
Seeder data, recovered from `views/labs/*.php` (`<h1>` + category badge); `sort` follows the original lab order:

| sort | slug | name | category |
|---|---|---|---|
| 1 | `osci` | OS Command Injection | backend |
| 2 | `dom-xss` | DOM XSS | frontend |
| 3 | `reflect-xss` | Reflected XSS | frontend |
| 4 | `stored-xss` | Stored XSS | frontend |
| 5 | `ssrf` | Server-Side Request Forgery | backend |
| 6 | `oauth-ato` | OAuth Account Takeover | backend |
| 7 | `rbac` | Incorrect RBAC | backend |
| 8 | `path-trav` | Path Traversal | backend |
| 9 | `type-juggle` | Type Juggling | backend |
| 10 | `insec-deser` | Insecure Deserialization | backend |

### Phase 1 — Foundation (Sonnet "Foundation", reviewed by Opus)
- `laravel new` / `composer create-project` in `appsec-laravel/`, `git init`.
- `docker-compose.yml`: `app` (php-fpm + Docker CLI + socket — platform trust boundary, documented), `nginx`, `mysql:8.0`, `redis`, `queue`, `scheduler`, `traefik` (routes `*.labs.localhost` to lab instances over the external Docker network `labs_proxy`).
- Packages: socialite, larastan, pint; Laravel Boost (dev).
- All migrations and models from the `CLAUDE.md` schema; `LabSeeder` with the 10 labs.
- Stubs: every class and route from the contracts exists, and methods throw `LogicException('not implemented')`. That way tests fail on assertions, not on missing symbols.
- `FakeLabRunner` fully implemented (it is test infrastructure).
- GitHub Actions: MySQL + Redis services, `php artisan test`, Larastan, Pint.
- **Done when:** `migrate:fresh --seed` works in Docker and CI runs (and the suite is empty).

### Phase 2 — Tests first (Sonnet "Test writer")
Writes the whole list below against the contracts. Expected result: everything red except the data-invariant tests. The orchestrator reviews the tests against the D-table before any implementation starts. This is the main review checkpoint for the owner.

### Phase 3 — Implementation (in parallel, worktrees)

| Track | Agent | Depends on | Turns green |
|---|---|---|---|
| Labs & views | Haiku | — | data invariants (views; compose files have no `stopper`, no `docker.sock`, no `container_name`, no host `ports`; the entry service has Traefik labels for `${LAB_HOST}` and joins `labs_proxy`) |
| Auth | Sonnet | phase 1 | auth tests |
| Catalog & hints | Sonnet | Haiku's views | catalog + hints tests |
| Runtime & flag | Sonnet | phase 1 | runtime, flag, queue, Redis tests |

Merge order: Haiku → Auth → Catalog → Runtime. The orchestrator runs the full suite after each merge.

### Phase 4 — Admin v1 (Sonnet "Admin")
Tests for it are already written in phase 2.
- **Progress matrix** `/admin`: users × labs; cell = status, solve time (`completed_at − started_at`), attempt count.
- **Lab analytics** `/admin/labs`: per lab: started, completed, completion %, median solve time, average attempts before success, hints released at completion (derived from solve time against the hint schedule).
- **Manual override** on `/admin/users/{user}`: reset or mark complete per lab; `reason` required; every action writes `admin_actions` in the same DB transaction.
- Admin promotion stays an artisan command (`user:promote {email}`) until user management is built.

### Phase 5 — Lab solvability (Sonnet, run locally)
For each of the 10 labs, an automated test in `@group docker`: launch an instance through `DockerLabRunner`, perform the intended exploit, read the flag, submit it through the platform, and assert success. This proves that removing `stopper` and changing mounts did not break any lab. It is not run in CI (it needs Docker and Chromium).

### Phase 6 — Per-user instances (in v1, after phase 5 is green)
- `InstanceResolver::project()` switches from `lab_{slug}` to `lab_{slug}_u{id}`; everything keyed by project keeps working, and the instance host becomes `{project}.labs.localhost`.
- Flag: each lab compose file mounts a per-instance file `storage/app/instances/{project}/flag.txt` read-only over the path the lab reads. Check that path per lab first, because today the whole lab folder is bind-mounted and `flag.txt` is shared. The runner passes the path via an env var.
- `LABS_MAX_INSTANCES` cap; XSS/OAuth labs are 6 containers (~1 GB) each.
- The phase 5 solvability suite is re-run as the acceptance test.

### Phase 7 — Public repo readiness
- README: purpose, architecture diagram, "v1 → v2: defects found and fixed" (D-table with links to tests), how to run it, CI badge.
- Prominent warning: the labs are intentionally vulnerable; run them only locally or on an isolated network.
- Check before the first push: no `.env`, `client_credentials.json`, `storage/`, or instance flag files are committed.

---

## Documentation

Documentation is written as part of each task, not after it: "docs updated" is part of every agent's definition of done, and the orchestrator rejects a merge without it.

| Doc | Audience | Contents | Created / owned by |
|---|---|---|---|
| `CLAUDE.md` | agents | Contracts (schema, routes, interfaces, Redis keys), conventions, agent rules | Orchestrator; changed only by the orchestrator |
| `docs/ARCHITECTURE.md` | humans + agents | Components, request flow, lab launch sequence, trust boundaries (Docker socket), diagrams (Mermaid) | Foundation in phase 1; each track extends its own section |
| `docs/decisions/NNNN-title.md` (ADR) | humans + agents | One short file per non-obvious decision: context → decision → consequences (e.g. 0001 Redis for flags, 0002 project-name keyed instances, 0003 tests first) | Whoever made the decision; orchestrator for contract-level ones |
| `docs/agent-log/phase-N-<track>.md` | orchestrator, owner | Agent's final report: what changed, tests turned green, open issues, deviations reported | Each agent at the end of its task |
| `docs/labs/{slug}.md` | instructors | Vulnerability class, containers, flag delivery, link to its solvability test. **No solutions** | Haiku, from inventory §7 |
| `docs/ADMIN.md` | admins | What each admin screen shows, how metrics are computed, the override and audit rules | Admin agent |
| `README.md` | public / reviewers | Purpose, quick start, architecture summary, v1 → v2 defects table with test links, safety warning, CI badge | Orchestrator in phase 7 |
| `CHANGELOG.md` | public | One entry per merged track | Orchestrator on merge |
| PHPDoc on contracts | IDE / agents | `LabRunner`, `InstanceResolver`, `FlagService`: behaviour, exceptions, invariants | Foundation (stubs), kept current by implementers |

---

## Test list (written in phase 2)

### Feature — auth
- `test_guest_is_redirected_from_every_protected_route` (data provider over all routes) — D5
- `test_google_callback_creates_user_with_user_role` — D12
- `test_admin_role_survives_relogin` — D6
- `test_callback_without_code_redirects_with_error` — D7
- `test_logout_requires_csrf_token` — D13

### Feature — catalog & hints
- `test_catalog_rejects_unknown_category`
- `test_catalog_shows_status_of_current_user_only` — D1
- `test_lab_page_renders_for_every_seeded_lab`
- `test_hints_are_hidden_before_launch`
- `test_hints_are_released_at_2_4_6_minutes_after_start` (`travel`) — D9
- `test_lab_page_html_contains_no_hint_text` — D9

### Feature + queue — runtime
- `test_launch_dispatches_launch_job` (`Queue::fake`) — D4
- `test_launch_job_marks_instance_ready` (`FakeLabRunner`) — D4
- `test_launching_lab_does_not_stop_other_labs` — D3
- `test_status_endpoint_returns_instance_status` — D4
- `test_reaper_stops_only_expired_instances` (`travel`) — D10

### Feature — flag
- `test_viewing_lab_page_does_not_change_flag` — D2
- `test_correct_flag_completes_lab_for_current_user_only` — D1
- `test_wrong_flag_records_attempt_and_keeps_progress` — D1
- `test_flag_check_is_rate_limited` — D8
- `test_correct_flag_stops_instance_and_forgets_flag` — D2

### Feature — admin
- `test_non_admin_gets_403_on_every_admin_route` (data provider)
- `test_progress_matrix_shows_status_time_and_attempts`
- `test_lab_analytics_computes_completion_rate_and_median_time`
- `test_override_requires_reason`
- `test_override_reset_and_complete_write_audit_record`
- `test_override_and_audit_are_atomic` (audit write failure rolls back the change)

### Unit
- `DockerLabRunnerTest`: builds an argument array and never a shell string; rejects a slug that is not in the DB — D11
- `FlagServiceTest`: issue → verify true; wrong flag false; verification uses `hash_equals`; forgotten flag → false
- `InstanceResolverTest`: project name format

### Integration (real Redis in CI)
- `test_concurrent_launches_start_instance_once` (`Cache::lock`) — D3
- `test_flag_expires_with_instance_ttl` — D2

### Data invariants
- `test_every_seeded_lab_has_view_and_lab_folder_with_compose_file`
- `test_no_lab_compose_file_mounts_docker_socket_or_defines_stopper` — D10

### Lab solvability (`@group docker`, local only) — phase 5
- One test per lab: `test_{slug}_is_solvable_via_intended_exploit`

---

## Open questions

None as of 2026-10-16.
