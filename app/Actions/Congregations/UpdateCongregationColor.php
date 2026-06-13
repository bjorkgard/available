<?php

namespace App\Actions\Congregations;

use App\Models\Congregation;
use App\Services\ColorService;
use Illuminate\Validation\ValidationException;

class UpdateCongregationColor
{
    public function __construct(private ColorService $colorService) {}

    /**
     * Validate and update a congregation's color.
     *
     * @throws ValidationException
     */
    public function handle(Congregation $congregation, string $color): Congregation
    {
        // Normalize to uppercase
        $color = strtoupper($color);

        // Validate hex format
        if (! ColorService::isValidHex($color)) {
            throw ValidationException::withMessages([
                'color' => [__('The color must be a valid hex color (e.g., #3B82F6).')],
            ]);
        }

        // Validate distance from siblings in same kingdom hall
        if ($congregation->kingdom_hall_id) {
            $siblingColors = Congregation::where('kingdom_hall_id', $congregation->kingdom_hall_id)
                ->where('id', '!=', $congregation->id)
                ->whereNotNull('color')
                ->pluck('color')
                ->all();

            if (! $this->colorService->isDistinctFromAll($color, $siblingColors)) {
                throw ValidationException::withMessages([
                    'color' => [__('This color is too similar to another congregation\'s color in this Kingdom Hall.')],
                ]);
            }
        }

        // Persist the new color
        $congregation->update(['color' => $color]);

        return $congregation->refresh();
    }
}
