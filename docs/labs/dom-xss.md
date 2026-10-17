# DOM XSS Lab

## Vulnerability

DOM-based XSS vulnerabilities arise when JavaScript takes data from an attacker-controllable source, such as the URL, and passes it to a sink that supports dynamic code execution — like `eval()` or `innerHTML`. This enables attackers to execute malicious JavaScript and typically allows them to hijack other users' accounts. The attack requires the attacker to construct a crafted link with a malicious payload in the URL fragment or query string, and deliver it to a victim who clicks the link. The victim's browser executes the injected code in the context of the vulnerable page.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving web forms and handlers | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |
| db | mysql:8.0 | MySQL database (reports, users) | default | — |
| selenium | seleniarm/standalone-chromium | Automated browser for bot | default | — |
| bot | Custom (Python + Selenium) | Admin bot that visits reported links | default | — |


## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/dom_xss/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
