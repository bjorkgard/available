<?php

namespace App\Actions\Congregations;

use App\Models\KingdomHall;
use Illuminate\Support\Facades\Validator;

class UpdateKingdomHall
{
    /**
     * Validate and update the Kingdom Hall address.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(KingdomHall $kingdomHall, array $data): KingdomHall
    {
        $validated = Validator::make($data, [
            'street_address' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
        ])->validate();

        $kingdomHall->update([
            'street_address' => $validated['street_address'],
            'zip_code' => $validated['zip_code'],
            'city' => $validated['city'],
        ]);

        return $kingdomHall->refresh();
    }
}
