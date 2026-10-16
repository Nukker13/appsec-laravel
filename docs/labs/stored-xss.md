# Stored XSS Lab

## Vulnerability

Stored XSS (also called Persistent or Type-II XSS) occurs when the injected script is permanently stored on the target server — in a database, message forum, comment field, or visitor log. The victim retrieves the malicious script from the server whenever they request the stored information. Unlike reflected XSS, stored XSS does not require tricking the victim into clicking a crafted link — anyone who views the page containing the stored payload is affected. The vulnerability enables an attacker to hijack any user who views the malicious content, including administrators.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving messages and storing user input | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |
| db | mysql:8.0 | MySQL database (messages, users) | default | — |
| selenium | seleniarm/standalone-chromium | Automated browser for bot | default | — |
| bot | Custom (Python + Selenium) | Admin bot that views stored messages | default | — |

**Build note:** Dockerfile references `composer.lock` (not in source). Pending owner decision on lock file creation.

## Flag Delivery

The PHP app stores the flag in `flag.txt` (relative to app root, at `labs/stored-xss/flag.txt` on host). The admin panel reads this file and displays it to authenticated admins. The bind mount `../:/labs/stored-xss:delegated` provides access. Successful exploitation stores an XSS payload in the message database, the admin bot views the messages page, the payload executes in the admin's session, and the flag is exfiltrated.

## Solvability Test

Phase 5 (pending)
