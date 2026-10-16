<?php

namespace App\Contracts;

interface LabRunner
{
    /** Start compose project $project from labs/{$slug}/docker/docker-compose.yml. Throws LabRunnerException. */
    public function up(string $slug, string $project): void;

    /** Stop and remove compose project $project and clear labs/{$slug} storage. Idempotent. */
    public function down(string $slug, string $project): void;

    public function isRunning(string $project): bool;
}
