<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ReapLabs extends Command
{
    protected $signature = 'labs:reap';

    protected $description = 'Stop lab instances idle for longer than LAB_IDLE_MINUTES';

    public function handle(): int
    {
        throw new \LogicException('not implemented');
    }
}
