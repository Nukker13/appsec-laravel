# Phase 2 — test log (Test writer)

Branch `phase-2-tests`. Only `tests/`, `phpunit.xml` (Integration suite), `composer.json`/`composer.lock` (`symfony/yaml` in require-dev) and this log changed. Nothing under `app/`, `routes/`, `config/`, `database/`, `CLAUDE.md`.

## 1. Test table

"Red now" reasons: `Logic:X` = LogicException('not implemented') in X (HTTP tests see it as a 500 and fail on the status assertion); `view` = labs views do not exist yet; `labs/` = `labs/{slug}/` does not exist yet.

| Test | File | D | Now | Reason |
|---|---|---|---|---|
| test_guest_is_redirected_from_every_protected_route (12 routes) | Feature/AuthTest | D5 | green | real `auth` middleware |
| test_google_callback_creates_user_with_user_role | Feature/AuthTest | D12 | red | Logic:GoogleController::callback |
| test_admin_role_survives_relogin | Feature/AuthTest | D6 | red | Logic:GoogleController::callback |
| test_callback_without_code_redirects_with_error (2 failures) | Feature/AuthTest | D7 | red | Logic:GoogleController::callback |
| test_logout_requires_csrf_token | Feature/AuthTest | D13 | red | 419 part passes (real middleware), then Logic:GoogleController::logout |
| test_catalog_rejects_unknown_category | Feature/CatalogTest | | red | Logic:LabController::index |
| test_catalog_filters_by_category (extra) | Feature/CatalogTest | | red | Logic:LabController::index |
| test_catalog_shows_status_of_current_user_only | Feature/CatalogTest | D1 | red | Logic:LabController::index |
| test_lab_page_renders_for_every_seeded_lab (10) | Feature/CatalogTest | | red | Logic:LabController::show / view |
| test_hints_are_hidden_before_launch | Feature/HintsTest | D9 | red | Logic:HintController::index |
| test_hints_are_released_at_2_4_6_minutes_after_start | Feature/HintsTest | D9 | red | Logic:HintController::index |
| test_hints_follow_each_users_own_start_time (extra) | Feature/HintsTest | D9 | red | Logic:HintController::index |
| test_lab_page_html_contains_no_hint_text | Feature/HintsTest | D9 | red | Logic:LabController::show |
| test_launch_dispatches_launch_job | Feature/RuntimeTest | D4 | red | Logic:LabInstanceController::launch |
| test_launch_keeps_status_when_instance_is_already_starting_or_ready (2, extra) | Feature/RuntimeTest | D4 | red | Logic:InstanceResolver::project |
| test_launch_job_marks_instance_ready | Feature/RuntimeTest | D4 | red | Logic:InstanceResolver::project |
| test_relaunch_does_not_overwrite_started_at (extra) | Feature/RuntimeTest | D9 | red | Logic:InstanceResolver::project |
| test_launching_lab_does_not_stop_other_labs | Feature/RuntimeTest | D3 | red | Logic:LabInstanceController::launch |
| test_status_endpoint_returns_instance_status (6) | Feature/RuntimeTest | D4 | red | Logic:InstanceResolver::project |
| test_reaper_stops_only_expired_instances | Feature/RuntimeTest | D10 | red | Logic:LabInstanceController::launch / ReapLabs |
| test_reaper_is_scheduled_every_minute (extra) | Feature/RuntimeTest | D10 | **green** | `routes/console.php` already schedules it (Foundation) |
| test_viewing_lab_page_does_not_change_flag | Feature/FlagTest | D2 | red | Logic:LaunchLab / InstanceResolver |
| test_viewing_a_lab_that_was_never_launched_issues_no_flag (extra) | Feature/FlagTest | D2 | red | Logic:LabController::show |
| test_relaunching_running_instance_keeps_flag (extra) | Feature/FlagTest | D2 | red | Logic:InstanceResolver::project |
| test_correct_flag_completes_lab_for_current_user_only | Feature/FlagTest | D1 | red | Logic:LabInstanceController::launch |
| test_wrong_flag_records_attempt_and_keeps_progress | Feature/FlagTest | D1 | red | Logic:InstanceResolver::project |
| test_flag_without_running_instance_is_incorrect_and_still_recorded (extra) | Feature/FlagTest | D2 | red | Logic:FlagController::store |
| test_flag_field_is_required (extra) | Feature/FlagTest | | red | Logic:FlagController::store (500 instead of 422) |
| test_flag_check_is_rate_limited | Feature/FlagTest | D8 | red | Logic:LabInstanceController::launch |
| test_correct_flag_stops_instance_and_forgets_flag | Feature/FlagTest | D2 | red | Logic:InstanceResolver::project |
| test_relaunch_after_completion_issues_a_new_flag (extra) | Feature/FlagTest | D2 | red | Logic:InstanceResolver::project |
| test_non_admin_gets_403_on_every_admin_route (5) | Feature/AdminTest | | green | real `can:admin` gate |
| test_progress_matrix_shows_status_time_and_attempts | Feature/AdminTest | | red | Logic:ProgressController::index |
| test_lab_analytics_computes_completion_rate_and_median_time | Feature/AdminTest | | red | Logic:LabAnalyticsController::index |
| test_lab_analytics_reports_hints_released_at_completion (extra) | Feature/AdminTest | | red | Logic:LabAnalyticsController::index |
| test_user_page_shows_progress_and_override_forms (extra) | Feature/AdminTest | | red | Logic:UserController::show |
| test_override_requires_reason (6: reset/complete x absent/empty/blank) | Feature/AdminTest | | red | stub throws before validation: no `reason` error |
| test_override_reset_and_complete_write_audit_record | Feature/AdminTest | D1 | red | Logic:ProgressOverrideController::reset |
| test_override_without_previous_progress_records_null_meta (extra) | Feature/AdminTest | | red | Logic:ProgressOverrideController::complete |
| test_override_and_audit_are_atomic (reset, complete) | Feature/AdminTest | | red | audit insert never attempted (counter assertion) |
| DockerLabRunnerTest: argument array + instance env (3), down/isRunning arrays, slug whitelist (5), up failure | Unit/DockerLabRunnerTest | D11 | red | Logic:DockerLabRunner::up/down/isRunning |
| FlagServiceTest (10 tests) | Unit/FlagServiceTest | D2 | red | Logic:FlagService::issue/verify; hash_equals one: source has no `hash_equals(` |
| InstanceResolverTest (10 + 1) | Unit/InstanceResolverTest | | red | Logic:InstanceResolver::project |
| HintServiceTest (2, extra) | Unit/HintServiceTest | D9 | red | Logic:HintService::due |
| UserCommandsTest (3, extra) | Feature/UserCommandsTest | D6 | red | Logic:PromoteUser/DemoteUser::handle |
| test_concurrent_launches_start_instance_once | Integration/ConcurrentLaunchTest | D3 | red | Logic:InstanceResolver::project (before the workers start) |
| test_flag_expires_with_instance_ttl | Integration/RedisIntegrationTest | D2 | red | Logic:FlagService::issue |
| test_every_seeded_lab_has_view_and_lab_folder_with_compose_file (10) | Feature/LabDataInvariantsTest | D12 | red | view, labs/ missing |
| test_no_lab_compose_file_mounts_docker_socket_or_defines_stopper (10) | Feature/LabDataInvariantsTest | D10 | red | labs/ missing |
| test_no_lab_service_has_container_name_or_published_ports (10, extra split) | Feature/LabDataInvariantsTest | D3 | red | labs/ missing |
| test_entry_service_is_routed_by_traefik_on_labs_proxy (10, extra split) | Feature/LabDataInvariantsTest | D3/D4 | red | labs/ missing |

