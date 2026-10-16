<?php

namespace App\Services;

use App\Contracts\LabRunner;
use App\Exceptions\LabRunnerException;
use Closure;
use Symfony\Component\Process\Process;

/**
 * Drives docker compose through Symfony Process with an argument array. Rejects slugs that are not in the labs table.
 *
 * up() sets env COMPOSE_PROJECT_NAME={project}, LAB_HOST={project with _ replaced by -}.labs.localhost and LAB_FLAG_FILE.
 */
class DockerLabRunner implements LabRunner
{
    /** @var Closure(list<string>, ?string, array<string, string>): Process */
    private Closure $processFactory;

    /**
     * @param  (Closure(list<string>, ?string, array<string, string>): Process)|null  $processFactory  test seam; every process is created through it
     */
    public function __construct(?Closure $processFactory = null)
    {
        $this->processFactory = $processFactory
            ?? fn (array $command, ?string $cwd, array $env): Process => new Process($command, $cwd, $env);
    }

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

    /**
     * @param  list<string>  $command
     * @param  array<string, string>  $env
     */
    protected function process(array $command, ?string $cwd = null, array $env = []): Process
    {
        return ($this->processFactory)($command, $cwd, $env);
    }
}
