# 0004 Per-instance flag hash in cache with TTL

## Context
The old code kept one flag per lab in the database and rewrote it on page views (defect D2), so one student's flag changed under another and the stored secret was plaintext.

## Decision
A flag belongs to a running instance, not to a lab or a user. `FlagService::issue` generates 32 random bytes (hex), writes the plaintext only to the instance flag file that the lab reads, and stores `sha256(flag)` in the cache under `lab:{project}:flag` with a TTL of `LAB_MAX_MINUTES` (default 120). `verify` compares hashes with `hash_equals`. `forget` deletes the key when the instance stops. Page views never issue or change a flag; only a launch that actually starts the instance does.

## Consequences
- The database holds no secret. A leaked database or cache dump does not reveal a valid flag (sha256 of 256 random bits).
- An instance that outlives `LAB_MAX_MINUTES` stops accepting its flag; the student relaunches and gets a new one.
- Losing the cache (Redis flush) invalidates running instances' flags until they are relaunched. Redis holds no durable data by design, so this is accepted.
- The plaintext sits in `flag.txt` on disk (phase 3: `labs/{slug}/flag.txt`, phase 6: `storage/app/instances/{project}/flag.txt`) for as long as the instance exists. It is overwritten on the next issue and not removed on stop, but it no longer verifies once `forget` ran.
- Because the key is the compose project name, phase 6 (per-user instances) needs no change here.
