# OAuth Account Takeover Lab

## Vulnerability

OAuth Login CSRF occurs when an application fails to properly bind an OAuth flow to the initiating user's session. The `state` parameter acts as a CSRF token — it should be generated, stored in the user's session, and verified on callback. When state is stored globally instead of per-session, a valid callback can be replayed by a different actor, allowing an attacker to hijack an OAuth identity and gain elevated access. The vulnerability enables account takeover and privilege escalation.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend with OAuth login flow | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |
| db | mysql:8.0 | MySQL database (users, OAuth state) | default | — |
| selenium | seleniarm/standalone-chromium | Automated browser for bot | default | — |
| bot | Custom (Python + Selenium) | Admin bot that follows reported OAuth links | default | — |

**Build note:** Dockerfile references `composer.lock` (not in source). Pending owner decision on lock file creation.

## Flag Delivery

The PHP app stores the flag in `flag.txt` (relative to app root, at `labs/oauth-ato/flag.txt` on host). The admin dashboard displays the flag to authenticated admin users. The bind mount `../:/labs/oauth_lab:delegated` provides access. Successful exploitation intercepts the OAuth callback state parameter, replays it with attacker credentials, and hijacks an admin session to retrieve the flag.

## Solvability Test

Phase 5 (pending)
