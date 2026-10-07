<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\ScraperTask;
use App\Services\RealEstateScraperService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunApiScrapers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scraper:run-apis {--source=all : The source to scrape (propnex, realestate, c21, arc, harbor, or all)} {--limit=50 : Items per portal}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute live direct-API scrapers for Cambodian real estate portals';

    /**
     * Execute the console command.
     */
    public function handle(RealEstateScraperService $service): int
    {
        $source = strtolower($this->option('source'));
        $limit = (int) $this->option('limit');

        $this->info("🚀 Starting Direct API Real Estate Scraper Suite (Source: {$source}, Limit: {$limit})...");

        $tasks = [
            'propnex' => [
                'name' => 'PropNex Cambodia (Direct OpenAPI)',
                'source_name' => 'PropNex Cambodia',
                'target_url' => 'https://www.propnexkh.com/api/v1/properties',
                'method' => 'scrapePropNex',
            ],
            'realestate' => [
                'name' => 'Realestate.com.kh (Direct REST API)',
                'source_name' => 'Realestate.com.kh',
                'target_url' => 'https://www.realestate.com.kh/api/portal/pages/results/',
                'method' => 'scrapeRealestateComKh',
            ],
            'c21' => [
                'name' => 'Century 21 Cambodia (Houzez REST API)',
                'source_name' => 'Century 21 Cambodia',
                'target_url' => 'https://cambodia-real-estate.com/wp-json/wp/v2/properties',
                'method' => 'scrapeCentury21',
            ],
            'arc' => [
                'name' => 'ARC Cambodia (PMS Website API)',
                'source_name' => 'ARC Real Estate',
                'target_url' => 'https://pms.arccambodia.com/v1/api/sale/website/property',
                'method' => 'scrapeARC',
            ],
            'harbor' => [
                'name' => 'Harbor Property (Direct REST API)',
                'source_name' => 'Harbor Property',
                'target_url' => 'https://api.harbor-property.com/api/Home/GetHouseList',
                'method' => 'scrapeHarbor',
            ],
        ];

        foreach ($tasks as $key => $info) {
            if ($source !== 'all' && $source !== $key) {
                continue;
            }

            $this->line("\n--------------------------------------------------");
            $this->info("📡 Fetching from: {$info['name']}");

            $task = ScraperTask::firstOrCreate(
                ['source_name' => $info['source_name']],
                [
                    'name' => $info['name'],
                    'target_url' => $info['target_url'],
                    'category' => 'All Categories',
                    'frequency' => 'Every 4 Hours',
                    'status' => 'active',
                    'items_scraped' => 0,
                    'user_id' => 1,
                ]
            );

            $method = $info['method'];
            $result = $service->$method($task, $limit);

            if ($result['success'] ?? false) {
                $this->info("  ✅ SUCCESS: " . ($result['message'] ?? 'Scraped successfully.'));
            } else {
                $this->warn("  ⚠️ NOTICE: " . ($result['message'] ?? 'Failed or skipped.'));
            }
        }

        $this->line("\n==================================================");
        $this->info("📊 Total Inventory Count: " . number_format(Property::count()) . " properties");
        $this->table(
            ['Source Code', 'Properties Stored'],
            Property::select('source', DB::raw('COUNT(*) as total'))
                ->groupBy('source')
                ->get()
                ->map(fn($r) => [$r->source, number_format($r->total)])
        );

        return 0;
    }
}
