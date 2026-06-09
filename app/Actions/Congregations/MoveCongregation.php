<?php

namespace App\Actions\Congregations;

use App\Enums\CongregationRole;
use App\Exceptions\ColorGenerationException;
use App\Models\Congregation;
use App\Models\KingdomHall;
use App\Models\Membership;
use App\Services\ColorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveCongregation
{
    public function __construct(private ColorService $colorService) {}

    /**
     * Move a congregation to a different Kingdom Hall.
     *
     * @throws ValidationException
     */
    public function handle(Congregation $congregation, KingdomHall $targetKingdomHall): Congregation
    {
        if ($congregation->kingdom_hall_id === null) {
            throw ValidationException::withMessages([
                'kingdom_hall' => ['This congregation is not currently assigned to a Kingdom Hall.'],
            ]);
        }

        if ($congregation->kingdom_hall_id === $targetKingdomHall->id) {
            throw ValidationException::withMessages([
                'kingdom_hall' => ['The target Kingdom Hall is the same as the current one.'],
            ]);
        }

        return DB::transaction(function () use ($congregation, $targetKingdomHall) {
            $originalKingdomHallId = $congregation->kingdom_hall_id;

            $congregation->update(['kingdom_hall_id' => $targetKingdomHall->id]);

            // Validate/regenerate color against destination hall siblings
            $siblingColors = Congregation::where('kingdom_hall_id', $targetKingdomHall->id)
                ->where('id', '!=', $congregation->id)
                ->whereNotNull('color')
                ->pluck('color')
                ->all();

            if ($congregation->color && ! $this->colorService->isDistinctFromAll($congregation->color, $siblingColors)) {
                try {
                    $newColor = $this->colorService->generateDistinctColor($siblingColors);
                    $congregation->update(['color' => $newColor]);
                } catch (ColorGenerationException $e) {
                    throw ValidationException::withMessages([
                        'kingdom_hall' => ['Cannot move congregation: unable to generate a sufficiently distinct color for the destination Kingdom Hall.'],
                    ]);
                }
            } elseif (! $congregation->color && $siblingColors !== []) {
                try {
                    $newColor = $this->colorService->generateDistinctColor($siblingColors);
                    $congregation->update(['color' => $newColor]);
                } catch (ColorGenerationException $e) {
                    throw ValidationException::withMessages([
                        'kingdom_hall' => ['Cannot move congregation: unable to generate a sufficiently distinct color for the destination Kingdom Hall.'],
                    ]);
                }
            } elseif (! $congregation->color && $siblingColors === []) {
                $congregation->update(['color' => $this->colorService->generateDistinctColor([])]);
            }

            // Find users who have superadmin role in this congregation
            $superadminMemberships = Membership::where('congregation_id', $congregation->id)
                ->where('role', CongregationRole::Superadmin)
                ->get();

            foreach ($superadminMemberships as $membership) {
                // Check if this user has any OTHER congregation in the original KH
                $hasOtherCongregationInOriginalKh = Membership::where('user_id', $membership->user_id)
                    ->where('congregation_id', '!=', $congregation->id)
                    ->whereHas('congregation', function ($query) use ($originalKingdomHallId) {
                        $query->where('kingdom_hall_id', $originalKingdomHallId);
                    })
                    ->exists();

                // If this was their only congregation in the original KH, demote to admin
                if (! $hasOtherCongregationInOriginalKh) {
                    $membership->update(['role' => CongregationRole::Admin]);
                }
            }

            return $congregation->refresh();
        });
    }
}
