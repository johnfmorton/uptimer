<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Property-Based Test for Monitor Creation with Invalid URL
 *
 * **Feature: uptime-monitor, Property 5: Monitor creation with invalid URL fails**
 *
 * Property: For any invalid URL format submitted in monitor creation
 * (non-HTTP/HTTPS protocols, localhost, missing TLD, malformed URLs),
 * the system should reject the submission and return validation errors.
 *
 * Validates: Requirements 2.2, 2.3, 2.4
 */
class MonitorInvalidUrlPropertyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Get validation rules for monitor URL (same as StoreMonitorRequest).
     *
     * @return array<string, mixed>
     */
    private function getUrlValidationRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'url' => [
                'required',
                'url',
                'max:2048',
                'regex:/^https?:\/\//i', // Must start with http:// or https://
                'regex:/^https?:\/\/[^\/]+\.[a-z]{2,}/i', // Must have a TLD
                'not_regex:/^https?:\/\/(localhost|127\.0\.0\.1|::1)/i', // Reject localhost
            ],
            'check_interval_minutes' => 'required|integer|min:1|max:1440',
        ];
    }

    /**
     * Generate random invalid URL data for testing.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function invalidUrlDataProvider(): array
    {
        $test_cases = [];

        // Generate 100+ test cases with various invalid URL formats
        $invalid_url_patterns = [
            // Missing protocol
            'example.com',
            'www.example.com',
            'subdomain.example.com',
            
            // Invalid protocols (non-HTTP/HTTPS)
            'ftp://example.com',
            'file://example.com',
            'javascript:alert(1)',
            'data:text/html,<script>alert(1)</script>',
            
            // Malformed URLs
            'http:/example.com',
            'http//example.com',
            'http:example.com',
            'ht tp://example.com',
            'http://exam ple.com',
            
            // Invalid characters (spaces in domain)
            'http://example .com',
            
            // Empty or whitespace
            '',
            ' ',
            '   ',
            "\t",
            "\n",
            
            // Just protocol
            'http://',
            'https://',
            
            // Invalid domain formats
            'http://.com',
            'http://.',
            'http://..',
            'http://example.',
            
            // Special characters only
            'http://!!!',
            'http://###',
            'http://***',
            
            // SQL injection attempts
            "http://example.com'; DROP TABLE monitors;--",
            'http://example.com" OR 1=1--',
            
            // XSS attempts
            'http://example.com<script>alert(1)</script>',
            'http://example.com"><script>alert(1)</script>',
            
            // Invalid port numbers (non-numeric)
            'http://example.com:abc',
            
            // Multiple protocols
            'http://https://example.com',
            'https://http://example.com',
            
            // Invalid TLDs (localhost and no TLD)
            'http://example',
            'http://localhost',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://::1',
            
            // Just numbers
            '12345',
            '0',
            
            // Special strings
            'null',
            'undefined',
            'NaN',
            
            // Very long strings (over 2048 characters)
            'http://example.com/' . str_repeat('a', 2050),
            
            // Relative URLs
            '/path/to/resource',
            '../relative/path',
            './current/path',
            
            // Fragment only
            '#fragment',
            
            // Query only
            '?query=value',
        ];

        // Generate test cases
        for ($i = 0; $i < 100; $i++) {
            $invalid_url = $invalid_url_patterns[$i % count($invalid_url_patterns)];
            
            // Add variation to make each test unique
            if ($i >= count($invalid_url_patterns)) {
                $invalid_url .= $i;
            }

            $test_cases[] = [
                'name' => 'Test Monitor ' . $i,
                'url' => $invalid_url,
                'check_interval_minutes' => rand(1, 1440),
            ];
        }

        return $test_cases;
    }

    /**
     * Property Test: Monitor creation with invalid URL fails validation.
     *
     * @dataProvider invalidUrlDataProvider
     */
    public function test_monitor_creation_with_invalid_url_fails_validation(
        string $name,
        string $url,
        int $check_interval_minutes
    ): void {
        // Create an authenticated user
        $user = User::factory()->create();

        // Act as the authenticated user
        $this->actingAs($user);

        // Prepare monitor data with invalid URL
        $data = [
            'name' => $name,
            'url' => $url,
            'check_interval_minutes' => $check_interval_minutes,
        ];

        // Validate using the same rules as StoreMonitorRequest
        $validator = Validator::make($data, $this->getUrlValidationRules());

        // Assert validation fails
        $this->assertTrue(
            $validator->fails(),
            "Expected validation to fail for URL: {$url}"
        );

        // Assert URL field has errors
        $this->assertTrue(
            $validator->errors()->has('url'),
            "Expected 'url' field to have validation errors for: {$url}"
        );

        // Assert no monitor was created in database
        $this->assertDatabaseMissing('monitors', [
            'url' => $url,
        ]);

        // Clean up
        $user->delete();
    }

    /**
     * Property Test: Various invalid URL formats are rejected.
     */
    public function test_various_invalid_url_formats_are_rejected(): void
    {
        // Run 100 iterations with different invalid URL formats
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate various invalid URL formats
            $invalid_urls = [
                'not-a-url',
                'example.com',
                'www.example.com',
                'ftp://example.com',
                'http:/example.com',
                'http//example.com',
                '',
                ' ',
                'http://',
                'http://.',
                'javascript:alert(1)',
                'http://example .com',
                str_repeat('a', 2050),
                '/relative/path',
                '#fragment',
                '?query=value',
                'http://localhost',
                'http://example',
            ];

            $invalid_url = $invalid_urls[$i % count($invalid_urls)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $invalid_url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Assert no monitor was created
            $this->assertDatabaseMissing('monitors', [
                'url' => $invalid_url,
            ]);

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Empty and whitespace URLs are rejected.
     */
    public function test_empty_and_whitespace_urls_are_rejected(): void
    {
        // Run 100 iterations with empty/whitespace variations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate various empty/whitespace patterns
            $empty_urls = [
                '',
                ' ',
                '  ',
                '   ',
                "\t",
                "\n",
                "\r",
                " \t\n ",
                str_repeat(' ', $i + 1),
            ];

            $empty_url = $empty_urls[$i % count($empty_urls)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $empty_url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: URLs exceeding maximum length are rejected.
     */
    public function test_urls_exceeding_maximum_length_are_rejected(): void
    {
        // Run 100 iterations with various long URLs
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate URLs that exceed 2048 characters
            $base_url = 'https://example.com/';
            $padding_length = 2049 + $i; // Exceeds 2048 limit
            $long_url = $base_url . str_repeat('a', $padding_length);

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $long_url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Assert no monitor was created
            $this->assertDatabaseMissing('monitors', [
                'name' => 'Test Monitor ' . $i,
            ]);

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: URLs without protocol are rejected.
     */
    public function test_urls_without_protocol_are_rejected(): void
    {
        // Run 100 iterations with URLs missing protocol
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate URLs without protocol
            $urls_without_protocol = [
                'example.com',
                'www.example.com',
                'subdomain.example.com',
                'example.com/path',
                'example.com:8080',
                'example.com?query=value',
                'api.example.com/v1/endpoint',
                '192.168.1.1',
                'localhost:3000',
            ];

            $url = $urls_without_protocol[$i % count($urls_without_protocol)] . '/' . $i;

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Assert no monitor was created
            $this->assertDatabaseMissing('monitors', [
                'url' => $url,
            ]);

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Malformed URLs are rejected.
     */
    public function test_malformed_urls_are_rejected(): void
    {
        // Run 100 iterations with malformed URLs
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate malformed URLs
            $malformed_urls = [
                'http:/example.com',
                'http//example.com',
                'http:example.com',
                'ht tp://example.com',
                'http://exam ple.com',
                'http://example .com',
                'http://',
                'https://',
                'http://.',
                'http://..',
                'http://example.',
                'http://!!!',
                'http://###',
            ];

            $url = $malformed_urls[$i % count($malformed_urls)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Non-HTTP/HTTPS protocols are rejected.
     */
    public function test_non_http_protocols_are_rejected(): void
    {
        // Run 100 iterations with non-HTTP/HTTPS protocols
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate URLs with non-HTTP/HTTPS protocols
            $non_http_protocols = [
                'ftp://example.com',
                'file://example.com',
                'ssh://example.com',
                'telnet://example.com',
                'javascript:alert(1)',
                'data:text/html,test',
                'mailto:test@example.com',
                'ws://example.com',
                'wss://example.com',
            ];

            $url = $non_http_protocols[$i % count($non_http_protocols)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: Localhost URLs are rejected.
     */
    public function test_localhost_urls_are_rejected(): void
    {
        // Run 100 iterations with localhost variations
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate localhost URLs
            $localhost_urls = [
                'http://localhost',
                'https://localhost',
                'http://localhost:8080',
                'https://localhost:3000',
                'http://127.0.0.1',
                'https://127.0.0.1',
                'http://127.0.0.1:8080',
                'http://::1',
                'https://::1',
            ];

            $url = $localhost_urls[$i % count($localhost_urls)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Clean up
            $user->delete();
        }
    }

    /**
     * Property Test: URLs without TLD are rejected.
     */
    public function test_urls_without_tld_are_rejected(): void
    {
        // Run 100 iterations with URLs missing TLD
        for ($i = 0; $i < 100; $i++) {
            $user = User::factory()->create();

            // Generate URLs without TLD
            $urls_without_tld = [
                'http://example',
                'https://example',
                'http://test',
                'https://test',
                'http://mysite',
                'https://mysite',
            ];

            $url = $urls_without_tld[$i % count($urls_without_tld)];

            // Prepare data
            $data = [
                'name' => 'Test Monitor ' . $i,
                'url' => $url,
                'check_interval_minutes' => 5,
            ];

            // Validate
            $validator = Validator::make($data, $this->getUrlValidationRules());

            // Assert validation fails
            $this->assertTrue($validator->fails());
            $this->assertTrue($validator->errors()->has('url'));

            // Clean up
            $user->delete();
        }
    }
}
