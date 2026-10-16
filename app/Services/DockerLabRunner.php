<?php

namespace App\Services;

use App\Contracts\LabRunner;
use App\Exceptions\LabRunnerException;

/**
 * Drives docker compose through Symfony Process with an argument array. Rejects slugs that are not in the labs table.
 *
 * up() sets env COMPOSE_PROJECT_NAME={project}, LAB_HOST={project with - for _}.labs.localhost and LAB_FLAG_FILE.
 */
class DockerLabRunner implements LabRunner
{
    /**
     * @throws LabRunnerException
     */
    public function up(string $slug, string $project): void
    {
        throw new \LogicException('not implemented');
    }

    public function down(string $slug, string $project): void
    {
        throw new \LogicException('not implemented');
    }

    public function isRunning(string $project): bool
    {
        throw new \LogicException('not implemented');
    }
}
