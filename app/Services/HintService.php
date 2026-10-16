<?php

namespace App\Services;

use App\Models\LabProgress;

/**
 * Hints released so far for a progress row, from config/hints.php (minutes after started_at).
 */
class HintService
{
    /**
     * @return list<array{n: int, text: string}>
     */
    public function due(LabProgress $progress): array
    {
        throw new \LogicException('not implemented');
    }
}
