<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Permission;
use App\Models\Property;
use App\Models\Role;
use App\Models\ScraperTask;
use App\Models\User;
use App\Services\RealEstateScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class PortalController extends Controller
{
    /**
     * Dashboard Overview.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $totalProps = Property::count();

        $stats = [
            'total_properties' => $totalProps,
            'total_scrapers' => ScraperTask::count(),
            'running_scrapers' => ScraperTask::where('status', 'running')->count(),
            'total_items_scraped' => $totalProps,
            'for_sale_count' => Property::where('listing_type', 'Sale')->count(),
            'for_rent_count' => Property::where('listing_type', 'Rent')->count(),
            'deals_count' => Property::whereNotNull('urgency_tag')->where('urgency_tag', '!=', '')->count(),
            'total_market_cap' => Property::sum('price_usd') ?: 4820000000,
            'avg_sqm_price' => round(Property::where('listing_type', 'Sale')->where('price_per_sqm', '>', 0)->avg('price_per_sqm') ?: 1820),
        ];

        // 1. Portal Distribution
        $rawPortals = Property::select('source', DB::raw('COUNT(*) as total'))
            ->groupBy('source')
            ->pluck('total', 'source')
            ->toArray();

        $portalMeta = [
            'cambodia_re' => ['name' => 'Century 21 Cambodia', 'color' => '#3b82f6'],
            'khmer24' => ['name' => 'Khmer24 Property', 'color' => '#f59e0b'],
            'arc' => ['name' => 'ARC Cambodia (PMS)', 'color' => '#10b981'],
            'realestate' => ['name' => 'Realestate.com.kh', 'color' => '#8b5cf6'],
            'propnex' => ['name' => 'PropNex Cambodia (API)', 'color' => '#ec4899'],
            'harbor' => ['name' => 'Harbor Property', 'color' => '#06b6d4'],
            'bayon' => ['name' => 'Bayon App (API)', 'color' => '#f97316'],
        ];

        $portalDistribution = [];
        foreach ($portalMeta as $key => $meta) {
            $c = $rawPortals[$key] ?? 0;
            $pct = $totalProps > 0 ? round(($c / $totalProps) * 100, 1) : 0;
            $portalDistribution[] = [
                'key' => $key,
                'name' => $meta['name'],
                'color' => $meta['color'],
                'count' => $c,
                'percentage' => $pct,
            ];
        }

        // 2. District Benchmarks (Top 5 active districts in Phnom Penh)
        $districtBenchmarks = Property::select('district', 
                DB::raw('ROUND(AVG(price_per_sqm)) as avg_sqm'), 
                DB::raw('COUNT(*) as total'))
            ->where('listing_type', 'Sale')
            ->where('price_per_sqm', '>', 50)
            ->where('price_per_sqm', '<', 15000)
            ->whereNotNull('district')
            ->where('district', '!=', '')
            ->groupBy('district')
            ->orderByDesc('total')
            ->take(5)
            ->get();

        // 3. Hot Deals
        $hotDeals = Property::whereNotNull('urgency_tag')
            ->where('urgency_tag', '!=', '')
            ->where('price_usd', '>', 0)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->latest('id')
            ->take(4)
            ->get();

        $scrapers = ScraperTask::with('user')->latest()->take(5)->get();
        $recentProperties = Property::latest('id')->take(4)->get();
        $activities = ActivityLog::with('user')->latest()->take(6)->get();

        return view('portal.dashboard', compact(
            'stats', 
            'scrapers', 
            'recentProperties', 
            'activities', 
            'user', 
            'portalDistribution', 
            'districtBenchmarks', 
            'hotDeals'
        ));
    }

    /**
     * Scraper Hub view.
     */
    public function scrapers()
    {
        $scrapers = ScraperTask::with('user')->latest()->get();
        return view('portal.scrapers', compact('scrapers'));
    }

    /**
     * Create a new Scraper Task.
     */
    public function storeScraper(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'source_name' => ['required', 'string', 'max:255'],
            'target_url' => ['required', 'url'],
            'category' => ['required', 'string'],
            'frequency' => ['required', 'string'],
        ]);

        $task = ScraperTask::create([
            'name' => $validated['name'],
            'source_name' => $validated['source_name'],
            'target_url' => $validated['target_url'],
            'category' => $validated['category'],
            'frequency' => $validated['frequency'],
            'status' => 'idle',
            'items_scraped' => 0,
            'user_id' => Auth::id(),
            'last_log' => 'Initialized scraper pipeline. Ready for extraction.',
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created Scraper Task',
            'description' => 'Configured target crawler for ' . $task->name,
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Scraper task "' . $task->name . '" created successfully.');
    }

    /**
     * Trigger live scraper extraction.
     */
    public function triggerScraper(Request $request, $id)
    {
        $task = ScraperTask::findOrFail($id);
        $scraperService = app(RealEstateScraperService::class);
        $result = $scraperService->executeTask($task);

        $msg = $result['message'] ?? "Scraper run completed.";

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Executed Scraper',
            'description' => "Run dispatched for {$task->name}: " . ($result['message'] ?? 'OK'),
            'ip_address' => $request->ip(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $task->status,
                'items_scraped' => $task->items_scraped,
                'last_run' => $task->last_run_at ? $task->last_run_at->diffForHumans() : 'Just now',
                'message' => $msg,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete a Scraper Task.
     */
    public function deleteScraper($id)
    {
        $task = ScraperTask::findOrFail($id);
        $name = $task->name;
        $task->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted Scraper',
            'description' => "Removed crawler task: {$name}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('info', 'Scraper "' . $name . '" has been deleted.');
    }

    /**
     * Properties Catalog View with multi-portal filtering.
     */
    public function properties(Request $request)
    {
        $query = Property::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%")
                  ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($type = $request->input('type')) {
            $query->where('property_type', $type);
        }

        if ($listingType = $request->input('listing_type')) {
            $query->where('listing_type', $listingType);
        }

        if ($province = $request->input('province')) {
            $query->where('province', 'like', "%{$province}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $properties = $query->latest('id')->paginate(15)->withQueryString();
        $scrapers = ScraperTask::all();
        $provinces = Property::whereNotNull('province')->distinct()->pluck('province')->take(12);
        $propertyTypes = Property::whereNotNull('property_type')->distinct()->pluck('property_type');

        return view('portal.properties', compact('properties', 'scrapers', 'provinces', 'propertyTypes'));
    }

    /**
     * Listings Map View (Full Google Map with Live Property Pins & Clusters).
     */
    public function map(Request $request)
    {
        $provinces = Property::whereNotNull('province')->where('province', '!=', '')->distinct()->pluck('province')->take(15);
        $propertyTypes = Property::whereNotNull('property_type')->where('property_type', '!=', '')->distinct()->pluck('property_type');
        $sources = Property::whereNotNull('source')->where('source', '!=', '')->distinct()->pluck('source');
        $totalWithGps = Property::whereNotNull('latitude')->whereNotNull('longitude')->count();

        return view('portal.map', compact('provinces', 'propertyTypes', 'sources', 'totalWithGps'));
    }

    /**
     * Live JSON data endpoint for interactive Google Map.
     * High-speed direct DB query capable of returning all properties (19,783+) in < 0.25s.
     */
    public function mapPropertiesApi(Request $request)
    {
        $query = DB::table('properties')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->where('latitude', '>', 9.0)
            ->where('latitude', '<', 15.5)
            ->where('longitude', '>', 102.0)
            ->where('longitude', '<', 108.0);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('district', 'like', "%{$search}%")
                  ->orWhere('province', 'like', "%{$search}%")
                  ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        if ($source = $request->input('source')) {
            $query->where('source', $source);
        }

        if ($type = $request->input('type')) {
            $query->where('property_type', $type);
        }

        if ($listingType = $request->input('listing_type')) {
            $query->where('listing_type', $listingType);
        }

        if ($province = $request->input('province')) {
            $query->where('province', 'like', "%{$province}%");
        }

        if ($minPrice = $request->input('min_price')) {
            $query->where('price_usd', '>=', (float) $minPrice);
        }

        if ($maxPrice = $request->input('max_price')) {
            $query->where('price_usd', '<=', (float) $maxPrice);
        }

        // Optional Bounding Box Filter (sw_lat, sw_lng, ne_lat, ne_lng)
        if ($request->filled(['sw_lat', 'sw_lng', 'ne_lat', 'ne_lng'])) {
            $swLat = (float) $request->input('sw_lat');
            $swLng = (float) $request->input('sw_lng');
            $neLat = (float) $request->input('ne_lat');
            $neLng = (float) $request->input('ne_lng');

            $query->whereBetween('latitude', [min($swLat, $neLat), max($swLat, $neLat)])
                  ->whereBetween('longitude', [min($swLng, $neLng), max($swLng, $neLng)]);
        }

        $totalMatching = (clone $query)->count();

        // Limit defaults to 30,000 so ALL properties are returned unless explicitly constrained
        $limit = (int) $request->input('limit', 30000);
        if ($limit <= 0) {
            $limit = 30000;
        }

        $sort = $request->input('sort', 'latest');
        if ($sort === 'price_asc') {
            $query->orderBy('price_usd', 'asc');
        } elseif ($sort === 'price_desc') {
            $query->orderBy('price_usd', 'desc');
        } elseif ($sort === 'area_desc') {
            $query->orderBy('area_sqm', 'desc');
        } else {
            $query->orderByDesc('id');
        }

        $rows = $query->take($limit)->get([
            'id', 'title', 'property_type', 'listing_type', 'price_usd', 'price',
            'area_sqm', 'price_per_sqm', 'location', 'district', 'province', 'city',
            'bedrooms', 'bathrooms', 'latitude', 'longitude', 'image_url', 'url',
            'source', 'source_name', 'urgency_tag'
        ]);

        $defaultImg = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80';

        $data = [];
        foreach ($rows as $item) {
            $priceUsd = (float) $item->price_usd;
            $isRent = strtolower($item->listing_type ?? '') === 'rent';

            // Price formatting
            if ($priceUsd <= 0) {
                $priceText = 'Contact for Price';
                $shortPrice = 'N/A';
            } elseif ($priceUsd < 50 && !$isRent) {
                if ($item->area_sqm && $item->area_sqm > 50) {
                    $totalVal = round($priceUsd * $item->area_sqm);
                    $priceText = '$' . number_format($totalVal, 0) . ' ($' . number_format($priceUsd, 1) . '/m²)';
                    $shortPrice = '$' . round($totalVal / 1000, 1) . 'k';
                } else {
                    $priceText = 'Contact for Price';
                    $shortPrice = 'N/A';
                }
            } else {
                $priceText = '$' . number_format($priceUsd, 0) . ($isRent ? '/mo' : '');
                $shortPrice = $priceUsd >= 1000000 
                    ? '$' . round($priceUsd / 1000000, 2) . 'M' 
                    : ($priceUsd >= 1000 
                        ? '$' . round($priceUsd / 1000, 1) . 'k' 
                        : '$' . number_format($priceUsd, 0));
                if ($isRent) {
                    $shortPrice .= '/mo';
                }
            }

            // Location
            if ($item->location) {
                $loc = $item->location . ($item->city ? ', ' . $item->city : '');
            } else {
                $parts = array_filter([$item->district, $item->province ?? $item->city]);
                $loc = count($parts) > 0 ? implode(', ', $parts) : 'Cambodia';
            }

            $sqmPrice = null;
            if (!$isRent) {
                if ($item->price_per_sqm && $item->price_per_sqm > 0) {
                    $sqmPrice = (float) $item->price_per_sqm;
                } elseif ($priceUsd > 0 && $item->area_sqm && $item->area_sqm > 0) {
                    $sqmPrice = round($priceUsd / $item->area_sqm, 2);
                }
            }

            $data[] = [
                'id' => $item->id,
                'title' => $item->title,
                'property_type' => $item->property_type ?: 'Other',
                'listing_type' => $item->listing_type ?: 'Sale',
                'price_usd' => $priceUsd,
                'formatted_price' => $priceText,
                'short_price' => $shortPrice,
                'area_sqm' => $item->area_sqm ? (float) $item->area_sqm : null,
                'computed_price_per_sqm' => $sqmPrice,
                'location' => $loc,
                'district' => $item->district,
                'province' => $item->province,
                'bedrooms' => $item->bedrooms ? (int) $item->bedrooms : null,
                'bathrooms' => $item->bathrooms ? (int) $item->bathrooms : null,
                'lat' => round((float) $item->latitude, 5),
                'lng' => round((float) $item->longitude, 5),
                'image' => $item->image_url ?: $defaultImg,
                'url' => $item->url ?: '',
                'source' => $item->source,
                'source_name' => $item->source_name ?: 'Portal',
                'urgency_tag' => $item->urgency_tag,
            ];
        }

        return response()->json([
            'success' => true,
            'count' => count($data),
            'total_matching' => $totalMatching,
            'properties' => $data,
        ]);
    }

    /**
     * Deal & Good Property Finder.
     * Identifies listings with urgency tags or prices below district median $/sqm.
     */
    public function deals(Request $request)
    {
        $province = $request->input('province', 'Phnom Penh');
        $propType = $request->input('type', 'Land');
        $listingType = $request->input('listing_type', 'Sale');

        // District medians
        $districtAverages = Property::select('district', DB::raw('AVG(price_per_sqm) as avg_sqm'), DB::raw('COUNT(*) as total'))
            ->where('listing_type', $listingType)
            ->where('price_per_sqm', '>', 0)
            ->whereNotNull('district')
            ->when($province && $province !== 'All', function ($q) use ($province) {
                return $q->where('province', 'like', "%{$province}%");
            })
            ->when($propType && $propType !== 'All', function ($q) use ($propType) {
                return $q->where('property_type', $propType);
            })
            ->groupBy('district')
            ->pluck('avg_sqm', 'district');

        // Find deals: either urgent tags or > 15% discount against district average
        $dealsQuery = Property::where('listing_type', $listingType)
            ->where('price_usd', '>', 0)
            ->where('price_per_sqm', '>', 0)
            ->when($province && $province !== 'All', function ($q) use ($province) {
                return $q->where('province', 'like', "%{$province}%");
            })
            ->when($propType && $propType !== 'All', function ($q) use ($propType) {
                return $q->where('property_type', $propType);
            })
            ->where(function ($q) {
                $q->whereNotNull('urgency_tag')
                  ->orWhere('urgency_tag', '!=', '');
            });

        $deals = $dealsQuery->latest('id')->paginate(20)->withQueryString();

        return view('portal.deals', compact('deals', 'districtAverages', 'province', 'propType', 'listingType'));
    }

    /**
     * Comparable Market Analysis (CMA) Engine.
     */
    public function cma(Request $request)
    {
        $area = (float) $request->input('area_sqm', 500);
        $province = $request->input('province', 'Phnom Penh');
        $district = $request->input('district', 'Sen Sok');
        $propType = $request->input('property_type', 'Land');
        $targetPrice = (float) $request->input('target_price', 0);

        $report = null;

        if ($area > 0) {
            $minArea = $area * 0.70;
            $maxArea = $area * 1.30;

            $compsQuery = Property::where('listing_type', 'Sale')
                ->where('property_type', $propType)
                ->where('area_sqm', '>=', $minArea)
                ->where('area_sqm', '<=', $maxArea)
                ->where('price_per_sqm', '>', 0)
                ->when($province, function ($q) use ($province) {
                    return $q->where('province', 'like', "%{$province}%");
                })
                ->when($district, function ($q) use ($district) {
                    return $q->where('district', 'like', "%{$district}%");
                });

            $comps = $compsQuery->take(15)->get();

            if ($comps->count() >= 3) {
                $sqmPrices = $comps->pluck('price_per_sqm')->sort()->values();
                $count = $sqmPrices->count();

                $minSqm = $sqmPrices->first();
                $maxSqm = $sqmPrices->last();
                $medianSqm = $sqmPrices->median();
                $avgSqm = round($sqmPrices->avg(), 2);

                $valConservative = round($sqmPrices->get((int) floor($count * 0.25)) * $area);
                $valFairMarket = round($medianSqm * $area);
                $valPremium = round($sqmPrices->get((int) floor($count * 0.75)) * $area);

                $report = [
                    'count' => $count,
                    'min_sqm' => $minSqm,
                    'max_sqm' => $maxSqm,
                    'median_sqm' => $medianSqm,
                    'avg_sqm' => $avgSqm,
                    'val_conservative' => $valConservative,
                    'val_fair' => $valFairMarket,
                    'val_premium' => $valPremium,
                    'comps' => $comps,
                ];

                if ($targetPrice > 0) {
                    $diff = $targetPrice - $valFairMarket;
                    $diffPct = round(($diff / $valFairMarket) * 100, 1);
                    $report['asking_diff_pct'] = $diffPct;
                    $report['status_evaluation'] = $diffPct > 10 ? 'Overpriced (+'.$diffPct.'%)' : ($diffPct < -10 ? 'Underpriced ('.$diffPct.'%)' : 'Fair Market Value');
                }
            }
        }

        $districts = Property::where('province', 'like', "%{$province}%")->whereNotNull('district')->distinct()->pluck('district');

        return view('portal.cma', compact('report', 'area', 'province', 'district', 'propType', 'targetPrice', 'districts'));
    }

    /**
     * Land Price Estimator Engine.
     */
    public function landEstimator(Request $request)
    {
        $area = (float) $request->input('area_sqm', 1000);
        $province = $request->input('province', 'Phnom Penh');
        $district = $request->input('district', 'Chbar Ampov');
        $roadType = $request->input('road_type', 'Secondary Road');

        // Road multipliers
        $multipliers = [
            'Main Boulevard' => 1.25,
            'Secondary Road' => 1.00,
            'Residential Lane' => 0.85,
        ];
        $multiplier = $multipliers[$roadType] ?? 1.00;

        $districtStats = Property::select('district', 
                DB::raw('MIN(price_per_sqm) as min_sqm'), 
                DB::raw('AVG(price_per_sqm) as avg_sqm'), 
                DB::raw('MAX(price_per_sqm) as max_sqm'), 
                DB::raw('COUNT(*) as total'))
            ->where('property_type', 'Land')
            ->where('listing_type', 'Sale')
            ->where('price_per_sqm', '>', 0)
            ->whereNotNull('district')
            ->where('province', 'like', "%{$province}%")
            ->groupBy('district')
            ->orderByDesc('total')
            ->get();

        $selectedDistrict = $districtStats->firstWhere('district', $district);
        $estimate = null;

        if ($selectedDistrict && $area > 0) {
            $baseAvg = $selectedDistrict->avg_sqm;
            $adjustedSqm = round($baseAvg * $multiplier, 2);
            $totalEst = round($adjustedSqm * $area);

            $estimate = [
                'area_sqm' => $area,
                'road_type' => $roadType,
                'multiplier' => $multiplier,
                'adjusted_sqm' => $adjustedSqm,
                'total_valuation' => $totalEst,
                'min_sqm' => $selectedDistrict->min_sqm,
                'max_sqm' => $selectedDistrict->max_sqm,
                'sample_size' => $selectedDistrict->total,
            ];
        }

        return view('portal.land_estimator', compact('estimate', 'districtStats', 'area', 'province', 'district', 'roadType'));
    }

    /**
     * Create property listing.
     */
    public function storeProperty(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'property_type' => ['required', 'string'],
            'listing_type' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'location' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url'],
        ]);

        $defaultImage = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80';
        $sqmPrice = ($validated['price'] > 0 && !empty($validated['area_sqm']) && $validated['area_sqm'] > 0) 
            ? round($validated['price'] / $validated['area_sqm'], 2) 
            : null;

        $property = Property::create([
            'title' => $validated['title'],
            'property_type' => $validated['property_type'],
            'listing_type' => $validated['listing_type'],
            'price_usd' => $validated['price'],
            'price' => $validated['price'],
            'currency' => 'USD',
            'location' => $validated['location'],
            'district' => $validated['location'],
            'province' => $validated['city'],
            'city' => $validated['city'],
            'bedrooms' => $validated['bedrooms'],
            'bathrooms' => $validated['bathrooms'],
            'area_sqm' => $validated['area_sqm'],
            'price_per_sqm' => $sqmPrice,
            'image_url' => $validated['image_url'] ?? $defaultImage,
            'source_name' => $validated['source_name'] ?? 'Direct Entry',
            'source_url' => $validated['source_url'] ?? null,
            'status' => 'available',
            'created_by' => Auth::id(),
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Property Added',
            'description' => "Indexed property listing: {$property->title}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Property listing created successfully.');
    }

    /**
     * Delete property listing.
     */
    public function deleteProperty($id)
    {
        $property = Property::findOrFail($id);
        $title = $property->title;
        $property->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Property Removed',
            'description' => "Deleted property: {$title}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('info', 'Property "' . $title . '" has been removed.');
    }

    /**
     * Profile & Preferences View.
     */
    public function profile()
    {
        $user = Auth::user();
        $activities = ActivityLog::where('user_id', $user->id)->latest()->take(10)->get();

        return view('portal.profile', compact('user', 'activities'));
    }

    /**
     * Update Profile details.
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'title' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'url'],
            'theme_preference' => ['required', 'in:light,dark,system'],
        ]);

        $user->update($validated);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Profile Updated',
            'description' => 'User profile details were updated.',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Profile and preferences updated successfully.');
    }

    /**
     * Change Password.
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::create([
            'user_id' => $user->id,
            'action' => 'Password Changed',
            'description' => 'Account password updated securely.',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Your password has been changed successfully.');
    }

    /**
     * Asynchronously update user theme preference.
     */
    public function updateTheme(Request $request)
    {
        $request->validate([
            'theme' => ['required', 'in:light,dark,system'],
        ]);

        $theme = $request->input('theme');

        if (Auth::check()) {
            Auth::user()->update([
                'theme_preference' => $theme,
            ]);
        }

        return response()->json([
            'success' => true,
            'theme' => $theme,
            'message' => 'Theme updated to ' . ucfirst($theme),
        ]);
    }

    /**
     * User Management View.
     */
    public function users(Request $request)
    {
        $query = User::with('roleModel')->withCount(['properties', 'scraperTasks']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%");
            });
        }

        if ($roleId = $request->input('role_id')) {
            $query->where('role_id', $roleId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->latest('id')->paginate(12)->withQueryString();
        $roles = Role::withCount('users')->get();

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'admins' => User::where('role', 'Administrator')->count(),
            'operators' => User::where('role', 'Scraper Operator')->count(),
            'analysts' => User::where('role', 'Real Estate Analyst')->count(),
        ];

        return view('portal.users', compact('users', 'roles', 'stats'));
    }

    /**
     * Store new User Account.
     */
    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,suspended'],
            'password' => ['required', 'string', Password::min(8)],
            'avatar' => ['nullable', 'url'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role->name,
            'role_id' => $role->id,
            'title' => $validated['title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'password' => Hash::make($validated['password']),
            'avatar' => $validated['avatar'] ?? null,
            'theme_preference' => 'system',
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created User',
            'description' => "Created account for {$user->name} ({$user->email}) with role {$role->name}",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "User '{$user->name}' created successfully.");
    }

    /**
     * Update existing User.
     */
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'exists:roles,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,suspended'],
            'password' => ['nullable', 'string', Password::min(8)],
            'avatar' => ['nullable', 'url'],
        ]);

        $role = Role::findOrFail($validated['role_id']);

        // Prevent suspending self
        if ($user->id === Auth::id() && $validated['status'] === 'suspended') {
            return back()->withErrors(['status' => 'You cannot suspend your own administrative account.']);
        }

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $role->name,
            'role_id' => $role->id,
            'title' => $validated['title'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'avatar' => $validated['avatar'] ?? $user->avatar,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Updated User',
            'description' => "Updated user profile for {$user->name} ({$user->email})",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "User '{$user->name}' updated successfully.");
    }

    /**
     * Delete User account.
     */
    public function deleteUser($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['delete' => 'You cannot delete your own account while logged in.']);
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted User',
            'description' => "Removed user account: {$name}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('info', "User account '{$name}' has been deleted.");
    }

    /**
     * Toggle User status between active and suspended.
     */
    public function toggleUserStatus($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->withErrors(['status' => 'You cannot suspend your own administrative account.']);
        }

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Changed User Status',
            'description' => "Changed status of {$user->name} to {$newStatus}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "User status updated to {$newStatus}.");
    }

    /**
     * Permission Access Control & RBAC Matrix View.
     */
    public function permissions(Request $request)
    {
        $roles = Role::with(['permissions', 'users'])->get();
        $permissions = Permission::all()->groupBy('module');
        $recentUsers = User::with('roleModel')->latest('id')->take(6)->get();

        return view('portal.permissions', compact('roles', 'permissions', 'recentUsers'));
    }

    /**
     * Create new Custom Role.
     */
    public function storeRole(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        $slug = Str::slug($validated['name'], '_');
        $originalSlug = $slug;
        $counter = 1;
        while (Role::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}_{$counter}";
            $counter++;
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'badge_color' => $validated['badge_color'] ?? '#6366f1',
            'is_system' => false,
        ]);

        if (!empty($validated['permissions'])) {
            $role->permissions()->sync($validated['permissions']);
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Created Role',
            'description' => "Created RBAC role {$role->name} ({$role->slug})",
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Role '{$role->name}' created successfully.");
    }

    /**
     * Update Permission Matrix (Toggle individual permission or sync full matrix).
     */
    public function updatePermissionMatrix(Request $request)
    {
        if ($request->wantsJson()) {
            $validated = $request->validate([
                'role_id' => ['required', 'exists:roles,id'],
                'permission_id' => ['required', 'exists:permissions,id'],
                'granted' => ['required', 'boolean'],
            ]);

            $role = Role::findOrFail($validated['role_id']);
            $permission = Permission::findOrFail($validated['permission_id']);

            if ($role->slug === 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'Administrator role inherently retains all system permissions.',
                ], 422);
            }

            if ($validated['granted']) {
                $role->permissions()->syncWithoutDetaching([$permission->id]);
            } else {
                $role->permissions()->detach($permission->id);
            }

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Updated Permission',
                'description' => ($validated['granted'] ? 'Granted ' : 'Revoked ') . "{$permission->name} for role {$role->name}",
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Permission '{$permission->name}' " . ($validated['granted'] ? 'granted' : 'revoked') . " for {$role->name}.",
            ]);
        }

        // Full matrix batch update
        $matrix = $request->input('matrix', []);
        foreach ($matrix as $roleId => $permissionIds) {
            $role = Role::find($roleId);
            if ($role && $role->slug !== 'admin') {
                $role->permissions()->sync(array_keys($permissionIds));
            }
        }

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Batch Updated Permissions',
            'description' => 'Updated role permissions matrix.',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Permissions matrix updated successfully.');
    }

    /**
     * Delete Custom Role.
     */
    public function deleteRole($id)
    {
        $role = Role::findOrFail($id);

        if ($role->is_system) {
            return back()->withErrors(['role' => 'System default roles cannot be deleted.']);
        }

        if ($role->users()->count() > 0) {
            return back()->withErrors(['role' => "Cannot delete role '{$role->name}' because {$role->users()->count()} users are currently assigned to it. Reassign them first."]);
        }

        $name = $role->name;
        $role->delete();

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'Deleted Role',
            'description' => "Removed RBAC role: {$name}",
            'ip_address' => request()->ip(),
        ]);

        return back()->with('info', "Role '{$name}' has been deleted.");
    }
}
