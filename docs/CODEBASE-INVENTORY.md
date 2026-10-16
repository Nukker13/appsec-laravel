# AppSec Training Platform — Codebase Inventory

**Project**: Internal AppSec CTF training platform with intentionally vulnerable labs  
**Language**: PHP (custom MVC, no framework)  
**Purpose**: Educational — labs are INTENTIONALLY vulnerable for learning web security

---

## 1. File Tree — Main App

```
backend_project_private/
├── index.php                 # Bootstrap, loads .env, includes routes.php
├── router.php                # Built-in server router delegate
├── routes.php                # Main route switch statement, handler dispatches
├── docker_loader.php         # Obsolete/deprecated loader (starts osci_lab on request)
├── composer.json             # Dependencies: Google API, Dotenv, Spatie Docker, Doctrine DBAL
├── .env.example              # Template: CLIENT_ID, CLIENT_SECRET, REDIRECT_URI
├── .env                       # Live secrets (not in git)
├── .gitignore                # Standard exclusions
├──
├── Controllers/
│   ├── LoginController.php    # Google OAuth login/callback/logout
│   ├── LabController.php      # Lab lifecycle: show, checkFlag, runDocker
│   └── DockerController.php   # Shell exec to docker compose up/down; hardcoded paths
├──
├── Models/
│   ├── User.php              # Upsert on google_id; session login
│   └── Lab.php               # Flag generation/validation; progress tracking
├──
├── helpers/
│   ├── SessionManager.php    # Session wrapper; login/logout; requireRole()
│   └── Config.php            # DBAL config from env vars
├──
├── views/
│   ├── index.php             # Landing: Google login button
│   ├── selector.php          # Category picker: Frontend / Backend
│   ├── lab_list.php          # Labs in category (from DB)
│   ├── partials/
│   │   └── head.php          # Global CSS: typography, colors, spacing, components
│   └── labs/
│       ├── osci.php          # OS Command Injection lab description
│       ├── dom-xss.php       # DOM XSS lab description
│       ├── reflect-xss.php   # Reflected XSS lab description
│       ├── stored-xss.php    # Stored XSS lab description
│       ├── ssrf.php          # SSRF lab description
│       ├── oauth-ato.php     # OAuth ATO lab description
│       ├── rbac.php          # RBAC lab description
│       ├── path-trav.php     # Path Traversal lab description
│       ├── type-juggle.php   # Type Juggling lab description
│       └── insec-deser.php   # Insecure Deserialization lab description
├──
└── docker/
    ├── docker-compose.db.yml # Main DB service: mysql:8.0, port 3306, storage/mysql:/var/lib/mysql
    └── .env                  # Not used; examples only
```

---

## 2. Routes — Main App

| URI | Method | Handler | Auth/Role Check |
|-----|--------|---------|-----------------|
| `/` | GET | `views/index.php` (inline) | No |
| `/login` | POST | `LoginController::login()` | No |
| `/auth/callback` | GET | `LoginController::callback()` | No |
| `/selector` | GET | `views/selector.php` (inline) | No (but session assumed) |
| `/logout` | POST | `LoginController::logout()` | No |
| `/labs?category={frontend\|backend}` | GET | `LabController::showSelector($category)` | No |
| `/lab/{slug}` | GET | `LabController::showLab()` | No (shows instructions) |
| `/lab/{slug}/flag-check` | POST | `LabController::checkFlag()` | No (flag checked against file) |
| `/lab/{slug}/docker` | GET | `LabController::runDocker()` → `DockerController::run($slug)` | No |

**Special routes in `routes.php`**: Routes with custom handlers defined in `$labRoutes` array (currently empty in main app; labs define their own routing).

---

## 3. Main App Classes

### Controllers/LoginController.php

**Public Methods:**

- `login(): void` — Line 16
  - Creates Google OAuth client from `client_credentials.json`
  - Sets offline/force consent, requests email + profile scopes
  - Redirects to Google's auth URL

- `callback(): void` — Line 33
  - Receives `$_GET['code']` from Google OAuth
  - Fetches access token, calls Google Oauth2 API for user info
  - Calls `User::userEntry($email, google_id, 'user')` to upsert in DB
  - Calls `SessionManager::login($google_id, $email, 'user')` to store in `$_SESSION`
  - Redirects to `/selector`

- `logout(): void` — Line 64
  - Calls `SessionManager::logout()` (destroys session)
  - Redirects to `/`

**DB Queries:**
```sql
INSERT INTO users (username, google_id, role, last_login)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        username = VALUES(username),
        google_id = VALUES(google_id),
        role = VALUES(role),
        last_login = NOW()
```

**Side Effects:**
- Reads `__DIR__ . '/client_credentials.json'` (hardcoded path, must exist)
- Sets `Location` headers

---

### Controllers/LabController.php

**Public Methods:**

- `__construct(?string $slug = null)` — Line 15
  - Initializes `Lab` instance with slug (defaults to 'osci' if null)
  - Initializes `DockerController` instance

- `showSelector(string $category): void` — Line 28
  - Calls `Lab::getLabsByCategory($category)` (category: 'frontend' or 'backend')
  - Includes `views/lab_list.php` with `$list` variable

- `showLab(): void` — Line 33
  - Calls `$this->lab->startLab()` (generates flag, logs progress)
  - Includes `views/labs/{slug}.php` if file exists, else 404

- `checkFlag(string $_POST['flag']): void` — Line 47
  - Calls `Lab::checkFlag($flag)` (string comparison against file)
  - On success: calls `Lab::labDone()`, `DockerController::stop()`, echoes 'Success!'
  - On failure: echoes 'Try again!'

- `runDocker(): void` — Line 62
  - Calls `DockerController::run($slug)`

