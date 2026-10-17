# Incorrect RBAC Lab

## Vulnerability

Broken Access Control occurs when an application relies on client-side mechanisms to enforce authorization instead of validating permissions on the server. Role-Based Access Control (RBAC) must be verified server-side on every protected endpoint — hiding UI elements or restricting navigation in the browser is not a security boundary. The vulnerability enables unauthorized access to sensitive resources and privilege escalation.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving dashboard and flag pages | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/rbac/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
