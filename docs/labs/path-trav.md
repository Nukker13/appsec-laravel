# Path Traversal Lab

## Vulnerability

Path Traversal (also known as directory traversal) is a vulnerability that allows an attacker to access files outside the web root by manipulating file path parameters with sequences such as `../`. When the application also accepts unrestricted file uploads, the attack escalates to Local File Inclusion (LFI) — an attacker can upload a malicious file and then force the server to execute it by including it through the same vulnerable parameter. The vulnerability enables unauthorized file access and code execution.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving image gallery with file inclusion | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The flag is issued by `FlagService` before the lab starts and written to the per-instance copy at `storage/app/instances/{project}/lab/flag.txt`. The instance directory is bind-mounted into `app` and `nginx`, so the lab reads it at `/labs/path_trav/flag.txt`.

## Solvability Test

Verified by hand (QA); there is no automated exploit suite (see CHANGELOG, "Phase 5: skipped").
