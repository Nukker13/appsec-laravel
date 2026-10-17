<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Cache-backed bookkeeping for lab instances, keyed by compose project name:
 * lab:{project}:status, lab:{project}:last_seen and the labs:running registry (project => slug) that the reaper walks.
 *
 * The registry is one cache entry (a map) rather than a Redis set so that it works on every cache store; writes to it
 * are serialised by the short lock labs:running:lock.
 */
class InstanceRegistry
{
    public const RUNNING_KEY = 'labs:running';

    public function status(string $project): string
    {
        $status = Cache::get("lab:{$project}:status");

        return is_string($status) ? $status : 'stopped';
    }

    public function setStatus(string $project, string $status): void
    {
        Cache::forever("lab:{$project}:status", $status);
    }

    public function touch(string $project): void
    {
        Cache::forever("lab:{$project}:last_seen", now()->timestamp);
    }

    public function lastSeen(string $project): ?int
    {
        $value = Cache::get("lab:{$project}:last_seen");

        return is_numeric($value) ? (int) $value : null;
    }

    public function markLaunched(string $project): void
    {
        Cache::forever("lab:{$project}:launched_at", now()->timestamp);
    }

    public function launchedAt(string $project): ?int
    {
        $value = Cache::get("lab:{$project}:launched_at");

        return is_numeric($value) ? (int) $value : null;
    }

    public function clearLaunched(string $project): void
    {
        Cache::forget("lab:{$project}:launched_at");
    }

    /**
     * Atomically claim a capacity slot: true when the project already holds one or a free slot was taken.
     */
    public function reserve(string $project, string $slug, int $max): bool
    {
        $reserved = Cache::lock(self::RUNNING_KEY.':lock', 10)->block(10, function () use ($project, $slug, $max): bool {
            $running = $this->running();

            if (! array_key_exists($project, $running) && count($running) >= $max) {
                return false;
            }

            $running[$project] = $slug;
            Cache::forever(self::RUNNING_KEY, $running);

            return true;
        });

        return $reserved === true;
    }

    public function register(string $project, string $slug): void
    {
        $this->mutate(function (array $running) use ($project, $slug): array {
            $running[$project] = $slug;

            return $running;
        });
    }

    public function unregister(string $project): void
    {
        $this->mutate(function (array $running) use ($project): array {
            unset($running[$project]);

            return $running;
        });
    }

    /**
     * @return array<string, string> project => slug
     */
    public function running(): array
    {
        $running = Cache::get(self::RUNNING_KEY);

        return is_array($running) ? $running : [];
    }

    /**
     * @param  callable(array<string, string>): array<string, string>  $change
     */
    private function mutate(callable $change): void
    {
        Cache::lock(self::RUNNING_KEY.':lock', 10)->block(10, function () use ($change): void {
            Cache::forever(self::RUNNING_KEY, $change($this->running()));
        });
    }
}
