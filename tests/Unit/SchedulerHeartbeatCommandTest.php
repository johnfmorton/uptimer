<?php

declare(strict_types=1);

namespace Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SchedulerHeartbeatCommandTest extends TestCase
{
    public function test_command_updates_cache_with_correct_key_and_ttl(): void
    {
        // Clear any existing cache
        Cache::forget('scheduler:heartbeat');

        // Execute the command
        $this->artisan('scheduler:heartbeat')
            ->assertSuccessful();

        // Verify the cache key exists
        $this->assertTrue(Cache::has('scheduler:heartbeat'));
    }

    public function test_cache_value_is_current_timestamp(): void
    {
        // Clear any existing cache
        Cache::forget('scheduler:heartbeat');

        // Capture the time before command execution
        $before = now()->timestamp;

        // Execute the command
        $this->artisan('scheduler:heartbeat')
            ->assertSuccessful();

        // Capture the time after command execution
        $after = now()->timestamp;

        // Get the cached value
        $cached_timestamp = Cache::get('scheduler:heartbeat');

        // Verify the cached value is a timestamp within the expected range
        $this->assertIsInt($cached_timestamp);
        $this->assertGreaterThanOrEqual($before, $cached_timestamp);
        $this->assertLessThanOrEqual($after, $cached_timestamp);
    }

    public function test_cache_ttl_is_90_seconds(): void
    {
        // Clear any existing cache
        Cache::forget('scheduler:heartbeat');

        // Execute the command
        $this->artisan('scheduler:heartbeat')
            ->assertSuccessful();

        // Verify the cache key exists
        $this->assertTrue(Cache::has('scheduler:heartbeat'));

        // Fast forward time by 89 seconds (should still exist)
        $this->travel(89)->seconds();
        $this->assertTrue(Cache::has('scheduler:heartbeat'));

        // Fast forward time by 2 more seconds (total 91 seconds, should be expired)
        $this->travel(2)->seconds();
        $this->assertFalse(Cache::has('scheduler:heartbeat'));
    }
}

