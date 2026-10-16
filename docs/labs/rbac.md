# Incorrect RBAC Lab

## Vulnerability

Broken Access Control occurs when an application relies on client-side mechanisms to enforce authorization instead of validating permissions on the server. Role-Based Access Control (RBAC) must be verified server-side on every protected endpoint — hiding UI elements or restricting navigation in the browser is not a security boundary. An attacker can bypass these restrictions by navigating directly to protected URLs, inspecting the page source, or using a proxy to observe traffic. The vulnerability enables unauthorized access to sensitive resources and privilege escalation.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving dashboard and flag pages | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The lab app serves a static flag page at `/flag`. The flag is stored in `labs/rbac/views/flag.php` (visible to authenticated users regardless of role). The bind mount `../:/labs/rbac` provides access. Successful exploitation navigates directly to the `/flag` endpoint without admin privileges, retrieving the flag from the unprotected page.

## Solvability Test

Phase 5 (pending)
