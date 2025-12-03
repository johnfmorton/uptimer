<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_pushover_credentials_when_pushover_is_disabled(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/notification-settings', [
            'email_enabled' => '1',
            'email_address' => 'test@example.com',
            'pushover_enabled' => '0', // Pushover disabled
            'pushover_user_key' => 'test_user_key_123',
            'pushover_api_token' => 'test_api_token_456',
        ]);

        $response->assertRedirect('/notification-settings');
        $response->assertSessionHas('success');

        // Verify credentials were saved even though pushover is disabled
        $this->assertDatabaseHas('notification_settings', [
            'user_id' => $user->id,
            'pushover_enabled' => false,
            'pushover_user_key' => 'test_user_key_123',
        ]);

        // Verify the encrypted token was saved (we can't check the exact value due to encryption)
        $settings = $user->notificationSettings;
        $this->assertNotNull($settings->pushover_api_token);
    }

    public function test_updating_settings_does_not_show_pushover_required_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch('/notification-settings', [
            'email_enabled' => '1',
            'email_address' => 'test@example.com',
            'pushover_enabled' => '0',
        ]);

        $response->assertRedirect('/notification-settings');
        $response->assertSessionHas('success');
        $response->assertSessionMissing('errors');
    }

    public function test_test_email_button_works_when_email_enabled(): void
    {
        $user = User::factory()->create();

        // Create notification settings with email enabled
        $user->notificationSettings()->create([
            'email_enabled' => true,
            'email_address' => 'test@example.com',
            'pushover_enabled' => false,
        ]);

        $response = $this->actingAs($user)->post('/notification-settings/test-email');

        $response->assertRedirect('/notification-settings');
        $response->assertSessionHas('success');
        $response->assertSessionHasNoErrors();
    }
}

