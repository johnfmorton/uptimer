<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationSettings extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'email_enabled',
        'email_address',
        'pushover_enabled',
        'pushover_user_key',
        'pushover_api_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'pushover_enabled' => 'boolean',
            'pushover_api_token' => 'encrypted',
        ];
    }

    /**
     * Get the user that owns the notification settings.
     *
     * @return BelongsTo<User, NotificationSettings>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
