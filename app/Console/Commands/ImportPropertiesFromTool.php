<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\ScraperTask;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDO;

class ImportPropertiesFromTool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:import-from-tool {--path=E:\\Python Test\\Tool Scrap Data RE\\properties.db} {--limit=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import real scraped Cambodian properties from the Python Tool Scrap Data RE database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dbPath = $this->option('path');
        $limit = (int) $this->option('limit');

        if (!file_exists($dbPath)) {
            $this->error("Source database not found at: {$dbPath}");
            return 1;
        }

        $this->info("Connecting to source database: {$dbPath}...");
        $sourceDb = new PDO("sqlite:{$dbPath}");
        $sourceDb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $countQuery = $sourceDb->query("SELECT count(*) FROM properties");
        $totalSource = $countQuery->fetchColumn();
        $this->info("Total properties in source database: " . number_format($totalSource));

        $sql = "SELECT id, source, source_id, title, property_type, listing_type, price_usd, area_sqm, 
                       price_per_sqm, province, district, commune, address, bedrooms, bathrooms, 
                       url, image_url, urgency_tag, latitude, longitude, scraped_at 
                FROM properties";

        if ($limit > 0) {
            $sql .= " LIMIT {$limit}";
        }

        $stmt = $sourceDb->query($sql);
        $imported = 0;
        $batch = [];
        $batchSize = 250;

        $bar = $this->output->createProgressBar($limit > 0 ? min($limit, $totalSource) : $totalSource);
        $bar->start();

        // Source name mapping for display
        $sourceMap = [
            'arc' => 'ARC Cambodia (arc.com.kh)',
            'cambodia_re' => 'Century 21 (cambodia-real-estate.com)',
            'harbor' => 'Harbor Property (harbor-property.com)',
            'khmer24' => 'Khmer24 Property (khmer24.com)',
            'realestate' => 'Realestate.com.kh',
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $title = trim($row['title'] ?? 'Untitled Listing');
            $price = $row['price_usd'] ? (float) $row['price_usd'] : null;
            $area = $row['area_sqm'] ? (float) $row['area_sqm'] : null;
            $sqmPrice = $row['price_per_sqm'] ? (float) $row['price_per_sqm'] : null;

            if (!$sqmPrice && $price && $area && $area > 0) {
                $sqmPrice = round($price / $area, 2);
            }

            $batch[] = [
                'source' => $row['source'] ?? 'manual',
                'source_name' => $sourceMap[$row['source']] ?? ucfirst($row['source']),
                'source_id' => $row['source_id'],
                'title' => $title,
                'property_type' => $row['property_type'] ?: 'Other',
                'listing_type' => $row['listing_type'] ?: 'Sale',
                'price_usd' => $price,
                'price' => $price,
                'currency' => 'USD',
                'area_sqm' => $area,
                'price_per_sqm' => $sqmPrice,
                'province' => $row['province'],
                'district' => $row['district'],
                'commune' => $row['commune'],
                'address' => $row['address'],
                'location' => $row['district'] ?: $row['province'] ?: 'Phnom Penh',
                'city' => $row['province'] ?: 'Phnom Penh',
                'bedrooms' => $row['bedrooms'],
                'bathrooms' => $row['bathrooms'],
                'url' => $row['url'],
                'source_url' => $row['url'],
                'image_url' => $row['image_url'],
                'urgency_tag' => $row['urgency_tag'] ?: null,
                'status' => 'available',
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude'],
                'created_at' => $row['scraped_at'] ?? now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                DB::table('properties')->insert($batch);
                $imported += count($batch);
                $bar->advance(count($batch));
                $batch = [];
            }
        }

        if (count($batch) > 0) {
            DB::table('properties')->insert($batch);
            $imported += count($batch);
            $bar->advance(count($batch));
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully imported {$imported} real properties into Laravel!");

        // Sync Scraper Tasks statistics in portal
        $this->syncScraperTasks();

        return 0;
    }

    /**
     * Synchronize portal scraper tasks with real data counts.
     */
    protected function syncScraperTasks()
    {
        $portals = [
            'cambodia_re' => [
                'name' => 'Century 21 Cambodia (Live API)',
                'source_name' => 'Century 21 (cambodia-real-estate.com)',
                'target_url' => 'https://cambodia-real-estate.com/properties/',
                'category' => 'All Categories',
                'frequency' => 'Every 3 Hours',
            ],
            'arc' => [
                'name' => 'Asia Real Estate Cambodia (Live API)',
                'source_name' => 'ARC Cambodia (arc.com.kh)',
                'target_url' => 'https://arc.com.kh/#/service/buy-sell-rent',
                'category' => 'Land & Villas',
                'frequency' => 'Every 6 Hours',
            ],
            'realestate' => [
                'name' => 'Realestate.com.kh Portal Crawler',
                'source_name' => 'Realestate.com.kh',
                'target_url' => 'https://www.realestate.com.kh/buy/',
                'category' => 'Condo & Land',
                'frequency' => 'Every 6 Hours',
            ],
            'khmer24' => [
                'name' => 'Khmer24 Property Marketplace',
                'source_name' => 'Khmer24 Property (khmer24.com)',
                'target_url' => 'https://www.khmer24.com/en/property.html',
                'category' => 'Residential & Commercial',
                'frequency' => 'Every 4 Hours',
            ],
            'harbor' => [
                'name' => 'Harbor Property API Spider',
                'source_name' => 'Harbor Property (harbor-property.com)',
                'target_url' => 'https://www.harbor-property.com/en/',
                'category' => 'Borey & High-Rises',
                'frequency' => 'Daily',
            ],
        ];

        foreach ($portals as $key => $meta) {
            $count = DB::table('properties')->where('source', $key)->count();
            ScraperTask::updateOrCreate(
                ['source_name' => $meta['source_name']],
                [
                    'name' => $meta['name'],
                    'target_url' => $meta['target_url'],
                    'category' => $meta['category'],
                    'frequency' => $meta['frequency'],
                    'status' => 'completed',
                    'items_scraped' => $count,
                    'last_run_at' => now(),
                    'last_log' => "Active sync pipeline. {$count} listings indexed from {$meta['source_name']}.",
                ]
            );
        }

        $this->info("Updated Scraper Tasks in portal database.");
    }
}
