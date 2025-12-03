<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QueueDiagnosticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class QueueDiagnosticsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private QueueDiagnosticsService $diagnosticsService
    ) {}

    /**
     * Dispatch a test job to the queue.
     */
    public function testQueue(): RedirectResponse
    {
        $this->diagnosticsService->dispatchTestJob();

        return redirect()
            ->back()
            ->with('success', 'Test job dispatched successfully. Check your logs to verify queue processing.');
    }

    /**
     * Get queue status as JSON for AJAX polling.
     */
    public function status(): JsonResponse
    {
        $diagnostics = $this->diagnosticsService->getQueueDiagnostics();

        return response()->json($diagnostics);
    }
}
