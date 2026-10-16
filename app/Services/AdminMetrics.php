<?php

namespace App\Services;

use App\Models\FlagAttempt;
use App\Models\Lab;
use App\Models\LabProgress;

/**
 * Admin read models. All data is loaded in bulk (three queries), then computed in PHP.
 * Definitions are documented in docs/ADMIN.md.
 */
class AdminMetrics
{
    /**
     * One cell per existing lab_user row, keyed [user_id][lab_id]. Pass a user id to scope to that user.
     *
     * @return array<int, array<int, array{status: string, solve_seconds: int|null, attempts: int}>>
     */
    public function cells(?int $userId = null): array
    {
        $progress = LabProgress::query()
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->get();

        $attempts = $this->attemptCounts($userId);

        $cells = [];
        foreach ($progress as $row) {
            $completed = $row->status === 'completed';
            $cells[$row->user_id][$row->lab_id] = [
                'status' => $row->status,
                'solve_seconds' => $completed && $row->completed_at !== null
                    ? max(0, (int) $row->started_at->diffInSeconds($row->completed_at, true))
                    : null,
                'attempts' => $attempts[$row->user_id][$row->lab_id] ?? 0,
            ];
        }

        return $cells;
    }

    /**
     * Per lab (ordered by sort), keyed by lab id.
     *
     * @return list<array{lab: Lab, started: int, completed: int, completion_pct: float|null, median_seconds: float|null, avg_attempts: float|null, avg_hints: float|null}>
     */
    public function labAnalytics(): array
    {
        $labs = Lab::query()->orderBy('sort')->orderBy('id')->get();
        $cells = $this->cells();

        $byLab = [];
        foreach ($cells as $labCells) {
            foreach ($labCells as $labId => $cell) {
                $byLab[$labId][] = $cell;
            }
        }

        $rows = [];
        foreach ($labs as $lab) {
            $all = $byLab[$lab->id] ?? [];
            $done = array_values(array_filter($all, fn (array $c): bool => $c['status'] === 'completed' && $c['solve_seconds'] !== null));
            $times = array_column($done, 'solve_seconds');

            /** @var list<array{after: int, text: string}> $hints */
            $hints = config('hints.'.$lab->slug, []);
            $released = array_map(
                fn (int $s): int => count(array_filter($hints, fn (array $h): bool => $s >= $h['after'] * 60)),
                $times,
            );

            $rows[] = [
                'lab' => $lab,
                'started' => count($all),
                'completed' => count($done),
                'completion_pct' => $all === [] ? null : count($done) / count($all) * 100,
                'median_seconds' => $this->median($times),
                'avg_attempts' => $done === [] ? null : array_sum(array_column($done, 'attempts')) / count($done),
                'avg_hints' => $done === [] ? null : array_sum($released) / count($done),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<int>  $values
     */
    public function median(array $values): float|int|null
    {
        if ($values === []) {
            return null;
        }
        sort($values);
        $n = count($values);
        $mid = intdiv($n, 2);

        return $n % 2 === 1 ? $values[$mid] : ($values[$mid - 1] + $values[$mid]) / 2;
    }

    public function formatDuration(float|int|null $seconds): string
    {
        if ($seconds === null) {
            return '-';
        }
        $total = (int) round($seconds);

        return intdiv($total, 60).' min '.($total % 60).' s';
    }

    public function formatNumber(float|int|null $value): string
    {
        return $value === null ? '-' : rtrim(rtrim(number_format($value, 1, '.', ''), '0'), '.');
    }

    /**
     * Attempts up to and including the first correct one, or all when none is correct.
     *
     * @return array<int, array<int, int>>
     */
    private function attemptCounts(?int $userId): array
    {
        $counts = [];
        $closed = [];

        $query = FlagAttempt::query()
            ->select(['id', 'user_id', 'lab_id', 'is_correct'])
            ->when($userId !== null, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('id');

        foreach ($query->cursor() as $attempt) {
            if (isset($closed[$attempt->user_id][$attempt->lab_id])) {
                continue;
            }
            $counts[$attempt->user_id][$attempt->lab_id] = ($counts[$attempt->user_id][$attempt->lab_id] ?? 0) + 1;
            if ($attempt->is_correct) {
                $closed[$attempt->user_id][$attempt->lab_id] = true;
            }
        }

        return $counts;
    }
}
