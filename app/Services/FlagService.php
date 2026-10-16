<?php

namespace App\Services;

use App\Models\Lab;

/**
 * Per-instance flag lifecycle. Only the sha256 of the flag is stored in cache (lab:{project}:flag, TTL LAB_MAX_MINUTES).
 */
class FlagService
{
    /** Random 32-byte hex flag: plaintext to the instance flag file, sha256 to cache. */
    public function issue(string $project, Lab $lab): void
    {
        throw new \LogicException('not implemented');
    }

    /** hash_equals against the stored hash. */
    public function verify(string $project, string $submitted): bool
    {
        throw new \LogicException('not implemented');
    }

    public function forget(string $project): void
    {
        throw new \LogicException('not implemented');
    }
}
