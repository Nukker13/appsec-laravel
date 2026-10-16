<?php

namespace App\Console\Commands;

use App\Jobs\StopLab;
use App\Services\InstanceRegistry;
use Illuminate\Console\Command;

class ReapLabs extends Command
{
    protected $signature = 'labs:reap';

    protected $description = 'Stop lab instances idle for longer than LAB_IDLE_MINUTES';

    public function handle(InstanceRegistry $registry): int
    {
        $deadline = now()->timestamp - config()->integer('labs.idle_minutes') * 60;

        foreach ($registry->running() as $project => $slug) {
            $lastSeen = $registry->lastSeen($project);

            // No last_seen means the entry is orphaned: treat it as expired.
            if ($lastSeen === null || $lastSeen < $deadline) {
                StopLab::dispatch($slug, $project);
            }
        }

        return self::SUCCESS;
    }
}
