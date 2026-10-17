# OS Command Injection Lab

## Vulnerability

Command injection is an attack where the goal is execution of arbitrary commands on the host OS via a vulnerable application. It is possible when an application passes unsafe user-supplied data to a system shell. This differs from Code Injection: in Command Injection the attacker extends what the application already does (runs system commands), without injecting new code. The attack succeeds when input validation fails to block shell metacharacters and the application executes attacker-controlled commands alongside legitimate ones.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend processing command input | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/osci/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
