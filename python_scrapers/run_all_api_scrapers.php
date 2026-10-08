<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ScraperTask;
use App\Models\Property;
use App\Services\RealEstateScraperService;

echo "=== APEX MULTI-PORTAL API INGESTION SUITE ===\n\n";

$service = new RealEstateScraperService();

// 1. Ensure ScraperTask for PropNex Cambodia
$propnexTask = ScraperTask::firstOrCreate(
    ['source_name' => 'PropNex Cambodia'],
    [
        'name' => 'PropNex Cambodia (Direct OpenAPI)',
        'target_url' => 'https://www.propnexkh.com/api/v1/properties',
        'category' => 'Borey & Luxury Residential',
        'frequency' => 'Every 4 Hours',
        'status' => 'active',
        'items_scraped' => 0,
        'user_id' => 1,
    ]
);

// 2. Ensure ScraperTask for Realestate.com.kh
$realestateTask = ScraperTask::firstOrCreate(
    ['source_name' => 'Realestate.com.kh'],
    [
        'name' => 'Realestate.com.kh (Direct REST API)',
        'target_url' => 'https://www.realestate.com.kh/api/portal/pages/results/',
        'category' => 'Condo, Villa & Land',
        'frequency' => 'Every 3 Hours',
        'status' => 'active',
        'items_scraped' => 2553,
        'user_id' => 1,
    ]
);

// 3. Ensure ScraperTask for Century 21
$c21Task = ScraperTask::where('source_name', 'like', '%Century 21%')->first();

// 4. Ensure ScraperTask for ARC
$arcTask = ScraperTask::where('source_name', 'like', '%ARC%')->first();

// 5. Ensure ScraperTask for Harbor
$harborTask = ScraperTask::where('source_name', 'like', '%Harbor%')->first();

// 6. Ensure ScraperTask for Bayon App
$bayonTask = ScraperTask::firstOrCreate(
    ['source_name' => 'Bayon App Real Estate (bayonapp.com)'],
    [
        'name' => 'Bayon App Real Estate (Live API)',
        'target_url' => 'https://bayonapp.com/',
        'category' => 'Nationwide & Land Portfolios',
        'frequency' => 'Every 3 Hours',
        'status' => 'active',
        'items_scraped' => 0,
        'user_id' => 1,
    ]
);

echo "[1/6] Ingesting from Bayon App Real Estate REST API (50 items)...\n";
$bayonResult = $service->scrapeBayonApp($bayonTask, 50);
echo "  -> Result: " . ($bayonResult['success'] ? 'SUCCESS' : 'FAILED') . " - " . ($bayonResult['message'] ?? '') . "\n";

echo "\n[2/6] Ingesting from PropNex Cambodia OpenAPI (page 1, 50 items)...\n";
$propnexResult = $service->scrapePropNex($propnexTask, 50);
echo "  -> Result: " . ($propnexResult['success'] ? 'SUCCESS' : 'FAILED') . " - " . ($propnexResult['message'] ?? '') . "\n";

echo "\n[3/6] Ingesting from Realestate.com.kh REST API (page 1, 50 items)...\n";
$reResult = $service->scrapeRealestateComKh($realestateTask, 50);
echo "  -> Result: " . ($reResult['success'] ? 'SUCCESS' : 'FAILED') . " - " . ($reResult['message'] ?? '') . "\n";

echo "\n[4/6] Ingesting from Century 21 Cambodia Houzez REST API (25 items)...\n";
$c21Result = $service->scrapeCentury21($c21Task, 25);
echo "  -> Result: " . ($c21Result['success'] ? 'SUCCESS' : 'FAILED') . " - " . ($c21Result['message'] ?? '') . "\n";

echo "\n[5/6] Ingesting from ARC Cambodia PMS API (25 items)...\n";
$arcResult = $service->scrapeARC($arcTask, 25);
echo "  -> Result: " . ($arcResult['success'] ? 'SUCCESS' : 'FAILED') . " - " . ($arcResult['message'] ?? '') . "\n";

echo "\n============================================\n";
echo "Ingestion cycle completed!\n";
echo "Total Properties in Database now: " . number_format(Property::count()) . "\n";
echo "Sources Breakdown:\n";
foreach (Property::select('source', \Illuminate\Support\Facades\DB::raw('COUNT(*) as total'))->groupBy('source')->get() as $row) {
    echo "  - " . str_pad($row->source, 16) . ": " . number_format($row->total) . " listings\n";
}
echo "============================================\n";
