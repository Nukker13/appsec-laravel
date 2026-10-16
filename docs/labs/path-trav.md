# Path Traversal Lab

## Vulnerability

Path Traversal (also known as directory traversal) is a vulnerability that allows an attacker to access files outside the web root by manipulating file path parameters with sequences such as `../`. When the application also accepts unrestricted file uploads, the attack escalates to Local File Inclusion (LFI) — an attacker can upload a malicious file and then force the server to execute it by including it through the same vulnerable parameter. The vulnerability enables unauthorized file access and code execution.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving image gallery with file inclusion | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

Host file `labs/path-trav/flag.txt` (written by `FlagService::issue`) is visible as `/labs/path_trav/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/path_trav`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
