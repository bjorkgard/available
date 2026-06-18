<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $kingdomHallId;

    /**
     * Create a new event instance.
     *
     * Only the first booking of a recurrence series is broadcast to keep
     * the payload within WebSocket size limits. Clients refetch as needed.
     */
    public function __construct(public Booking $booking)
    {
        $this->kingdomHallId = $booking->congregation->kingdom_hall_id;
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
            'booking' => [
                'id' => $this->booking->id,
                'name' => $this->booking->name,
                'starts_at' => $this->booking->starts_at->toIso8601String(),
                'ends_at' => $this->booking->ends_at->toIso8601String(),
                'congregation_id' => $this->booking->congregation_id,
                'congregation_color' => $this->booking->congregation->color,
                'user_id' => $this->booking->user_id,
                'user_name' => $this->booking->user?->name,
                'rooms' => $this->booking->rooms->map(fn ($room) => [
                    'id' => $room->id,
                    'name' => $room->name,
                ])->all(),
                'recurrence_pattern_id' => $this->booking->recurrence_pattern_id,
                'is_exception' => $this->booking->is_exception,
            ],
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'booking.created';
    }
}
