<?php

namespace App\Actions\Congregations;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\KingdomHall;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CreateCongregation
{
    /**
     * Create a new congregation linked to a Kingdom Hall and invite the initial user.
     *
     * @param  array{name: string, congregation_number: string, initial_user_name: string, initial_user_email: string}  $data
     */
    public function handle(User $creator, KingdomHall $kingdomHall, array $data): Congregation
    {
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'congregation_number' => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9]+$/', 'unique:congregations,congregation_number'],
            'initial_user_name' => ['required', 'string', 'max:255'],
            'initial_user_email' => ['required', 'email', 'max:255'],
        ])->validate();

        return DB::transaction(function () use ($creator, $kingdomHall, $data): Congregation {
            $congregation = Congregation::create([
                'name' => $data['name'],
                'congregation_number' => $data['congregation_number'],
                'kingdom_hall_id' => $kingdomHall->id,
            ]);

            CongregationInvitation::create([
                'congregation_id' => $congregation->id,
                'name' => $data['initial_user_name'],
                'email' => $data['initial_user_email'],
                'role' => CongregationRole::Admin,
                'invited_by' => $creator->id,
                'expires_at' => now()->addHours(72),
            ]);

            return $congregation;
        });
    }
}
