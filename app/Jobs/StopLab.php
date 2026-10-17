<?php

namespace App\Jobs;

use App\Contracts\LabRunner;
use App\Services\FlagService;
use App\Services\InstanceRegistry;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

/**
 * LabRunner::down, FlagService::forget, status stopped, project removed from labs:running.
 * Takes the same instance lock as LaunchLab so a stop never interleaves with a start.
 */
class StopLab implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public string $slug, public string $project) {}

    public function handle(LabRunner $runner, FlagService $flags, InstanceRegistry $registry): void
    {
        try {
            Cache::lock("lab:{$this->project}:lock", 120)->block(10, function () use ($runner, $flags, $registry): void {
                $runner->down($this->slug, $this->project);
                $flags->forget($this->project);
                $registry->clearLaunched($this->project);
                $registry->setStatus($this->project, 'stopped');
                $registry->unregister($this->project);
            });
        } catch (LockTimeoutException) {
            $this->release(2);
        }
    }
}
