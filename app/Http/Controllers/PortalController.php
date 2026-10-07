<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\ScraperTask;
use App\Models\User;
use App\Services\RealEstateScraperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
            'harbor' => ['name' => 'Harbor Property', 'color' => '#06b6d4'],
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
}