Green now (18 of 155), all explained: 12 guest redirects (real `auth`), 5 non-admin 403 (real gate), 1 schedule registration (real `routes/console.php`).

## 2. Summary line

`php artisan test` (stubs, no implementation): `Tests: 137 failed, 18 passed (168 assertions)`. `pint --test` PASS (74 files), `phpstan analyse` OK.

## 3. Would the test catch the defect?

- **D1 `test_correct_flag_completes_lab_for_current_user_only`**: pass if the implementation updated `lab_user` by `lab_id` only? No. Bob (same lab, started) must stay `started` with null `completed_at`, and Carol's earlier `completed_at` must be unchanged. A `WHERE lab_id=?` update changes both.
- **D1 `test_wrong_flag_records_attempt_and_keeps_progress`**: pass if a wrong flag changed anyone's progress or attempts were attributed to the wrong user? No. Bob's pivot, Bob's attempt count and the real flag are all asserted.
- **D1 `test_catalog_shows_status_of_current_user_only`**: pass if the catalog showed lab status globally? No. The newcomer's catalog must differ from the solver's; a global status makes them identical. Also the starter must differ from the newcomer.
- **D1 admin `test_override_reset_and_complete_write_audit_record`**: pass if reset/complete touched other users? No. The bystander's rows (completed osci, started rbac) are asserted unchanged.
- **D2 `test_viewing_lab_page_does_not_change_flag`**: pass if page views (also by a second user), status or hints calls regenerated the flag? No. The file content and the original flag's `verify()` are checked after repeated views.
- **D2 `test_correct_flag_stops_instance_and_forgets_flag`**: pass if the flag stayed valid or the instance kept running after completion? No. `verify()` must be false, the fake must record `down`, status must be `stopped`.
- **D2 `FlagServiceTest` / `test_flag_expires_with_instance_ttl`**: pass if the plaintext were cached, the TTL missing or too long? No. The cache value must equal sha256, and real Redis `TTL` must be in (420-30, 420] for `LAB_MAX_MINUTES=7`. `forget` must delete the key.
- **D3 `test_launching_lab_does_not_stop_other_labs`**: pass if launching stopped other instances? No. `downCalls` must be empty and all three projects still running.
- **D3 `test_concurrent_launches_start_instance_once`**: pass if `LaunchLab` had no lock? No. Two real PHP processes run the job against real Redis/MySQL with a runner whose `up()` takes 1.5 s; the shared up-log must have exactly one line. I verified the harness with a throwaway minimal `LaunchLab` (reverted, `app/` untouched): with `Cache::lock(...)->block()` it passed, without the lock it failed with "started more than once (size 2)".
- **D4 `test_launch_dispatches_launch_job` / `test_launch_job_marks_instance_ready` / `test_status_endpoint_returns_instance_status`**: pass if the request ran `up` synchronously (the old `sleep(5)` design) or readiness was not tracked? No. Queue::fake sees the job and no `up` call, and no `lab_user` row; the status endpoint reads the cached status and shows `url` only for `ready`.
- **D8 `test_flag_check_is_rate_limited`**: pass if there were no limit, a global limit, or a limit not per user+lab? No. The 11th call must be 429 (also for the correct flag), the 10 allowed attempts are recorded and the throttled ones are not, and the same lab for another user and another lab for the same user stay 200.
- **D9 `test_hints_are_released_at_2_4_6_minutes_after_start`**: pass if the clock restarted on reload, or hints were released early/late? No. It checks 1:59 (none), 2:00, 4:00, 6:00 boundaries, `next_at`, and a lab page view in between, and `started_at` unchanged.
- **D9 `test_lab_page_html_contains_no_hint_text`**: pass if hints sat in the HTML hidden with CSS? No. Unique hint strings must be absent for a fresh user, a user with one hint released, and a user with all released.
- **D9 `test_hints_are_hidden_before_launch`**: pass if hints depended on someone else's start? No. Another user's progress and this user's progress on another lab exist, and the response must be exactly `{"hints":[]}`.
- **D10 `test_reaper_stops_only_expired_instances`**: pass if the reaper stopped everything, or nothing, or ignored the idle limit? No. At 14 min nothing is stopped, at 16 min only the instance idle for 16 min is stopped (flag forgotten, status `stopped`); the 6-min-idle one keeps running with a valid flag.
- **D10 data invariants**: pass if a `stopper` service or `docker.sock` (any volume syntax, or any string in the file) remained? No, the recursive scan fails. I verified the parsing with a throwaway compose file (a socket volume and a `ports` entry both made the tests fail).
- **D11 `DockerLabRunnerTest`**: pass if commands were shell strings? No. The factory seam is typed `array $command` (TypeError for strings), every argument is checked for shell metacharacters, and a runner that bypasses the factory leaves `calls` empty. Slug whitelist: `ghost`, `osci; touch ...`, `$(id)`, `../osci` and empty are rejected with no process created.
- **D5**: pass if a route had no `auth`? No: all 12 non-public routes are requested as guest and must redirect to `home`.
- **D6 `test_admin_role_survives_relogin`**: pass if the callback wrote `role`? No. An admin with the same google_id logs in and must stay `admin`, with no second user.
- **D7 `test_callback_without_code_redirects_with_error`**: pass if the callback ran on after the failure (user created or crash)? No. Redirect to `home` with the `error` flash, guest, zero users, for both a state exception and a rejected token exchange.
- **D12 `test_google_callback_creates_user_with_user_role`**: pass if the new user got a different role or the callback did not persist? No: row, role `user`, authenticated.
- **D13 `test_logout_requires_csrf_token`**: pass if CSRF were not enforced? No. See assumption 1: with the environment reported as non-testing the real middleware answers 419 without a token and redirects with the right token.
- **Atomicity `test_override_and_audit_are_atomic`**: pass if the override committed before/outside the audit insert? No. A `creating` listener on `AdminAction` throws; the override must be rolled back, and the listener must have fired once (so the audit really goes through the model).

