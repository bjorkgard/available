<?php

namespace App\Actions\Congregations;

use App\Models\KingdomHall;
use App\Models\Room;
use Illuminate\Support\Facades\DB;

class DeleteKingdomHall
{
    public function __construct(
        private DeleteCongregation $deleteCongregation,
    ) {}

    /**
     * Delete a Kingdom Hall and all its connected congregations.
     */
    public function handle(KingdomHall $kingdomHall): void
    {
        DB::transaction(function () use ($kingdomHall) {
            // Get all congregations linked to this KH (not yet soft-deleted)
            $congregations = $kingdomHall->congregations()->get();

            // Delete each congregation (handles member cleanup and soft-deletion)
            foreach ($congregations as $congregation) {
                $this->deleteCongregation->handle($congregation);
            }

            // Delete all rooms associated with the KH
            Room::where('kingdom_hall_id', $kingdomHall->id)->delete();

            // Delete the KH record itself
            $kingdomHall->delete();
        });
    }
}
