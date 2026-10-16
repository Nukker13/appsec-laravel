# OS Command Injection Lab

## Vulnerability

Command injection is an attack where the goal is execution of arbitrary commands on the host OS via a vulnerable application. It is possible when an application passes unsafe user-supplied data to a system shell. This differs from Code Injection: in Command Injection the attacker extends what the application already does (runs system commands), without injecting new code. The attack succeeds when input validation fails to block shell metacharacters and the application executes attacker-controlled commands alongside legitimate ones.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend processing command input | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

Host file `labs/osci/flag.txt` (written by `FlagService::issue`) is visible as `/labs/osci/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/osci`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
