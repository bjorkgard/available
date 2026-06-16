<?php

namespace App\Actions\Congregations;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\User;
use App\Notifications\Congregations\InvitationNotification;
use App\Rules\NotJwpubEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class SendInvitation
{
    /**
     * Send an invitation to join a congregation.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(User $inviter, Congregation $congregation, array $data): CongregationInvitation
    {
        Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', new NotJwpubEmail],
            'role' => ['required', new Enum(CongregationRole::class)],
            'locale' => ['sometimes', 'string', Rule::in(config('app.supported_locales'))],
        ])->validate();

        $role = CongregationRole::from($data['role']);
        $locale = $data['locale'] ?? $congregation->locale ?? config('app.locale');

        return DB::transaction(function () use ($inviter, $congregation, $data, $role, $locale) {
            $existingUser = User::where('email', $data['email'])->first();

            if ($existingUser) {
                return $this->handleExistingUser($inviter, $congregation, $existingUser, $data, $role, $locale);
            }

            return $this->handleNewUser($inviter, $congregation, $data, $role, $locale);
        });
    }

    /**
     * Handle invitation for an existing user by adding them directly to the congregation.
     */
    private function handleExistingUser(
        User $inviter,
        Congregation $congregation,
        User $existingUser,
        array $data,
        CongregationRole $role,
        string $locale,
    ): CongregationInvitation {
        $congregation->memberships()->firstOrCreate(
            ['user_id' => $existingUser->id],
            ['role' => $role],
        );

        return CongregationInvitation::create([
            'congregation_id' => $congregation->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role,
            'invited_by' => $inviter->id,
            'locale' => $locale,
            'expires_at' => now()->addHours(72),
            'accepted_at' => now(),
        ]);
    }

    /**
     * Handle invitation for a new user by creating an invitation record.
     */
    private function handleNewUser(
        User $inviter,
        Congregation $congregation,
        array $data,
        CongregationRole $role,
        string $locale,
    ): CongregationInvitation {
        // Replace any existing pending invitation for the same email+congregation
        CongregationInvitation::where('congregation_id', $congregation->id)
            ->where('email', $data['email'])
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->delete();

        $invitation = CongregationInvitation::create([
            'congregation_id' => $congregation->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $role,
            'invited_by' => $inviter->id,
            'locale' => $locale,
            'expires_at' => now()->addHours(72),
        ]);

        Notification::route('mail', $data['email'])
            ->notify((new InvitationNotification($invitation))->locale($invitation->locale));

        return $invitation;
    }
}
