<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Check;
use App\Models\Monitor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Check>
 */
class CheckFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<Check>
     */
    protected $model = Check::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'monitor_id' => Monitor::factory(),
            'status' => fake()->randomElement(['success', 'failed']),
            'status_code' => fake()->optional()->numberBetween(200, 599),
            'response_time_ms' => fake()->optional()->numberBetween(50, 5000),
            'error_message' => fake()->optional()->sentence(),
            'checked_at' => fake()->dateTime(),
            'created_at' => now(),
        ];
    }

    /**
     * Indicate that the check was successful.
     *
     * @return static
     */
    public function successful(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'success',
            'status_code' => fake()->numberBetween(200, 299),
            'response_time_ms' => fake()->numberBetween(50, 2000),
            'error_message' => null,
        ]);
    }

    /**
     * Indicate that the check failed.
     *
     * @return static
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'status_code' => fake()->optional()->numberBetween(400, 599),
            'error_message' => fake()->sentence(),
        ]);
    }
}
