<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckService
{
    /**
     * Create a new CheckService instance.
     *
     * @param  NotificationService|null  $notificationService
     */
    public function __construct(
        private ?NotificationService $notificationService = null
    ) {}

    /**
     * Perform an HTTP check on a monitor.
     *
     * This method executes an HTTP request to the monitored URL with a 30-second timeout,
     * evaluates the response status code, records the check result, and triggers
     * notifications if the monitor's status changes.
     *
     * @param  Monitor  $monitor
     * @return Check
     */
    public function performCheck(Monitor $monitor): Check
    {
        $checked_at = Carbon::now(config('app.timezone'));
        $old_status = $monitor->status;
        
        try {
            // Execute HTTP HEAD request with 30-second timeout, without following redirects
            // HEAD requests are more efficient and often give more accurate status codes
            $start_time = microtime(true);
            $response = Http::timeout(30)->withoutRedirecting()->head($monitor->url);
            $response_time_ms = (int) ((microtime(true) - $start_time) * 1000);
            
            $status_code = $response->status();
            
            // Evaluate status code: 2xx = success, 4xx/5xx = failure
            if ($status_code >= 200 && $status_code < 300) {
                $check_status = 'success';
                $new_monitor_status = 'up';
                $error_message = null;
            } else {
                $check_status = 'failed';
                $new_monitor_status = 'down';
                $error_message = "HTTP {$status_code} response received";
            }
            
            // Create check record
            $check = $monitor->checks()->create([
                'status' => $check_status,
                'status_code' => $status_code,
                'response_time_ms' => $response_time_ms,
                'error_message' => $error_message,
                'checked_at' => $checked_at,
                'created_at' => $checked_at,
            ]);
            
        } catch (ConnectionException $e) {
            // Handle timeout and connection failures
            $check = $monitor->checks()->create([
                'status' => 'failed',
                'status_code' => null,
                'response_time_ms' => null,
                'error_message' => $this->getConnectionErrorMessage($e),
                'checked_at' => $checked_at,
                'created_at' => $checked_at,
            ]);
            
            $new_monitor_status = 'down';
            
        } catch (RequestException $e) {
            // Handle other HTTP request failures
            $check = $monitor->checks()->create([
                'status' => 'failed',
                'status_code' => $e->response?->status(),
                'response_time_ms' => null,
                'error_message' => $e->getMessage(),
                'checked_at' => $checked_at,
                'created_at' => $checked_at,
            ]);
            
            $new_monitor_status = 'down';
            
        } catch (\Exception $e) {
            // Handle unexpected errors
            Log::error('Unexpected error during check', [
                'monitor_id' => $monitor->id,
                'error' => $e->getMessage(),
            ]);
            
            $check = $monitor->checks()->create([
                'status' => 'failed',
                'status_code' => null,
                'response_time_ms' => null,
                'error_message' => 'Unexpected error: ' . $e->getMessage(),
                'checked_at' => $checked_at,
                'created_at' => $checked_at,
            ]);
            
            $new_monitor_status = 'down';
        }
        
        // Update monitor status and detect status changes
        $status_changed = $old_status !== 'pending' && $old_status !== $new_monitor_status;
        
        $monitor->update([
            'status' => $new_monitor_status,
            'last_checked_at' => $checked_at,
            'last_status_change_at' => $status_changed ? $checked_at : $monitor->last_status_change_at,
        ]);
        
        // Trigger notifications on status change (but not for first check)
        if ($status_changed && $this->notificationService) {
            try {
                $this->notificationService->notifyStatusChange($monitor, $old_status, $new_monitor_status);
            } catch (\Exception $e) {
                // Log notification errors but don't block check recording
                Log::error('Failed to send notification', [
                    'monitor_id' => $monitor->id,
                    'old_status' => $old_status,
                    'new_status' => $new_monitor_status,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $check;
    }

    /**
     * Calculate uptime percentage for a monitor over a given time period.
     *
     * @param  Monitor  $monitor
     * @param  int  $hours
     * @return float|null Returns null if no checks exist in the time period
     */
    public function calculateUptime(Monitor $monitor, int $hours): ?float
    {
        $since = Carbon::now(config('app.timezone'))->subHours($hours);
        
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

    /**
     * Get a user-friendly error message from a connection exception.
     *
     * @param  ConnectionException  $exception
     * @return string
     */
    private function getConnectionErrorMessage(ConnectionException $exception): string
    {
        $message = $exception->getMessage();
        
        // Check for timeout
        if (str_contains($message, 'timed out') || str_contains($message, 'timeout')) {
            return 'Connection timeout after 30 seconds';
        }
        
        // Check for DNS resolution failure
        if (str_contains($message, 'resolve') || str_contains($message, 'getaddrinfo')) {
            return 'Unable to resolve hostname';
        }
        
        // Check for SSL/TLS errors
        if (str_contains($message, 'SSL') || str_contains($message, 'certificate')) {
            return 'SSL certificate validation failed';
        }
        
        // Generic connection error
        return 'Network error: ' . $message;
    }
}
