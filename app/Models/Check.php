<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Check extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'monitor_id',
        'status',
        'status_code',
        'response_time_ms',
        'error_message',
        'checked_at',
        'created_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_at' => 'datetime',
            'created_at' => 'datetime',
            'response_time_ms' => 'integer',
            'status_code' => 'integer',
        ];
    }

    /**
     * Get the monitor that owns the check.
     *
     * @return BelongsTo<Monitor, Check>
     */
    public function monitor(): BelongsTo
    {
        return $this->belongsTo(Monitor::class);
    }

    /**
     * Check if the check was successful.
     *
     * @return bool
     */
    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Check if the check failed.
     *
     * @return bool
     */
    public function wasFailed(): bool
    {
        return $this->status === 'failed';
    }
}
