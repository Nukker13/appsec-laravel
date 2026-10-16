# 0005. Per-instance copy of the lab directory

**Context.** Phase 6 runs one instance per user and lab (`lab_{slug}_u{id}`). Every lab compose file bind-mounts its own folder read-write (`../:/labs/...`), and several keep state in it: MySQL data in `docker/storage/mysql`, `/tmp` in `docker/storage/tmp`, uploads (path-trav), a SQLite file (type-juggle), invoices (insec-deser), and `flag.txt`. Two instances of the same lab would share and corrupt that state, and one student's uploads would be visible to another.

**Decision.** `DockerLabRunner::up()` copies `labs/{slug}` to `storage/app/instances/{project}/lab` (without runtime storage contents) and runs compose from the copy. `FlagService` writes the flag into the copy's `flag.txt`, before `up`, because ssrf mounts it as a single file. `down()` removes the project's containers, volumes and locally built images, then deletes the instance directory.

**Consequences.**
- No lab file or compose file changes; the labs keep reading `flag.txt` where they always did.
- `labs/{slug}` is read-only at runtime, so the repo tree stays clean.
- Each instance builds its own image tag; the build cache makes this fast, and `--rmi local` removes the image on stop.
- Disk use is one lab-folder copy per running instance (largest is about 6 MB).
