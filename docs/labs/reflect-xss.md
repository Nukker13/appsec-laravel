# Reflected XSS Lab

## Vulnerability

Reflected XSS occurs when the injected script is reflected off the web server — in an error message, search result, or any response that includes input sent as part of the request. Attacks are delivered to victims via another route: a malicious link in email or another website. When a user clicks a malicious link, the injected code travels to the vulnerable server, which reflects it back to the browser. The browser executes it because it came from a "trusted" server. Unlike stored XSS, reflected XSS is not persistent — the attack is carried out through a single request/response cycle.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving facts pages with reflection vulnerabilities | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |
| db | mysql:8.0 | MySQL database (reports, users) | default | — |
| selenium | seleniarm/standalone-chromium | Automated browser for bot | default | — |
| bot | Custom (Python + Selenium) | Admin bot that visits reported links | default | — |

**Build note:** Dockerfile references `composer.lock` (not in source). Pending owner decision on lock file creation.

## Flag Delivery

The PHP app stores the flag in `flag.txt` (relative to app root, at `labs/reflect-xss/flag.txt` on host). The admin endpoint reads this file and displays it to authenticated admins. The bind mount `../:/labs/reflect-xss:delegated` provides access. Successful exploitation crafts a URL that reflects XSS payload, reports it to the bot, and the bot's admin session reveals the flag.

## Solvability Test

Phase 5 (pending)
