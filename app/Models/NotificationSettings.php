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

    /**
     * Get the effective Pushover user key.
     * 
     * Returns environment variable if set, otherwise returns database value.
     * This treats .env as the source of truth for Pushover credentials.
     *
     * @return string|null
     */
    public function getEffectivePushoverUserKey(): ?string
    {
        $env_user_key = env('PUSHOVER_USER_KEY');
        
        if ($env_user_key && strlen($env_user_key) === 30) {
            return $env_user_key;
        }
        
        return $this->pushover_user_key;
    }

    /**
     * Get the effective Pushover API token.
     * 
     * Returns environment variable if set, otherwise returns database value.
     * This treats .env as the source of truth for Pushover credentials.
     *
     * @return string|null
     */
    public function getEffectivePushoverApiToken(): ?string
    {
        $env_api_token = env('PUSHOVER_API_TOKEN');
        
        if ($env_api_token && strlen($env_api_token) === 30) {
            return $env_api_token;
        }
        
        return $this->pushover_api_token;
    }

    /**
     * Check if Pushover is effectively enabled and configured.
     * 
     * Returns true if Pushover is enabled AND we have valid credentials
     * (either from environment or database).
     *
     * @return bool
     */
    public function isPushoverEffectivelyEnabled(): bool
    {
        if (!$this->pushover_enabled) {
            return false;
        }
        
        $user_key = $this->getEffectivePushoverUserKey();
        $api_token = $this->getEffectivePushoverApiToken();
        
        return !empty($user_key) && !empty($api_token);
    }

    /**
     * Get the source of the current Pushover credentials.
     * 
     * Returns 'environment', 'database', or 'none' to help with debugging.
     *
     * @return string
     */
    public function getPushoverCredentialSource(): string
    {
        $env_user_key = env('PUSHOVER_USER_KEY');
        $env_api_token = env('PUSHOVER_API_TOKEN');
        
        $has_env_credentials = $env_user_key && strlen($env_user_key) === 30 && 
                              $env_api_token && strlen($env_api_token) === 30;
        
        $has_db_credentials = !empty($this->pushover_user_key) && !empty($this->pushover_api_token);
        
        if ($has_env_credentials) {
            return 'environment';
        } elseif ($has_db_credentials) {
            return 'database';
        } else {
            return 'none';
        }
    }

    /**
     * Get the source of Pushover credentials for debugging.
     * 
     * Returns information about where the credentials are coming from.
     *
     * @return array{user_key_source: string, api_token_source: string, both_from_env: bool}
     */
    public function getPushoverCredentialSources(): array
    {
        $env_user_key = env('PUSHOVER_USER_KEY');
        $env_api_token = env('PUSHOVER_API_TOKEN');
        
        $user_key_from_env = $env_user_key && strlen($env_user_key) === 30;
        $api_token_from_env = $env_api_token && strlen($env_api_token) === 30;
        
        return [
            'user_key_source' => $user_key_from_env ? 'environment' : 'database',
            'api_token_source' => $api_token_from_env ? 'environment' : 'database',
            'both_from_env' => $user_key_from_env && $api_token_from_env,
        ];
    }
}
