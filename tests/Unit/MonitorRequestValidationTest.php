<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MonitorRequestValidationTest extends TestCase
{
    /**
     * Test StoreMonitorRequest validates required fields.
     */
    public function test_store_monitor_request_requires_all_fields(): void
    {
        $request = new StoreMonitorRequest();
        $validator = Validator::make([], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('name'));
        $this->assertTrue($validator->errors()->has('url'));
        $this->assertTrue($validator->errors()->has('check_interval_minutes'));
    }

    /**
     * Test StoreMonitorRequest validates URL format.
     */
    public function test_store_monitor_request_validates_url_format(): void
    {
        $request = new StoreMonitorRequest();
        $validator = Validator::make([
            'name' => 'Test Monitor',
            'url' => 'not-a-valid-url',
            'check_interval_minutes' => 5,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('url'));
    }

    /**
     * Test StoreMonitorRequest validates check interval bounds.
     */
    public function test_store_monitor_request_validates_check_interval_bounds(): void
    {
        $request = new StoreMonitorRequest();

        // Test minimum bound
        $validator = Validator::make([
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'check_interval_minutes' => 0,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('check_interval_minutes'));

        // Test maximum bound
        $validator = Validator::make([
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'check_interval_minutes' => 1441,
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('check_interval_minutes'));
    }

    /**
     * Test StoreMonitorRequest accepts valid data.
     */
    public function test_store_monitor_request_accepts_valid_data(): void
    {
        $request = new StoreMonitorRequest();
        $validator = Validator::make([
            'name' => 'Test Monitor',
            'url' => 'https://example.com',
            'check_interval_minutes' => 5,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test UpdateMonitorRequest allows partial updates.
     */
    public function test_update_monitor_request_allows_partial_updates(): void
    {
        $request = new UpdateMonitorRequest();

        // Test updating only name
        $validator = Validator::make([
            'name' => 'Updated Name',
        ], $request->rules());

        $this->assertTrue($validator->passes());

        // Test updating only URL
        $validator = Validator::make([
            'url' => 'https://updated-example.com',
        ], $request->rules());

        $this->assertTrue($validator->passes());

        // Test updating only check interval
        $validator = Validator::make([
            'check_interval_minutes' => 10,
        ], $request->rules());

        $this->assertTrue($validator->passes());
    }

    /**
     * Test UpdateMonitorRequest validates URL format when provided.
     */
    public function test_update_monitor_request_validates_url_format_when_provided(): void
    {
        $request = new UpdateMonitorRequest();
        $validator = Validator::make([
            'url' => 'not-a-valid-url',
        ], $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->errors()->has('url'));
    }

    /**
     * Test custom error messages are defined.
     */
    public function test_custom_error_messages_are_defined(): void
    {
        $request = new StoreMonitorRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('name.required', $messages);
        $this->assertArrayHasKey('url.required', $messages);
        $this->assertArrayHasKey('url.url', $messages);
        $this->assertArrayHasKey('check_interval_minutes.min', $messages);
        $this->assertArrayHasKey('check_interval_minutes.max', $messages);
    }
}
