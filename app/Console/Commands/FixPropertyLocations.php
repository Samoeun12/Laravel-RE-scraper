<?php

namespace App\Console\Commands;

use App\Services\GeocodingService;
use Illuminate\Console\Command;

class FixPropertyLocations extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'portal:fix-locations {--dry-run : Only show mismatches without updating the database}';

    /**
     * The console command description.
     */
    protected $description = 'Audit and repair property coordinates, validating that pins match their declared Province, Khan, and Sangkat';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Auditing all property GPS locations across Cambodia...");

        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn("Running in DRY-RUN mode. No database records will be modified.");
        }

        $res = GeocodingService::repairAllProperties($isDryRun);

        $this->info("Completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Properties Audited', number_format($res['total'])],
                ['Misplaced / Adjusted Listings', number_format($res['updated'])],
                ['Accurate Unchanged Listings', number_format($res['total'] - $res['updated'])],
            ]
        );

        return Command::SUCCESS;
    }
}
