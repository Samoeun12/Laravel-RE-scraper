<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\ScraperTask;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(RolePermissionSeeder::class);

        // 1. Users
        $admin = User::firstOrCreate(
            ['email' => 'admin@portal.test'],
            [
                'name' => 'Alexander Vance',
                'password' => Hash::make('password123'),
                'role' => 'Administrator',
                'title' => 'Chief Real Estate Analyst',
                'theme_preference' => 'dark',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&q=80',
            ]
        );

        $user = User::firstOrCreate(
            ['email' => 'sarah@portal.test'],
            [
                'name' => 'Sarah Connor',
                'password' => Hash::make('password123'),
                'role' => 'Scraper Operator',
                'title' => 'Data Ingestion Specialist',
                'theme_preference' => 'system',
                'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=256&q=80',
            ]
        );

        // 2. Real Scraper Pipelines (matching the 5 Cambodia portals)
        $scrapers = [
            [
                'name' => 'Century 21 Cambodia (Live API)',
                'source_name' => 'Century 21 (cambodia-real-estate.com)',
                'target_url' => 'https://cambodia-real-estate.com/properties/',
                'category' => 'All Categories',
                'status' => 'completed',
                'frequency' => 'Every 3 Hours',
                'items_scraped' => Property::where('source', 'cambodia_re')->count() ?: 8759,
                'last_log' => 'Active Houzez REST API integration ready for real-time querying.',
            ],
            [
                'name' => 'Asia Real Estate Cambodia (Live API)',
                'source_name' => 'ARC Cambodia (arc.com.kh)',
                'target_url' => 'https://arc.com.kh/#/service/buy-sell-rent',
                'category' => 'Land & Villas',
                'status' => 'completed',
                'frequency' => 'Every 6 Hours',
                'items_scraped' => Property::where('source', 'arc')->count() ?: 2954,
                'last_log' => 'Direct PMS API endpoint with map coordinates connected.',
            ],
            [
                'name' => 'Khmer24 Property Marketplace',
                'source_name' => 'Khmer24 Property (khmer24.com)',
                'target_url' => 'https://www.khmer24.com/en/property.html',
                'category' => 'Residential & Commercial',
                'status' => 'completed',
                'frequency' => 'Every 4 Hours',
                'items_scraped' => Property::where('source', 'khmer24')->count() ?: 5368,
                'last_log' => 'Marketplace listings and seller contacts parsed.',
            ],
            [
                'name' => 'Realestate.com.kh Portal Crawler',
                'source_name' => 'Realestate.com.kh',
                'target_url' => 'https://www.realestate.com.kh/buy/',
                'category' => 'Condo & Land',
                'status' => 'completed',
                'frequency' => 'Every 6 Hours',
                'items_scraped' => Property::where('source', 'realestate')->count() ?: 2561,
                'last_log' => 'Verified price trends and schema records indexed.',
            ],
            [
                'name' => 'Harbor Property API Spider',
                'source_name' => 'Harbor Property (harbor-property.com)',
                'target_url' => 'https://www.harbor-property.com/en/',
                'category' => 'Borey & High-Rises',
                'status' => 'completed',
                'frequency' => 'Daily',
                'items_scraped' => Property::where('source', 'harbor')->count() ?: 85,
                'last_log' => 'Harbor API verified.',
            ],
            [
                'name' => 'PropNex Cambodia (Direct OpenAPI)',
                'source_name' => 'PropNex Cambodia',
                'target_url' => 'https://www.propnexkh.com/api/v1/properties',
                'category' => 'Borey & Luxury Residential',
                'status' => 'completed',
                'frequency' => 'Every 4 Hours',
                'items_scraped' => Property::where('source', 'propnex')->count() ?: 50,
                'last_log' => 'OpenAPI v1 endpoint connected. Borey and villa inventory parsed.',
            ],
            [
                'name' => 'Bayon App Real Estate (Live API)',
                'source_name' => 'Bayon App Real Estate (bayonapp.com)',
                'target_url' => 'https://bayonapp.com/',
                'category' => 'Nationwide & Land Portfolios',
                'status' => 'completed',
                'frequency' => 'Every 3 Hours',
                'items_scraped' => Property::where('source', 'bayon')->count() ?: 50,
                'last_log' => 'Direct REST API integration active with high-res photos & GPS coordinates.',
            ],
        ];

        foreach ($scrapers as $s) {
            ScraperTask::firstOrCreate(
                ['name' => $s['name']],
                array_merge($s, [
                    'user_id' => $admin->id,
                    'last_run_at' => now()->subHours(rand(1, 12)),
                ])
            );
        }

        // 3. Load Real Curated Cambodian Properties Seed (only if database is empty)
        $seedPath = __DIR__ . '/cambodia_properties_seed.json';
        if (Property::count() === 0 && file_exists($seedPath)) {
            $json = file_get_contents($seedPath);
            $items = json_decode($json, true) ?: [];

            $sourceMap = [
                'arc' => 'ARC Cambodia (arc.com.kh)',
                'cambodia_re' => 'Century 21 (cambodia-real-estate.com)',
                'harbor' => 'Harbor Property (harbor-property.com)',
                'khmer24' => 'Khmer24 Property (khmer24.com)',
                'realestate' => 'Realestate.com.kh',
            ];

            foreach ($items as $row) {
                $price = $row['price_usd'] ? (float) $row['price_usd'] : null;
                $area = $row['area_sqm'] ? (float) $row['area_sqm'] : null;
                $sqmPrice = $row['price_per_sqm'] ? (float) $row['price_per_sqm'] : ($price && $area && $area > 0 ? round($price / $area, 2) : null);

                Property::firstOrCreate(
                    ['url' => $row['url']],
                    [
                        'source' => $row['source'],
                        'source_name' => $sourceMap[$row['source']] ?? ucfirst($row['source']),
                        'source_id' => $row['source_id'],
                        'title' => $row['title'],
                        'property_type' => $row['property_type'] ?: 'Other',
                        'listing_type' => $row['listing_type'] ?: 'Sale',
                        'price_usd' => $price,
                        'price' => $price,
                        'currency' => 'USD',
                        'area_sqm' => $area,
                        'price_per_sqm' => $sqmPrice,
                        'province' => $row['province'] ?: 'Phnom Penh',
                        'district' => $row['district'] ?: 'Chamkarmon',
                        'commune' => $row['commune'],
                        'address' => $row['address'],
                        'location' => $row['district'] ?: $row['province'] ?: 'Phnom Penh',
                        'city' => $row['province'] ?: 'Phnom Penh',
                        'bedrooms' => $row['bedrooms'],
                        'bathrooms' => $row['bathrooms'],
                        'image_url' => $row['image_url'],
                        'urgency_tag' => $row['urgency_tag'],
                        'status' => 'available',
                        'latitude' => $row['latitude'],
                        'longitude' => $row['longitude'],
                        'created_by' => $admin->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        // 4. Activity Logs
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Pipeline Integration',
            'description' => 'Ingested 5 Cambodian portal crawlers and valuation models from Python scraping tool.',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
