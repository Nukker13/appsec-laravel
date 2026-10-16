# Path Traversal Lab

## Vulnerability

Path Traversal (also known as directory traversal) is a vulnerability that allows an attacker to access files outside the web root by manipulating file path parameters with sequences such as `../`. When the application also accepts unrestricted file uploads, the attack escalates to Local File Inclusion (LFI) — an attacker can upload a malicious file and then force the server to execute it by including it through the same vulnerable parameter. The vulnerability enables unauthorized file access and code execution.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend serving image gallery with file inclusion | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The lab app stores the flag in `flag.txt` at the app root (`labs/path-trav/flag.txt` on host). The app allows file uploads to `images/` directory and includes files via the `f` query parameter. Successful exploitation uploads a PHP file containing `shell_exec()` to the images directory, then uses path traversal to read the flag file or execute code that outputs it.

## Solvability Test

Phase 5 (pending)
