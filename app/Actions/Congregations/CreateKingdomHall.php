<?php

namespace App\Actions\Congregations;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateKingdomHall
{
    /**
     * Validate and create a new Kingdom Hall with auto-generated rooms.
     *
     * Links the given congregation to the Kingdom Hall and assigns the
     * superadmin role to the user.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $user, Congregation $congregation, array $data): KingdomHall
    {
        $validated = Validator::make($data, [
            'street_address' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'number_of_rooms' => ['required', 'integer', 'min:1', 'max:50'],
        ])->validate();

        return DB::transaction(function () use ($user, $congregation, $validated) {
            $kingdomHall = KingdomHall::create([
                'street_address' => $validated['street_address'],
                'zip_code' => $validated['zip_code'],
                'city' => $validated['city'],
                'number_of_rooms' => $validated['number_of_rooms'],
            ]);

            for ($i = 1; $i <= $validated['number_of_rooms']; $i++) {
                $kingdomHall->rooms()->create([
                    'name' => "Room {$i}",
                    'sort_order' => $i,
                ]);
            }

            $congregation->update(['kingdom_hall_id' => $kingdomHall->id]);

            $membership = $congregation->memberships()
                ->where('user_id', $user->id)
                ->first();

            if ($membership) {
                $membership->update(['role' => CongregationRole::Superadmin]);
            } else {
                $congregation->memberships()->create([
                    'user_id' => $user->id,
                    'role' => CongregationRole::Superadmin,
                ]);
            }

            return $kingdomHall;
        });
    }
}
