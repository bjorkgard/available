<?php

namespace App\Actions\Congregations;

use App\Models\Room;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RenameRoom
{
    /**
     * Validate and rename a room.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ValidationException
     */
    public function handle(Room $room, array $data): Room
    {
        $trimmedName = trim($data['name'] ?? '');

        $validated = Validator::make(['name' => $trimmedName], [
            'name' => ['required', 'string', 'max:255'],
        ])->validate();

        $nameExists = Room::where('kingdom_hall_id', $room->kingdom_hall_id)
            ->where('name', $validated['name'])
            ->where('id', '!=', $room->id)
            ->exists();

        if ($nameExists) {
            throw ValidationException::withMessages([
                'name' => __('A room with this name already exists in the Kingdom Hall.'),
            ]);
        }

        $room->update([
            'name' => $validated['name'],
        ]);

        return $room->refresh();
    }
}
