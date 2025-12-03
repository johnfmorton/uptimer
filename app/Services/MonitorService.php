<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MonitorService
{
    /**
     * Create a new monitor for a user with pending status.
     *
     * @param  User  $user
     * @param  array{name: string, url: string, check_interval_minutes: int}  $data
     * @return Monitor
     */
    public function createMonitor(User $user, array $data): Monitor
    {
        return $user->monitors()->create([
            'name' => $data['name'],
            'url' => $data['url'],
            'check_interval_minutes' => $data['check_interval_minutes'],
            'status' => 'pending',
        ]);
    }

    /**
     * Update an existing monitor's details.
     *
     * @param  Monitor  $monitor
     * @param  array<string, mixed>  $data
     * @return Monitor
     */
    public function updateMonitor(Monitor $monitor, array $data): Monitor
    {
        $monitor->update($data);
        
        return $monitor->fresh();
    }

    /**
     * Delete a monitor and all associated data.
     *
     * @param  Monitor  $monitor
     * @return bool
     */
    public function deleteMonitor(Monitor $monitor): bool
    {
        return $monitor->delete();
    }

    /**
     * Get all monitors for a specific user.
     *
     * @param  User  $user
     * @return Collection<int, Monitor>
     */
    public function getAllMonitorsForUser(User $user): Collection
    {
        return $user->monitors()->get();
    }

    /**
     * Get a monitor with uptime statistics.
     *
     * @param  Monitor  $monitor
     * @return array{
     *     monitor: Monitor,
     *     uptime_24h: float|null,
     *     uptime_7d: float|null,
     *     uptime_30d: float|null
     * }
     */
    public function getMonitorWithStats(Monitor $monitor): array
    {
        return [
            'monitor' => $monitor,
            'uptime_24h' => $this->calculateUptime($monitor, 24),
            'uptime_7d' => $this->calculateUptime($monitor, 24 * 7),
            'uptime_30d' => $this->calculateUptime($monitor, 24 * 30),
        ];
    }

    /**
     * Calculate uptime percentage for a monitor over a given time period.
     *
     * @param  Monitor  $monitor
     * @param  int  $hours
     * @return float|null Returns null if no checks exist in the time period
     */
    private function calculateUptime(Monitor $monitor, int $hours): ?float
    {
        $since = Carbon::now()->subHours($hours);
        
        $checks = $monitor->checks()
            ->where('checked_at', '>=', $since)
            ->get();
        
        if ($checks->isEmpty()) {
            return null;
        }
        
        $successful_checks = $checks->filter(fn($check) => $check->wasSuccessful())->count();
        $total_checks = $checks->count();
        
        return ($successful_checks / $total_checks) * 100;
    }
}

