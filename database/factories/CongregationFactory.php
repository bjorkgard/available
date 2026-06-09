<?php

namespace Database\Factories;

use App\Models\Congregation;
use App\Models\KingdomHall;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Congregation>
 */
class CongregationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'congregation_number' => strtoupper(fake()->unique()->bothify('??####')),
        ];
    }

    /**
     * Indicate that the congregation belongs to a Kingdom Hall.
     */
    public function withKingdomHall(?KingdomHall $kingdomHall = null): static
    {
        return $this->state(fn (array $attributes) => [
            'kingdom_hall_id' => $kingdomHall?->id ?? KingdomHall::factory(),
        ]);
    }

    /**
     * Indicate that the congregation has been deleted.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }
}
