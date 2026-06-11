<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $kingdomHallId;

    /**
     * Create a new event instance.
     *
     * @param  Collection<int, Booking>  $bookings
     */
    public function __construct(public Collection $bookings)
    {
        $this->kingdomHallId = $bookings->first()->congregation->kingdom_hall_id;
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('kingdom-hall.'.$this->kingdomHallId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'bookings' => $this->bookings->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'name' => $booking->name,
                'starts_at' => $booking->starts_at->toIso8601String(),
                'ends_at' => $booking->ends_at->toIso8601String(),
                'congregation_id' => $booking->congregation_id,
                'congregation_color' => $booking->congregation->color,
                'user_id' => $booking->user_id,
                'user_name' => $booking->user?->name,
                'rooms' => $booking->rooms->map(fn ($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                ])->all(),
                'recurrence_pattern_id' => $booking->recurrence_pattern_id,
                'is_exception' => $booking->is_exception,
            ])->all(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'booking.updated';
    }
}