## 4. Spec assumptions

1. **CSRF**: `PreventRequestForgery` skips checks when `app->runningUnitTests()` (env `testing`). The test calls `$this->app->detectEnvironment(fn () => 'local')` so the real middleware runs. No `app/` change. The token is supplied via `withSession(['_token' => ...])`.
2. **Socialite** is mocked as `Socialite::driver()` returning a `GoogleProvider` mock that tolerates `stateless/scopes/with` and returns a `Two\User` from `user()`. For D7 the mock throws `InvalidStateException` or a Guzzle `ClientException`; the request has no `code`. If the implementation checks the query itself and never calls Socialite, the test still passes.
3. **LaunchLab creates the `lab_user` row for each launching user** even when the instance is already running (needed by several tests where two users share the phase-3 project). Contract: "sets started_at on first launch".
4. **Catalog status** has no specified markup. The D1 catalog test compares whole pages (name, email and CSRF token normalised): user without progress != user with a completed lab != user with a started lab. It assumes started and nothing are rendered differently.
5. **Hints**: `n` is only checked to exist (not 1- or 0-based). `next_at` is parsed with Carbon and compared to the instant. `next_at` absent when all are released. Boundary "exactly at `after` minutes" counts as released.
6. **Admin views** (all asserted on `strip_tags` text): status words `completed`/`started` case-insensitive; solve time "47" for 47 min 20 s (any format containing the minutes number); attempts "7" as an isolated number; analytics: "60" (3 of 5), median "23" (mean would be 25), average attempts "14" (not-finished users excluded), hints released "12" with solve time "38". These are weak by design (a coincidental number in the page would satisfy them); numbers were chosen to be distinct. User page: user name, lab name, and the reset/complete route paths appear in the HTML.
7. **Override meta**: only checked that the previous state's status word occurs in `json_encode(meta)` (or `null` when there was no row). Redirect target is the `Referer` (`->from(...)`). A reset on a user without a row still writes an audit row with `meta = null`.
8. **Reaper discovery**: instances are registered by `LaunchLab` (test only uses launch + time travel, never the `labs:running` representation). `last_seen` is assumed refreshed by a launch. `LAB_IDLE_MINUTES` is set through `config('labs.idle_minutes')`.
9. **DockerLabRunner**: rejection of a bad slug is a `LabRunnerException` for `up()` (for `down()` rejecting is allowed but not required, a process must not run). `up` is detected as a process whose argv contains `up`; the compose file is looked for as `labs/{slug}/docker` in the argv or cwd; env keys `COMPOSE_PROJECT_NAME`, `LAB_HOST`, `LAB_FLAG_FILE` come through the factory's third argument. `isRunning` only has to build array commands and return a bool (its output format is not specified).
10. **FlagService hash_equals** is checked by reading `app/Services/FlagService.php` for `hash_equals(`; it is not observable otherwise. Expiry: the array cache honours `travel()`, so the unit test uses 119/121 minutes against `labs.max_minutes=120`.
11. **InstanceResolverTest** asserts the phase-3 format and must be changed together with phase 6.
12. **Data invariants**: "only the entry service joins `labs_proxy`" (from the phase-1 review decision, not from the CLAUDE.md contract text) and `labs_proxy` is declared `external` in the compose file. The Traefik label may be a list or a map; any `traefik.*` label whose value contains `Host(` counts.
13. **Integration**: uses Redis DB 15 (platform uses 0 and 1), set in the test (`UsesRealRedis`), flushed around each test. `ConcurrentLaunchTest` commits real rows (no `RefreshDatabase`, runs `migrate` first) and deletes them in `tearDown`; it refuses to run unless the DB name is `testing`. Phase 3 flag path (`labs/osci/flag.txt`) is written by the job; `tests/TestCase::tearDown` and the tests delete flag files they caused.
14. **Concurrency is done with two `Symfony\Process` children** (`tests/Integration/launch_worker.php`), not `pcntl_fork`: a fork would share the parent's database socket and the RefreshDatabase transaction. The workers boot the app from env vars and bind `Tests\Support\SharedFileLabRunner` (file-based, 1.5 s `up()`).
15. `Integration` was added to `phpunit.xml` as its own suite, so `php artisan test` runs it (CI has Redis). Group `redis` allows `--exclude-group=redis`.
16. `throttle` test relies on the array cache (the rate limiter), no window reset is tested.
17. Extra tests beyond the plan list are marked "extra" in the table (contract behaviour that the list did not cover: status/launch edge cases, started_at never overwritten, hint service, user commands, user page).

