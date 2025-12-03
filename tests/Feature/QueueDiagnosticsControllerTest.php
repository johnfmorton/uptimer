<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\TestQueueJob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class QueueDiagnosticsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_dispatch_test_job(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('queue.diagnostics.test'));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        Queue::assertPushed(TestQueueJob::class);
    }

    public function test_unauthenticated_user_cannot_dispatch_test_job(): void
    {
        $response = $this->post(route('queue.diagnostics.test'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_get_queue_status(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('queue.diagnostics.status'));

        $response->assertOk();
        $response->assertJsonStructure([
            'pending_jobs',
            'failed_jobs_last_hour',
            'stuck_jobs',
            'queue_worker_running',
            'scheduler_running',
            'has_issues',
        ]);
    }

    public function test_unauthenticated_user_cannot_get_queue_status(): void
    {
        $response = $this->get(route('queue.diagnostics.status'));

        $response->assertRedirect(route('login'));
    }
}
