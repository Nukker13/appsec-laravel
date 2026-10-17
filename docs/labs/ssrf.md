# Server-Side Request Forgery Lab

## Vulnerability

Server-Side Request Forgery (SSRF) is a vulnerability that allows an attacker to cause the server to make requests to unintended locations. In a typical SSRF attack, the attacker causes the server to connect to internal-only services within the organization's infrastructure, or to arbitrary external systems — potentially leaking sensitive data such as credentials or flags. The vulnerability occurs when the application accepts user-controlled URLs and fetches them server-side without validating the destination.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP image proxy, Traefik entry point | labnet, labs_proxy | — |
| admin | Custom (Dockerfile) | PHP admin service with flag (port 9090) | labnet | ssrf_admin |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. Only the `admin` service bind-mounts it (read-only, `../flag.txt` -> `/labs/SSRF/flag.txt`); the public `app` service has no mount of it, and `admin` is reachable only on the internal network `labnet` (alias `ssrf_admin`).

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
