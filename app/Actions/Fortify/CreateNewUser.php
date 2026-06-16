<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CongregationRole;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Models\User;
use App\Rules\NotJwpubEmail;
use App\Services\ColorService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    public function __construct(private ColorService $colorService) {}

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => $this->nameRules(),
            'email' => ['required', 'string', 'email', 'max:255', new NotJwpubEmail, Rule::unique(User::class)],
            'password' => $this->passwordRules(),
            'congregation_name' => ['required', 'string', 'max:255'],
            'congregation_number' => [
                'required',
                'string',
                'max:20',
                'regex:/^[A-Z0-9]+$/',
                Rule::unique(Congregation::class, 'congregation_number'),
            ],
            'street_address' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
        ], [
            'congregation_number.regex' => __('The congregation number must contain only digits and uppercase letters (A–Z).'),
            'congregation_number.unique' => __('This congregation number is already in use.'),
            'email.unique' => __('This email address is already in use.'),
        ])->validate();

        // Check if a Kingdom Hall with this exact address already exists
        $existingHall = KingdomHall::where('street_address', $input['street_address'])
            ->where('zip_code', $input['zip_code'])
            ->where('city', $input['city'])
            ->where('country', $input['country'])
            ->first();

        if ($existingHall) {
            $superadmins = $this->getSuperadminsForHall($existingHall);

            throw ValidationException::withMessages([
                'street_address' => [__('Det finns redan en Rikets sal registrerad på denna adress. Kontakta en superadmin för att bli inbjuden.')],
                'existing_hall_superadmins' => [$superadmins->toJson()],
            ]);
        }

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            try {
                $kingdomHall = KingdomHall::create([
                    'street_address' => $input['street_address'],
                    'zip_code' => $input['zip_code'],
                    'city' => $input['city'],
                    'country' => $input['country'],
                    'number_of_rooms' => 1,
                ]);
            } catch (QueryException $e) {
                // Race condition: another request created the same hall concurrently
                if (str_contains($e->getMessage(), 'UNIQUE constraint failed') || str_contains($e->getMessage(), 'Duplicate entry')) {
                    $existingHall = KingdomHall::where('street_address', $input['street_address'])
                        ->where('zip_code', $input['zip_code'])
                        ->where('city', $input['city'])
                        ->where('country', $input['country'])
                        ->firstOrFail();

                    throw ValidationException::withMessages([
                        'street_address' => [__('Det finns redan en Rikets sal registrerad på denna adress. Kontakta en superadmin för att bli inbjuden.')],
                        'existing_hall_superadmins' => [$this->getSuperadminsForHall($existingHall)->toJson()],
                    ]);
                }

                throw $e;
            }

            // Create a default room
            $kingdomHall->rooms()->create([
                'name' => __('Rum :number', ['number' => 1]),
                'sort_order' => 1,
            ]);

            $color = $this->colorService->generateDistinctColor([]);

            $congregation = Congregation::create([
                'name' => $input['congregation_name'],
                'congregation_number' => $input['congregation_number'],
                'kingdom_hall_id' => $kingdomHall->id,
                'color' => $color,
            ]);

            $congregation->memberships()->create([
                'user_id' => $user->id,
                'role' => CongregationRole::Superadmin,
            ]);

            $user->switchCongregation($congregation);

            return $user;
        });
    }

    /**
     * Get superadmin contact information for an existing Kingdom Hall.
     *
     * @return Collection<int, array{name: string, email: string}>
     */
    private function getSuperadminsForHall(KingdomHall $hall): Collection
    {
        return Membership::where('role', CongregationRole::Superadmin)
            ->whereHas('congregation', fn ($query) => $query->where('kingdom_hall_id', $hall->id))
            ->with('user:id,name,email')
            ->get()
            ->map(fn (Membership $membership) => [
                'name' => $membership->user->name,
                'email' => $membership->user->email,
            ])
            ->unique('email')
            ->values();
    }
}
