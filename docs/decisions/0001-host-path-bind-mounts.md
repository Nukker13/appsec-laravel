# 0001 Host-path bind mounts

## Context
`LabRunner` runs `docker compose` from inside the `app`/`queue` containers against the host daemon. Lab compose files contain bind mounts such as `../:/labs/osci`. The daemon resolves those source paths on the host, not inside our container, so a container-internal path like `/var/www/html/labs/osci` would not exist for it.

## Decision
Mount the project at the same absolute path inside `app`, `queue` and `scheduler` as on the host (`${HOST_PROJECT_PATH:-${PWD}}`), and use it as the working directory. `config/labs.php` derives `path` and `instances_path` from `base_path()`/`storage_path()`, which therefore equal host paths. nginx mounts `public/` at the same path so `SCRIPT_FILENAME` matches php-fpm. The whole project is mounted (not only `labs/` and `storage/app/instances/`) because Laravel itself lives there and the two mounts would otherwise be nested.

## Consequences
- Lab compose files can use relative bind mounts unchanged.
- The path may contain spaces or parentheses (it does on the author's machine); the nginx template quotes it. Lab compose files and Process argument arrays must never rely on shell word splitting.
- Run `docker compose` from the project root, or set `HOST_PROJECT_PATH` explicitly.
- On Linux hosts, file ownership between host and the container user must be sorted out manually (`storage/` writable by www-data).