**DB Queries:**
```sql
SELECT name FROM labs WHERE slug = ?
SELECT id FROM labs WHERE name = ? LIMIT 1
SELECT id FROM users WHERE username = ? LIMIT 1
INSERT IGNORE INTO user_lab_progress (user_id, lab_id, started_at) VALUES (?, ?, NOW())
UPDATE user_lab_progress SET completed_at = NOW(), status = 'completed' WHERE lab_id = ?
SELECT name, slug FROM labs WHERE category = ?
```

**Side Effects:**
- Generates and writes to `labs/{slug}/flag.txt`
- Includes lab-specific view files

---

### Controllers/DockerController.php

**Public Methods:**

- `run(?string $labName): void` — Line 16
  - **CRITICAL**: Shells out to stop other running labs: `docker ps --format "{{.Names}}"`, then `docker compose down` for each `{*}_lab` container not matching current lab
  - Checks if current lab is running; if not, execs `docker compose up -d` from `labs/{labName}/docker/`
  - Sleeps 5 seconds, redirects to `http://localhost`
  - **Hardcoded paths**: `labs/{labName}/docker` (no validation of labName)

- `stop(?string $labName): void` — Line 35
  - Execs `docker compose down` from `labs/{labName}/docker/`
  - Deletes storage at `labs/{labName}/docker/storage` and `labs/{labName}/storage` using `find -mindepth 1 -delete`
  - Uses `escapeshellarg()` for path parameter

**Shell Commands Executed:**
```bash
docker ps --format "{{.Names}}"
cd labs/{labName}/docker && docker compose down
cd labs/{labName}/docker && docker compose up -d
find {path} -mindepth 1 -delete 2>/dev/null
```

**Side Effects:**
- Modifies Docker state
- Deletes lab storage directories

---

### Models/User.php

**Public Methods:**

- `__construct()` — Line 14
  - Creates Doctrine DBAL connection via `Config::db()`

- `userEntry(string $name, string $googleId, string $role): array` — Line 18
  - **Upsert query**: `INSERT INTO users ... ON DUPLICATE KEY UPDATE ...`
  - Returns fetched user row from DB

**DB Queries:**
```sql
INSERT INTO users (username, google_id, role, last_login)
    VALUES (?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE
        username = VALUES(username),
        google_id = VALUES(google_id),
        role = VALUES(role),
        last_login = NOW()
SELECT * FROM users WHERE google_id = ? LIMIT 1
```

---

### Models/Lab.php

**Public Methods:**

- `__construct(?string $slug = null)` — Line 27
  - Fetches lab name from DB: `SELECT name FROM labs WHERE slug = ?`
  - Fetches lab ID: `SELECT id FROM labs WHERE name = ? LIMIT 1` (uses name, not slug)
  - Fetches current user ID: `SELECT id FROM users WHERE username = ?`
  - Sets `$this->flagPath = __DIR__ . '/../labs/' . $this->slug . '/flag.txt'`

- `getLabsByCategory(string $category): array` — Line 53
  - Returns `SELECT name, slug FROM labs WHERE category = ?`

- `startLab(): void` — Line 79
  - Calls `generateFlag()` (generates random SHA256, writes to file)
  - Inserts into `user_lab_progress` (upsert): `INSERT IGNORE INTO user_lab_progress (user_id, lab_id, started_at) VALUES (?, ?, NOW())`

- `labDone(): void` — Line 90
  - Updates progress: `UPDATE user_lab_progress SET completed_at = NOW(), status = 'completed' WHERE lab_id = ?`
  - **BUG**: Uses only `lab_id`, not `user_id` — marks ALL users' progress as completed for that lab

- `generateFlag(): void` — Line 100
  - Generates SHA256 hash of random 16 bytes
  - Writes to `labs/{slug}/flag.txt` via `file_put_contents()`

- `checkFlag(string $userFlag): bool` — Line 106
  - Reads `flag.txt`, does strict `===` comparison

**DB Queries:**
```sql
SELECT name FROM labs WHERE slug = ?
SELECT id FROM labs WHERE name = ? LIMIT 1
SELECT id FROM users WHERE username = ? LIMIT 1
INSERT IGNORE INTO user_lab_progress (user_id, lab_id, started_at) VALUES (?, ?, NOW())
UPDATE user_lab_progress SET completed_at = NOW(), status = 'completed' WHERE lab_id = ?
SELECT name, slug FROM labs WHERE category = ?
```

---

### Helpers/SessionManager.php

**Public Methods:**

- `__construct()` — Line 7
  - Calls `session_start()` if not already active

- `login(string $googleId, string $username, string $role): void` — Line 14
  - Sets `$_SESSION['google_id']`, `$_SESSION['username']`, `$_SESSION['role']`

- `logout(): void` — Line 21
  - Clears `$_SESSION = []`, calls `session_destroy()`

- `set(string $key, mixed $val): void` — Line 27
  - Sets `$_SESSION[$key] = $val`

- `get(string $key, mixed $default = null): mixed` — Line 32
  - Returns `$_SESSION[$key] ?? $default`

- `requireRole(string $role): void` — Line 37
  - Checks `$_SESSION['role'] === $role`; if not, sends 403 Forbidden and exits

**Side Effects:**
- Session manipulation

---

### Helpers/Config.php

**Public Methods:**

- `db(): array` (static) — Line 7
  - Returns array with Doctrine DBAL config from environment variables:
    - `dbname`: `$_ENV['DB_NAME']`
    - `user`: `$_ENV['DB_USER']`
    - `password`: `$_ENV['DB_PASS']`
    - `host`: `$_ENV['DB_HOST']`
    - `port`: `$_ENV['DB_PORT']`
    - `driver`: `$_ENV['DB_DRIVER']` (defaults to `'pdo_mysql'`)

---

## 4. Database Schema — Main App

### Table: `users`

