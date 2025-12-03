<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

/**
 * Property-Based Test for Status Transitions Triggering Notifications
 *
 * **Feature: uptime-monitor, Property 12: Status transitions trigger notifications**
 *
 * Property: For any monitor that changes from 'up' to 'down' or 'down' to 'up',
 * the system should send notifications to all enabled channels.
 *
 * Validates: Requirements 7.1, 7.2
 */
class StatusTransitionNotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Generate test cases for status transitions.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function statusTransitionProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases covering both transition types
        for ($i = 0; $i < 100; $i++) {
            // Alternate between up->down and down->up transitions
            if ($i % 2 === 0) {
                // up -> down transition
                $test_cases[] = [
                    'initial_status' => 'up',
                    'http_status_code' => 500, // Will cause down status
                    'expected_new_status' => 'down',
                    'should_notify' => true,
                    'url' => 'https://example-up-to-down-' . $i . '.com',
                ];
            } else {
                // down -> up transition
                $test_cases[] = [
                    'initial_status' => 'down',
                    'http_status_code' => 200, // Will cause up status
                    'expected_new_status' => 'up',
                    'should_notify' => true,
                    'url' => 'https://example-down-to-up-' . $i . '.com',
                ];
            }
        }

        return $test_cases;
    }

    /**
     * Property Test: Status transitions from up to down trigger notifications.
     *
     * @dataProvider statusTransitionProvider
     */
    public function test_status_transitions_trigger_notifications(
        string $initial_status,
        int $http_status_code,
        string $expected_new_status,
        bool $should_notify,
        string $url
    ): void {
        // Create monitor with initial status
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'status' => $initial_status,
        ]);

        // Mock NotificationService
        $notification_service = Mockery::mock(NotificationService::class);
        
        if ($should_notify) {
            // Expect notification to be called exactly once with correct parameters
            $notification_service->shouldReceive('notifyStatusChange')
                ->once()
                ->with(
                    Mockery::on(fn($m) => $m->id === $monitor->id),
                    $initial_status,
                    $expected_new_status
                );
        } else {
            // Should not be called
            $notification_service->shouldNotReceive('notifyStatusChange');
        }

        // Create CheckService with mocked NotificationService
        $check_service = new CheckService($notification_service);

        // Fake HTTP response
        Http::fake([
            '*' => Http::response('Response', $http_status_code),
        ]);

        // Perform check
        $check = $check_service->performCheck($monitor);

        // Assert status changed as expected
        $monitor->refresh();
        $this->assertEquals($expected_new_status, $monitor->status);

        // Mockery will automatically verify expectations
    }

    /**
     * Property Test: Up to down transitions always trigger notifications.
     */
    public function test_up_to_down_transitions_always_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Use various 4xx and 5xx codes that cause down status
            $error_codes = [400, 401, 403, 404, 500, 502, 503, 504];
            $status_code = $error_codes[$i % count($error_codes)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-up-down-' . $i . '.com',
                'status' => 'up',
            ]);

            // Mock NotificationService
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldReceive('notifyStatusChange')
                ->once()
                ->with(
                    Mockery::on(fn($m) => $m->id === $monitor->id),
                    'up',
                    'down'
                );

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $check_service->performCheck($monitor);

            // Assert transition occurred
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: Down to up transitions always trigger notifications.
     */
    public function test_down_to_up_transitions_always_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Use various 2xx codes that cause up status
            $success_codes = [200, 201, 202, 203, 204, 205, 206];
            $status_code = $success_codes[$i % count($success_codes)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-down-up-' . $i . '.com',
                'status' => 'down',
            ]);

            // Mock NotificationService
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldReceive('notifyStatusChange')
                ->once()
                ->with(
                    Mockery::on(fn($m) => $m->id === $monitor->id),
                    'down',
                    'up'
                );

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check_service->performCheck($monitor);

            // Assert transition occurred
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
        }
    }

    /**
     * Property Test: Pending to up transitions do NOT trigger notifications.
     */
    public function test_pending_to_up_transitions_do_not_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-pending-up-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('OK', 200),
            ]);

            $check_service->performCheck($monitor);

            // Assert transition occurred but no notification
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
        }
    }

    /**
     * Property Test: Pending to down transitions do NOT trigger notifications.
     */
    public function test_pending_to_down_transitions_do_not_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-pending-down-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('Error', 500),
            ]);

            $check_service->performCheck($monitor);

            // Assert transition occurred but no notification
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: Stable up status does NOT trigger notifications.
     */
    public function test_stable_up_status_does_not_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-stable-up-' . $i . '.com',
                'status' => 'up',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('OK', 200),
            ]);

            $check_service->performCheck($monitor);

            // Assert status remains up and no notification
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
        }
    }

    /**
     * Property Test: Stable down status does NOT trigger notifications.
     */
    public function test_stable_down_status_does_not_trigger_notifications(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-stable-down-' . $i . '.com',
                'status' => 'down',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('Error', 500),
            ]);

            $check_service->performCheck($monitor);

            // Assert status remains down and no notification
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: Notification failures do not block check recording.
     */
    public function test_notification_failures_do_not_block_check_recording(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-notification-failure-' . $i . '.com',
                'status' => 'up',
            ]);

            // Mock NotificationService to throw exception
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldReceive('notifyStatusChange')
                ->once()
                ->andThrow(new \Exception('Notification service unavailable'));

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('Error', 500),
            ]);

            // Should not throw exception
            $check = $check_service->performCheck($monitor);

            // Assert check was still recorded despite notification failure
            $this->assertNotNull($check);
            $this->assertEquals('failed', $check->status);
            
            // Assert monitor status was still updated
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            
            // Assert check was persisted
            $this->assertDatabaseHas('checks', [
                'id' => $check->id,
                'monitor_id' => $monitor->id,
                'status' => 'failed',
            ]);
        }
    }

    /**
     * Property Test: Multiple consecutive transitions trigger notifications each time.
     */
    public function test_multiple_consecutive_transitions_trigger_notifications_each_time(): void
    {
        // Test 10 pairs of transitions (20 total)
        for ($pair = 0; $pair < 10; $pair++) {
            $url_down = 'https://example-down-' . $pair . '.com';
            $url_up = 'https://example-up-' . $pair . '.com';
            
            // Create monitor for down transition
            $monitor_down = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url_down,
                'status' => 'up',
            ]);

            // First transition: up -> down
            $notification_service_1 = Mockery::mock(NotificationService::class);
            $notification_service_1->shouldReceive('notifyStatusChange')
                ->once()
                ->with(
                    Mockery::on(fn($m) => $m->id === $monitor_down->id),
                    'up',
                    'down'
                );

            $check_service_1 = new CheckService($notification_service_1);

            Http::fake([
                $url_down => Http::response('Error', 500),
            ]);

            $check_service_1->performCheck($monitor_down);
            $monitor_down->refresh();
            $this->assertEquals('down', $monitor_down->status);

            // Create monitor for up transition
            $monitor_up = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url_up,
                'status' => 'down',
            ]);

            // Second transition: down -> up
            $notification_service_2 = Mockery::mock(NotificationService::class);
            $notification_service_2->shouldReceive('notifyStatusChange')
                ->once()
                ->with(
                    Mockery::on(fn($m) => $m->id === $monitor_up->id),
                    'down',
                    'up'
                );

            $check_service_2 = new CheckService($notification_service_2);

            Http::fake([
                $url_up => Http::response('OK', 200),
            ]);

            $check_service_2->performCheck($monitor_up);
            $monitor_up->refresh();
            $this->assertEquals('up', $monitor_up->status);
        }
    }

    /**
     * Property Test: last_status_change_at is updated on transitions.
     */
    public function test_last_status_change_at_is_updated_on_transitions(): void
    {
        // Test 10 transitions to avoid transaction issues
        for ($i = 0; $i < 10; $i++) {
            $url = 'https://example-status-change-' . $i . '.com';
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
                'last_status_change_at' => null,
            ]);

            // Verify last_status_change_at is initially null
            $this->assertNull($monitor->last_status_change_at);

            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldReceive('notifyStatusChange')->once();

            $check_service = new CheckService($notification_service);

            Http::fake([
                $url => Http::response('Error', 500),
            ]);

            $check_service->performCheck($monitor);

            // Assert last_status_change_at was updated
            $monitor->refresh();
            $this->assertNotNull($monitor->last_status_change_at);
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: last_status_change_at is NOT updated when status remains stable.
     */
    public function test_last_status_change_at_is_not_updated_when_status_stable(): void
    {
        // Test 10 stable checks to avoid transaction issues
        for ($i = 0; $i < 10; $i++) {
            $initial_time = now()->subHours(1);
            $url = 'https://example-stable-' . $i . '.com';
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
                'last_status_change_at' => $initial_time,
            ]);

            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                $url => Http::response('OK', 200),
            ]);

            $check_service->performCheck($monitor);

            // Assert last_status_change_at was NOT updated
            $monitor->refresh();
            $this->assertEquals($initial_time->timestamp, $monitor->last_status_change_at->timestamp);
            $this->assertEquals('up', $monitor->status);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
