<?php

namespace App\Services;

use App\Models\Lab;
use App\Models\LabProgress;
use Carbon\CarbonInterface;

/**
 * Hints released so far for a progress row, from config/hints.php (minutes after the current instance launch).
 *
 * A hint is released when now >= since + after. The schedule is read in config order and
 * numbered from 1 by its position, so numbering stays stable as more hints are released.
 */
class HintService
{
    /**
     * @return list<array{n: int, text: string}>
     */
    public function due(LabProgress $progress, CarbonInterface $since): array
    {
        $now = now();
        $due = [];

        foreach ($this->schedule($progress) as $i => $hint) {
            if ($now->greaterThanOrEqualTo($since->copy()->addMinutes($hint['after']))) {
                $due[] = ['n' => $i + 1, 'text' => $hint['text']];
            }
        }

        return $due;
    }

    /**
     * Release time of the earliest hint that is not yet released, or null when all are out.
     */
    public function nextAt(LabProgress $progress, CarbonInterface $since): ?CarbonInterface
    {
        $now = now();
        $next = null;

        foreach ($this->schedule($progress) as $hint) {
            $at = $since->copy()->addMinutes($hint['after']);
            if ($at->greaterThan($now) && ($next === null || $at->lessThan($next))) {
                $next = $at;
            }
        }

        return $next;
    }

    /**
     * @return list<array{after: int, text: string}>
     */
    private function schedule(LabProgress $progress): array
    {
        $slug = Lab::query()->whereKey($progress->lab_id)->value('slug');
        $schedule = config('hints.'.$slug, []);

        /** @var list<array{after: int, text: string}> $list */
        $list = is_array($schedule) ? array_values($schedule) : [];

        return $list;
    }
}
