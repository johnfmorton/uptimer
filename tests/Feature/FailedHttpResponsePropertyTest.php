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
 * Property-Based Test for Failed HTTP Responses Marking Monitors as Down
 *
 * **Feature: uptime-monitor, Property 10: Failed HTTP responses mark monitors as down**
 *
 * Property: For any HTTP check that receives a 4xx or 5xx status code,
 * the system should record the check as failed and set monitor status to 'down'.
 *
 * Validates: Requirements 6.3
 */
class FailedHttpResponsePropertyTest extends TestCase
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
     * Generate random 4xx and 5xx status codes for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function failed4xxAnd5xxStatusCodeProvider(): array
    {
        $test_cases = [];

        // All common 4xx status codes
        $status_codes_4xx = [
            400, // Bad Request
            401, // Unauthorized
            402, // Payment Required
            403, // Forbidden
            404, // Not Found
            405, // Method Not Allowed
            406, // Not Acceptable
            407, // Proxy Authentication Required
            408, // Request Timeout
            409, // Conflict
            410, // Gone
            411, // Length Required
            412, // Precondition Failed
            413, // Payload Too Large
            414, // URI Too Long
            415, // Unsupported Media Type
            416, // Range Not Satisfiable
            417, // Expectation Failed
            418, // I'm a teapot
            421, // Misdirected Request
            422, // Unprocessable Entity
            423, // Locked
            424, // Failed Dependency
            425, // Too Early
            426, // Upgrade Required
            428, // Precondition Required
            429, // Too Many Requests
            431, // Request Header Fields Too Large
            451, // Unavailable For Legal Reasons
        ];

        // All common 5xx status codes
        $status_codes_5xx = [
            500, // Internal Server Error
            501, // Not Implemented
            502, // Bad Gateway
            503, // Service Unavailable
            504, // Gateway Timeout
            505, // HTTP Version Not Supported
            506, // Variant Also Negotiates
            507, // Insufficient Storage
            508, // Loop Detected
            510, // Not Extended
            511, // Network Authentication Required
        ];

        $all_error_codes = array_merge($status_codes_4xx, $status_codes_5xx);

        // Generate 100+ test cases with random 4xx/5xx status codes
        for ($i = 0; $i < 100; $i++) {
            $status_code = $all_error_codes[$i % count($all_error_codes)];
            
            // Generate various response bodies
            $response_bodies = [
                'Error',
                '{"error": "Something went wrong"}',
                '<html><body>Error</body></html>',
                'Service unavailable',
                '',
                'Internal server error',
                '{"message": "Not found"}',
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
     * Property Test: Failed HTTP responses (4xx/5xx) mark monitors as down.
     *
     * @dataProvider failed4xxAnd5xxStatusCodeProvider
     */
    public function test_failed_http_responses_mark_monitors_as_down(
        int $status_code,
        string $response_body,
        string $url
    ): void {
        // Create a monitor with various initial statuses
        $initial_statuses = ['pending', 'up', 'down'];
        $initial_status = $initial_statuses[array_rand($initial_statuses)];
        
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'status' => $initial_status,
        ]);

        // Fake HTTP response with 4xx/5xx status code
        Http::fake([
            '*' => Http::response($response_body, $status_code),
        ]);

        // Perform check
        $check = $this->checkService->performCheck($monitor);

        // Assert check was recorded as failed
        $this->assertNotNull($check);
        $this->assertEquals('failed', $check->status);
        $this->assertEquals($status_code, $check->status_code);
        $this->assertNotNull($check->error_message);
        $this->assertStringContainsString((string) $status_code, $check->error_message);
        $this->assertNotNull($check->response_time_ms);
        $this->assertGreaterThanOrEqual(0, $check->response_time_ms);

        // Assert monitor status is now 'down'
        $monitor->refresh();
        $this->assertEquals('down', $monitor->status);
        $this->assertTrue($monitor->isDown());
        $this->assertFalse($monitor->isUp());
        $this->assertFalse($monitor->isPending());

        // Assert last_checked_at was updated
        $this->assertNotNull($monitor->last_checked_at);

        // Assert check was persisted to database
        $this->assertDatabaseHas('checks', [
            'id' => $check->id,
            'monitor_id' => $monitor->id,
            'status' => 'failed',
            'status_code' => $status_code,
        ]);

        // Assert monitor status was persisted
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'down',
        ]);
    }

    /**
     * Property Test: All 4xx status codes result in 'down' status.
     */
    public function test_all_4xx_status_codes_result_in_down_status(): void
    {
        // All common 4xx status codes
        $status_codes_4xx = [
            400, 401, 402, 403, 404, 405, 406, 407, 408, 409,
            410, 411, 412, 413, 414, 415, 416, 417, 418, 421,
            422, 423, 424, 425, 426, 428, 429, 431, 451,
        ];

        // Test each status code at least once
        foreach ($status_codes_4xx as $index => $status_code) {
            // Use unique domain for each test to avoid HTTP fake conflicts
            $domain = 'example-4xx-' . $status_code . '.com';
            $url = 'https://' . $domain;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
            ]);

            // Fake specific domain with specific status code
            Http::fake([
                $domain . '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert check is failed
            $this->assertEquals('failed', $check->status);
            $this->assertEquals($status_code, $check->status_code);
            $this->assertNotNull($check->error_message);

            // Assert monitor is marked as 'down'
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
        }
    }

    /**
     * Property Test: All 5xx status codes result in 'down' status.
     */
    public function test_all_5xx_status_codes_result_in_down_status(): void
    {
        // All common 5xx status codes
        $status_codes_5xx = [
            500, 501, 502, 503, 504, 505, 506, 507, 508, 510, 511,
        ];

        // Test each status code multiple times (100+ total iterations)
        foreach ($status_codes_5xx as $status_code) {
            for ($j = 0; $j < 10; $j++) {
                // Use unique domain for each test to avoid HTTP fake conflicts
                $domain = 'example-5xx-' . $status_code . '-' . $j . '.com';
                $url = 'https://' . $domain;
                
                $monitor = Monitor::factory()->create([
                    'user_id' => $this->user->id,
                    'url' => $url,
                    'status' => 'up',
                ]);

                // Fake specific domain with specific status code
                Http::fake([
                    $domain . '*' => Http::response('Server Error', $status_code),
                ]);

                $check = $this->checkService->performCheck($monitor);

                // Assert check is failed
                $this->assertEquals('failed', $check->status);
                $this->assertEquals($status_code, $check->status_code);
                $this->assertNotNull($check->error_message);

                // Assert monitor is marked as 'down'
                $monitor->refresh();
                $this->assertEquals('down', $monitor->status);
                $this->assertTrue($monitor->isDown());
            }
        }
    }

    /**
     * Property Test: 4xx/5xx responses from pending status mark monitors as down.
     */
    public function test_4xx_5xx_responses_from_pending_status_mark_monitors_as_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 404 : 500;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'pending',
            ]);

            // Verify initial status
            $this->assertEquals('pending', $monitor->status);
            $this->assertTrue($monitor->isPending());

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from pending to down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            $this->assertFalse($monitor->isPending());
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
        }
    }

    /**
     * Property Test: 4xx/5xx responses from up status mark monitors as down.
     */
    public function test_4xx_5xx_responses_from_up_status_mark_monitors_as_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 403 : 503;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'up',
            ]);

            // Verify initial status
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from up to down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            $this->assertFalse($monitor->isUp());
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
        }
    }

    /**
     * Property Test: 4xx/5xx responses maintain down status when already down.
     */
    public function test_4xx_5xx_responses_maintain_down_status_when_already_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 401 : 502;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'down',
            ]);

            // Verify initial status
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert status remains down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
        }
    }

    /**
     * Property Test: Response time is recorded for all 4xx/5xx responses.
     */
    public function test_response_time_is_recorded_for_all_4xx_5xx_responses(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 404 : 500;
            
            // Use unique domain for each test to avoid HTTP fake conflicts
            $domain = 'example-response-time-' . $i . '.com';
            $url = 'https://' . $domain;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
            ]);

            Http::fake([
                $domain . '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert response time is recorded
            $this->assertNotNull($check->response_time_ms);
            $this->assertIsInt($check->response_time_ms);
            $this->assertGreaterThanOrEqual(0, $check->response_time_ms);
            
            // Assert error message is present for failed checks
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString((string) $status_code, $check->error_message);
        }
    }

    /**
     * Property Test: last_checked_at is updated for all 4xx/5xx responses.
     */
    public function test_last_checked_at_is_updated_for_all_4xx_5xx_responses(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 404 : 500;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://example' . $i . '.com',
                'status' => 'up',
                'last_checked_at' => null,
            ]);

            // Verify last_checked_at is initially null
            $this->assertNull($monitor->last_checked_at);

            Http::fake([
                '*' => Http::response('Error', $status_code),
            ]);

            $this->checkService->performCheck($monitor);

            // Assert last_checked_at was updated
            $monitor->refresh();
            $this->assertNotNull($monitor->last_checked_at);
        }
    }

    /**
     * Property Test: Various response bodies with 4xx/5xx codes all result in failure.
     */
    public function test_various_response_bodies_with_4xx_5xx_codes_result_in_failure(): void
    {
        // Run 100 iterations with different response bodies
        for ($i = 0; $i < 100; $i++) {
            $response_bodies = [
                '',
                'Error',
                'Not Found',
                '{"error": "Something went wrong"}',
                '<html><body>Error</body></html>',
                'Service unavailable',
                str_repeat('e', 1000), // Large error response
                '{"message": "Internal server error"}',
                'Plain text error',
            ];
            
            $response_body = $response_bodies[$i % count($response_bodies)];
            
            // Alternate between 4xx and 5xx
            $status_code = $i % 2 === 0 ? 404 : 500;
            
            // Use unique domain for each test to avoid HTTP fake conflicts
            $domain = 'example-response-body-' . $i . '.com';
            $url = 'https://' . $domain;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
            ]);

            Http::fake([
                $domain . '*' => Http::response($response_body, $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert check is failed regardless of response body
            $this->assertEquals('failed', $check->status);
            $this->assertEquals($status_code, $check->status_code);
            $this->assertNotNull($check->error_message);
            
            // Assert monitor is down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: Error message format is consistent for all 4xx/5xx responses.
     */
    public function test_error_message_format_is_consistent_for_all_4xx_5xx_responses(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            // Use various 4xx and 5xx codes
            $error_codes = [400, 401, 403, 404, 500, 502, 503, 504];
            $status_code = $error_codes[$i % count($error_codes)];
            
            // Use unique domain for each test to avoid HTTP fake conflicts
            $domain = 'example-error-format-' . $i . '.com';
            $url = 'https://' . $domain;
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => $url,
                'status' => 'up',
            ]);

            Http::fake([
                $domain . '*' => Http::response('Error', $status_code),
            ]);

            $check = $this->checkService->performCheck($monitor);

            // Assert error message contains the status code
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString('HTTP', $check->error_message);
            $this->assertStringContainsString((string) $status_code, $check->error_message);
            $this->assertStringContainsString('response received', $check->error_message);
        }
    }
}
