# 0002 Docker socket trust boundary

## Context
The platform starts and stops lab containers on demand, so the process that does it needs control of a Docker daemon.

## Decision
`app`, `queue` and `scheduler` mount `/var/run/docker.sock` (read-write) and contain the Docker CLI. All Docker access goes through `App\Contracts\LabRunner` (`DockerLabRunner` uses Symfony Process with an argument array; slugs are checked against the `labs` table). Traefik mounts the socket read-only. No lab container receives the socket. Web requests (php-fpm workers, `www-data`) do not call Docker; only `queue` and `scheduler` (root) do.

## Consequences
- Network rule: the lab network `labs_proxy` never contains a platform service except Traefik. Otherwise a lab shell could speak FastCGI to `app:9000` and reach the socket. Readiness checks must go through Traefik, not directly to instances (needs its own ADR if added).
- Code execution in `app`, `queue` or `scheduler` is root on the host. They must never run lab code or untrusted input; labs run only in their own compose projects.
- A rootless/remote daemon or a socket proxy could narrow this later; it is out of scope for v1.
- Ports are published on `127.0.0.1` only because the labs are deliberately vulnerable.
