<?php

namespace Database\Factories;

use App\Models\KingdomHall;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'kingdom_hall_id' => KingdomHall::factory(),
            'name' => fake()->word().' Room',
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
