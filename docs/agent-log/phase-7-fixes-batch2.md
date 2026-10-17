# Phase 7 fixes, batch 2

- `app/Services/DockerLabRunner.php`: `copyLab()` now creates the parent directory of each file under `docker/storage/` or `storage/` in the copy, without copying contents, so the empty tree is recreated.
- `labs/insec-deser/storage/invoices/.gitkeep`: new, so the invoices dir exists in the lab source.
- `labs/{dom-xss,reflect-xss,stored-xss,oauth-ato}/docker/docker-compose.yml`: `db` healthcheck (mysqladmin ping, 5s/5s/30); `app` `depends_on: db: condition: service_healthy`.

Results: `php artisan test` 176 passed (1361 assertions); phpstan no errors; pint 80 files PASS; four `compose config -q` produced no errors.