Inferred from `Models/User.php` and `LoginController.php`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT | Inferred |
| `username` | VARCHAR(255) | From INSERT statement |
| `google_id` | VARCHAR(255), UNIQUE | From INSERT statement |
| `role` | ENUM('user', 'admin') | From INSERT statement, defaults 'user' |
| `last_login` | TIMESTAMP | From INSERT statement |
| `created_at` | TIMESTAMP | Inferred (standard practice) |

**SQL (inferred)**:
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    google_id VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    last_login TIMESTAMP DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `labs`

Inferred from `Models/Lab.php` queries:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED, PRIMARY KEY | Inferred |
| `name` | VARCHAR(255) | From query `SELECT name FROM labs WHERE slug` |
| `slug` | VARCHAR(255), UNIQUE | From query `WHERE slug = ?` |
| `category` | ENUM('frontend', 'backend') | From `getLabsByCategory()` |
| `is_completed` | BOOLEAN | From lab code (optional) |

**SQL (inferred)**:
```sql
CREATE TABLE labs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    category ENUM('frontend', 'backend') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### Table: `user_lab_progress`

Inferred from `Models/Lab.php`:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED, PRIMARY KEY | Inferred |
| `user_id` | BIGINT UNSIGNED, FOREIGN KEY | From query |
| `lab_id` | BIGINT UNSIGNED, FOREIGN KEY | From query |
| `status` | ENUM('started', 'completed') | From UPDATE statement |
| `started_at` | TIMESTAMP | From query |
| `completed_at` | TIMESTAMP | From UPDATE statement |

**SQL (inferred)**:
```sql
CREATE TABLE user_lab_progress (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    lab_id BIGINT UNSIGNED NOT NULL,
    status ENUM('started', 'completed') DEFAULT 'started',
    started_at TIMESTAMP DEFAULT NULL,
    completed_at TIMESTAMP DEFAULT NULL,
    UNIQUE KEY uq_user_lab (user_id, lab_id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (lab_id) REFERENCES labs(id)
);
```

---

## 5. Auth Flow

### Google OAuth Login

1. **POST `/login`** → `LoginController::login()`
   - Creates Google OAuth client (reads `client_credentials.json`)
   - Requests email + profile scopes
   - Redirects to Google's authorization endpoint

2. **GET `/auth/callback?code={auth_code}`** → `LoginController::callback()`
   - Exchanges `code` for access token
   - Calls Google Oauth2 API to fetch user info (email, google_id)
   - Calls `User::userEntry($email, google_id, 'user')` → upserts into `users` table
   - Calls `SessionManager::login(google_id, email, 'user')` → stores in `$_SESSION`:
     - `$_SESSION['google_id']` = user's Google ID
     - `$_SESSION['username']` = user's email
     - `$_SESSION['role']` = `'user'` (hardcoded)
   - Redirects to `/selector`

### Session Roles

- **Default role**: `'user'` (all OAuth users)
- **Admin role**: Must be set manually in DB (no UI to promote users)
- **Role checks**: Via `SessionManager::requireRole('admin')` (used in lab views)

### Session Persistence

- Standard PHP `$_SESSION` superglobal
- Session ID stored in browser cookie (default `PHPSESSID`)
- Server-side storage: default PHP session handler (temp directory)

---

## 6. Views — Main App

### index.php (`/`)

**Variables Expected**: None (form only)

**Content**:
- Heading: "AppSec Training"
- Description: "CTF-style labs for learning web vulnerabilities"
- Form with POST to `/login`
- Google sign-in button (SVG icon, hardcoded)

**Inline JS/CSS**: No. Uses global CSS from `partials/head.php`

---

### selector.php (`/selector`)

**Variables Expected**: None (reads from DB)

**Content**:
- Header with logout button (POST form to `/logout`)
- Two category cards: "Frontend" (XSS labs) and "Backend" (other vulns)
- Links to `/labs?category=frontend` and `/labs?category=backend`

**Inline JS/CSS**: No

---

### lab_list.php (`/labs?category={category}`)

**Variables Expected**:
- `$category` (string: 'frontend' or 'backend', from `$_GET`)
- `$list` (array of labs: [['name' => '...', 'slug' => '...'], ...])

**Content**:
- Header with back link, category name, category badge
- Grid of lab cards, each linking to `/lab/{slug}`
- Lab name and slug displayed

**Inline JS/CSS**: No

---

### partials/head.php

**Content**: Shared CSS (no JS)

**Key Styles**:
- Color variables (light/dark mode aware): `--color-text-default`, `--color-bg-primary`, etc.
- Spacing scale: `--spacing-1` to `--spacing-16`
- Typography: Display, heading, body, caption (responsive)
- Button styles: `.btn-primary`, `.btn-secondary`, `.btn-compact`
- Card styles: `.card`, `.card-header`, `.card-body`
- Badge styles: `.badge-frontend`, `.badge-backend`
- Flag input styles: `.flag-row input`
- Hint card pattern: `.hint-text` (yellow background, border)

**Fonts**: Inter from Google Fonts, monospace fallback for code

---

### views/labs/{slug}.php

Each lab view (e.g., `osci.php`, `dom-xss.php`) follows a consistent pattern:

**Variables Expected**: None (hardcoded lab slug in fetch calls)

**Content** (standard sections):
1. **Description** card: Brief explanation of vulnerability
2. **Vulnerable Code Example** card: PHP code snippet (syntax-highlighted with highlight.js)
3. **Actions** card: "Launch Lab" button (POST form to `/lab/{slug}/docker`)
4. **Hints** card: 1-3 hidden hints, auto-revealed via `setTimeout`:
   - Hint 1: after 2 minutes (120000ms)
   - Hint 2: after 4 minutes (240000ms)
   - Hint 3: after 6 minutes (360000ms)
5. **Submit Flag** card: Input field + button; submits via fetch to `/lab/{slug}/flag-check`

**Inline JS**:
```javascript
setTimeout(() => document.getElementById('hint1').style.display = 'block', 120000);
setTimeout(() => document.getElementById('hint2').style.display = 'block', 240000);
setTimeout(() => document.getElementById('hint3').style.display = 'block', 360000);

function submitFlag() {
    const flag = document.getElementById('flagInput').value.trim();
    fetch('/lab/{SLUG}/flag-check', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'flag=' + encodeURIComponent(flag)
    })
    .then(r => r.text())
    .then(text => {
        // Update result div with 'Success!' or 'Try again!'
    });
}
```

**Inline CSS**: Per-lab custom styles (rarely; mostly uses global classes)

**Syntax Highlighting**: highlight.js (CDN) applied to `<code>` blocks

---

## 7. Labs

Each lab is an isolated Docker environment with its own app code, database, and routing. Labs are accessed via `/lab/{slug}` from the main app, which displays instructions and a "Launch Lab" button that triggers the main app's `DockerController::run($slug)`, spinning up containers.

---

### Lab: `osci` (OS Command Injection)

**Vulnerability**: `shell_exec()` with weak filter bypass (rejects `;|>&<` but not newlines, backticks, or alternative separators)

**Intended Exploit Path**: Inject shell metacharacters to append/chain commands and read `flag.txt`

**Containers** (from `labs/osci/docker/docker-compose.yml`):

| Service | Image/Dockerfile | Container Name | Ports | Volumes | Networks |
|---------|------------------|-----------------|-------|---------|----------|
| `app` | Custom Dockerfile | `osci_app` | None | `../:/labs/osci` | default |
| `nginx` | `nginx:1.29` | `osci_nginx` | `80:80` | `../:/labs/osci`, `./nginx:/etc/nginx/conf.d` | default |
| `stopper` | `docker:cli` | `osci_stopper` | None | `/var/run/docker.sock`, `./storage` | default |

**Stopper Command**: Sleeps 900s, then stops `osci_app` and `osci_nginx`, deletes `/storage`

**Flag Delivery**: Flag file written by main app to `labs/osci/flag.txt` at lab start

**Lab App** (`index.php` at `labs/osci/`):
- Provides form with input `name="oauthToken"`
- Calls `filter_exec($input)` which:
  - Rejects inputs containing `[;&|><]` (regex)
  - Checks if first word is in `['ls', 'cat', 'pwd', 'find', 'grep', 'echo', 'flag.txt']`
  - Executes via `shell_exec()`
- Outputs result in `<iframe name="response">`

**SQL**: None (no database in this lab)

**Env Vars**: None referenced in lab code

**Hardcoded Items**:
- Container names: `osci_app`, `osci_nginx`, `osci_stopper`
- Port: `80:80`
- Working dir: `/labs/osci`

---

### Lab: `dom-xss` (DOM-based XSS)

**Vulnerability**: JavaScript directly writes URL fragment to `innerHTML` without escaping

**Intended Exploit Path**: Craft URL with XSS payload in fragment; admin bot visits and executes; payload exfiltrates admin's flag via AJAX

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes | Networks | Notes |
|---------|------------------|-----------------|-------|---------|----------|-------|
| `app` | Custom PHP | `dom_xss_app` | None | `../:/labs/dom_xss:delegated`, `./storage/tmp:/tmp` | default | |
| `nginx` | `nginx:1.29` | `dom_xss_nginx` | `80:80` | `../:/labs/dom_xss`, `./nginx:/etc/nginx/conf.d` | default | |
| `db` | `mysql:8.0` | `dom_xss_db` | `3307:3306` | `./storage/mysql:/var/lib/mysql`, `./db/init:/docker-entrypoint-initdb.d:ro` | default | Root PW: `root` |
| `selenium` | `seleniarm/standalone-chromium:latest` | `dom_xss_selenium` | None | `shm_size: 512m` | default | Health check on port 4444 |
| `bot` | Custom Python (Selenium) | `dom_xss_sel_bot` | None | None | default | Depends on healthy Selenium |
| `stopper` | `docker:cli` | `dom_xss_stopper` | None | `/var/run/docker.sock`, `./storage` | default | Sleeps 900s, stops all, deletes storage |

**Lab App** (`index.php` at `labs/dom-xss/`):

Router (`router.php`) dispatches:
- `/facts/{dog|cat|shrimp}` → `XssController::showView($type)` → includes `Views/{type}_facts.php`
- `/flag` → `XssController::flagView()` → requires admin role, shows flag from file
- `/form` → `XssController::reportView()` → shows report form
- `/report` → `XssController::reportLink()` (POST) → saves URL to DB
- `/next-report` → `XssController::nextReport()` → returns next URL for bot
- `/secret` → `XssController::adminLogin()` → auto-logs in as admin (for bot)
- `/roleChange` → `XssController::roleChange()` (POST) → updates user role in DB (admin only)

**Database** (`mysql:8.0`):

Schema (from `02-schema-xss-lab.sql`):
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

Seed (from `03-seed-xss-lab.sql`): Inserts admin + student users

**Bot Behavior** (`admin_bot.py`):
1. Waits for Selenium to be healthy
2. Creates Selenium Chrome driver in headless mode
3. Visits `/secret` endpoint (auto-logs in as admin)
4. Polls `/next-report` every 5 seconds
5. For each URL, navigates and waits 3 seconds (XSS triggers)
6. Returns to loop

**Env Vars**: None in lab code

**Hardcoded Items**:
- Container names, port 3307, working dir `/labs/dom_xss`
- Selenium service name hardcoded in bot: `http://selenium:4444/wd/hub`
- Nginx service hardcoded in bot: `http://nginx`

