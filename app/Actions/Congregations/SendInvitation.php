<?php

namespace App\Actions\Congregations;

use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\CongregationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', new Enum(CongregationRole::class)],
        ])->validate();

        $role = CongregationRole::from($data['role']);

        return DB::transaction(function () use ($inviter, $congregation, $data, $role) {
            $existingUser = User::where('email', $data['email'])->first();

            if ($existingUser) {
                return $this->handleExistingUser($inviter, $congregation, $existingUser, $data, $role);
            }

            return $this->handleNewUser($inviter, $congregation, $data, $role);
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
            'expires_at' => now()->addHours(72),
        ]);

        // TODO: Send notification (will be implemented in task 13.1)

        return $invitation;
    }
}
