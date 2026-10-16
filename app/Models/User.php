<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int $id
 * @property string $google_id
 * @property string $email
 * @property string $name
 * @property string $role user|admin
 * @property CarbonInterface|null $last_login_at
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Lab> $labs
 * @property-read Collection<int, FlagAttempt> $flagAttempts
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    /**
     * Mass assignment: "role" is deliberately NOT fillable (defect D6: the OAuth callback must never write it).
     *
     * @var list<string>
     */
    protected $fillable = ['google_id', 'email', 'name'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsToMany<Lab, $this, LabProgress>
     */
    public function labs(): BelongsToMany
    {
        return $this->belongsToMany(Lab::class, 'lab_user')
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

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
