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


## Flag Delivery

Host file `labs/stored-xss/flag.txt` (written by `FlagService::issue`) is visible as `/labs/stored_xss/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/stored_xss`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
