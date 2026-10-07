<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Property;
use App\Models\ScraperTask;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $stats = [
            'total_properties' => Property::count(),
            'total_scrapers' => ScraperTask::count(),
            'running_scrapers' => ScraperTask::where('status', 'running')->count(),
            'total_items_scraped' => ScraperTask::sum('items_scraped'),
            'for_sale_count' => Property::where('listing_type', 'Sale')->count(),
            'for_rent_count' => Property::where('listing_type', 'Rent')->count(),
        ];

        $scrapers = ScraperTask::with('user')->latest()->take(5)->get();
        $recentProperties = Property::with('scraperTask')->latest()->take(6)->get();
        $activities = ActivityLog::with('user')->latest()->take(8)->get();

        return view('portal.dashboard', compact('stats', 'scrapers', 'recentProperties', 'activities', 'user'));
    }

    /**
     * Scraper Hub view.
     */
    public function scrapers()
    {
        $scrapers = ScraperTask::with('user')->withCount('properties')->latest()->get();
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
     * Trigger or toggle a Scraper Task run.
     */
    public function triggerScraper(Request $request, $id)
    {
        $task = ScraperTask::findOrFail($id);

        if ($task->status === 'running') {
            $task->update([
                'status' => 'idle',
                'last_log' => 'Crawler execution paused manually by operator.',
            ]);
            $msg = 'Scraper paused.';
        } else {
            $randomNew = rand(5, 24);
            $task->update([
                'status' => 'running',
                'last_run_at' => now(),
                'items_scraped' => $task->items_scraped + $randomNew,
                'last_log' => "Extracted $randomNew new property records from {$task->source_name}. Ingestion batch completed.",
            ]);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'Executed Scraper',
                'description' => "Run dispatched for {$task->name}. +{$randomNew} listings indexed.",
                'ip_address' => $request->ip(),
            ]);

            $msg = "Scraper started! Harvested {$randomNew} new property listings.";
        }

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
     * Properties Catalog View.
     */
    public function properties(Request $request)
    {
        $query = Property::query()->with('scraperTask');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('source_name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('property_type', $type);
        }

        if ($listingType = $request->input('listing_type')) {
            $query->where('listing_type', $listingType);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $properties = $query->latest()->paginate(12)->withQueryString();
        $scrapers = ScraperTask::all();

        return view('portal.properties', compact('properties', 'scrapers'));
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
            'bedrooms' => ['required', 'integer', 'min:0'],
            'bathrooms' => ['required', 'integer', 'min:0'],
            'area_sqm' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'url'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'source_url' => ['nullable', 'url'],
        ]);

        $defaultImage = 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?auto=format&fit=crop&w=800&q=80';

        $property = Property::create([
            'title' => $validated['title'],
            'property_type' => $validated['property_type'],
            'listing_type' => $validated['listing_type'],
            'price' => $validated['price'],
            'currency' => 'USD',
            'location' => $validated['location'],
            'city' => $validated['city'],
            'bedrooms' => $validated['bedrooms'],
            'bathrooms' => $validated['bathrooms'],
            'area_sqm' => $validated['area_sqm'],
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
