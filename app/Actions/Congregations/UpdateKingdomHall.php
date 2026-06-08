<?php

namespace App\Actions\Congregations;

use App\Models\KingdomHall;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UpdateKingdomHall
{
    /**
     * Validate and update an existing Kingdom Hall.
     *
     * Handles room count changes:
     * - Increase: auto-generates new Room records with sequential names and sort_order
     * - Decrease: rejected with a ValidationException (rooms must be removed individually)
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(KingdomHall $kingdomHall, array $data): KingdomHall
    {
        $validated = Validator::make($data, [
            'street_address' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'number_of_rooms' => ['required', 'integer', 'min:1', 'max:50'],
        ])->validate();

        return DB::transaction(function () use ($kingdomHall, $validated) {
            $currentRoomCount = $kingdomHall->rooms()->count();
            $newRoomCount = $validated['number_of_rooms'];

            // Reject decrease: rooms must be removed individually before reducing the count
            if ($newRoomCount < $currentRoomCount) {
                throw ValidationException::withMessages([
                    'number_of_rooms' => ['Rooms must be removed individually before reducing the count.'],
                ]);
            }

            $kingdomHall->update([
                'street_address' => $validated['street_address'],
                'zip_code' => $validated['zip_code'],
                'city' => $validated['city'],
                'number_of_rooms' => $validated['number_of_rooms'],
            ]);

            // Auto-generate new rooms if the count increased
            if ($newRoomCount > $currentRoomCount) {
                for ($i = $currentRoomCount + 1; $i <= $newRoomCount; $i++) {
                    $kingdomHall->rooms()->create([
                        'name' => "Room {$i}",
                        'sort_order' => $i,
                    ]);
                }
            }

            return $kingdomHall->refresh();
        });
    }
}
