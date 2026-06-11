<?php

namespace App\Notifications\Bookings;

use App\Enums\CongregationRole;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The backoff intervals between retries (seconds).
     *
     * @var array<int, int>
     */
    public array $backoff = [10, 60, 300];

    /**
     * Create a new notification instance.
     *
     * @param  array<int, string>  $roomNames
     */
    public function __construct(
        public string $bookingName,
        public Carbon $startsAt,
        public Carbon $endsAt,
        public array $roomNames,
        public User $deleter,
        public CongregationRole $deleterRole,
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

        $startsAt = $this->startsAt->timezone($timezone)->format('Y-m-d H:i');
        $endsAt = $this->endsAt->timezone($timezone)->format('Y-m-d H:i');
        $actionTime = $this->actionTimestamp->timezone($timezone)->format('Y-m-d H:i');
        $rooms = implode(', ', $this->roomNames);

        return (new MailMessage)
            ->subject(__('Your booking ":bookingName" has been deleted', ['bookingName' => $this->bookingName]))
            ->greeting(__('Hello :name,', ['name' => $notifiable->name]))
            ->line(__('Your booking has been deleted by another user.'))
            ->line(__('**Booking:** :bookingName', ['bookingName' => $this->bookingName]))
            ->line(__('**Time:** :startsAt – :endsAt', ['startsAt' => $startsAt, 'endsAt' => $endsAt]))
            ->line(__('**Rooms:** :rooms', ['rooms' => $rooms]))
            ->line(__('**Deleted by:** :name (:role)', ['name' => $this->deleter->name, 'role' => $this->deleterRole->label()]))
            ->line(__('**Deleted at:** :timestamp', ['timestamp' => $actionTime]));
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
            'starts_at' => $this->startsAt->toIso8601String(),
            'ends_at' => $this->endsAt->toIso8601String(),
            'room_names' => $this->roomNames,
            'deleter_id' => $this->deleter->id,
            'deleter_name' => $this->deleter->name,
            'deleter_role' => $this->deleterRole->value,
            'action_timestamp' => $this->actionTimestamp->toIso8601String(),
        ];
    }
}
