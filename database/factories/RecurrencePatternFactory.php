<?php

namespace Database\Factories;

use App\Enums\RecurrenceFrequency;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecurrencePattern>
 */
class RecurrencePatternFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'congregation_id' => Congregation::factory(),
            'frequency' => fake()->randomElement(RecurrenceFrequency::cases()),
            'end_date' => fake()->optional()->dateTimeBetween('+1 month', '+1 year'),
            'end_count' => fake()->optional()->numberBetween(1, 52),
        ];
    }

    /**
     * Indicate that the pattern ends on a specific date.
     */
    public function endsOnDate(): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => fake()->dateTimeBetween('+1 month', '+1 year'),
            'end_count' => null,
        ]);
    }

    /**
     * Indicate that the pattern ends after a specific count.
     */
    public function endsAfterCount(int $count = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'end_date' => null,
            'end_count' => $count,
        ]);
    }
}
