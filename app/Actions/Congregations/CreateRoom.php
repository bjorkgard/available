<?php

namespace App\Actions\Congregations;

use App\Models\KingdomHall;
use App\Models\Room;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateRoom
{
    /**
     * Create a new room in the given Kingdom Hall.
     *
     * @param  array{name: string}  $data
     */
    public function handle(KingdomHall $kingdomHall, array $data): Room
    {
        $trimmedName = trim($data['name'] ?? '');

        Validator::make(['name' => $trimmedName], [
            'name' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) use ($kingdomHall): void {
                    if (Room::where('kingdom_hall_id', $kingdomHall->id)->where('name', $value)->exists()) {
                        $fail('A room with this name already exists in this Kingdom Hall.');
                    }
                },
            ],
        ])->validate();

        return DB::transaction(function () use ($kingdomHall, $trimmedName): Room {
            $maxSortOrder = $kingdomHall->rooms()->max('sort_order');
            $sortOrder = $maxSortOrder ? $maxSortOrder + 1 : 1;

            $room = Room::create([
                'kingdom_hall_id' => $kingdomHall->id,
                'name' => $trimmedName,
                'sort_order' => $sortOrder,
            ]);

            $kingdomHall->refresh();
            $kingdomHall->update(['number_of_rooms' => $kingdomHall->rooms()->count()]);

            return $room;
        });
    }
}
