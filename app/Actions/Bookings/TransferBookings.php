<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Models\Congregation;
use App\Models\User;
use Carbon\Carbon;

class TransferBookings
{
    /**
     * Transfer all future bookings from one user to another within a congregation.
     *
     * @return int The number of bookings transferred
     */
    public function handle(User $source, User $target, Congregation $congregation): int
    {
        $now = Carbon::now('Europe/Stockholm');

        return Booking::query()
            ->where('congregation_id', $congregation->id)
            ->where('user_id', $source->id)
            ->where('starts_at', '>=', $now)
            ->update(['user_id' => $target->id]);
    }
}
