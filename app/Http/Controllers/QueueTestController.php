<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Jobs\TestQueueJob;
use Illuminate\Http\RedirectResponse;

class QueueTestController extends Controller
{
    /**
     * Dispatch a test job to the queue.
     */
    public function dispatch(): RedirectResponse
    {
        $message = 'Queue test dispatched at ' . now()->toDateTimeString();

        TestQueueJob::dispatch($message);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Test job dispatched to queue! Check logs with: ddev artisan pail');
    }
}
