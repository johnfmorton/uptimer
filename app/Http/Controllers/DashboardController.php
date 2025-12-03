<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QueueDiagnosticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private QueueDiagnosticsService $diagnosticsService
    ) {}

    /**
     * Display the dashboard with all monitors for the authenticated user.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get authenticated user
        $user = $request->user();

        // Fetch all monitors for the user, ordered by status priority
        // (down first, then up, then pending)
        // Eager load the most recent check for response time display
        $monitors = $user->monitors()
            ->with(['checks' => function ($query) {
                $query->latest('checked_at')->limit(1);
            }])
            ->orderByRaw("CASE 
                WHEN status = 'down' THEN 1 
                WHEN status = 'up' THEN 2 
                WHEN status = 'pending' THEN 3 
                ELSE 4 
            END")
            ->orderBy('name')
            ->get();

        // Get queue diagnostics
        $diagnostics = $this->diagnosticsService->getQueueDiagnostics();

        return view('dashboard', [
            'monitors' => $monitors,
            'diagnostics' => $diagnostics,
        ]);
    }
}
