<?php

namespace App\Actions\Congregations;

use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DeleteRoom
{
    /**
     * Delete a room and sync the Kingdom Hall room count.
     */
    public function handle(Room $room): void
    {
        DB::transaction(function () use ($room): void {
            $kingdomHall = $room->kingdomHall;

            $room->delete();

            $kingdomHall->refresh();
            $kingdomHall->update(['number_of_rooms' => $kingdomHall->rooms()->count()]);
        });
    }
}
