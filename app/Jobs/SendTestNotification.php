<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendTestNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  User  $user  The user to send the test notification to
     * @param  string  $channel  The notification channel ('email' or 'pushover')
     */
    public function __construct(
        public User $user,
        public string $channel
    ) {
    }

    /**
     * Execute the job.
     *
     * This job sends a test notification through the specified channel
     * by delegating to NotificationService. Success and failures are logged
     * with user context for audit trail.
     *
     * @param  NotificationService  $notificationService
     * @return void
     */
    public function handle(NotificationService $notificationService): void
    {
        try {
            Log::channel('notifications')->info("Starting test {$this->channel} notification", [
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'user_email' => $this->user->email,
                'notification_settings_exist' => $this->user->notificationSettings !== null,
                'job_id' => $this->job?->getJobId(),
                'queue' => $this->queue,
            ]);

            if ($this->channel === 'email') {
                // Log email-specific configuration before attempting
                $settings = $this->user->notificationSettings;
                Log::channel('notifications')->info('Email notification configuration check', [
                    'user_id' => $this->user->id,
                    'email_enabled' => $settings?->email_enabled ?? false,
                    'email_address_configured' => !empty($settings?->email_address),
                    'email_address' => $settings?->email_address,
                    'mail_driver' => config('mail.default'),
                ]);
                
                $notificationService->sendTestEmail($this->user);
                
                Log::channel('notifications')->info('Test email sent successfully', [
                    'user_id' => $this->user->id,
                    'email_address' => $settings?->email_address,
                ]);
                
            } elseif ($this->channel === 'pushover') {
                // Log Pushover-specific configuration before attempting
                $settings = $this->user->notificationSettings;
                Log::channel('notifications')->info('Pushover notification configuration check', [
                    'user_id' => $this->user->id,
                    'pushover_enabled' => $settings?->pushover_enabled ?? false,
                    'pushover_user_key_configured' => !empty($settings?->pushover_user_key),
                    'pushover_api_token_configured' => !empty($settings?->pushover_api_token),
                    'user_key_preview' => $settings?->pushover_user_key ? substr($settings->pushover_user_key, 0, 8) . '...' . substr($settings->pushover_user_key, -4) : 'not_configured',
                ]);
                
                $notificationService->sendTestPushover($this->user);
                
                Log::channel('notifications')->info('Test Pushover sent successfully', [
                    'user_id' => $this->user->id,
                ]);
                
            } else {
                throw new \InvalidArgumentException("Invalid notification channel: {$this->channel}");
            }

        } catch (\Exception $e) {
            Log::channel('notifications')->error("Test {$this->channel} notification failed", [
                'user_id' => $this->user->id,
                'channel' => $this->channel,
                'error' => $e->getMessage(),
                'error_class' => get_class($e),
                'trace' => $e->getTraceAsString(),
                'job_id' => $this->job?->getJobId(),
                'attempt' => $this->attempts(),
                'max_tries' => $this->tries ?? 3,
            ]);

            // Re-throw for job retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('notifications')->error("Test {$this->channel} notification job failed permanently", [
            'user_id' => $this->user->id,
            'channel' => $this->channel,
            'error' => $exception->getMessage(),
            'error_class' => get_class($exception),
            'job_id' => $this->job?->getJobId(),
            'final_attempt' => true,
            'troubleshooting_next_steps' => [
                'Check application logs for detailed error information',
                'Verify notification settings are properly configured',
                'Test connectivity to external services manually',
                'Check environment variables and configuration files',
            ],
        ]);
    }
}
