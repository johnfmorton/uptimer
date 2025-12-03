<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\TestQueueJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueTestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_dispatch_test_queue_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('queue.test'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        Queue::assertPushed(TestQueueJob::class);
    }

    public function test_unauthenticated_user_cannot_dispatch_test_queue_job(): void
    {
        $response = $this->post(route('queue.test'));

        $response->assertRedirect(route('login'));
    }

    public function test_test_queue_job_executes_successfully(): void
    {
        $message = 'Test message';
        $job = new TestQueueJob($message);

        $job->handle();

        // Job should complete without throwing exceptions
        $this->assertTrue(true);
    }
}
