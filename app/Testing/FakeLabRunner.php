<?php

namespace App\Testing;

use App\Contracts\LabRunner;
use App\Exceptions\LabRunnerException;
use PHPUnit\Framework\Assert;

/**
 * In-memory LabRunner for tests. Not bound anywhere by default; tests swap it in:
 * $this->app->instance(LabRunner::class, $fake = new FakeLabRunner());
 */
class FakeLabRunner implements LabRunner
{
    /** @var list<array{slug: string, project: string}> */
    public array $upCalls = [];

    /** @var list<array{slug: string, project: string}> */
    public array $downCalls = [];

    /** @var array<string, string> running project => slug */
    private array $running = [];

    private ?LabRunnerException $nextUpFailure = null;

    public function up(string $slug, string $project): void
    {
        if ($this->nextUpFailure !== null) {
            $failure = $this->nextUpFailure;
            $this->nextUpFailure = null;

            throw $failure;
        }

        $this->upCalls[] = ['slug' => $slug, 'project' => $project];
        $this->running[$project] = $slug;
    }

    public function down(string $slug, string $project): void
    {
        $this->downCalls[] = ['slug' => $slug, 'project' => $project];
        unset($this->running[$project]);
    }

    public function isRunning(string $project): bool
    {
        return isset($this->running[$project]);
    }

    /** Make the next up() call throw LabRunnerException (only that one call). */
    public function failNextUp(string $message = 'fake up failure'): static
    {
        $this->nextUpFailure = new LabRunnerException($message);

        return $this;
    }

    /** Mark a project as running without recording an up() call. */
    public function markRunning(string $project, string $slug = ''): static
    {
        $this->running[$project] = $slug;

        return $this;
    }

    public function startedCount(string $project): int
    {
        return count(array_filter($this->upCalls, fn (array $c): bool => $c['project'] === $project));
    }

    public function stoppedCount(string $project): int
    {
        return count(array_filter($this->downCalls, fn (array $c): bool => $c['project'] === $project));
    }

    public function assertStarted(string $project): void
    {
        Assert::assertGreaterThan(0, $this->startedCount($project), "Project [{$project}] was never started.");
    }

    public function assertStopped(string $project): void
    {
        Assert::assertGreaterThan(0, $this->stoppedCount($project), "Project [{$project}] was never stopped.");
    }

    public function assertNotStopped(string $project): void
    {
        Assert::assertSame(0, $this->stoppedCount($project), "Project [{$project}] was stopped.");
    }
}
