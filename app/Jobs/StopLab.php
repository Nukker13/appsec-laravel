<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** LabRunner::down, FlagService::forget, status stopped. */
class StopLab implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $slug, public string $project) {}

    public function handle(): void
    {
        throw new \LogicException('not implemented');
    }
}
