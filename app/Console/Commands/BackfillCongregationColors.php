<?php

namespace App\Console\Commands;

use App\Exceptions\ColorGenerationException;
use App\Models\Congregation;
use App\Services\ColorService;
use Illuminate\Console\Command;

class BackfillCongregationColors extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'congregations:backfill-colors';

    /**
     * The console command description.
     */
    protected $description = 'Assign colors to congregations that do not yet have one';

    /**
     * Execute the console command.
     */
    public function handle(ColorService $colorService): int
    {
        $congregations = Congregation::whereNull('color')->get();

        if ($congregations->isEmpty()) {
            $this->info('All congregations already have colors assigned.');

            return self::SUCCESS;
        }

        $this->info("Found {$congregations->count()} congregation(s) without colors.");

        $processed = 0;
        $failed = 0;

        // Group by kingdom_hall_id to respect distance constraints
        $grouped = $congregations->groupBy('kingdom_hall_id');

        foreach ($grouped as $kingdomHallId => $hallCongregations) {
            // Get existing sibling colors in this hall (from congregations that already have colors)
            $existingSiblingColors = [];
            if ($kingdomHallId) {
                $existingSiblingColors = Congregation::where('kingdom_hall_id', $kingdomHallId)
                    ->whereNotNull('color')
                    ->pluck('color')
                    ->all();
            }

            foreach ($hallCongregations as $congregation) {
                try {
                    $color = $colorService->generateDistinctColor($existingSiblingColors);
                    $congregation->update(['color' => $color]);
                    $existingSiblingColors[] = $color; // Track for next sibling
                    $processed++;
                } catch (ColorGenerationException $e) {
                    $this->error("Failed to generate color for congregation '{$congregation->name}' (ID: {$congregation->id}): {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $this->info("Done. Assigned colors to {$processed} congregation(s).");

        if ($failed > 0) {
            $this->warn("{$failed} congregation(s) could not be assigned a color.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