## 5. Not written

- Lab solvability (phase 5) as instructed.
- Nothing else from the list is missing. Not tested because not observable: the exact cache key layout of `labs:running`, `last_seen` internals, the failure path of `LaunchLab` (`failed` status): the contract does not say what the job does when `LabRunner::up` throws.

## Addendum: unverified Google email

`AuthTest::googleUser()` now returns a user whose raw payload has `email_verified => true`. New: `test_callback_refuses_unverified_google_email` (false, missing, string "false": redirect `home` + `error`, no users row, guest) and `test_callback_refuses_email_owned_by_another_google_account` (existing user's google_id, email, name, role and `updated_at` unchanged, still one user). The first is red until the implementation checks `email_verified`; the second was not covered before.

## Addendum: phase 6 tests (per-user instances)

Changed to the new contract: `TestCase` (fresh temp `labs.instances_path` per test, removed in tearDown; the `labs/` flag sweep is gone), `InstanceResolverTest` (`lab_{slug}_u{id}`), `FlagServiceTest::flag_file_path` (`{instances_path}/{project}/lab/flag.txt`), `DockerLabRunnerTest` (rewritten: temp `labs.path` with fake labs; compose from the instance copy; `down` with `-v --remove-orphans --rmi local`), and the integration worker (`launch_worker.php` takes the instances dir).

New, all red against the pre-phase-6 implementation:
- `InstancesTest::test_two_users_get_separate_instances_and_flags` (D2/D3): shared project name, shared flag, Alice solving stops Bob's.
- `InstancesTest::test_instance_urls_are_per_user` (extra): URL is `lab-{slug}-u{id}.labs.localhost`.
- `InstancesTest::test_flag_file_exists_before_up`: current order is up then issue. Uses `Tests\Support\SpyLabRunner`, which records the flag file at the moment `up()` runs. It also checks that flag is the final one.
- `InstancesTest::test_failed_up_forgets_flag`: spy runner fails `up()`. It first asserts a flag existed at that moment (otherwise `isIssued() == false` would be vacuous, which is why it is red today), then `isIssued` false, status `failed`, no `lab_user` row.
- `InstancesTest::test_launch_is_refused_at_capacity`: `labs.max_instances=2` is not enforced today (202).
- `InstancesTest::test_capacity_frees_up_when_an_instance_stops` (extra): red because the third launch is not refused.
- `DockerLabRunnerTest::test_runner_up_copies_lab_and_down_removes_instance_dir`: no copy, no removal today.
- `DockerLabRunnerTest::test_up_keeps_a_flag_that_was_issued_before_the_copy_existed` (extra): LaunchLab issues the flag first, which creates `{instance}/lab/flag.txt`; `up()` must still copy the lab and must not overwrite that flag.
- `DockerLabRunnerTest::test_down_removes_volumes_and_local_images_from_the_instance_copy`.

Assumptions: the copy contains the `docker/storage` and `storage` directories but none of their contents (including subdirectories such as `docker/storage/mysql`); a pre-existing `{instance}/lab` containing only `flag.txt` still gets the lab copied. Capacity counts running instances by project, so a second lab for a user who already runs one counts as new.

Note: with the current implementation, tests that launch labs still write `labs/{slug}/flag.txt` (ignored by git). That goes away with `flagFile` under `labs.instances_path`.
