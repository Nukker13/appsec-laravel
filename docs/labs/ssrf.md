# Server-Side Request Forgery Lab

## Vulnerability

Server-Side Request Forgery (SSRF) is a vulnerability that allows an attacker to cause the server to make requests to unintended locations. In a typical SSRF attack, the attacker causes the server to connect to internal-only services within the organization's infrastructure, or to arbitrary external systems — potentially leaking sensitive data such as credentials or flags. The vulnerability occurs when the application accepts user-controlled URLs and fetches them server-side without validating the destination.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | Node.js image proxy, Traefik entry point | labnet, labs_proxy | — |
| admin | Custom (Dockerfile) | Node.js admin service with flag (port 9090) | labnet | ssrf_admin |

## Flag Delivery

Host file `labs/ssrf/flag.txt` is bind-mounted read-only by the `admin` service at `/labs/SSRF/flag.txt`. Only `admin` (internal network `labnet`, alias `ssrf_admin`) can read it; the public `app` service has no mount of it.

## Solvability Test

Phase 5 (pending)
