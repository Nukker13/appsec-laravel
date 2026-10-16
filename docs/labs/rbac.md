# Incorrect RBAC Lab

## Vulnerability

Broken Access Control occurs when an application relies on client-side mechanisms to enforce authorization instead of validating permissions on the server. Role-Based Access Control (RBAC) must be verified server-side on every protected endpoint — hiding UI elements or restricting navigation in the browser is not a security boundary. The vulnerability enables unauthorized access to sensitive resources and privilege escalation.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving dashboard and flag pages | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

Host file `labs/rbac/flag.txt` (written by `FlagService::issue`) is visible as `/labs/rbac/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/rbac`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
