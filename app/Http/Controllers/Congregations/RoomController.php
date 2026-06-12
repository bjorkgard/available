<?php

namespace App\Http\Controllers\Congregations;

use App\Actions\Congregations\CreateRoom;
use App\Actions\Congregations\DeleteRoom;
use App\Actions\Congregations\RenameRoom;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RoomController extends Controller
{
    /**
     * Store a new room.
     */
    public function store(Request $request, string $currentCongregation, CreateRoom $createRoom): RedirectResponse
    {
        $kingdomHall = $request->user()->currentCongregation->kingdomHall;

        Gate::authorize('manageRooms', $kingdomHall);

        $createRoom->handle($kingdomHall, $request->only(['name']));

        return back()->with('success', __('Room created.'));
    }

    /**
     * Update a room's name.
     */
    public function update(Request $request, string $currentCongregation, Room $room, RenameRoom $renameRoom): RedirectResponse
    {
        $kingdomHall = $room->kingdomHall;

        Gate::authorize('manageRooms', $kingdomHall);

        $renameRoom->handle($room, $request->only(['name']));

        return back()->with('success', __('Room renamed.'));
    }

    /**
     * Delete a room.
     */
    public function destroy(Request $request, string $currentCongregation, Room $room, DeleteRoom $deleteRoom): RedirectResponse
    {
        $kingdomHall = $room->kingdomHall;

        Gate::authorize('manageRooms', $kingdomHall);

        $deleteRoom->handle($room);

        return back()->with('success', __('Room deleted.'));
    }
}
