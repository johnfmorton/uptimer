<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Property-Based Test for Successful HTTP Responses Marking Monitors as Up
 *
 * **Feature: uptime-monitor, Property 9: Successful HTTP responses mark monitors as up**
 *
 * Property: For any HTTP check that receives a 2xx status code,
 * the system should record the check as successful and set monitor status to 'up'.
 *
 * Validates: Requirements 6.2
 */
class SuccessfulHttpResponsePropertyTest extends TestCase
{
    use RefreshDatabase;

    private CheckService $checkService;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->checkService = new CheckService();
        $this->user = User::factory()->create();
    }

    /**
     * Generate random 2xx status codes for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function successful2xxStatusCodeProvider(): array
    {
        $test_cases = [];

        // All valid 2xx status codes
        $status_codes_2xx = [
            200, // OK
            201, // Created
            202, // Accepted
            203, // Non-Authoritative Information
            204, // No Content
            205, // Reset Content
            206, // Partial Content
            207, // Multi-Status (WebDAV)
            208, // Already Reported (WebDAV)
            226, // IM Used
        ];

        // Generate 100+ test cases with random 2xx status codes
        for ($i = 0; $i < 100; $i++) {
            $status_code = $status_codes_2xx[$i % count($status_codes_2xx)];
            
            // Generate various response bodies
            $response_bodies = [
                'OK',
                '{"status": "success"}',
                '<html><body>Success</body></html>',
                'Service is running',
                '',
                'Health check passed',
                '{"data": {"status": "healthy"}}',
            ];
            
            $response_body = $response_bodies[$i % count($response_bodies)];
            
            // Generate various URLs
            $urls = [
                'https://example' . $i . '.com',
                'http://test-site-' . $i . '.org',
                'https://api.example' . $i . '.com/health',
                'https://subdomain.example' . $i . '.net',
                'https://example.com/endpoint' . $i,
            ];
            
            $url = $urls[$i % count($urls)];

            $test_cases[] = [
                'status_code' => $status_code,
                'response_body' => $response_body,
                'url' => $url,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Successful HTTP responses (2xx) mark monitors as up.
     *
     * @dataProvider successful2xxStatusCodeProvider
     */
    public function test_successful_http_responses_mark_monitors_as_up(
        int $status_code,
        string $response_body,
        string $url
    ): void {
        // Create a monitor with various initial statuses
        $initial_statuses = ['pending', 'down', 'up'];
        $initial_status = $initial_statuses[array_rand($initial_statuses)];
        
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'status' => $initial_status,
        ]);

        // Fake HTTP response with 2xx status code
        Http::fake([
            '*' => Http::response($response_body, $status_code),
        ]);

        // Perform check
        $check = $this->checkService->performCheck($monitor);

        // Assert check was recorded as successful
        $this->assertNotNull($check);
        $this->assertEquals('success', $check->status);
        $this->assertEquals($status_code, $check->status_code);
        $this->assertNull($check->error_message);
        $this->assertNotNull($check->response_time_ms);
        $this->assertGreaterThanOrEqual(0, $check->response_time_ms);

        // Assert monitor status is now 'up'
        $monitor->refresh();
        $this->assertEquals('up', $monitor->status);
        $this->assertTrue($monitor->isUp());
        $this->assertFalse($monitor->isDown());
        $this->assertFalse($monitor->isPending());

        // Assert last_checked_at was updated
        $this->assertNotNull($monitor->last_checked_at);

        // Assert check was persisted to database
        $this->assertDatabaseHas('checks', [
            'id' => $check->id,
            'monitor_id' => $monitor->id,
            'status' => 'success',
            'status_code' => $status_code,
        ]);

        // Assert monitor status was persisted
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'up',
        ]);
    }

    /**
     * Property Test: All 2xx status codes result in 'up' status.
     */
    public function test_all_2xx_status_codes_result_in_up_status(): void
    {
        // All valid 2xx status codes
        $status_codes_2xx = [
            200, 201, 202, 203, 204, 205, 206, 207, 208, 226,
        ];

        // Test each status code 10 times (100 total iterations)
        foreach ($status_codes_2xx as $status_code) {
            for ($j = 0; $j < 10; $j++) {
                // Use unique domain for each test to avoid HTTP fake conflicts
                $domain = 'example-' . $status_code . '-' . $j . '.com';
                $url = 'https://' . $domain;
                
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                    'url' => $url,
                    'status' => 'pending',
                ]);

                // Fake specific domain with specific status code
                Http::fake([
                    $domain . '*' => Http::response('OK', $status_code),
                ]);

                $check = $this->checkService->performCheck($monitor);

                // Assert check is successful
                $this->assertEquals('success', $check->status);
                $this->assertEquals($status_code, $check->status_code);

                // Assert monitor is marked as 'up'
                $monitor->refresh();
                $this->assertEquals('up', $monitor->status);
                $this->assertTrue($monitor->isUp());
            }
        }
    }

    /**
     * Property Test: 2xx responses from pending status mark monitors as up.
     */
    public function test_2xx_responses_from_pending_status_mark_monitors_as_up(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $status_code = 200 + ($i % 10); // 200-209
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
            ]);

            // Verify initial status
            $this->assertEquals('pending', $monitor->status);
            $this->assertTrue($monitor->isPending());

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from pending to up
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());
            $this->assertFalse($monitor->isPending());
            
            // Assert check is successful
            $this->assertEquals('success', $check->status);
        }
    }

    /**
     * Property Test: 2xx responses from down status mark monitors as up (recovery).
     */
    public function test_2xx_responses_from_down_status_mark_monitors_as_up(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $status_code = 200 + ($i % 10); // 200-209
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'down',
            ]);

            // Verify initial status
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from down to up (recovery)
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());
            $this->assertFalse($monitor->isDown());
            
            // Assert check is successful
            $this->assertEquals('success', $check->status);
        }
    }

    /**
     * Property Test: 2xx responses maintain up status when already up.
     */
    public function test_2xx_responses_maintain_up_status_when_already_up(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $status_code = 200 + ($i % 10); // 200-209
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'up',
            ]);

            // Verify initial status
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert status remains up
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());
            
            // Assert check is successful
            $this->assertEquals('success', $check->status);
        }
    }

    /**
     * Property Test: Response time is recorded for all 2xx responses.
     */
    public function test_response_time_is_recorded_for_all_2xx_responses(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $status_code = 200 + ($i % 10); // 200-209
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
            ]);

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert response time is recorded
            $this->assertNotNull($check->response_time_ms);
            $this->assertIsInt($check->response_time_ms);
            $this->assertGreaterThanOrEqual(0, $check->response_time_ms);
            
            // Assert no error message for successful checks
            $this->assertNull($check->error_message);
        }
    }

    /**
     * Property Test: last_checked_at is updated for all 2xx responses.
     */
    public function test_last_checked_at_is_updated_for_all_2xx_responses(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $status_code = 200 + ($i % 10); // 200-209
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
                'last_checked_at' => null,
            ]);

            // Verify last_checked_at is initially null
            $this->assertNull($monitor->last_checked_at);

            Http::fake([
                '*' => Http::response('OK', $status_code),
            ]);

            $this->checkService->performCheck($monitor);

            // Assert last_checked_at was updated
            $monitor->refresh();
            $this->assertNotNull($monitor->last_checked_at);
        }
    }

    /**
     * Property Test: Various response bodies with 2xx codes all result in success.
     */
    public function test_various_response_bodies_with_2xx_codes_result_in_success(): void
    {
        // Run 100 iterations with different response bodies
        for ($i = 0; $i < 100; $i++) {
            $response_bodies = [
                '',
                'OK',
                'Success',
                '{"status": "ok"}',
                '<html><body>OK</body></html>',
                'Health check passed',
                str_repeat('a', 1000), // Large response
                '{"data": {"items": [1, 2, 3]}}',
                'Plain text response',
            ];
            
            $response_body = $response_bodies[$i % count($response_bodies)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
            ]);

            Http::fake([
                '*' => Http::response($response_body, 200),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert check is successful regardless of response body
            $this->assertEquals('success', $check->status);
            $this->assertEquals(200, $check->status_code);
            
            // Assert monitor is up
            $monitor->refresh();
            $this->assertEquals('up', $monitor->status);
        }
    }
}

