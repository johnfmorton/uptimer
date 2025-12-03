<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\QueueDiagnosticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct(
        private QueueDiagnosticsService $diagnosticsService
    ) {}

    /**
     * Display the application settings page.
     *
     * @param  Request  $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Get queue diagnostics
        $diagnostics = $this->diagnosticsService->getQueueDiagnostics();

        return view('settings.index', [
            'diagnostics' => $diagnostics,
        ]);
    }
}
