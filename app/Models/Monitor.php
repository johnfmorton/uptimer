<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Monitor extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'check_interval_minutes',
        'status',
        'last_checked_at',
        'last_status_change_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_checked_at' => 'datetime',
            'last_status_change_at' => 'datetime',
            'check_interval_minutes' => 'integer',
        ];
    }

    /**
     * Get the user that owns the monitor.
     *
     * @return BelongsTo<User, Monitor>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the checks for the monitor.
     *
     * @return HasMany<Check>
     */
    public function checks(): HasMany
    {
        return $this->hasMany(Check::class);
    }

    /**
     * Check if the monitor is currently up.
     *
     * @return bool
     */
    public function isUp(): bool
    {
        return $this->status === 'up';
    }

    /**
     * Check if the monitor is currently down.
     *
     * @return bool
     */
    public function isDown(): bool
    {
        return $this->status === 'down';
    }

    /**
     * Check if the monitor is in pending status.
     *
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
