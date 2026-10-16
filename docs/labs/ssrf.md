# Server-Side Request Forgery Lab

## Vulnerability

Server-Side Request Forgery (SSRF) is a vulnerability that allows an attacker to cause the server to make requests to unintended locations. In a typical SSRF attack, the attacker causes the server to connect to internal-only services within the organization's infrastructure, or to arbitrary external systems — potentially leaking sensitive data such as credentials or flags. The vulnerability occurs when the application accepts user-controlled URLs and fetches them server-side without validating the destination.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | Node.js image proxy, Traefik entry point | labnet, labs_proxy | — |
| admin | Custom (Dockerfile) | Node.js admin service with flag (port 9090) | labnet | ssrf_admin |

## Flag Delivery

The admin service stores the flag in `flag.txt` at the working directory root (`labs/ssrf/flag.txt` on host). The app service reads this flag via HTTP request to the admin service (SSRF vulnerability). The bind mount `../flag.txt:/labs/SSRF/flag.txt:ro` on the admin service makes the flag available inside the container at `/labs/SSRF/flag.txt`. The app proxies image requests to the admin service; successful exploitation causes the app to fetch the flag endpoint from admin and return it to the attacker.

## Solvability Test

Phase 5 (pending)
