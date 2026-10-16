<?php

namespace App\Jobs;

use App\Contracts\LabRunner;
use App\Exceptions\LabRunnerException;
use App\Models\Lab;
use App\Models\LabProgress;
use App\Models\User;
use App\Services\FlagService;
use App\Services\InstanceRegistry;
use App\Services\InstanceResolver;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Under Cache::lock("lab:{project}:lock", 120): if not running -> status starting -> LabRunner::up ->
 * FlagService::issue -> status ready. In every case the launching user gets a lab_user row (started_at only on the
 * first launch), last_seen is refreshed and the project is registered in labs:running.
 *
 * A held lock is waited for up to 10 s; after that the job goes back to the queue. If up() throws LabRunnerException
 * the status becomes failed, no flag is issued, no lab_user row is created, and the project stays registered so that
 * the reaper cleans up whatever compose left behind.
 */
class LaunchLab implements ShouldQueue
{
    use Queueable;

    /** Above DockerLabRunner's compose timeout (150 s). */
    public int $timeout = 180;

    public function __construct(public User $user, public Lab $lab) {}

    public function handle(LabRunner $runner, FlagService $flags, InstanceResolver $resolver, InstanceRegistry $registry): void
    {
        $project = $resolver->project($this->user, $this->lab);

        try {
            Cache::lock("lab:{$project}:lock", 120)->block(10, function () use ($runner, $flags, $registry, $project): void {
                $this->launch($runner, $flags, $registry, $project);
            });
        } catch (LockTimeoutException) {
            $this->release(2);
        }
    }

    private function launch(LabRunner $runner, FlagService $flags, InstanceRegistry $registry, string $project): void
    {
        $registry->register($project, $this->lab->slug);
        $registry->touch($project);

        try {
            if (! $runner->isRunning($project)) {
                $registry->setStatus($project, 'starting');
                $runner->up($this->lab->slug, $project);
                $flags->issue($project, $this->lab);
            }
        } catch (LabRunnerException $e) {
            $registry->setStatus($project, 'failed');
            Log::error('Lab launch failed.', ['project' => $project, 'user_id' => $this->user->getKey(), 'error' => $e->getMessage()]);

            return;
        }

        LabProgress::query()->firstOrCreate(
            ['user_id' => $this->user->getKey(), 'lab_id' => $this->lab->getKey()],
            ['status' => 'started', 'started_at' => now()],
        );
        $registry->setStatus($project, 'ready');
        $registry->touch($project);
    }
}
