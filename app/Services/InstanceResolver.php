<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\User;

/**
 * Maps (user, lab) to the compose project name that keys every instance-related cache entry.
 *
 * Phase 3: lab_{slug with - replaced by _}. Phase 6: lab_{slug}_u{user_id}. Only this method changes.
 */
class InstanceResolver
{
    public function project(User $user, Lab $lab): string
    {
        throw new \LogicException('not implemented');
    }
}
