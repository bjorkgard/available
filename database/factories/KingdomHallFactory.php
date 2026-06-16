<?php

namespace Database\Factories;

use App\Models\KingdomHall;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KingdomHall>
 */
class KingdomHallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'street_address' => fake()->streetAddress(),
            'zip_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Sverige',
            'number_of_rooms' => fake()->numberBetween(1, 50),
        ];
    }
}
