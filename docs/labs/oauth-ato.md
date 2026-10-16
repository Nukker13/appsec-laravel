# OAuth Account Takeover Lab

## Vulnerability

OAuth Login CSRF occurs when an application fails to properly bind an OAuth flow to the initiating user's session. The `state` parameter acts as a CSRF token — it should be generated, stored in the user's session, and verified on callback. The vulnerability enables account takeover and privilege escalation.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend with OAuth login flow | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |
| db | mysql:8.0 | MySQL database (users, OAuth state) | default | — |
| selenium | seleniarm/standalone-chromium | Automated browser for bot | default | — |
| bot | Custom (Python + Selenium) | Admin bot that follows reported OAuth links | default | — |


## Flag Delivery

Host file `labs/oauth-ato/flag.txt` (written by `FlagService::issue`) is visible as `/labs/oauth_lab/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/oauth_lab`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
