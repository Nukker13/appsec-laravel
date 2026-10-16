<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PromoteUser extends Command
{
    protected $signature = 'user:promote {email}';

    protected $description = 'Grant the admin role to a user';

    public function handle(): int
    {
        throw new \LogicException('not implemented');
    }
}
