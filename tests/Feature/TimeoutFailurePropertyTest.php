<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use App\Services\CheckService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Property-Based Test for Timeout Failures Marking Monitors as Down
 *
 * **Feature: uptime-monitor, Property 11: Timeout failures mark monitors as down**
 *
 * Property: For any HTTP check that exceeds 30 seconds without response,
 * the system should record the check as failed with a timeout error.
 *
 * Validates: Requirements 6.4
 */
class TimeoutFailurePropertyTest extends TestCase
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
     * Generate test cases with various URLs and initial monitor statuses.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function timeoutScenarioProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with various URLs and initial statuses
        for ($i = 0; $i < 100; $i++) {
            // Generate various URLs
            $urls = [
                'https://timeout-example' . $i . '.com',
                'http://slow-server-' . $i . '.org',
                'https://api.timeout' . $i . '.com/health',
                'https://subdomain.timeout' . $i . '.net',
                'https://timeout.com/endpoint' . $i,
                'https://very-slow-' . $i . '.example.com',
            ];
            
            $url = $urls[$i % count($urls)];

            // Vary initial monitor statuses
            $initial_statuses = ['pending', 'up', 'down'];
            $initial_status = $initial_statuses[$i % count($initial_statuses)];

            $test_cases[] = [
                'url' => $url,
                'initial_status' => $initial_status,
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Timeout failures mark monitors as down.
     *
     * @dataProvider timeoutScenarioProvider
     */
    public function test_timeout_failures_mark_monitors_as_down(
        string $url,
        string $initial_status
    ): void {
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'url' => $url,
            'status' => $initial_status,
        ]);

        // Simulate timeout by throwing ConnectionException
        Http::fake(function () {
            throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
        });

        // Perform check
        $check = $this->checkService->performCheck($monitor);

        // Assert check was recorded as failed
        $this->assertNotNull($check);
        $this->assertEquals('failed', $check->status);
        $this->assertNull($check->status_code);
        $this->assertNull($check->response_time_ms);
        $this->assertNotNull($check->error_message);
        $this->assertStringContainsString('timeout', strtolower($check->error_message));
        $this->assertStringContainsString('30 seconds', $check->error_message);

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
            'status_code' => null,
        ]);

        // Assert monitor status was persisted
        $this->assertDatabaseHas('monitors', [
            'id' => $monitor->id,
            'status' => 'down',
        ]);
    }

    /**
     * Property Test: Timeout failures from pending status mark monitors as down.
     */
    public function test_timeout_failures_from_pending_status_mark_monitors_as_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-pending-' . $i . '.com',
                'status' => 'pending',
            ]);

            // Verify initial status
            $this->assertEquals('pending', $monitor->status);
            $this->assertTrue($monitor->isPending());

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from pending to down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            $this->assertFalse($monitor->isPending());
            
            // Assert check is failed with timeout error
            $this->assertEquals('failed', $check->status);
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString('timeout', strtolower($check->error_message));
        }
    }

    /**
     * Property Test: Timeout failures from up status mark monitors as down.
     */
    public function test_timeout_failures_from_up_status_mark_monitors_as_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-up-' . $i . '.com',
                'status' => 'up',
            ]);

            // Verify initial status
            $this->assertEquals('up', $monitor->status);
            $this->assertTrue($monitor->isUp());

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert transition from up to down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            $this->assertFalse($monitor->isUp());
            
            // Assert check is failed with timeout error
            $this->assertEquals('failed', $check->status);
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString('timeout', strtolower($check->error_message));
        }
    }

    /**
     * Property Test: Timeout failures maintain down status when already down.
     */
    public function test_timeout_failures_maintain_down_status_when_already_down(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-down-' . $i . '.com',
                'status' => 'down',
            ]);

            // Verify initial status
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert status remains down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
            
            // Assert check is failed with timeout error
            $this->assertEquals('failed', $check->status);
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString('timeout', strtolower($check->error_message));
        }
    }

    /**
     * Property Test: Various timeout error messages are handled correctly.
     */
    public function test_various_timeout_error_messages_are_handled_correctly(): void
    {
        // Different timeout error message formats
        $timeout_messages = [
            'cURL error 28: Operation timed out after 30000 milliseconds',
            'Connection timed out',
            'Request timeout',
            'cURL error 28: Timeout was reached',
            'Operation timed out',
            'Connection timeout after 30 seconds',
        ];

        // Run 100+ iterations with various timeout messages
        for ($i = 0; $i < 100; $i++) {
            $timeout_message = $timeout_messages[$i % count($timeout_messages)];
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-message-' . $i . '.com',
                'status' => 'up',
            ]);

            Http::fake(function () use ($timeout_message) {
                throw new ConnectionException($timeout_message);
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert check is failed
            $this->assertEquals('failed', $check->status);
            $this->assertNull($check->status_code);
            $this->assertNull($check->response_time_ms);
            $this->assertNotNull($check->error_message);
            
            // Assert error message indicates timeout
            $this->assertStringContainsString('timeout', strtolower($check->error_message));
            
            // Assert monitor is down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
            $this->assertTrue($monitor->isDown());
        }
    }

    /**
     * Property Test: Timeout failures have null status_code.
     */
    public function test_timeout_failures_have_null_status_code(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-null-code-' . $i . '.com',
                'status' => 'up',
            ]);

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert status_code is null for timeout failures
            $this->assertNull($check->status_code);
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
            
            // Assert monitor is down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: Timeout failures have null response_time_ms.
     */
    public function test_timeout_failures_have_null_response_time_ms(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-null-time-' . $i . '.com',
                'status' => 'up',
            ]);

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert response_time_ms is null for timeout failures
            $this->assertNull($check->response_time_ms);
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
            
            // Assert monitor is down
            $monitor->refresh();
            $this->assertEquals('down', $monitor->status);
        }
    }

    /**
     * Property Test: last_checked_at is updated for timeout failures.
     */
    public function test_last_checked_at_is_updated_for_timeout_failures(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-last-checked-' . $i . '.com',
                'status' => 'up',
                'last_checked_at' => null,
            ]);

            // Verify last_checked_at is initially null
            $this->assertNull($monitor->last_checked_at);

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $this->checkService->performCheck($monitor);

            // Assert last_checked_at was updated
            $monitor->refresh();
            $this->assertNotNull($monitor->last_checked_at);
        }
    }

    /**
     * Property Test: Timeout error message format is consistent.
     */
    public function test_timeout_error_message_format_is_consistent(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-format-' . $i . '.com',
                'status' => 'up',
            ]);

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert error message contains expected timeout information
            $this->assertNotNull($check->error_message);
            $this->assertStringContainsString('timeout', strtolower($check->error_message));
            $this->assertStringContainsString('30 seconds', $check->error_message);
            
            // Assert check is failed
            $this->assertEquals('failed', $check->status);
        }
    }

    /**
     * Property Test: Timeout failures are persisted correctly to database.
     */
    public function test_timeout_failures_are_persisted_correctly_to_database(): void
    {
        // Run 100 iterations
        for ($i = 0; $i < 100; $i++) {
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'url' => 'https://timeout-persist-' . $i . '.com',
                'status' => 'up',
            ]);

            Http::fake(function () {
                throw new ConnectionException('cURL error 28: Operation timed out after 30000 milliseconds');
            });

            $check = $this->checkService->performCheck($monitor);

            // Assert check was persisted with correct values
            $this->assertDatabaseHas('checks', [
                'id' => $check->id,
                'monitor_id' => $monitor->id,
                'status' => 'failed',
                'status_code' => null,
                'response_time_ms' => null,
            ]);

            // Assert monitor status was persisted
            $this->assertDatabaseHas('monitors', [
                'id' => $monitor->id,
                'status' => 'down',
            ]);
        }
    }
}
