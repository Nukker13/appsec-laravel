# Insecure Deserialization Lab

## Vulnerability

Insecure Deserialization occurs when an application deserializes attacker-controlled data without validation. In PHP, `unserialize()` reconstructs a full object from a string — and automatically invokes lifecycle magic methods on it. This allows an attacker to inject a crafted object of any class available in the application, turning existing code into a gadget chain that produces unintended side effects such as arbitrary file writes or code execution. The vulnerability is particularly dangerous when untrusted data (cookies, form inputs) is unserialized without integrity verification.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend deserializing booking cookie without validation | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/insec_deser/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
