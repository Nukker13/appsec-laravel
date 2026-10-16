<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot model for lab_user (one row per user+lab).
 *
 * @property int $id
 * @property int $user_id
 * @property int $lab_id
 * @property string $status started|completed
 * @property CarbonInterface $started_at set on first launch, never overwritten
 * @property CarbonInterface|null $completed_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 */
class LabProgress extends Pivot
{
    protected $table = 'lab_user';

    public $incrementing = true;

    /** @var list<string> */
    protected $fillable = ['user_id', 'lab_id', 'status', 'started_at', 'completed_at'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
