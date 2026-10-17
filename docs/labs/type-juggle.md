# Type Juggling Lab

## Vulnerability

PHP Type Juggling is a vulnerability that arises from PHP's loose comparison operator (`==`), which performs type coercion before comparing values. When hash strings are compared with `==` instead of `===`, an attacker can craft an input whose hash evaluates as equal to the stored hash under PHP's coercion rules — without knowing the original value — effectively bypassing authentication.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend with login form and password hash comparison | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/type_juggle/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
