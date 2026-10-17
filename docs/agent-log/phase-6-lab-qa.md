# Phase 6: functional QA of the 10 labs

Date: 2026-10-17. Method: test user `qa-1` (id 4), real queue, `LaunchLab`/`StopLab` dispatched through tinker, lab pages walked with curl (normal, non-attack requests only), `FlagService` and `HintService` called directly. At most 2 QA instances ran at once (in practice 1). Nothing under `labs/`, `app/`, `tests/`, `config/`, `resources/`, `docs/labs/` was edited. No exploits were written and no lab was solved.

Startup time is the time until `lab:{project}:status` became `ready`, measured by polling (so +-2 s). osci was not polled, its value is the `LaunchLab` job duration from the queue log.

## Summary table

| slug | startup | ready? | normal pages OK? | doc/view match? | flag ok / wrong ok | hints ok |
|---|---|---|---|---|---|---|
| osci | ~3 s (queue log) | yes | partial: GET / and `?iframe=1` 200; command form not exercised (see "Not checked") | view ok; doc flag-location text stale | ok / ok | ok (3 of 3 per schedule) |
| dom-xss | 17 s (image build) | yes | **NO: every page is a PHP fatal (P1)** | view mismatch (P4), doc stale | ok / ok | ok (2) |
| reflect-xss | 17 s | yes | **NO: same fatal (P1)** | view mismatch (P4), doc stale | ok / ok | ok (1) |
| stored-xss | 6 s | yes | **NO: same fatal (P1)** | view mismatch (P4), doc stale | ok / ok | ok (3) |
| ssrf | 4 s | yes | yes (all 200, counts 3/2/9, images load) | view code example differs from real code (P5); doc flag text stale | ok / ok | ok (no hints, none configured) |
| oauth-ato | 16 s | yes | yes (/, /form, /oauth/provider, /oauth/start, /report, /secret; /flag 403 for a user) | view ok (`/tmp/oauth_state` matches code); doc flag text stale | ok / ok | ok (2) |
| rbac | 4 s | yes | yes (dashboard 200, no warnings); `/flag` deliberately not requested | view ok | ok / ok | ok (none configured) |
| path-trav | 4 s | yes | yes (gallery, 3 images served, upload of a PNG then viewed) | view ok; hint contradicts app (P6) | ok / ok | ok (1) |
| type-juggle | 4 s | yes | yes (register, duplicate register, bad login, good login, logout; sqlite DB auto-created) | view ok | ok / ok | ok (2) |
| insec-deser | 4 s | yes | partial: 3-step booking flow works, but PHP warning on summary page (P3) | view ok | ok / ok | ok (2) |

Platform flow, identical for all 10 labs: `isIssued` true after launch; the flag file at `FlagService::flagFile()` exists and holds 64 chars; `verify` returns false for `"wrong-flag"` and `""`, true for the file content, false for content + `"\n"` (exact match, as expected). Hints: for t = 0, 1, 3, 5, 7, 30 minutes since `started_at` the number of due hints equals the number of `config/hints.php` entries with `after <= t`, and `nextAt` is correct (null when all are released). I shifted `started_at` in memory only, nothing was saved. `StopLab`: for every lab the containers, images, named volumes and `storage/app/instances/{project}` were gone after the job, status `stopped`.

The `labs.flag` HTTP route (throttle, `flag_attempts` rows, completion) was not exercised, only the services. `flag_attempts` for the QA user: 0 rows.

## Problems

### P1 (blocker). dom-xss, reflect-xss, stored-xss: composer `vendor/` is hidden by the bind mount, every page is a PHP fatal

