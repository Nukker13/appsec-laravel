# Type Juggling Lab

## Vulnerability

PHP Type Juggling is a vulnerability that arises from PHP's loose comparison operator (`==`), which performs type coercion before comparing values. When hash strings are compared with `==` instead of `===`, an attacker can craft an input whose hash evaluates as equal to the stored hash under PHP's coercion rules — without knowing the original value — effectively bypassing authentication.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend with login form and password hash comparison | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

Host file `labs/type-juggle/flag.txt` (written by `FlagService::issue`) is visible as `/labs/type_juggle/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/type_juggle`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
