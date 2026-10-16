<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\User;

/**
 * Maps (user, lab) to the compose project name that keys every instance-related cache entry.
 *
 * Phase 6: lab_{slug with - replaced by _}_u{user_id} (phase 3 used lab_{slug with - replaced by _}).
 */
class InstanceResolver
{
    /**
     * Compose project name of the instance $user uses for $lab. One instance per user and lab.
     * The name is lowercase [a-z0-9_] (valid compose project name) and is the key of every lab:{project}:* cache entry.
     */
    public function project(User $user, Lab $lab): string
    {
        return 'lab_'.str_replace('-', '_', $lab->slug).'_u'.$user->getKey();
    }
}
