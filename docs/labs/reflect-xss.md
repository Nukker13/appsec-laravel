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


## Flag Delivery

Host file `labs/reflect-xss/flag.txt` (written by `FlagService::issue`) is visible as `/labs/reflect_xss/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/reflect_xss`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
