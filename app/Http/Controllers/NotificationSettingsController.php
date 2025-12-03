<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationSettingsRequest;
use App\Jobs\SendTestNotification;
use App\Models\NotificationSettings;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    /**
     * Show the notification settings form.
     */
    public function edit(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Get or create notification settings for the user
        $settings = $user->notificationSettings ?? new NotificationSettings([
            'email_enabled' => true,
            'email_address' => $user->email,
            'pushover_enabled' => false,
        ]);

        // Check if Pushover credentials are set in .env
        $pushover_env_configured = ! empty(config('services.pushover.user_key'))
            && ! empty(config('services.pushover.token'));

        return view('notification-settings.edit', compact('settings', 'pushover_env_configured'));
    }

    /**
     * Update the notification settings.
     */
    public function update(UpdateNotificationSettingsRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $validated = $request->validated();

        // Check if Pushover credentials are set in .env
        $pushover_env_configured = ! empty(config('services.pushover.user_key'))
            && ! empty(config('services.pushover.token'));

        // If Pushover is configured in .env, remove user-provided credentials
        // Users should only control the enabled/disabled state
        if ($pushover_env_configured) {
            unset($validated['pushover_user_key']);
            unset($validated['pushover_api_token']);
        }

        // Update or create notification settings
        // Pushover API token will be automatically encrypted via model casting
        $user->notificationSettings()->updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return redirect()
            ->route('notification-settings.edit')
            ->with('success', 'Notification settings updated successfully.');
    }

    /**
     * Send a test email notification.
     *
     * Validates that email notifications are enabled for the authenticated user,
     * then dispatches a SendTestNotification job to send a test email asynchronously.
     * Returns immediate feedback to the user without waiting for job execution.
     */
    public function testEmail(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $settings = $user->notificationSettings;

        // Validate notification channel is enabled
        if (! $settings || ! $settings->email_enabled) {
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Email notifications are not enabled. Please enable email notifications first.');
        }

        // Validate email address is configured
        if (empty($settings->email_address)) {
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Email address is not configured. Please configure your email address first.');
        }

        try {
            // Dispatch SendTestNotification job
            SendTestNotification::dispatch($user, 'email');

            // Return immediate redirect with success message
            return redirect()
                ->route('notification-settings.edit')
                ->with('success', 'Test email queued successfully. Check your inbox shortly.');
        } catch (\Exception $e) {
            // Handle errors with appropriate error messages
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Failed to queue test email: '.$e->getMessage());
        }
    }

    /**
     * Send a test Pushover notification.
     *
     * Validates that Pushover notifications are enabled for the authenticated user,
     * then dispatches a SendTestNotification job to send a test Pushover notification asynchronously.
     * Returns immediate feedback to the user without waiting for job execution.
     */
    public function testPushover(): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $settings = $user->notificationSettings;

        // Validate notification channel is enabled
        if (! $settings || ! $settings->pushover_enabled) {
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Pushover notifications are not enabled. Please enable Pushover notifications first.');
        }

        // Check if Pushover credentials are available (either from .env or user settings)
        $pushover_env_configured = ! empty(config('services.pushover.user_key'))
            && ! empty(config('services.pushover.token'));

        $user_credentials_configured = ! empty($settings->pushover_user_key)
            && ! empty($settings->pushover_api_token);

        if (! $pushover_env_configured && ! $user_credentials_configured) {
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Pushover credentials are not configured. Please configure your Pushover credentials first.');
        }

        try {
            // Dispatch SendTestNotification job
            SendTestNotification::dispatch($user, 'pushover');

            // Return immediate redirect with success message
            return redirect()
                ->route('notification-settings.edit')
                ->with('success', 'Test Pushover notification queued successfully. Check your device shortly.');
        } catch (\Exception $e) {
            // Handle errors with appropriate error messages
            return redirect()
                ->route('notification-settings.edit')
                ->with('error', 'Failed to queue test Pushover notification: '.$e->getMessage());
        }
    }
}
