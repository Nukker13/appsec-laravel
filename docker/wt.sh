#!/usr/bin/env bash
# Isolated toolchain for a git worktree: its own MySQL + Redis on a private network, so parallel
# agents never share the `testing` database. Runs the platform image (appsec-php:8.5) with the
# worktree mounted at its host path. No docker.sock is mounted: tests use FakeLabRunner.
#
#   docker/wt.sh setup              composer install + .env for this worktree, start mysql/redis
#   docker/wt.sh test [args]        php artisan test [args]
#   docker/wt.sh run <cmd...>       any command, e.g. vendor/bin/phpstan analyse
#   docker/wt.sh down               remove this worktree's containers and network
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
NAME="wt_$(basename "$ROOT" | tr -c 'a-zA-Z0-9\n' '_')"
NET="$NAME"
IMAGE="appsec-php:8.5"
MAIN_ENV="$(git -C "$ROOT" worktree list --porcelain | awk '/^worktree /{sub(/^worktree /,""); print; exit}')/.env"

ensure_services() {
    docker network inspect "$NET" >/dev/null 2>&1 || docker network create "$NET" >/dev/null
    if ! docker container inspect "${NAME}_mysql" >/dev/null 2>&1; then
        docker run -d --name "${NAME}_mysql" --network "$NET" --network-alias mysql \
            -e MYSQL_ROOT_PASSWORD=root -e MYSQL_DATABASE=testing mysql:8.0 >/dev/null
    fi
    if ! docker container inspect "${NAME}_redis" >/dev/null 2>&1; then
        docker run -d --name "${NAME}_redis" --network "$NET" --network-alias redis redis:7-alpine >/dev/null
    fi
    docker start "${NAME}_mysql" "${NAME}_redis" >/dev/null
    for _ in $(seq 1 60); do
        docker exec "${NAME}_mysql" mysqladmin ping -h 127.0.0.1 -uroot -proot --silent >/dev/null 2>&1 && return 0
        sleep 1
    done
    echo "mysql did not become ready" >&2
    exit 1
}

run() {
    docker run --rm --network "$NET" -v "$ROOT:$ROOT" -w "$ROOT" \
        -e DB_HOST=mysql -e DB_USERNAME=root -e DB_PASSWORD=root -e REDIS_HOST=redis \
        "$IMAGE" "$@"
}

case "${1:-}" in
    setup)
        ensure_services
        [ -f "$ROOT/.env" ] || cp "$MAIN_ENV" "$ROOT/.env"
        run composer install --no-interaction --no-progress
        ;;
    test)
        shift
        ensure_services
        run php artisan test "$@"
        ;;
    run)
        shift
        ensure_services
        run "$@"
        ;;
    down)
        docker rm -f "${NAME}_mysql" "${NAME}_redis" >/dev/null 2>&1 || true
        docker network rm "$NET" >/dev/null 2>&1 || true
        ;;
    *)
        sed -n '2,10p' "$0"
        exit 1
        ;;
esac