- Labs: dom-xss, reflect-xss, stored-xss (the owner's own `lab_dom_xss_u1`, `lab_reflect_xss_u1`, `lab_stored_xss_u1` instances show the same).
- Repro: launch the lab, then `curl -s http://lab-dom-xss-u4.labs.localhost/` (also `/facts/cat`, `/form`, `/flag`, any path).
- Actual: HTTP 200 with an xdebug HTML error:
  `Warning: require_once(/labs/dom_xss/vendor/autoload.php): Failed to open stream: No such file or directory in /labs/dom_xss/router.php on line 7` followed by `Fatal error: Uncaught Error: Failed opening required '/labs/dom_xss/vendor/autoload.php'`. Reflect: `/labs/reflect_xss/vendor/autoload.php`; stored: `/labs/stored_xss/vendor/autoload.php`. Status is 200, so the lab looks "up" to Traefik and to `labs.status`.
- Expected: the app pages (facts pages, report form, message board).
- Cause: each Dockerfile does `COPY --from=vendor /app/vendor /labs/<dir>/vendor` (verified: the image contains `vendor/autoload.php`, `docker run --rm --entrypoint ls <image> /labs/dom_xss/vendor`), but the compose file bind-mounts `../:/labs/<dir>` over `/labs/<dir>`, and the instance copy of `labs/{slug}` has no `vendor/`. The host dir wins, vendor disappears.
- Contrast: `oauth-ato` works because its compose declares a named volume `oauth_lab_vendor:/labs/oauth_lab/vendor` on the `app` service (see `labs/oauth-ato/docker/docker-compose.yml` lines 1-3 and 18). The same line is missing from dom-xss, reflect-xss and stored-xss compose files. The Dockerfiles of those three are otherwise identical in structure.
- Consequences seen in logs: the admin bot (`docker compose -p lab_reflect_xss_u4 logs bot`) polls `/next-report`, receives the PHP error HTML as a "URL" and logs `Bot visiting: <br /> <font size='1'>...Failed opening required...`. The whole XSS family is unsolvable in this state. The DB is fine (database `xss_lab`, tables `users` and `reports`, seeded: stored-xss has 2 users, 3 messages).
- Proposed fix (for the orchestrator, `labs/*/docker/docker-compose.yml` only): add a named volume for `vendor` on the `app` service as oauth-ato does. Not applied (QA agent may not edit).

### P2 (minor, race). `ready` is reported before the lab database accepts connections

- Labs with a `db` service (dom-xss, reflect-xss, stored-xss, oauth-ato).
- Repro: launch oauth-ato, wait for `ready` (16 s), query within the first ~10-15 s.
- Actual: `mysql -uroot -proot` gave `ERROR 1045 Access denied` while the MySQL init was still running (stored-xss, first query); the oauth-ato bot logged `SQLSTATE[HY000] [2002] Connection refused` in `OAuthController->nextReport` (fetch from `reports`), then `WebDriver error: invalid argument` because it tried to open that error HTML as a URL. A short while later queries worked. The container stayed `running`.
- Expected: either `ready` after the DB is up, or labs tolerating a short warm-up. Students hitting the lab in the first ~15 s may see a DB connection error. The platform only waits for `compose up` to return (no healthcheck on `db`).

### P3. insec-deser: invoice directory missing, PHP warning on every normal run

- Repro: `POST /book` (room_type, check_in, check_out, guests), `POST /book/details` (names, email), `GET /book/summary` with the same cookie jar.
- Actual: HTTP 200, but the page contains `Warning: file_put_contents(/labs/insec_deser/storage/invoices/20261017_114025_2be8db9f.txt): Failed to open stream: No such file or directory in /labs/insec_deser/index.php on line 31`.
- Expected: invoice written to `storage/invoices/` with no warning.
- Cause: `labs/insec-deser/` has no `storage/invoices/` directory (the runner creates an empty `storage/` in the copy, but `invoices/` is not in the source and the Dockerfile does not create it). The exploitable sink (`Invoice::__destruct` writing to an attacker-controlled `path`) is still reachable, so the lab is probably still solvable (not tested), but the baseline page shows a warning that students will see.

### P4. View text for dom-xss, reflect-xss and stored-xss shows the same, unrelated code sample

- `resources/views/labs/{dom-xss,reflect-xss,stored-xss}.blade.php` all contain the same "Vulnerable Code Example" (`require_once 'auth_moderator_or_admin.php'; ... User profile ... $user['username_raw']`). `auth_moderator_or_admin.php` and a "User profile" page do not exist in any of the three labs. The descriptive text is fine.

### P5. ssrf view code example differs from the real code

- View: `$imageUrl = $_GET['url'] ?? ''; ... file_get_contents($imageUrl); header('Content-Type: image/jpeg')`. Real app (`labs/ssrf/app/index.php`): the page uses `?image=cats|dogs|shrimps&action=count` with `imageUrl`, plus a separate `/proxy?url=` endpoint. Hint-free lab, so students rely on the description only.

### P6. path-trav hint contradicts the app

- `config/hints.php` hint 1 says "Upload a .php file containing shell_exec() ... include it through the file parameter". `labs/path-trav/index.php` rejects uploaded `.php` files containing `exec(`, `shell_exec(`, `system(`, `passthru(`, `popen(`, `proc_open(`, `pcntl_exec(` with `400 Don't hack me bro!`. Whether the lab is solvable some other way was not tested; the hint as written is misleading.

### P7. docs/labs/{slug}.md "Flag Delivery" is stale for all 10 labs

- Every doc says "Host file `labs/{slug}/flag.txt` (written by `FlagService::issue`) ... Phase 6 mounts the per-instance file ... read-only". Reality: the flag is written to `storage/app/instances/{project}/lab/flag.txt` in the per-instance copy of the lab, and the compose bind mount `../:/labs/{dir}` (ssrf: `../flag.txt:/labs/SSRF/flag.txt:ro` on `admin`) points at that copy. Only `labs/osci/flag.txt` exists in the source tree (committed file; the others have none). "Solvability Test: Phase 5 (pending)" is also still in every doc. Container tables and service lists match the compose files (checked: osci, dom-xss, reflect-xss, stored-xss, ssrf, oauth-ato, rbac, path-trav, type-juggle, insec-deser: services, networks, aliases).

### Other observations (not bugs)

- `labs/osci/flag_check.php` and `labs/stored-xss/msg_handler.php` are leftovers from the old app: they `require` a `vendor`/`db.php` that do not exist in the lab, so a direct request errors out. They are not linked from any page. Not touched.
- `LaunchLab::dispatch` called directly skips the `labs.launch` capacity check; the owner already had 4 instances (the `labs.max_instances` default), so my instances ran above the cap. Nothing failed because of it.
- While I was working, the owner's `lab_dom_xss_u1` and `lab_ssrf_u1` disappeared and `lab_rbac_u1` appeared. I did not touch u1; the first two are consistent with the idle reaper.

## Where the owner should look by hand

- osci: `GET /` shows one text field "Send your OAUTH Token" posting to `/?iframe=1`, result in an iframe. `GET /?iframe=1` without POST answers `Wrong token!`. The field accepts only commands from an allow-list (`ls cat pwd find grep echo flag.txt`) and rejects `; & | > <`. I did not submit anything to it. Start here to confirm the filter bypass is reachable.
- dom-xss, reflect-xss, stored-xss: blocked by P1. After the vendor fix check: `/facts/dog`, `/facts/cat`, `/facts/shrimp` (dom-xss), `/facts/404` (reflect), `/form` + `POST /report` (dom, reflect), message board and `/admin`, `/student`, `/secret` (stored), and that the bot picks up reports (every ~15 s).
- ssrf: main page with image selector (cats/dogs/shrimps), `Count` button (`action=count`) answers `Found: 3/2/9`, static images load, `admin` service is up on port 9090 (log line `Development Server (http://0.0.0.0:9090) started`) and has the flag mounted read-only (non-empty file). The `/proxy` endpoint and `imageUrl` parameter are the vulnerable parts; not exercised.
- oauth-ato: lands logged in as `student@example.com` (USER). "Connect with Google/Apple" (`POST /oauth/start`) redirects to `/oauth/provider?state=...&provider=...&mode=link` (works for both providers). `/form` + `POST /report` accepts a URL (302 back to `/form`). `/secret` logs the visitor in as admin (id 2) and prints the session; `/flag` is 403 for a user. The state is a global file `/tmp/oauth_state` as the view says. Bot behaviour after a report was not verified (the first poll hit P2).
- rbac: dashboard auto-logs in as `student`; link to `/flag` is in the page. `/flag` itself was not requested.
- path-trav: gallery with a select box (`shrimp1-3.jpg`, served as `image/jpeg` via `/?f=`) and an upload form. A PNG upload is accepted and viewable afterwards. `?f=` containing `flag` returns 403 per the code (not requested). Check P6 before relying on the hint.
- type-juggle: `/register` (username, password, algorithm md5/sha1/sha256) and `/` login form work; wrong credentials give "Invalid username or password."; `users.db` (sqlite) is created automatically in the instance copy with the seeded `admin` row. The dashboard for a normal user renders without the flag.
- insec-deser: `/` -> `/book` -> `/book/details` -> `/book/summary`, booking cookie `booking` is set (not HttpOnly) after step 1. Summary page shows the name and the P3 warning.

## Not checked

- The HTTP routes `labs.launch`, `labs.status`, `labs.hints`, `labs.flag` and the admin pages (service layer only).
- osci: the command form with real input. My first attempt (a batch of benign POSTs to the form) was denied by the auto-mode permission classifier, so I did not retry it. osci's startup time comes from the queue log, not from polling.
- rbac `/flag`, path-trav `?f=` with a `flag` string, stored-xss `/admin` etc.: deliberately not requested (solution paths) or blocked by P1.
- Bot flows (report -> admin bot visit) for every XSS/oauth lab.
- Any exploit, solvability, or whether the vulnerable functionality triggers.

## Cleanup

`qa-1` (id 4) deleted with its 10 `lab_user` rows (0 `flag_attempts`); its `lab:*_u4:*` cache keys were removed. `docker ps -a`, `docker volume ls`, `docker images` and `storage/app/instances` contain nothing of project `*_u4`. The owner's user and `*_u1` instances were not touched. Scratch scripts (`t.sh`, `launch.sh`, `stop.sh`, `flow.sh`) are in the session scratchpad only.