---

### Lab: `reflect-xss` (Reflected XSS)

**Vulnerability**: User input reflected into 404 page without escaping

**Intended Exploit Path**: Craft URL with XSS payload; bot visits; executes; exfiltrates flag

**Containers**: Same structure as `dom-xss` (app, nginx, db, selenium, bot, stopper)

**Container Names**: `reflect_xss_*` variant

**Database Schema**: Same as `dom-xss`

**Lab App**: Similar router to `dom-xss`; differs in vulnerability location (404 handler instead of JavaScript)

**Bot Behavior**: Same as `dom-xss`

---

### Lab: `stored-xss` (Stored XSS)

**Vulnerability**: User-submitted messages stored without escaping; admin reads messages page, executing injected script

**Intended Exploit Path**: Submit XSS payload in message; bot (admin) visits messages page; script exfiltrates flag

**Containers**: Same structure as `dom-xss`

**Container Names**: `stored_xss_*` variant

**Database Schema**:
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE messages (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    email VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Lab App**:

Router (`router.php`) dispatches:
- `/admin` → shows admin view (lists all messages)
- `/student` → shows student view (lists user's own messages)
- `/flag` → shows flag (admin only)
- `/message` (POST) → saves message to DB
- `/secret` → auto-logs in as admin (for bot)

**Hardcoded Items**: Container names, port 3307, service names in bot

---

### Lab: `ssrf` (Server-Side Request Forgery)

**Vulnerability**: Public app's image proxy (`/proxy?url=...`) uses `file_get_contents()` on user-supplied URL without validation; can reach internal admin service

**Intended Exploit Path**: Craft proxy URL to reach internal `admin:9090` service; exfiltrate flag

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Environment | Volumes | Networks |
|---------|------------------|-----------------|-------|-------------|---------|----------|
| `app` | Custom PHP (ARG: `APP_DIR=app`) | `ssrf_app` | `80:8080` (port 8080 in container) | `PORT=8080` | None | `labnet` |
| `admin` | Custom PHP (ARG: `APP_DIR=admin`) | `ssrf_admin` | None | `PORT=9090` | `../flag.txt:/labs/SSRF/flag.txt:ro` | `labnet` |
| `stopper` | `docker:cli` | `ssrf_stopper` | None | None | `/var/run/docker.sock` | default |

**Networks**: Custom bridge network `labnet` (app and admin on same network)

**Lab Apps**:

- **App** (`app/index.php`):
  - Public endpoint at `localhost:8080` (port mapped to host 80)
  - Provides image selector UI
  - `/proxy?url=...` endpoint: fetches URL via `file_get_contents()`, returns content
  - Vulnerable to SSRF; allows reaching `http://ssrf_admin:9090` (internal)

- **Admin** (`admin/index.php`):
  - Runs on port 9090 (no port mapping; internal only)
  - Shows flag on GET `/`
  - Accessible only from within network (from `app` container)

**Flag Delivery**: Flag file mounted as volume into admin container (read-only)

**Env Vars**: 
- `PORT` (8080 for app, 9090 for admin)

**Hardcoded Items**:
- Container names: `ssrf_app`, `ssrf_admin`, `ssrf_stopper`
- Port 8080 in app, 9090 in admin
- Network name: `labnet`
- Volume mount: `../flag.txt:/labs/SSRF/flag.txt:ro`

---

### Lab: `oauth-ato` (OAuth Account Takeover / Login CSRF)

**Vulnerability**: OAuth state parameter stored globally in `/tmp/oauth_state` (file) instead of per-session; can be replayed by different user

**Intended Exploit Path**: Trigger OAuth flow with bot's credentials; bot writes state to file; attacker reuses that state to log in as bot

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes | Networks |
|---------|------------------|-----------------|-------|---------|----------|
| `app` | Custom PHP | `oauth_lab_app` | None | `../:/labs/oauth_lab:delegated`, `./storage/tmp:/tmp`, `oauth_lab_vendor:/labs/oauth_lab/vendor` | default |
| `nginx` | `nginx:1.29` | `oauth_lab_nginx` | `80:80` | `../:/labs/oauth_lab`, `./nginx:/etc/nginx/conf.d` | default |
| `db` | `mysql:8.0` | `oauth_lab_db` | `3307:3306` | `./storage/mysql:/var/lib/mysql`, `./db/init:/docker-entrypoint-initdb.d:ro` | default |
| `selenium` | `seleniarm/standalone-chromium:latest` | `oauth_lab_selenium` | None | `shm_size: 512m` | default |
| `bot` | Custom Python (Selenium) | `oauth_lab_sel_bot` | None | None | default |
| `stopper` | `docker:cli` | `oauth_lab_stopper` | None | `/var/run/docker.sock`, `./storage` | default |

**Database Schema** (from `02-schema.sql`):
```sql
CREATE TABLE users (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    google_email VARCHAR(255) UNIQUE DEFAULT NULL,
    apple_email VARCHAR(255) UNIQUE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE reports (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    url VARCHAR(500) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Lab App** (`OAuthController.php`):

Routes:
- `POST /oauth/start` → `start()` — generates state, writes to `/tmp/oauth_state`, redirects to provider
- `POST /oauth/social-start` → `socialStart()` — same as start, but mode=login
- `GET /oauth/provider` → `providerView()` — shows fake OAuth provider login form
- `POST /oauth/provider` → `providerSubmit()` — validates credentials (hardcoded), redirects to callback
- `GET /oauth/callback` → `callback()` — validates state, **but never clears `/tmp/oauth_state`** (VULNERABILITY), logs in user
- `GET /flag` → `flagView()` — shows flag (admin only)
- `POST /report` → `reportLink()` — saves URL to DB
- `GET /next-report` → `nextReport()` — returns next URL for bot
- `GET /secret` → `adminLogin()` — auto-logs in as admin (for bot)
- `POST /reset` → `reset()` — unlinks all providers, clears state file
- `POST /logout` → `logout()` — destroys session

**Hardcoded Credentials** (in `OAuthController`):
```php
private const GOOGLE_USERS = [
    'student@gmail.com' => 'google123',
    'admin@gmail.com' => 'admin_google_secret'
];
private const APPLE_USERS = [
    'student@icloud.com' => 'apple123',
    'admin@icloud.com' => 'admin_apple_secret'
];
```

**Vulnerability**: State file `/tmp/oauth_state` is never cleared after use (line 124 has intentional comment). Allows state reuse across different users.

**Bot Behavior**: Same as XSS labs; visits `/secret`, polls `/next-report`, navigates to reported URLs

**Env Vars**: None in lab code

**Hardcoded Items**: Container names, port 3307, service names, `/tmp/oauth_state` path

---

### Lab: `rbac` (Incorrect Role-Based Access Control)

**Vulnerability**: Flag endpoint (`/flag`) has no server-side role check; UI hides it with CSS `display:none`

**Intended Exploit Path**: Navigate directly to `/flag` as regular user; server serves flag without role verification

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes |
|---------|------------------|-----------------|-------|---------|
| `app` | Custom PHP | `rbac_app` | None | `../:/labs/rbac` |
| `nginx` | `nginx:1.29` | `rbac_nginx` | `80:80` | `../:/labs/rbac`, `./nginx:/etc/nginx/conf.d` |
| `stopper` | `docker:cli` | `rbac_stopper` | None | `/var/run/docker.sock` |

**Lab App** (`index.php`):

- Auto-logs in as 'user' role on first visit
- Routes:
  - `GET /flag` → includes `views/flag.php` **without role check** (VULNERABILITY)
  - `GET /` → includes `views/dashboard.php` (UI hides flag button with CSS)

**Database**: None

**Env Vars**: None

**Hardcoded Items**: Container names, port 80

---

### Lab: `path-trav` (Path Traversal + LFI)

**Vulnerability**: File inclusion via `GET ?f=` parameter; filter blocks 'flag' string but allows `../` traversal

**Intended Exploit Path**: 
1. Upload malicious PHP file (blocks `exec()`, `shell_exec()`, etc., but allows other functions)
2. Use `../ ` to traverse above `images/` directory and include uploaded file
3. Executed code reads and outputs flag

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes |
|---------|------------------|-----------------|-------|---------|
| `app` | Custom PHP | `path_trav_app` | None | `../:/labs/path_trav` |
| `nginx` | `nginx:1.29` | `path_trav_nginx` | `80:80` | `../:/labs/path_trav`, `./nginx:/etc/nginx/conf.d` |
| `stopper` | `docker:cli` | `path_trav_stopper` | None | `/var/run/docker.sock` |

**Lab App** (`index.php`):

- `POST /` (file upload) → saves file to `images/` with original filename
  - Blocks `.php` files containing: `exec(`, `shell_exec(`, `system(`, `passthru(`, `popen(`, `proc_open(`, `pcntl_exec(`
  - Uses `move_uploaded_file()` (no randomization; predictable paths)

- `GET /?f=...` → includes/serves file
  - Blocks URLs containing 'flag' string
  - If extension is image, serves as image; else `include()`s the file
  - **Vulnerable**: `$_GET['f']` passed directly to path; allows `../` traversal

- Root listing: Scans `images/` directory, displays available files

**Database**: None

**Env Vars**: None

**Hardcoded Items**: Container names, port 80, directory `/images/`

---

### Lab: `type-juggle` (PHP Type Juggling / Magic Hash)

**Vulnerability**: Authentication uses loose `==` comparison on hash values; PHP's magic hash bypass applies

**Intended Exploit Path**: 
- Admin password hash: `0e830400451993494058024219903391` (MD5)
- Login with username "admin" and password that MD5-hashes to another `0e*` string
- Both are treated as zero by loose comparison (`0e123... == 0e456... → true`)

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes |
|---------|------------------|-----------------|-------|---------|
| `app` | Custom PHP | `type_juggle_app` | None | `../:/labs/type_juggle` |
| `nginx` | `nginx:1.29` | `type_juggle_nginx` | `80:80` | `../:/labs/type_juggle`, `./nginx:/etc/nginx/conf.d` |
| `stopper` | `docker:cli` | `type_juggle_stopper` | None | `/var/run/docker.sock` |

**Lab App** (`index.php`):

- Uses **SQLite** (`users.db`), not MySQL
- Creates table on first run if not exists
- Seeds admin user with magic hash: `0e830400451993494058024219903391` (MD5 algorithm)

- Routes:
  - `GET /` → login form or dashboard (depending on session)
  - `POST /` → login handler
    - Queries: `SELECT * FROM users WHERE username = ?`
    - Computes hash: `hash($algorithm, $password)` or `md5()`, `sha1()`, `sha256()`
    - **Vulnerable**: `if ($computed == $user['password_hash'])` (loose comparison)
  - `GET /register` → registration form
  - `POST /register` → creates new user
  - `GET /logout` → destroys session

**Database** (SQLite):
```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    algorithm TEXT NOT NULL,
    role TEXT DEFAULT 'user'
);
```

Admin seed:
```php
INSERT INTO users (username, password_hash, algorithm, role) 
VALUES ('admin', '0e830400451993494058024219903391', 'md5', 'admin')
```

**Env Vars**: None

**Hardcoded Items**: Container names, port 80, SQLite path `users.db`

---

### Lab: `insec-deser` (Insecure Deserialization)

**Vulnerability**: `unserialize()` on user-controlled cookie; `Invoice::__destruct()` writes arbitrary file

**Intended Exploit Path**:
1. Craft serialized `Invoice` object with `$path = 'flag.txt'`, `$content = 'malicious'`
2. URL-encode, set as `booking` cookie
3. Visit `/book/summary`
4. Cookie is unserialized; `Invoice::__destruct()` fires on out-of-scope, writes to file

**Containers**:

| Service | Image/Dockerfile | Container Name | Ports | Volumes |
|---------|------------------|-----------------|-------|---------|
| `app` | Custom PHP | `insec_deser_app` | None | `../:/labs/insec_deser` |
| `nginx` | `nginx:1.29` | `insec_deser_nginx` | `80:80` | `../:/labs/insec_deser`, `./nginx:/etc/nginx/conf.d` |
| `stopper` | `docker:cli` | `insec_deser_stopper` | None | `/var/run/docker.sock`, `../storage` |

**Lab App** (`index.php`):

Two classes defined inline:
- `BookingOptions` — data holder
- `Invoice` — has `__destruct()` that calls `file_put_contents($this->path, $this->content)` (VULNERABILITY)

Routes:
- `GET /` → `views/home.php`
- `GET /book` → `views/step1.php` (booking form)
- `POST /book` → serializes `BookingOptions`, sets as URL-encoded cookie `booking`, redirects to `/book/details`
- `GET /book/details` → `views/step2.php` (guest details form)
- `POST /book/details` → stores in `$_SESSION`, redirects to `/book/summary`
- `GET /book/summary` → **unserialization point** (line 84):
  ```php
  $booking = unserialize(urldecode($_COOKIE['booking']));
  if ($booking instanceof BookingOptions) {
      $invoice = new Invoice();
      $invoice->path = __DIR__ . '/storage/invoices/' . /* timestamp */;
      $invoice->generate(...); // fills $content
  }
  ```
  On exit, `$invoice->__destruct()` writes file

**Database**: None

**Env Vars**: None

**Hardcoded Items**: Container names, port 80, storage path `/storage/invoices/`

---

## 8. Cross-Cutting Issues — Platform Defects

(INTENTIONAL VULNERABILITIES IN LABS ARE NOT LISTED HERE; ONLY PLATFORM/UNINTENDED ISSUES)

> **Verification notes (2026-10-16, checked against source by hand):**
> - **#2 and #3 are not exploitable as written.** `routes.php` only matches slugs against `[a-zA-Z0-9_\-]+` (no `;`, `/`, `.`), and `new Lab($slug)` throws a RuntimeException for slugs missing from the `labs` table before any Docker call. The lab names in `run()` come from `docker ps` output, not from the user. Treat them as defence-in-depth only: build commands with an argument array (Symfony Process) rather than concatenated strings.
> - **#5 is overstated.** Login/logout CSRF gives a forced logout or a login under the attacker's account, not session hijacking. Laravel's CSRF middleware covers it anyway.
> - **#7 is partly wrong.** `sleep(900)` runs inside the `stopper` container (lab auto-stop after 15 min) and does not block PHP. Only `sleep(5)` in `DockerController::run()` and in `docker_loader.php` blocks the request.
> - **Missed: no auth on any main-app route.** `SessionManager::requireRole()` in `helpers/` is never called by the main app (it is only used inside the labs' own copies). `/selector`, `/labs`, `/lab/{slug}`, `/lab/{slug}/docker` and `/flag-check` all work without login.
> - **Missed: admin role is reset on every login.** `User::userEntry()` upserts with `role = VALUES(role)`, and `LoginController::callback()` always passes `'user'`, so a manually promoted admin becomes `user` on their next login.
> - **Missed: `callback()` has no `exit` after the redirect when `code` is missing** (`LoginController.php` ~line 40), so it carries on into `fetchAccessTokenWithAuthCode(null)`.
> - **Missed: the flag is global and regenerated on every view.** `showLab()` → `startLab()` → `generateFlag()` overwrites `labs/{slug}/flag.txt` for everyone each time anyone opens `/lab/{slug}`.
> - **Missed: `DockerController::run()` stops other users' labs.** It runs `docker compose down` on every running `*_lab` project except the one requested.
> - **Schema has no source of truth.** There is no SQL/migration file in the repo. `docker/docker-compose.db.yml` only mounts `./storage/mysql`, which is gitignored, so the section 4 schema and the `labs` seed rows (names, categories) are all inferred. The `is_completed` column under `labs` is not backed by any query, so ignore it.
> - **Unused dependency:** `spatie/docker` is required in composer.json, but `DockerController` uses raw `exec`/`shell_exec`. `docker_loader.php` is a legacy osci-only launcher and is not routed.

### 1. **Lab::labDone() — Missing user_id in UPDATE**
- **File**: `Models/Lab.php`, line 92-97
- **Issue**: `UPDATE user_lab_progress SET completed_at = NOW(), status = 'completed' WHERE lab_id = ?`
- **Problem**: Uses only `lab_id`, not `user_id`. Marks **all users'** progress as completed for that lab when any user completes it.
- **Impact**: Progress tracking inaccurate; all users show completed status after first person completes

---

### 2. **DockerController::run() — Shell Injection via labName**
- **File**: `Controllers/DockerController.php`, line 16-33
- **Issue**: `labName` parameter passed directly to `exec()` and `str_ends_with()`, `str_contains()`
  - Line 22: `exec('cd labs/' . $otherLab . '/docker && docker compose down')`
  - Line 27: `exec('cd labs/' . $labName . '/docker && docker compose up -d')`
- **Problem**: No validation of `$labName`; attacker can pass `; malicious_command` to execute arbitrary shell commands
- **Impact**: Remote Code Execution on host

---

### 3. **DockerController::stop() — Path Traversal via labName**
- **File**: `Controllers/DockerController.php`, line 35-47
- **Issue**: `labName` used to construct paths without validation
  - Line 39: `'labs/' . $labName . '/docker/storage'`
  - Line 40: `'labs/' . $labName . '/storage'`
  - Line 44: Deletes paths using `find` + `escapeshellarg()`, but path itself is user-controlled
- **Problem**: Attacker can pass `../other_dir` to delete arbitrary directories on host
- **Impact**: Data loss; denial of service

---

### 4. **LoginController::login() — Hardcoded client_credentials.json Path**
- **File**: `Controllers/LoginController.php`, line 20
- **Issue**: `$client->setAuthConfig(__DIR__ . '/client_credentials.json')`
- **Problem**: Path is absolute relative to Controllers/ directory; file must be placed there manually; no error handling if missing
- **Impact**: Lab cannot start without file in correct location

---

### 5. **Main App Routes — No CSRF Protection**
- **File**: `routes.php`, lines 22-47 (logout, login endpoints accept POST)
- **Issue**: No CSRF token validation; POST requests not protected
- **Problem**: Attacker can craft page that POSTs to `/logout` or `/login` from victim's browser
- **Impact**: Forced logout; session hijacking via redirects

---

### 6. **SQL — Charset/Collation Not Specified**
- **Database Schema** (inferred): Tables created without explicit `COLLATE` clause
- **Problem**: Default collation may differ between environments; affects string comparisons (case sensitivity)
- **Impact**: Minor; mostly doesn't affect these labs

---

### 7. **All Labs — sleep() Calls Block Execution**
- **Files**: `docker_loader.php` (line 15), `DockerController.php` (line 30)
- **Issue**: `sleep(5)` and `sleep(900)` halt request processing
- **Problem**: Blocks thread; doesn't scale in concurrent environments; could timeout client
- **Impact**: Poor UX; potential timeout errors in slow environments

---

### 8. **Lab Container Naming — Enables Hardcoding**
- **All Labs**: Container names like `osci_app`, `dom_xss_nginx` are hardcoded in docker-compose.yml
- **Problem**: No parameterization; impossible to run two instances of same lab simultaneously (names collide)
- **Impact**: Cannot support concurrent lab sessions without manual renaming

---

### 9. **Session Storage — Default PHP Handler**
- **File**: `index.php`, line 7 (session_start)
- **Issue**: Uses default PHP session handler (temp directory)
- **Problem**: Sessions lost if temp directory cleared; not shared across web servers (no scaling)
- **Impact**: Not production-safe; educational labs only

---

### 10. **No Input Validation on Lab Selector**
- **File**: `routes.php`, line 50-52
- **Issue**: `$_GET['category']` passed directly to `LabController::showSelector()`
  ```php
  $category = $_GET['category'];
  $controller->showSelector($category);
  ```
- **Problem**: No validation; SQL injection possible if `$category` used in unparameterized query
- **Current Status**: Uses parameterized query in `Lab::getLabsByCategory()`, so safe; but bad practice
- **Impact**: Risk if query is later changed to string concatenation

---

## 9. Dependencies

### composer.json (Main App)

```json
{
    "require": {
        "google/apiclient": "^2.18",
        "google/apiclient-services": "v0.396.0",
        "vlucas/phpdotenv": "^5.6",
        "spatie/docker": "^1.13",
        "doctrine/dbal": "^4.3"
    }
}
```

| Package | Version | Purpose |
|---------|---------|---------|
| `google/apiclient` | ^2.18 | Google OAuth client library |
| `google/apiclient-services` | v0.396.0 | Google API service definitions (Oauth2) |
| `vlucas/phpdotenv` | ^5.6 | Load `.env` file into `$_ENV` |
| `spatie/docker` | ^1.13 | Docker container orchestration (currently unused; labs use raw `exec()` instead) |
| `doctrine/dbal` | ^4.3 | Database abstraction layer (MySQL queries) |

**PHP Version**: No explicit version constraint in composer.json; project uses PHP 7.4+ features (named arguments, match expression, nullsafe operator)

**Docker Images Used**:
- `mysql:8.0` — Primary database (main app + XSS labs)
- `nginx:1.29` — Web server for all labs
- `php:*` — (Custom Dockerfile; likely PHP 8.1 or later based on syntax)
- `seleniarm/standalone-chromium:latest` — Selenium Chrome for bot automation (XSS labs)
- `docker:cli` — Helper container for stopper service (docker socket access)

---

## Summary of Key Findings for Migration

1. **No Framework Used**: Custom PHP MVC with manual routing (switch-based dispatcher in `routes.php`)

2. **Session-Based Auth**: Google OAuth → `$_SESSION['role', 'username', 'google_id']`; no JWT or token-based auth

3. **Database Queries**: All Doctrine DBAL queries are parameterized; no SQL injection in main app

4. **Hardcoded Paths**: Lab names hardcoded in docker compose files and `DockerController`; impossible to run multiple instances concurrently

5. **Security Gaps in Platform**:
   - No CSRF protection on logout/login
   - `DockerController` has shell injection risk via `labName` parameter
   - `Lab::labDone()` updates all users, not just current user
   - No input validation on `$_GET['category']`

6. **Labs are Intentionally Vulnerable**: Each lab is a self-contained demo of ONE vulnerability; platform flaws above are separate from intended lab vulnerabilities

7. **Storage**: Flags written to filesystem (`labs/{slug}/flag.txt`), not database

8. **No API Layer**: All communication via HTML forms and redirects; no REST API or JSON endpoints

9. **Database Tables**: `users`, `labs`, `user_lab_progress`, plus lab-specific tables (users, messages, reports, etc.)

10. **Docker Organization**: Each lab has its own `docker/docker-compose.yml`; main app uses separate `docker/docker-compose.db.yml`; labs started on-demand via `docker compose up -d`

---

**Document Generated**: 2026-10-16  
**Inventory Accuracy**: Based on code analysis of all PHP, Docker, and SQL files in project  
**Status**: INTENTIONALLY VULNERABLE FOR TRAINING — DO NOT USE IN PRODUCTION
