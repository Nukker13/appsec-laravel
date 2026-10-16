<?php

namespace Tests\Support;

use App\Exceptions\LabRunnerException;
use App\Models\Lab;
use App\Services\FlagService;
use App\Testing\FakeLabRunner;

/**
 * FakeLabRunner that records what the instance flag file looked like at the moment up() was called
 * (ssrf bind-mounts the file, so it must exist before compose runs), and can fail on up().
 */
class SpyLabRunner extends FakeLabRunner
{
    /** @var list<array{project: string, exists: bool, content: ?string}> */
    public array $flagAtUp = [];

    public bool $failUp = false;

    public function up(string $slug, string $project): void
    {
        $file = app(FlagService::class)->flagFile($project, Lab::query()->where('slug', $slug)->firstOrFail());
        $exists = is_file($file);
        $this->flagAtUp[] = ['project' => $project, 'exists' => $exists, 'content' => $exists ? trim((string) file_get_contents($file)) : null];

        if ($this->failUp) {
            throw new LabRunnerException('spy: up failed');
        }

        parent::up($slug, $project);
    }
}
