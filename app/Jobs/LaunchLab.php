<?php

namespace App\Jobs;

use App\Models\Lab;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Under Cache::lock("lab:{project}:lock", 120): if not running -> status starting -> LabRunner::up ->
 * FlagService::issue -> status ready; sets started_at on first launch; touches last_seen.
 */
class LaunchLab implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user, public Lab $lab) {}

    public function handle(): void
    {
        throw new \LogicException('not implemented');
    }
}
