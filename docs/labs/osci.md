# OS Command Injection Lab

## Vulnerability

Command injection is an attack where the goal is execution of arbitrary commands on the host OS via a vulnerable application. It is possible when an application passes unsafe user-supplied data to a system shell. This differs from Code Injection: in Command Injection the attacker extends what the application already does (runs system commands), without injecting new code. The attack succeeds when input validation fails to block shell metacharacters and the application executes attacker-controlled commands alongside legitimate ones.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend processing command input | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The lab app reads flag.txt from the current working directory (`/labs/osci/flag.txt` inside the container). The flag is written to `labs/osci/flag.txt` on the host (relative to the worktree root) via the bind mount `../:/labs/osci`. The HTTP endpoint allows the attacker to execute shell commands, and successful exploitation reads the flag file.

## Solvability Test

Phase 5 (pending)
