<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Database\Seeder;

class CheckSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Get all monitors
        $monitors = Monitor::all();

        foreach ($monitors as $monitor) {
            // Skip pending monitors (they haven't been checked yet)
            if ($monitor->isPending()) {
                continue;
            }

            // Create check history for the last 7 days
            $days_of_history = 7;
            $checks_per_day = 24 * (60 / $monitor->check_interval_minutes);
            $total_checks = (int) ($days_of_history * $checks_per_day);

            // Determine success rate based on monitor status
            $success_rate = $monitor->isUp() ? 0.98 : 0.30; // 98% for up, 30% for down

            for ($i = 0; $i < $total_checks; $i++) {
                $checked_at = now()->subMinutes($i * $monitor->check_interval_minutes);
                $is_successful = (mt_rand() / mt_getrandmax()) < $success_rate;

                if ($is_successful) {
                    Check::factory()->successful()->create([
                        'monitor_id' => $monitor->id,
                        'checked_at' => $checked_at,
                        'created_at' => $checked_at,
                    ]);
                } else {
                    Check::factory()->failed()->create([
                        'monitor_id' => $monitor->id,
                        'checked_at' => $checked_at,
                        'created_at' => $checked_at,
                    ]);
                }
            }
        }
    }
}
