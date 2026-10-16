<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DemoteUser extends Command
{
    protected $signature = 'user:demote {email}';

    protected $description = 'Revoke the admin role from a user';

    public function handle(): int
    {
        throw new \LogicException('not implemented');
    }
}
