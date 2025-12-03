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
        
        // If no notification settings exist, skip notifications
        if (! $notification_settings) {
            return;
        }
        
        // Send email notification if enabled
        if ($notification_settings->email_enabled && $notification_settings->email_address) {
            try {
                $this->sendEmailNotification($monitor, $new_status, $notification_settings->email_address);
            } catch (\Exception $e) {
                Log::error('Failed to send email notification', [
                    'monitor_id' => $monitor->id,
                    'email' => $notification_settings->email_address,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Send Pushover notification if enabled
        if ($notification_settings->pushover_enabled && 
            $notification_settings->pushover_user_key && 
            $notification_settings->pushover_api_token) {
            try {
                $priority = $new_status === 'down' ? 2 : 0;
                $this->sendPushoverNotification(
                    $monitor, 
                    $new_status, 
                    $priority,
                    $notification_settings->pushover_user_key,
                    $notification_settings->pushover_api_token
                );
            } catch (\Exception $e) {
                Log::error('Failed to send Pushover notification', [
                    'monitor_id' => $monitor->id,
                    'error' => $e->getMessage(),
                ]);
            }
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
        
        $data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => now(),
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
        
        Mail::send($template, $data, function ($message) use ($email_address, $subject) {
            $message->to($email_address)
                    ->subject($subject);
        });
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
        
        $response = Http::asForm()->post('https://api.pushover.net/1/messages.json', [
            'token' => $api_token,
            'user' => $user_key,
            'message' => $message,
            'title' => $title,
            'priority' => $priority,
            'url' => $monitor->url,
            'url_title' => 'View Monitor',
        ]);
        
        if (! $response->successful()) {
            throw new \RuntimeException(
                "Pushover API request failed: {$response->status()} - {$response->body()}"
            );
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
