<?php

namespace App\Notifications\Bookings;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingModifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The backoff strategy (seconds) between retries.
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    /**
     * Create a new notification instance.
     *
     * @param  array<int, string>  $oldRooms
     * @param  array<int, string>  $newRooms
     */
    public function __construct(
        public string $bookingName,
        public Carbon $oldStartsAt,
        public Carbon $oldEndsAt,
        public Carbon $newStartsAt,
        public Carbon $newEndsAt,
        public array $oldRooms,
        public array $newRooms,
        public User $modifier,
        public string $modifierRole,
        public Carbon $actionTimestamp,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $timezone = 'Europe/Stockholm';
        $format = 'Y-m-d H:i';

        $oldTimeRange = $this->oldStartsAt->timezone($timezone)->format($format)
            .' – '.$this->oldEndsAt->timezone($timezone)->format($format);

        $newTimeRange = $this->newStartsAt->timezone($timezone)->format($format)
            .' – '.$this->newEndsAt->timezone($timezone)->format($format);

        $oldRoomList = implode(', ', $this->oldRooms);
        $newRoomList = implode(', ', $this->newRooms);

        $actionTime = $this->actionTimestamp->timezone($timezone)->format($format);

        return (new MailMessage)
            ->subject(__('Your booking ":bookingName" has been modified', ['bookingName' => $this->bookingName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__(':modifierName (:modifierRole) modified your booking.', [
                'modifierName' => $this->modifier->name,
                'modifierRole' => $this->modifierRole,
            ]))
            ->line(__('**Booking:** :bookingName', ['bookingName' => $this->bookingName]))
            ->line(__('**Previous time:** :oldTimeRange', ['oldTimeRange' => $oldTimeRange]))
            ->line(__('**New time:** :newTimeRange', ['newTimeRange' => $newTimeRange]))
            ->line(__('**Previous rooms:** :oldRooms', ['oldRooms' => $oldRoomList]))
            ->line(__('**New rooms:** :newRooms', ['newRooms' => $newRoomList]))
            ->line(__('**Changed at:** :actionTime', ['actionTime' => $actionTime]));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_name' => $this->bookingName,
            'old_starts_at' => $this->oldStartsAt->toIso8601String(),
            'old_ends_at' => $this->oldEndsAt->toIso8601String(),
            'new_starts_at' => $this->newStartsAt->toIso8601String(),
            'new_ends_at' => $this->newEndsAt->toIso8601String(),
            'old_rooms' => $this->oldRooms,
            'new_rooms' => $this->newRooms,
            'modifier_name' => $this->modifier->name,
            'modifier_role' => $this->modifierRole,
            'action_timestamp' => $this->actionTimestamp->toIso8601String(),
        ];
    }
}
