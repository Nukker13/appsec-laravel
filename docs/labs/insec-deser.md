# Insecure Deserialization Lab

## Vulnerability

Insecure Deserialization occurs when an application deserializes attacker-controlled data without validation. In PHP, `unserialize()` reconstructs a full object from a string — and automatically invokes lifecycle magic methods on it. This allows an attacker to inject a crafted object of any class available in the application, turning existing code into a gadget chain that produces unintended side effects such as arbitrary file writes or code execution. The vulnerability is particularly dangerous when untrusted data (cookies, form inputs) is unserialized without integrity verification.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend deserializing booking cookie without validation | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

Host file `labs/insec-deser/flag.txt` (written by `FlagService::issue`) is visible as `/labs/insec_deser/flag.txt` inside `app`, `nginx` through the bind mount `../:/labs/insec_deser`. Phase 6 mounts the per-instance file (`LAB_FLAG_FILE`) read-only over this path.

## Solvability Test

Phase 5 (pending)
