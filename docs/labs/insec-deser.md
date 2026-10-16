# Insecure Deserialization Lab

## Vulnerability

Insecure Deserialization occurs when an application deserializes attacker-controlled data without validation. In PHP, `unserialize()` reconstructs a full object from a string — and automatically invokes lifecycle magic methods on it. This allows an attacker to inject a crafted object of any class available in the application, turning existing code into a gadget chain that produces unintended side effects such as arbitrary file writes or code execution. The vulnerability is particularly dangerous when untrusted data (cookies, form inputs) is unserialized without integrity verification.

## Containers

| Service | Image | Role | Networks | Aliases |
|---------|-------|------|----------|---------|
| app | Custom (Dockerfile) | PHP backend deserializing booking cookie without validation | default | — |
| nginx | nginx:1.29 | HTTP server, Traefik entry point | default, labs_proxy | — |

## Flag Delivery

The lab app stores the flag in `flag.txt` at the app root (`labs/insec-deser/flag.txt` on host). The app deserializes the `booking` cookie and stores it in the `storage/` directory. Successful exploitation crafts a serialized object that exploits a `__destruct()` magic method in the app's class hierarchy, writes a PHP file containing code that reads the flag, and executes it to retrieve the flag.

## Solvability Test

Phase 5 (pending)
