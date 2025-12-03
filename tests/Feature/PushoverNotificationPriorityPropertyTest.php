<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\NotificationSettings;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Property-Based Test for Pushover Notification Priority
 *
 * **Feature: uptime-monitor, Property 16: Pushover notifications use correct priority**
 *
 * Property: For any Pushover notification for a down status, the system should set 
 * priority to high (2), and for recovery status, priority should be normal (0).
 *
 * Validates: Requirements 9.4, 9.5
 */
class PushoverNotificationPriorityPropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private NotificationService $notification_service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->notification_service = new NotificationService();
    }

    /**
     * Property Test: Down status always uses high priority (2).
     *
     * Tests that for any monitor going down, the Pushover notification
     * is sent with priority level 2 (high priority).
     */
    public function test_down_status_always_uses_high_priority(): void
    {
        // Run 100 iterations with different monitors
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-down-' . $i . '.com',
                'name' => 'Test Monitor Down ' . $i,
                'status' => 'down',
            ]);

            // Create notification settings with Pushover enabled
            $notification_settings = NotificationSettings::create([
                'user_id' => $this->user->id,
                'email_enabled' => false,
                'pushover_enabled' => true,
                'pushover_user_key' => 'test_user_key_' . $i,
                'pushover_api_token' => 'test_api_token_' . $i,
            ]);

            // Fake HTTP to capture the request
            Http::fake([
                'https://api.pushover.net/1/messages.json' => Http::response(['status' => 1], 200),
            ]);

            // Send notification for down status
            $this->notification_service->notifyStatusChange($monitor, 'up', 'down');

            // Assert that the Pushover API was called with priority 2
            Http::assertSent(function ($request) {
                return $request->url() === 'https://api.pushover.net/1/messages.json'
                    && $request['priority'] === 2;
            });

            // Clean up for next iteration
            $notification_settings->delete();
            $monitor->delete();
            Http::clearResolvedInstances();
        }
    }

    /**
     * Property Test: Up/recovery status always uses normal priority (0).
     *
     * Tests that for any monitor recovering (going up), the Pushover notification
     * is sent with priority level 0 (normal priority).
     */
    public function test_recovery_status_always_uses_normal_priority(): void
    {
        // Run 100 iterations with different monitors
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-up-' . $i . '.com',
                'name' => 'Test Monitor Up ' . $i,
                'status' => 'up',
            ]);

            // Create notification settings with Pushover enabled
            $notification_settings = NotificationSettings::create([
                'user_id' => $this->user->id,
                'email_enabled' => false,
                'pushover_enabled' => true,
                'pushover_user_key' => 'test_user_key_' . $i,
                'pushover_api_token' => 'test_api_token_' . $i,
            ]);

            // Fake HTTP to capture the request
            Http::fake([
                'https://api.pushover.net/1/messages.json' => Http::response(['status' => 1], 200),
            ]);

            // Send notification for recovery status
            $this->notification_service->notifyStatusChange($monitor, 'down', 'up');

            // Assert that the Pushover API was called with priority 0
            Http::assertSent(function ($request) {
                return $request->url() === 'https://api.pushover.net/1/messages.json'
                    && $request['priority'] === 0;
            });

            // Clean up for next iteration
            $notification_settings->delete();
            $monitor->delete();
            Http::clearResolvedInstances();
        }
    }

    /**
     * Property Test: Priority is correctly set for various status transitions.
     *
     * Tests that priority is correctly determined based on the new status,
     * regardless of the old status.
     */
    public function test_priority_is_based_on_new_status(): void
    {
        $test_cases = [
            // [old_status, new_status, expected_priority]
            ['up', 'down', 2],
            ['pending', 'down', 2],
            ['down', 'up', 0],
            ['pending', 'up', 0],
        ];

        // Run each test case 25 times (100 total iterations)
        foreach ($test_cases as $case_index => [$old_status, $new_status, $expected_priority]) {
            for ($i = 0; $i < 25; $i++) {
                $iteration = ($case_index * 25) + $i;
                
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                    'url' => 'https://example-transition-' . $iteration . '.com',
                    'name' => 'Test Monitor Transition ' . $iteration,
                    'status' => $new_status,
                ]);

                // Create notification settings with Pushover enabled
                $notification_settings = NotificationSettings::create([
                    'user_id' => $this->user->id,
                    'email_enabled' => false,
                    'pushover_enabled' => true,
                    'pushover_user_key' => 'test_user_key_' . $iteration,
                    'pushover_api_token' => 'test_api_token_' . $iteration,
                ]);

                // Fake HTTP to capture the request
                Http::fake([
                    'https://api.pushover.net/1/messages.json' => Http::response(['status' => 1], 200),
                ]);

                // Send notification
                $this->notification_service->notifyStatusChange($monitor, $old_status, $new_status);

                // Assert that the Pushover API was called with correct priority
                Http::assertSent(function ($request) use ($expected_priority) {
                    return $request->url() === 'https://api.pushover.net/1/messages.json'
                        && $request['priority'] === $expected_priority;
                });

                // Clean up for next iteration
                $notification_settings->delete();
                $monitor->delete();
                Http::clearResolvedInstances();
            }
        }
    }

    /**
     * Property Test: Pushover notification includes all required fields with correct priority.
     *
     * Tests that Pushover notifications include all required fields (token, user, message, 
     * title, priority, url) and that priority is correctly set.
     */
    public function test_pushover_notification_includes_all_required_fields_with_correct_priority(): void
    {
        $statuses = [
            ['status' => 'down', 'priority' => 2],
            ['status' => 'up', 'priority' => 0],
        ];

        // Run 50 iterations for each status (100 total)
        foreach ($statuses as $status_config) {
            for ($i = 0; $i < 50; $i++) {
                $iteration = ($status_config['status'] === 'down' ? 0 : 50) + $i;
                
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                    'url' => 'https://example-fields-' . $iteration . '.com',
                    'name' => 'Test Monitor Fields ' . $iteration,
                    'status' => $status_config['status'],
                ]);

                $user_key = 'test_user_key_' . $iteration;
                $api_token = 'test_api_token_' . $iteration;

                // Create notification settings with Pushover enabled
                $notification_settings = NotificationSettings::create([
                    'user_id' => $this->user->id,
                    'email_enabled' => false,
                    'pushover_enabled' => true,
                    'pushover_user_key' => $user_key,
                    'pushover_api_token' => $api_token,
                ]);

                // Fake HTTP to capture the request
                Http::fake([
                    'https://api.pushover.net/1/messages.json' => Http::response(['status' => 1], 200),
                ]);

                // Send notification
                $old_status = $status_config['status'] === 'down' ? 'up' : 'down';
                $this->notification_service->notifyStatusChange($monitor, $old_status, $status_config['status']);

                // Assert that the Pushover API was called with all required fields
                Http::assertSent(function ($request) use ($user_key, $api_token, $monitor, $status_config) {
                    $has_token = $request['token'] === $api_token;
                    $has_user = $request['user'] === $user_key;
                    $has_message = !empty($request['message']) && str_contains($request['message'], $monitor->name);
                    $has_title = !empty($request['title']);
                    $has_correct_priority = $request['priority'] === $status_config['priority'];
                    $has_url = $request['url'] === $monitor->url;
                    
                    return $request->url() === 'https://api.pushover.net/1/messages.json'
                        && $has_token
                        && $has_user
                        && $has_message
                        && $has_title
                        && $has_correct_priority
                        && $has_url;
                });

                // Clean up for next iteration
                $notification_settings->delete();
                $monitor->delete();
                Http::clearResolvedInstances();
            }
        }
    }

    /**
     * Property Test: Priority value is always an integer (2 or 0).
     *
     * Tests that the priority value sent to Pushover is always an integer,
     * never a string or other type.
     */
    public function test_priority_is_always_integer(): void
    {
        $statuses = ['down', 'up'];

        // Run 50 iterations for each status (100 total)
        foreach ($statuses as $status) {
            for ($i = 0; $i < 50; $i++) {
                $iteration = ($status === 'down' ? 0 : 50) + $i;
                
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                    'url' => 'https://example-type-' . $iteration . '.com',
                    'name' => 'Test Monitor Type ' . $iteration,
                    'status' => $status,
                ]);

                // Create notification settings with Pushover enabled
                $notification_settings = NotificationSettings::create([
                    'user_id' => $this->user->id,
                    'email_enabled' => false,
                    'pushover_enabled' => true,
                    'pushover_user_key' => 'test_user_key_' . $iteration,
                    'pushover_api_token' => 'test_api_token_' . $iteration,
                ]);

                // Fake HTTP to capture the request
                Http::fake([
                    'https://api.pushover.net/1/messages.json' => Http::response(['status' => 1], 200),
                ]);

                // Send notification
                $old_status = $status === 'down' ? 'up' : 'down';
                $this->notification_service->notifyStatusChange($monitor, $old_status, $status);

                // Assert that priority is an integer
                Http::assertSent(function ($request) {
                    return $request->url() === 'https://api.pushover.net/1/messages.json'
                        && is_int($request['priority'])
                        && in_array($request['priority'], [0, 2], true);
                });

                // Clean up for next iteration
                $notification_settings->delete();
                $monitor->delete();
                Http::clearResolvedInstances();
            }
        }
    }
}
