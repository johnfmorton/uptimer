<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    /**
     * Notify about a monitor status change.
     *
     * Routes notifications to all enabled channels based on user's notification settings.
     * Errors in notification delivery are logged but do not block the notification process.
     *
     * @param  Monitor  $monitor
     * @param  string  $old_status
     * @param  string  $new_status
     * @return void
     */
    public function notifyStatusChange(Monitor $monitor, string $old_status, string $new_status): void
    {
        // Load user's notification settings
        $notification_settings = $monitor->user->notificationSettings ?? null;
        
        // Log notification attempt with context
        Log::channel('notifications')->info('Processing status change notification', [
            'monitor_id' => $monitor->id,
            'monitor_name' => $monitor->name,
            'monitor_url' => $monitor->url,
            'user_id' => $monitor->user->id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'has_notification_settings' => $notification_settings !== null,
            'email_enabled' => $notification_settings?->email_enabled ?? false,
            'pushover_enabled' => $notification_settings?->pushover_enabled ?? false,
        ]);
        
        // If no notification settings exist, skip notifications
        if (! $notification_settings) {
            Log::channel('notifications')->warning('No notification settings found for user, skipping notifications', [
                'monitor_id' => $monitor->id,
                'user_id' => $monitor->user->id,
            ]);
            return;
        }
        
        // Send email notification if enabled
        if ($notification_settings->email_enabled && $notification_settings->email_address) {
            try {
                Log::channel('notifications')->info('Sending email notification for status change', [
                    'monitor_id' => $monitor->id,
                    'email_address' => $notification_settings->email_address,
                    'status' => $new_status,
                ]);
                
                $this->sendEmailNotification($monitor, $new_status, $notification_settings->email_address);
                
            } catch (\Exception $e) {
                Log::channel('notifications')->error('Failed to send email notification for status change', [
                    'monitor_id' => $monitor->id,
                    'email' => $notification_settings->email_address,
                    'status' => $new_status,
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);
            }
        } else {
            Log::channel('notifications')->info('Email notification skipped', [
                'monitor_id' => $monitor->id,
                'email_enabled' => $notification_settings->email_enabled,
                'email_address_configured' => !empty($notification_settings->email_address),
            ]);
        }
        
        // Send Pushover notification if enabled
        if ($notification_settings->isPushoverEffectivelyEnabled()) {
            try {
                $priority = $new_status === 'down' ? 2 : 0;
                
                $user_key = $notification_settings->getEffectivePushoverUserKey();
                $api_token = $notification_settings->getEffectivePushoverApiToken();
                $credential_sources = $notification_settings->getPushoverCredentialSources();
                
                Log::channel('notifications')->info('Sending Pushover notification for status change', [
                    'monitor_id' => $monitor->id,
                    'status' => $new_status,
                    'priority' => $priority,
                    'credential_sources' => $credential_sources,
                    'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
                ]);
                
                $this->sendPushoverNotification(
                    $monitor, 
                    $new_status, 
                    $priority,
                    $user_key,
                    $api_token
                );
                
            } catch (\Exception $e) {
                Log::channel('notifications')->error('Failed to send Pushover notification for status change', [
                    'monitor_id' => $monitor->id,
                    'status' => $new_status,
                    'credential_sources' => $notification_settings->getPushoverCredentialSources(),
                    'error' => $e->getMessage(),
                    'error_class' => get_class($e),
                ]);
            }
        } else {
            Log::channel('notifications')->info('Pushover notification skipped', [
                'monitor_id' => $monitor->id,
                'pushover_enabled' => $notification_settings->pushover_enabled,
                'credential_sources' => $notification_settings->getPushoverCredentialSources(),
                'effectively_enabled' => $notification_settings->isPushoverEffectivelyEnabled(),
            ]);
        }
    }

    /**
     * Send an email notification about a monitor status change.
     *
     * @param  Monitor  $monitor
     * @param  string  $status
     * @param  string  $email_address
     * @return void
     */
    public function sendEmailNotification(Monitor $monitor, string $status, string $email_address): void
    {
        $subject = $status === 'down' 
            ? "🔴 Monitor Down: {$monitor->name}"
            : "✅ Monitor Recovered: {$monitor->name}";
        
        // Log email notification attempt with configuration details
        Log::channel('notifications')->info('Attempting to send email notification', [
            'monitor_id' => $monitor->id,
            'monitor_name' => $monitor->name,
            'monitor_url' => $monitor->url,
            'status' => $status,
            'email_address' => $email_address,
            'subject' => $subject,
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
        ]);
        
        $data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => now()->timezone(config('app.display_timezone')),
        ];
        
        // Determine which template to use based on status
        $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
        
        // Add status-specific data
        if ($status === 'down') {
            $latest_check = $monitor->checks()->latest('checked_at')->first();
            $data['error_details'] = $latest_check?->error_message ?? 'Unknown error';
            $data['status_code'] = $latest_check?->status_code;
        } else {
            // Calculate downtime duration for recovery notifications
            if ($monitor->last_status_change_at) {
                $downtime_minutes = now()->diffInMinutes($monitor->last_status_change_at);
                $data['downtime_duration'] = $this->formatDuration($downtime_minutes);
            }
        }
        
        try {
            Mail::send($template, $data, function ($message) use ($email_address, $subject) {
                $message->to($email_address)
                        ->subject($subject);
            });
            
            Log::channel('notifications')->info('Email notification sent successfully', [
                'monitor_id' => $monitor->id,
                'email_address' => $email_address,
                'template' => $template,
                'subject' => $subject,
            ]);
            
        } catch (\Exception $e) {
            Log::channel('notifications')->error('Email notification failed to send', [
                'monitor_id' => $monitor->id,
                'email_address' => $email_address,
                'template' => $template,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'troubleshooting_hints' => [
                    'Check MAIL_* environment variables in .env',
                    'Verify SMTP server is accessible from production server',
                    'Check firewall rules for outbound SMTP connections',
                    'Verify mail credentials and authentication',
                    'Check mail server logs for rejected messages',
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Send a Pushover notification about a monitor status change.
     *
     * @param  Monitor  $monitor
     * @param  string  $status
     * @param  int  $priority
     * @param  string  $user_key
     * @param  string  $api_token
     * @return void
     */
    public function sendPushoverNotification(
        Monitor $monitor, 
        string $status, 
        int $priority,
        string $user_key,
        string $api_token
    ): void {
        $message = $status === 'down'
            ? "Monitor '{$monitor->name}' is DOWN"
            : "Monitor '{$monitor->name}' has RECOVERED";
        
        $title = $status === 'down' ? 'Monitor Down' : 'Monitor Recovered';
        
        $payload = [
            'token' => $api_token,
            'user' => $user_key,
            'message' => $message,
            'title' => $title,
            'priority' => $priority,
            'url' => $monitor->url,
            'url_title' => 'View Monitor',
        ];
        
        // Priority 2 (emergency) requires expire and retry parameters
        if ($priority === 2) {
            $payload['expire'] = 3600; // Expire after 1 hour
            $payload['retry'] = 60;    // Retry every 60 seconds
        }
        
        // Log Pushover notification attempt with configuration details
        Log::channel('notifications')->info('Attempting to send Pushover notification', [
            'monitor_id' => $monitor->id,
            'monitor_name' => $monitor->name,
            'monitor_url' => $monitor->url,
            'status' => $status,
            'priority' => $priority,
            'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4), // Partially masked for security
            'api_token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4), // Partially masked for security
            'message' => $message,
            'title' => $title,
            'payload_keys' => array_keys($payload),
        ]);
        
        try {
            $response = Http::asForm()->post('https://api.pushover.net/1/messages.json', $payload);
            
            // Log detailed response information
            Log::channel('notifications')->info('Pushover API response received', [
                'monitor_id' => $monitor->id,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'successful' => $response->successful(),
            ]);
            
            if (! $response->successful()) {
                $error_message = "Pushover API request failed: {$response->status()} - {$response->body()}";
                
                Log::channel('notifications')->error('Pushover notification failed', [
                    'monitor_id' => $monitor->id,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'response_headers' => $response->headers(),
                    'payload_sent' => array_merge($payload, [
                        'token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
                        'user' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
                    ]),
                    'troubleshooting_hints' => [
                        'Verify Pushover API token is valid and active',
                        'Check Pushover user key is correct',
                        'Ensure production server can reach api.pushover.net (port 443)',
                        'Check firewall rules for outbound HTTPS connections',
                        'Verify Pushover account has not exceeded message limits',
                        'Check Pushover API status at https://status.pushover.net/',
                    ],
                ]);
                
                throw new \RuntimeException($error_message);
            }
            
            Log::channel('notifications')->info('Pushover notification sent successfully', [
                'monitor_id' => $monitor->id,
                'priority' => $priority,
                'message_length' => strlen($message),
            ]);
            
        } catch (\Exception $e) {
            Log::channel('notifications')->error('Pushover notification exception occurred', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
                'api_token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
                'troubleshooting_hints' => [
                    'Check internet connectivity from production server',
                    'Verify DNS resolution for api.pushover.net',
                    'Check SSL/TLS certificate validation',
                    'Ensure cURL is properly configured on server',
                    'Check for any proxy or firewall blocking HTTPS requests',
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Send a test email notification.
     *
     * Sends a test email to verify email configuration is working correctly.
     * Uses the user's notification settings for the email address.
     *
     * @param  \App\Models\User  $user
     * @return void
     * @throws \Exception If email sending fails
     */
    public function sendTestEmail(\App\Models\User $user): void
    {
        $notification_settings = $user->notificationSettings;
        
        if (! $notification_settings || ! $notification_settings->email_address) {
            throw new \RuntimeException('Email address not configured');
        }
        
        $app_name = config('app.name', 'Laravel App');
        $subject = "Test Notification - {$app_name}";
        
        $data = [
            'user' => $user,
            'timestamp' => now()->timezone(config('app.display_timezone')),
        ];
        
        // Log test email attempt with detailed configuration
        Log::channel('notifications')->info('Attempting to send test email notification', [
            'user_id' => $user->id,
            'email_address' => $notification_settings->email_address,
            'subject' => $subject,
            'mail_driver' => config('mail.default'),
            'mail_host' => config('mail.mailers.smtp.host'),
            'mail_port' => config('mail.mailers.smtp.port'),
            'mail_username' => config('mail.mailers.smtp.username') ? 'configured' : 'not_configured',
            'mail_password' => config('mail.mailers.smtp.password') ? 'configured' : 'not_configured',
            'mail_encryption' => config('mail.mailers.smtp.encryption') ?? 'none',
            'mail_from_address' => config('mail.from.address'),
            'mail_from_name' => config('mail.from.name'),
            'template' => 'emails.test-notification',
        ]);
        
        try {
            Mail::send('emails.test-notification', $data, function ($message) use ($notification_settings, $subject) {
                $message->to($notification_settings->email_address)
                        ->subject($subject);
            });
            
            Log::channel('notifications')->info('Test email notification sent successfully', [
                'user_id' => $user->id,
                'email_address' => $notification_settings->email_address,
                'subject' => $subject,
            ]);
            
        } catch (\Exception $e) {
            Log::channel('notifications')->error('Test email notification failed to send', [
                'user_id' => $user->id,
                'email_address' => $notification_settings->email_address,
                'subject' => $subject,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'mail_driver' => config('mail.default'),
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'troubleshooting_hints' => [
                    'Check MAIL_* environment variables in .env file',
                    'Verify SMTP server credentials are correct',
                    'Test SMTP connection manually (telnet/openssl)',
                    'Check if SMTP server requires authentication',
                    'Verify email address format is valid',
                    'Check spam/junk folders for delivered emails',
                    'Ensure production server can reach SMTP server',
                    'Check mail server logs for rejected messages',
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Send a test Pushover notification.
     *
     * Sends a test Pushover notification to verify Pushover configuration is working correctly.
     * Uses the user's notification settings for Pushover credentials.
     *
     * @param  \App\Models\User  $user
     * @return void
     * @throws \Exception If Pushover sending fails
     */
    public function sendTestPushover(\App\Models\User $user): void
    {
        $notification_settings = $user->notificationSettings;
        
        if (! $notification_settings || ! $notification_settings->isPushoverEffectivelyEnabled()) {
            $credential_sources = $notification_settings?->getPushoverCredentialSources() ?? ['user_key_source' => 'none', 'api_token_source' => 'none', 'both_from_env' => false];
            throw new \RuntimeException("Pushover credentials not configured (sources: " . json_encode($credential_sources) . ")");
        }
        
        $user_key = $notification_settings->getEffectivePushoverUserKey();
        $api_token = $notification_settings->getEffectivePushoverApiToken();
        $credential_sources = $notification_settings->getPushoverCredentialSources();
        
        $app_name = config('app.name', 'Laravel App');
        $app_url = config('app.url', 'localhost');
        
        $message = "This is a test notification from {$app_name} ({$app_url}) to verify your Pushover configuration is working correctly.";
        $title = "Test Notification - {$app_name}";
        $priority = 0; // Normal priority
        
        $payload = [
            'token' => $api_token,
            'user' => $user_key,
            'message' => $message,
            'title' => $title,
            'priority' => $priority,
        ];
        
        // Log test Pushover attempt with configuration details
        Log::channel('notifications')->info('Attempting to send test Pushover notification', [
            'user_id' => $user->id,
            'credential_sources' => $credential_sources,
            'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
            'api_token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
            'message' => $message,
            'title' => $title,
            'priority' => $priority,
            'payload_keys' => array_keys($payload),
        ]);
        
        try {
            $response = Http::asForm()->post('https://api.pushover.net/1/messages.json', $payload);
            
            // Log detailed response information
            Log::channel('notifications')->info('Test Pushover API response received', [
                'user_id' => $user->id,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'response_headers' => $response->headers(),
                'successful' => $response->successful(),
            ]);
            
            if (! $response->successful()) {
                $error_message = "Pushover API request failed: {$response->status()} - {$response->body()}";
                
                Log::channel('notifications')->error('Test Pushover notification failed', [
                    'user_id' => $user->id,
                    'credential_sources' => $credential_sources,
                    'status_code' => $response->status(),
                    'response_body' => $response->body(),
                    'response_headers' => $response->headers(),
                    'payload_sent' => array_merge($payload, [
                        'token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
                        'user' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
                    ]),
                    'troubleshooting_hints' => [
                        'Verify Pushover API token is valid and active',
                        'Check Pushover user key is correct (30 characters)',
                        'Ensure production server can reach api.pushover.net (port 443)',
                        'Check firewall rules for outbound HTTPS connections',
                        'Verify Pushover account has not exceeded message limits',
                        'Test credentials manually with curl command',
                        'Check Pushover API status at https://status.pushover.net/',
                    ],
                ]);
                
                throw new \RuntimeException($error_message);
            }
            
            Log::channel('notifications')->info('Test Pushover notification sent successfully', [
                'user_id' => $user->id,
                'priority' => $priority,
                'message_length' => strlen($message),
            ]);
            
        } catch (\Exception $e) {
            Log::channel('notifications')->error('Test Pushover notification exception occurred', [
                'user_id' => $user->id,
                'credential_sources' => $credential_sources,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'user_key' => substr($user_key, 0, 8) . '...' . substr($user_key, -4),
                'api_token' => substr($api_token, 0, 8) . '...' . substr($api_token, -4),
                'troubleshooting_hints' => [
                    'Check internet connectivity from production server',
                    'Verify DNS resolution for api.pushover.net',
                    'Test with: nslookup api.pushover.net',
                    'Test with: curl -I https://api.pushover.net',
                    'Check SSL/TLS certificate validation',
                    'Ensure cURL is properly configured on server',
                    'Check for any proxy or firewall blocking HTTPS requests',
                ],
            ]);
            throw $e;
        }
    }

    /**
     * Format duration in minutes to a human-readable string.
     *
     * @param  int|float  $minutes
     * @return string
     */
    private function formatDuration(int|float $minutes): string
    {
        $minutes = (int) round($minutes);
        
        if ($minutes < 60) {
            return "{$minutes} minute" . ($minutes !== 1 ? 's' : '');
        }
        
        $hours = floor($minutes / 60);
        $remaining_minutes = $minutes % 60;
        
        if ($hours < 24) {
            $duration = "{$hours} hour" . ($hours !== 1 ? 's' : '');
            if ($remaining_minutes > 0) {
                $duration .= " and {$remaining_minutes} minute" . ($remaining_minutes !== 1 ? 's' : '');
            }
            return $duration;
        }
        
        $days = floor($hours / 24);
        $remaining_hours = $hours % 24;
        
        $duration = "{$days} day" . ($days !== 1 ? 's' : '');
        if ($remaining_hours > 0) {
            $duration .= " and {$remaining_hours} hour" . ($remaining_hours !== 1 ? 's' : '');
        }
        
        return $duration;
    }
}
