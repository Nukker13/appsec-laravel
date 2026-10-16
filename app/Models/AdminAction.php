<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $admin_id
 * @property int $target_user_id
 * @property int|null $lab_id
 * @property string $action progress_reset|progress_complete
 * @property string $reason
 * @property array<string, mixed>|null $meta
 * @property CarbonInterface|null $created_at
 */
class AdminAction extends Model
{
    public const UPDATED_AT = null;

    /** @var list<string> */
    protected $fillable = ['admin_id', 'target_user_id', 'lab_id', 'action', 'reason', 'meta'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }
}
