<?php

namespace Tests\Support;

use App\Contracts\LabRunner;

/**
 * LabRunner whose state lives in files, so that several PHP processes see the same instances.
 * up() is slow on purpose: without a lock, a second launch arrives while the first one is still starting.
 */
class SharedFileLabRunner implements LabRunner
{
    public function __construct(private readonly string $dir, private readonly int $upMicroseconds = 1_500_000) {}

    public function up(string $slug, string $project): void
    {
        file_put_contents($this->dir.'/up.log', getmypid()." {$slug} {$project}\n", FILE_APPEND | LOCK_EX);
        usleep($this->upMicroseconds);
        touch($this->marker($project));
    }

    public function down(string $slug, string $project): void
    {
        file_put_contents($this->dir.'/down.log', getmypid()." {$slug} {$project}\n", FILE_APPEND | LOCK_EX);
        @unlink($this->marker($project));
    }

    public function isRunning(string $project): bool
    {
        return is_file($this->marker($project));
    }

    /** @return list<string> one entry per up() call, across all processes */
    public function upLog(): array
    {
        return is_file($this->dir.'/up.log') ? file($this->dir.'/up.log', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
    }

    private function marker(string $project): string
    {
        return $this->dir.'/running-'.$project;
    }
}
