<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Seeder;

class MonitorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Get the admin user
        $admin_user = User::where('email', 'admin@example.com')->first();

        if ($admin_user) {
            // Create monitors with different statuses for the admin user
            Monitor::factory()->create([
                'user_id' => $admin_user->id,
                'name' => 'Google',
                'url' => 'https://www.google.com',
                'check_interval_minutes' => 5,
                'status' => 'up',
                'last_checked_at' => now()->subMinutes(3),
                'last_status_change_at' => now()->subHours(2),
            ]);

            Monitor::factory()->create([
                'user_id' => $admin_user->id,
                'name' => 'GitHub',
                'url' => 'https://github.com',
                'check_interval_minutes' => 10,
                'status' => 'up',
                'last_checked_at' => now()->subMinutes(5),
                'last_status_change_at' => now()->subHours(1),
            ]);

            Monitor::factory()->create([
                'user_id' => $admin_user->id,
                'name' => 'Example API',
                'url' => 'https://api.example.com/health',
                'check_interval_minutes' => 15,
                'status' => 'down',
                'last_checked_at' => now()->subMinutes(2),
                'last_status_change_at' => now()->subMinutes(30),
            ]);

            Monitor::factory()->create([
                'user_id' => $admin_user->id,
                'name' => 'New Monitor',
                'url' => 'https://newsite.example.com',
                'check_interval_minutes' => 5,
                'status' => 'pending',
                'last_checked_at' => null,
                'last_status_change_at' => null,
            ]);
        }

        // Get the test user
        $test_user = User::where('email', 'test@example.com')->first();

        if ($test_user) {
            // Create a few monitors for the test user
            Monitor::factory()->create([
                'user_id' => $test_user->id,
                'name' => 'Laravel',
                'url' => 'https://laravel.com',
                'check_interval_minutes' => 5,
                'status' => 'up',
                'last_checked_at' => now()->subMinutes(4),
                'last_status_change_at' => now()->subHours(3),
            ]);

            Monitor::factory()->create([
                'user_id' => $test_user->id,
                'name' => 'Test Site',
                'url' => 'https://test.example.com',
                'check_interval_minutes' => 30,
                'status' => 'pending',
                'last_checked_at' => null,
                'last_status_change_at' => null,
            ]);
        }

        // Create random monitors for other users
        $other_users = User::whereNotIn('email', ['admin@example.com', 'test@example.com'])->get();
        
        foreach ($other_users as $user) {
            Monitor::factory(rand(2, 5))->create([
                'user_id' => $user->id,
            ]);
        }
    }
}
