<?php

namespace App\Notifications\Congregations;

use App\Models\CongregationInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public CongregationInvitation $invitation)
    {
        //
    }

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
        $congregation = $this->invitation->congregation;
        $roleLabel = $this->invitation->role->label();

        return (new MailMessage)
            ->subject(__("You've been invited to join :congregationName", ['congregationName' => $congregation->name]))
            ->greeting(__('Hello :name,', ['name' => $this->invitation->name]))
            ->line(__("You've been invited to join :congregationName as :role.", [
                'congregationName' => $congregation->name,
                'role' => $roleLabel,
            ]))
            ->action(__('Accept Invitation'), route('invitations.accept', $this->invitation->code))
            ->line(__('This invitation expires in 72 hours.'))
            ->line(__('If you did not expect this invitation, you can ignore this email.'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_id' => $this->invitation->id,
            'congregation_id' => $this->invitation->congregation_id,
            'congregation_name' => $this->invitation->congregation->name,
            'role' => $this->invitation->role->value,
        ];
    }
}
