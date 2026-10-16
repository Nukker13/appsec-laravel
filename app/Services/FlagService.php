<?php

namespace App\Services;

use App\Models\Lab;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * Per-instance flag lifecycle. Only the sha256 of the flag is stored in cache (lab:{project}:flag, TTL LAB_MAX_MINUTES).
 */
class FlagService
{
    /**
     * Random 32-byte hex flag: plaintext to the instance flag file, sha256 to cache with TTL labs.max_minutes.
     * Issuing again replaces both. The plaintext is never kept anywhere but the file.
     */
    public function issue(string $project, Lab $lab): void
    {
        $flag = bin2hex(random_bytes(32));
        $file = $this->flagFile($project, $lab);

        File::ensureDirectoryExists(dirname($file));
        File::put($file, $flag."\n");
        // The lab container's user is not the queue worker's user: the flag file must be readable by it.
        @chmod($file, 0644);

        Cache::put($this->key($project), hash('sha256', $flag), now()->addMinutes(config()->integer('labs.max_minutes')));
    }

    /** hash_equals against the stored hash. */
    public function verify(string $project, string $submitted): bool
    {
        $stored = Cache::get($this->key($project));

        if (! is_string($stored) || $submitted === '') {
            return false;
        }

        return hash_equals($stored, hash('sha256', $submitted));
    }

    /** Drop the stored hash. Idempotent. */
    public function forget(string $project): void
    {
        Cache::forget($this->key($project));
    }

    /** Absolute path of the instance flag file. Phase 3: labs/{slug}/flag.txt; phase 6: storage/app/instances/{project}/flag.txt. */
    public function flagFile(string $project, Lab $lab): string
    {
        return rtrim(config()->string('labs.path'), '/').'/'.$lab->slug.'/flag.txt';
    }

    private function key(string $project): string
    {
        return "lab:{$project}:flag";
    }
}
