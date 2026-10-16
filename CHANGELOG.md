# Changelog

## Unreleased

### Phase 3: platform implementation (2026-10-16)
- **Auth** (D5, D6, D7, D13): Google login via Socialite, users matched by `google_id`, `role` never written by the callback, unverified Google emails and email/`google_id` conflicts refused, CSRF-protected logout, `user:promote` / `user:demote`.
- **Runtime & flag** (D1, D2, D3, D4, D8, D10, D11): `DockerLabRunner` with argument arrays and a slug whitelist, per-instance flag hash in cache with TTL, `LaunchLab` / `StopLab` under a per-instance lock, status polling, idle reaper, rate-limited flag check scoped to the current user. An instance is never `ready` without a valid flag.
- **Labs** (D10): lab apps imported unchanged. Compose files have no `stopper`, host ports or `container_name` and are routed by Traefik on `*.labs.localhost`. `composer.lock` is shipped for the four composer-based labs, nginx waits for app, and the platform image now ships `docker-buildx`.
- **Catalog & hints** (D9): catalog with per-user status, a lab page shell with a launch/status/flag UI, hints served only by the endpoint and released from the server-side `started_at`.
- Verified end to end: launch through the queue, `ready` in about 6 s, reachable through Traefik, flag verifies; stop tears the instance down and forgets the flag.

### Phase 2: tests first
- Full spec test suite written before the implementation (feature, unit, Redis integration, data invariants).

### Phase 1: foundation
- Laravel skeleton, Docker stack (app, nginx, mysql, redis, queue, scheduler, traefik), schema, models, seeder, contract stubs, `FakeLabRunner`, CI, Larastan, Pint.
- Platform services kept off the lab network `labs_proxy` (review finding).
