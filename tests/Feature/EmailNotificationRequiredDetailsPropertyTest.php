<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Monitor;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

/**
 * Property-Based Test for Email Notifications Including Required Details
 *
 * **Feature: uptime-monitor, Property 15: Email notifications include required details**
 *
 * Property: For any email notification sent, the message should contain
 * the monitor name, URL, status change, and timestamp.
 *
 * Validates: Requirements 8.2
 */
class EmailNotificationRequiredDetailsPropertyTest extends TestCase
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
     * Generate test cases for email notifications with various monitor configurations.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function emailNotificationProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with different monitor names, URLs, and statuses
        for ($i = 0; $i < 100; $i++) {
            // Alternate between down and up statuses
            $status = $i % 2 === 0 ? 'down' : 'up';
            
            // Generate varied monitor names
            $monitor_names = [
                'Production API',
                'Staging Server',
                'Database Cluster',
                'CDN Endpoint',
                'Payment Gateway',
                'Authentication Service',
                'Email Service',
                'File Storage',
                'Analytics Dashboard',
                'Admin Panel',
            ];
            
            // Generate varied URLs
            $domains = [
                'example.com',
                'api.service.io',
                'staging.app.net',
                'prod.system.org',
                'cdn.content.co',
            ];
            
            $paths = [
                '/health',
                '/api/status',
                '/ping',
                '/check',
                '',
            ];
            
            $test_cases[] = [
                'monitor_name' => $monitor_names[$i % count($monitor_names)] . ' ' . $i,
                'monitor_url' => 'https://' . $domains[$i % count($domains)] . $paths[$i % count($paths)],
                'status' => $status,
                'email' => 'test' . $i . '@example.com',
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Email view contains monitor name.
     *
     * @dataProvider emailNotificationProvider
     */
    public function test_email_view_contains_monitor_name(
        string $monitor_name,
        string $monitor_url,
        string $status,
        string $email
    ): void {
        // Create monitor
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'name' => $monitor_name,
            'url' => $monitor_url,
            'status' => $status,
        ]);

        // Render the email view directly
        $view_data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => now(),
        ];
        
        // Determine which template to use and add status-specific data
        $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
        
        if ($status === 'down') {
            $view_data['error_details'] = 'Test error';
            $view_data['status_code'] = 500;
        }
        
        $rendered = View::make($template, $view_data)->render();
        
        // Assert monitor name is present in the rendered view
        $this->assertStringContainsString($monitor_name, $rendered);
    }

    /**
     * Property Test: Email view contains monitor URL.
     *
     * @dataProvider emailNotificationProvider
     */
    public function test_email_view_contains_monitor_url(
        string $monitor_name,
        string $monitor_url,
        string $status,
        string $email
    ): void {
        // Create monitor
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'name' => $monitor_name,
            'url' => $monitor_url,
            'status' => $status,
        ]);

        // Render the email view directly
        $view_data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => now(),
        ];
        
        // Determine which template to use and add status-specific data
        $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
        
        if ($status === 'down') {
            $view_data['error_details'] = 'Test error';
            $view_data['status_code'] = 500;
        }
        
        $rendered = View::make($template, $view_data)->render();
        
        // Assert monitor URL is present in the rendered view
        $this->assertStringContainsString($monitor_url, $rendered);
    }

    /**
     * Property Test: Email view contains status.
     *
     * @dataProvider emailNotificationProvider
     */
    public function test_email_view_contains_status(
        string $monitor_name,
        string $monitor_url,
        string $status,
        string $email
    ): void {
        // Create monitor
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'name' => $monitor_name,
            'url' => $monitor_url,
            'status' => $status,
        ]);

        // Render the email view directly
        $view_data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => now(),
        ];
        
        // Determine which template to use and add status-specific data
        $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
        
        if ($status === 'down') {
            $view_data['error_details'] = 'Test error';
            $view_data['status_code'] = 500;
        }
        
        $rendered = View::make($template, $view_data)->render();
        
        // Assert status is present in the rendered view (case-insensitive)
        $this->assertStringContainsString(strtolower($status), strtolower($rendered));
    }

    /**
     * Property Test: Email view contains timestamp.
     *
     * @dataProvider emailNotificationProvider
     */
    public function test_email_view_contains_timestamp(
        string $monitor_name,
        string $monitor_url,
        string $status,
        string $email
    ): void {
        // Create monitor
        $monitor = Monitor::factory()->create([
            'user_id' => $this->user->id,
            'name' => $monitor_name,
            'url' => $monitor_url,
            'status' => $status,
        ]);

        $timestamp = now();

        // Render the email view directly
        $view_data = [
            'monitor' => $monitor,
            'status' => $status,
            'timestamp' => $timestamp,
        ];
        
        // Determine which template to use and add status-specific data
        $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
        
        if ($status === 'down') {
            $view_data['error_details'] = 'Test error';
            $view_data['status_code'] = 500;
        }
        
        $rendered = View::make($template, $view_data)->render();
        
        // Assert timestamp is present - look for common date/time patterns
        // The email template uses format: 'F j, Y g:i A T' (e.g., "December 2, 2025 3:45 PM UTC")
        $has_month = preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\b/', $rendered);
        $has_year = preg_match('/\b20\d{2}\b/', $rendered);
        $has_time = preg_match('/\b\d{1,2}:\d{2}\s*(AM|PM)\b/', $rendered);
        
        $this->assertTrue($has_month && $has_year && $has_time, 'Email does not contain a properly formatted timestamp');
    }

    /**
     * Property Test: All required details are present in email view.
     */
    public function test_all_required_details_present_in_email_view(): void
    {
        // Run 100 iterations with random data
        for ($i = 0; $i < 100; $i++) {
            $status = $i % 2 === 0 ? 'down' : 'up';
            $monitor_name = 'Test Monitor ' . $i;
            $monitor_url = 'https://test-' . $i . '.example.com';
            
            $monitor = Monitor::factory()->create([
                'user_id' => $this->user->id,
                'name' => $monitor_name,
                'url' => $monitor_url,
                'status' => $status,
            ]);

            $timestamp = now();

            // Render the email view directly
            $view_data = [
                'monitor' => $monitor,
                'status' => $status,
                'timestamp' => $timestamp,
            ];
            
            // Determine which template to use and add status-specific data
            $template = $status === 'down' ? 'emails.monitor-down' : 'emails.monitor-recovery';
            
            if ($status === 'down') {
                $view_data['error_details'] = 'Test error';
                $view_data['status_code'] = 500;
            }
            
            $rendered = View::make($template, $view_data)->render();
            
            // Check all required fields
            $has_name = str_contains($rendered, $monitor_name);
            $has_url = str_contains($rendered, $monitor_url);
            $has_status = str_contains(strtolower($rendered), strtolower($status));
            $has_timestamp = preg_match('/\b(January|February|March|April|May|June|July|August|September|October|November|December)\b/', $rendered)
                && preg_match('/\b20\d{2}\b/', $rendered)
                && preg_match('/\b\d{1,2}:\d{2}\s*(AM|PM)\b/', $rendered);
            
            $this->assertTrue($has_name, "Email for monitor {$i} missing name");
            $this->assertTrue($has_url, "Email for monitor {$i} missing URL");
            $this->assertTrue($has_status, "Email for monitor {$i} missing status");
            $this->assertTrue($has_timestamp, "Email for monitor {$i} missing timestamp");
        }
    }
}

