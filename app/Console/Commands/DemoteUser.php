<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class DemoteUser extends Command
{
    protected $signature = 'user:demote {email}';

    protected $description = 'Revoke the admin role from a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user with email {$email}.");

            return self::FAILURE;
        }

        $user->role = 'user'; // not fillable by design
        $user->save();
        $this->info("{$email} now has role user.");

        return self::SUCCESS;
    }
}
