<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateNotificationSettingsRequest;
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
}
