<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LabFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $slug
 * @property string $name
 * @property string $category frontend|backend
 * @property int $sort
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, FlagAttempt> $flagAttempts
 */
class Lab extends Model
{
    /** @use HasFactory<LabFactory> */
    use HasFactory;

    /** @var list<string> */
    protected $fillable = ['slug', 'name', 'category', 'sort'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @return BelongsToMany<User, $this, LabProgress>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'lab_user')
            ->using(LabProgress::class)
            ->withPivot(['status', 'started_at', 'completed_at'])
            ->withTimestamps();
    }

    /**
     * @return HasMany<FlagAttempt, $this>
     */
    public function flagAttempts(): HasMany
    {
        return $this->hasMany(FlagAttempt::class);
    }
}
