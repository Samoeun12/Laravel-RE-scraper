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
        // 1. Users
        $admin = User::create([
            'name' => 'Alexander Vance',
            'email' => 'admin@portal.test',
            'password' => Hash::make('password123'),
            'role' => 'Administrator',
            'title' => 'Chief Real Estate Analyst',
            'theme_preference' => 'dark',
            'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=256&q=80',
        ]);

        $user = User::create([
            'name' => 'Sarah Connor',
            'email' => 'sarah@portal.test',
            'password' => Hash::make('password123'),
            'role' => 'Scraper Operator',
            'title' => 'Data Ingestion Specialist',
            'theme_preference' => 'system',
            'avatar' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=256&q=80',
        ]);

        // 2. Scraper Tasks
        $task1 = ScraperTask::create([
            'name' => 'Phnom Penh Luxury High-Rises',
            'source_name' => 'Realestate.com.kh',
            'target_url' => 'https://www.realestate.com.kh/buy/phnom-penh/condos/',
            'category' => 'Condo',
            'status' => 'completed',
            'frequency' => 'Every 6 Hours',
            'items_scraped' => 428,
            'last_run_at' => now()->subMinutes(35),
            'last_log' => 'Successfully parsed 428 units. 12 updated prices, 4 new listings indexed.',
            'user_id' => $admin->id,
        ]);

        $task2 = ScraperTask::create([
            'name' => 'BKK1 & Tonle Bassac Rental Suites',
            'source_name' => 'Khmer24 Property',
            'target_url' => 'https://www.khmer24.com/en/property/apartments-for-rent.html',
            'category' => 'Apartment',
            'status' => 'running',
            'frequency' => 'Every 3 Hours',
            'items_scraped' => 192,
            'last_run_at' => now()->subMinutes(12),
            'last_log' => 'Crawl in progress: page 14 of 25. Response latency: 240ms.',
            'user_id' => $admin->id,
        ]);

        $task3 = ScraperTask::create([
            'name' => 'Siem Reap Commercial Land Plots',
            'source_name' => 'Angkor Real Estate',
            'target_url' => 'https://angkorrealestate.com/category/land-for-sale/',
            'category' => 'Land',
            'status' => 'idle',
            'frequency' => 'Daily at Midnight',
            'items_scraped' => 85,
            'last_run_at' => now()->subHours(14),
            'last_log' => 'Routine scan finished with 0 warnings.',
            'user_id' => $user->id,
        ]);

        $task4 = ScraperTask::create([
            'name' => 'Toul Kork Modern Villas & Townhouses',
            'source_name' => 'Compass Cambodia',
            'target_url' => 'https://compass.com.kh/property/villas',
            'category' => 'Villa',
            'status' => 'completed',
            'frequency' => 'Every 12 Hours',
            'items_scraped' => 310,
            'last_run_at' => now()->subHours(2),
            'last_log' => 'Extracted metadata and 3D virtual tour URLs for 310 villa assets.',
            'user_id' => $admin->id,
        ]);

        $task5 = ScraperTask::create([
            'name' => 'Koh Pich Prime Grade-A Offices',
            'source_name' => 'CBRE Cambodia',
            'target_url' => 'https://cbre.com.kh/commercial-offices',
            'category' => 'Commercial',
            'status' => 'idle',
            'frequency' => 'Weekly on Mondays',
            'items_scraped' => 46,
            'last_run_at' => now()->subDays(1),
            'last_log' => 'Commercial yields and floor area rates calculated.',
            'user_id' => $admin->id,
        ]);

        // 3. Properties
        $properties = [
            [
                'title' => 'Sky Villa Penthouse with Panoramic River View',
                'property_type' => 'Condo',
                'listing_type' => 'Sale',
                'price' => 745000,
                'currency' => 'USD',
                'location' => 'Veal Vong, 7 Makara',
                'city' => 'Phnom Penh',
                'bedrooms' => 4,
                'bathrooms' => 5,
                'area_sqm' => 420.5,
                'source_name' => 'Realestate.com.kh',
                'source_url' => 'https://realestate.com.kh/property/sky-villa-492',
                'image_url' => 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?auto=format&fit=crop&w=800&q=80',
                'status' => 'available',
                'is_featured' => true,
                'scraper_task_id' => $task1->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'BKK1 Executive Duplex Loft & Sky Pool Access',
                'property_type' => 'Apartment',
                'listing_type' => 'Rent',
                'price' => 2800,
                'currency' => 'USD',
                'location' => 'Boeung Keng Kang 1',
                'city' => 'Phnom Penh',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area_sqm' => 145.0,
                'source_name' => 'Khmer24 Property',
                'source_url' => 'https://khmer24.com/bkk1-loft-2800',
                'image_url' => 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=800&q=80',
                'status' => 'available',
                'is_featured' => true,
                'scraper_task_id' => $task2->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Ultra-Contemporary Villa with Private Lap Pool',
                'property_type' => 'Villa',
                'listing_type' => 'Sale',
                'price' => 1250000,
                'currency' => 'USD',
                'location' => 'Toul Kork, Boeung Kak 2',
                'city' => 'Phnom Penh',
                'bedrooms' => 5,
                'bathrooms' => 6,
                'area_sqm' => 580.0,
                'source_name' => 'Compass Cambodia',
                'source_url' => 'https://compass.com.kh/villa-tk-88',
                'image_url' => 'https://images.unsplash.com/photo-1613490493576-7fde63acd811?auto=format&fit=crop&w=800&q=80',
                'status' => 'available',
                'is_featured' => true,
                'scraper_task_id' => $task4->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Heritage Riverfront Land Plot for Hospitality Resort',
                'property_type' => 'Land',
                'listing_type' => 'Sale',
                'price' => 890000,
                'currency' => 'USD',
                'location' => 'Wat Bo Village, Sala Kamreuk',
                'city' => 'Siem Reap',
                'bedrooms' => 0,
                'bathrooms' => 0,
                'area_sqm' => 2400.0,
                'source_name' => 'Angkor Real Estate',
                'source_url' => 'https://angkorrealestate.com/siem-reap-land-2400',
                'image_url' => 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',
                'status' => 'pending',
                'is_featured' => false,
                'scraper_task_id' => $task3->id,
                'created_by' => $user->id,
            ],
            [
                'title' => 'The Bridge Grade-A Fitted Corporate Office Suite',
                'property_type' => 'Commercial',
                'listing_type' => 'Rent',
                'price' => 3500,
                'currency' => 'USD',
                'location' => 'Tonle Bassac, Chamkarmon',
                'city' => 'Phnom Penh',
                'bedrooms' => 0,
                'bathrooms' => 2,
                'area_sqm' => 195.0,
                'source_name' => 'CBRE Cambodia',
                'source_url' => 'https://cbre.com.kh/the-bridge-commercial',
                'image_url' => 'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',
                'status' => 'available',
                'is_featured' => false,
                'scraper_task_id' => $task5->id,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Modern Minimalist Studio in Diamond Island',
                'property_type' => 'Condo',
                'listing_type' => 'Rent',
                'price' => 650,
                'currency' => 'USD',
                'location' => 'Koh Pich, Chamkarmon',
                'city' => 'Phnom Penh',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'area_sqm' => 52.0,
                'source_name' => 'Realestate.com.kh',
                'source_url' => 'https://realestate.com.kh/diamond-island-studio',
                'image_url' => 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80',
                'status' => 'rented',
                'is_featured' => false,
                'scraper_task_id' => $task1->id,
                'created_by' => $admin->id,
            ],
        ];

        foreach ($properties as $prop) {
            Property::create($prop);
        }

        // 4. Activity Logs
        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Scraper Execution',
            'description' => 'Triggered batch scraper task: "Phnom Penh Luxury High-Rises"',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subMinutes(35),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'Theme Changed',
            'description' => 'Switched interface display mode to Dark Theme',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subHours(1),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Export Generated',
            'description' => 'Exported 85 Siem Reap Land listings as CSV dataset',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subHours(14),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action' => 'System Login',
            'description' => 'Authenticated via session token from Chrome 126 (Windows)',
            'ip_address' => '127.0.0.1',
            'created_at' => now()->subHours(18),
        ]);
    }
}
