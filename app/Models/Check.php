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
            'checked_at' => 'immutable_datetime',
            'created_at' => 'immutable_datetime',
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
     * Get the checked_at attribute in display timezone.
     *
     * @param  \DateTimeInterface|null  $value
     * @return \Illuminate\Support\Carbon|null
     */
    public function getCheckedAtAttribute($value): ?\Illuminate\Support\Carbon
    {
        if ($value === null) {
            return null;
        }
        
        $display_timezone = config('app.display_timezone');
        
        return \Illuminate\Support\Carbon::parse($value)
            ->timezone($display_timezone);
    }

    /**
     * Get the created_at attribute in display timezone.
     *
     * @param  \DateTimeInterface|null  $value
     * @return \Illuminate\Support\Carbon|null
     */
    public function getCreatedAtAttribute($value): ?\Illuminate\Support\Carbon
    {
        if ($value === null) {
            return null;
        }
        
        $display_timezone = config('app.display_timezone');
        
        return \Illuminate\Support\Carbon::parse($value)
            ->timezone($display_timezone);
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
