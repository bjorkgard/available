<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\RecurrencePattern;
use Illuminate\Console\Command;

class CleanupExpiredBookings extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:cleanup';

    /**
     * The console command description.
     */
    protected $description = 'Delete bookings older than 6 months and orphaned recurrence patterns';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now('Europe/Stockholm')->subMonths(6);

        $deletedBookings = Booking::where('ends_at', '<', $cutoff)->delete();

        $deletedPatterns = RecurrencePattern::whereDoesntHave('bookings')->delete();

        $this->info("Deleted {$deletedBookings} bookings and {$deletedPatterns} orphaned patterns.");

        return self::SUCCESS;
    }
}
