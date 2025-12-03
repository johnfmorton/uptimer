<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMonitorRequest;
use App\Http\Requests\UpdateMonitorRequest;
use App\Models\Monitor;
use App\Services\MonitorService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MonitorController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private MonitorService $monitorService
    ) {}

    /**
     * Display a listing of all monitors for the authenticated user.
     *
     * @return View
     */
    public function index(): View
    {
        $monitors = $this->monitorService->getAllMonitorsForUser(Auth::user());
        
        return view('monitors.index', compact('monitors'));
    }

    /**
     * Show the form for creating a new monitor.
     *
     * @return View
     */
    public function create(): View
    {
        return view('monitors.create');
    }

    /**
     * Store a newly created monitor in storage.
     *
     * @param  StoreMonitorRequest  $request
     * @return RedirectResponse
     */
    public function store(StoreMonitorRequest $request): RedirectResponse
    {
        $monitor = $this->monitorService->createMonitor(
            Auth::user(),
            $request->validated()
        );
        
        return redirect()
            ->route('monitors.show', $monitor)
            ->with('success', 'Monitor created successfully.');
    }

    /**
     * Display the specified monitor with check history.
     *
     * @param  Monitor  $monitor
     * @return View
     */
    public function show(Monitor $monitor): View
    {
        $this->authorize('view', $monitor);
        
        $stats = $this->monitorService->getMonitorWithStats($monitor);
        
        // Load check history with pagination
        $checks = $monitor->checks()
            ->orderBy('checked_at', 'desc')
            ->paginate(50);
        
        return view('monitors.show', [
            'monitor' => $stats['monitor'],
            'uptime_24h' => $stats['uptime_24h'],
            'uptime_7d' => $stats['uptime_7d'],
            'uptime_30d' => $stats['uptime_30d'],
            'checks' => $checks,
        ]);
    }

    /**
     * Show the form for editing the specified monitor.
     *
     * @param  Monitor  $monitor
     * @return View
     */
    public function edit(Monitor $monitor): View
    {
        $this->authorize('update', $monitor);
        
        return view('monitors.edit', compact('monitor'));
    }

    /**
     * Update the specified monitor in storage.
     *
     * @param  UpdateMonitorRequest  $request
     * @param  Monitor  $monitor
     * @return RedirectResponse
     */
    public function update(UpdateMonitorRequest $request, Monitor $monitor): RedirectResponse
    {
        $this->authorize('update', $monitor);
        
        $this->monitorService->updateMonitor($monitor, $request->validated());
        
        return redirect()
            ->route('monitors.show', $monitor)
            ->with('success', 'Monitor updated successfully.');
    }

    /**
     * Remove the specified monitor from storage.
     *
     * @param  Monitor  $monitor
     * @return RedirectResponse
     */
    public function destroy(Monitor $monitor): RedirectResponse
    {
        $this->authorize('delete', $monitor);
        
        $this->monitorService->deleteMonitor($monitor);
        
        return redirect()
            ->route('monitors.index')
            ->with('success', 'Monitor deleted successfully.');
    }
}
