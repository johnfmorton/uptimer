<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Monitor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Monitor>
 */
class MonitorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Monitor>
     */
    protected $model = Monitor::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'url' => fake()->url(),
            'check_interval_minutes' => fake()->numberBetween(1, 60),
            'status' => fake()->randomElement(['up', 'down', 'pending']),
            'last_checked_at' => fake()->optional()->dateTime(),
            'last_status_change_at' => fake()->optional()->dateTime(),
        ];
    }

    /**
     * Indicate that the monitor is up.
     *
     * @return static
     */
    public function up(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'up',
        ]);
    }

    /**
     * Indicate that the monitor is down.
     *
     * @return static
     */
    public function down(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'down',
        ]);
    }

    /**
     * Indicate that the monitor is pending.
     *
     * @return static
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }
}
