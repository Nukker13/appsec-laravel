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
    /**
     * Compose project name of the instance $user uses for $lab. Phase 3: one shared instance per lab, so $user is unused.
     * The name is lowercase [a-z0-9_] (valid compose project name) and is the key of every lab:{project}:* cache entry.
     */
    public function project(User $user, Lab $lab): string
    {
        return 'lab_'.str_replace('-', '_', $lab->slug);
    }
}
