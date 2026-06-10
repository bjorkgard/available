<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\RecurrencePattern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 day', '+1 month');
        // Align to 15-minute intervals
        $minutes = (int) $startsAt->format('i');
        $alignedMinutes = (int) (floor($minutes / 15) * 15);
        $startsAt->setTime((int) $startsAt->format('H'), $alignedMinutes, 0);

        // Duration: 15-minute increments between 15 min and 2 hours
        $durationMinutes = fake()->randomElement([15, 30, 45, 60, 75, 90, 105, 120]);
        $endsAt = (clone $startsAt)->modify("+{$durationMinutes} minutes");

        return [
            'congregation_id' => Congregation::factory(),
            'user_id' => User::factory(),
            'name' => fake()->words(fake()->numberBetween(2, 4), true),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'recurrence_pattern_id' => null,
            'is_exception' => false,
            'original_starts_at' => null,
        ];
    }

    /**
     * Indicate that the booking is an exception to a recurrence pattern.
     */
    public function exception(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_exception' => true,
            'original_starts_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * Indicate that the booking is part of a recurrence pattern.
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence_pattern_id' => RecurrencePattern::factory(),
        ]);
    }
}
