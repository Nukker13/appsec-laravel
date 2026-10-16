<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class PromoteUser extends Command
{
    protected $signature = 'user:promote {email}';

    protected $description = 'Grant the admin role to a user';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            $this->error("No user with email {$email}.");

            return self::FAILURE;
        }

        $user->role = 'admin'; // not fillable by design
        $user->save();
        $this->info("{$email} now has role admin.");

        return self::SUCCESS;
    }
}
