<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('kingdom-hall.{kingdomHallId}', function (User $user, string $kingdomHallId) {
    return $user->currentCongregation?->kingdom_hall_id === $kingdomHallId
        || $user->congregations()->where('kingdom_hall_id', $kingdomHallId)->exists();
});
