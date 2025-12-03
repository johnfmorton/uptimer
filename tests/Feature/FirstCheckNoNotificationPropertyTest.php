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
 * Property-Based Test for First Check Not Triggering Notification
 *
 * **Feature: uptime-monitor, Property 14: First check does not trigger notification**
 *
 * Property: For any newly created monitor completing its first check,
 * the system should not send a notification regardless of the result.
 *
 * Validates: Requirements 7.5
 */
class FirstCheckNoNotificationPropertyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
    }

    /**
     * Generate test cases for first check scenarios.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function firstCheckProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases covering various HTTP status codes
        $status_codes = [
            // Success codes (2xx)
            200, 201, 202, 203, 204, 205, 206,
            // Client error codes (4xx)
            400, 401, 403, 404, 405, 408, 409, 410, 429,
            // Server error codes (5xx)
            500, 501, 502, 503, 504, 505,
        ];

        for ($i = 0; $i < 100; $i++) {
            $status_code = $status_codes[$i % count($status_codes)];
            
            $test_cases[] = [
                'http_status_code' => $status_code,
                'url' => 'https://example-first-check-' . $i . '.com',
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: First check with any status code does not trigger notification.
     *
     * @dataProvider firstCheckProvider
     */
    public function test_first_check_does_not_trigger_notification_regardless_of_result(
        int $http_status_code,
        string $url
    ): void {
        // Create monitor with pending status (initial state for new monitors)
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'status' => 'pending',
            'last_checked_at' => null,
            'last_status_change_at' => null,
        ]);

        // Verify this is truly the first check
        $this->assertEquals('pending', $monitor->status);
        $this->assertNull($monitor->last_checked_at);
        $this->assertEquals(0, $monitor->checks()->count());

        // Mock NotificationService - should NEVER be called for first check
        $notification_service = Mockery::mock(NotificationService::class);
        $notification_service->shouldNotReceive('notifyStatusChange');

        $check_service = new CheckService($notification_service);

        // Fake HTTP response
        Http::fake([
            $url => Http::response('Response', $http_status_code),
        ]);

        // Perform first check
        $check = $check_service->performCheck($monitor);

        // Assert check was recorded
        $this->assertNotNull($check);
        $this->assertEquals(1, $monitor->checks()->count());

        // Assert monitor status was updated from pending
        $monitor->refresh();
        $this->assertNotEquals('pending', $monitor->status);
        
        // Determine expected status based on HTTP status code
        if ($http_status_code >= 200 && $http_status_code < 300) {
            $this->assertEquals('up', $monitor->status);
            $this->assertEquals('success', $check->status);
        } else {
            $this->assertEquals('down', $monitor->status);
            $this->assertEquals('failed', $check->status);
        }

        // Assert last_checked_at was updated
        $this->assertNotNull($monitor->last_checked_at);

        // Mockery will automatically verify that notifyStatusChange was NOT called
    }

    /**
     * Property Test: First check resulting in 'up' status does not trigger notification.
     */
    public function test_first_check_resulting_in_up_does_not_trigger_notification(): void
    {
        // Run 100 iterations with various success codes
        $success_codes = [200, 201, 202, 203, 204, 205, 206];
        
        for ($i = 0; $i < 100; $i++) {
            $status_code = $success_codes[$i % count($success_codes)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-first-up-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check_service->performCheck($monitor);

            // Assert status changed to up but no notification
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
        }
    }

    /**
     * Property Test: First check resulting in 'down' status does not trigger notification.
     */
    public function test_first_check_resulting_in_down_does_not_trigger_notification(): void
    {
        // Run 100 iterations with various error codes
        $error_codes = [400, 401, 403, 404, 500, 502, 503, 504];
        
        for ($i = 0; $i < 100; $i++) {
            $status_code = $error_codes[$i % count($error_codes)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-first-down-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $check_service->performCheck($monitor);

            // Assert status changed to down but no notification
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: First check with timeout does not trigger notification.
     */
    public function test_first_check_with_timeout_does_not_trigger_notification(): void
    {
        // Run 50 iterations (fewer due to timeout simulation)
        for ($i = 0; $i < 50; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example-first-timeout-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Mock NotificationService - should NOT be called
            $notification_service = Mockery::mock(NotificationService::class);
            $notification_service->shouldNotReceive('notifyStatusChange');

            $check_service = new CheckService($notification_service);

            // Simulate timeout by throwing ConnectionException
            Http::fake([
                '*' => function () {
                    throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
                },
            ]);

            $check_service->performCheck($monitor);

            // Assert status changed to down but no notification
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }



    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
