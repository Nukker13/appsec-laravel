# Type Juggling Lab

## Vulnerability

PHP Type Juggling is a vulnerability that arises from PHP's loose comparison operator (`==`), which performs type coercion before comparing values. When hash strings are compared with `==` instead of `===`, an attacker can craft an input whose hash evaluates as equal to the stored hash under PHP's coercion rules — without knowing the original value — effectively bypassing authentication. The vulnerability occurs because hashes beginning with `0e` followed by only digits are treated as zero in loose comparisons with other similar hashes.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend with login form and password hash comparison | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The lab app stores the flag in `flag.txt` at the app root (`labs/type-juggle/flag.txt` on host). The app serves a dashboard page at `/dashboard` that displays the flag to authenticated users. Successful exploitation crafts a password that hashes to a magic hash value, logs in as admin via the loose comparison vulnerability, and retrieves the flag from the authenticated dashboard.

## Solvability Test

Phase 5 (pending)
