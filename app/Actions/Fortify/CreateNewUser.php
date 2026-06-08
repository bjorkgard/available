<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'congregation_name' => ['required', 'string', 'max:255'],
            'congregation_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique(Congregation::class, 'congregation_number'),
            ],
        ], [
            'congregation_number.regex' => 'The congregation number must contain only digits and uppercase letters (A–Z).',
            'congregation_number.unique' => 'This congregation number is already in use.',
            'email.unique' => 'This email address is already in use.',
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $congregation = Congregation::create([
                'name' => $input['congregation_name'],
                'congregation_number' => $input['congregation_number'],
            ]);

            $congregation->memberships()->create([
                'user_id' => $user->id,
                'role' => CongregationRole::Admin,
            ]);

            $user->switchCongregation($congregation);

            return $user;
        });
    }
}
