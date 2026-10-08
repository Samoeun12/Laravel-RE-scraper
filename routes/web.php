<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PortalController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Root redirect
Route::get('/', function () {
    return redirect()->route('portal.dashboard');
});

// Authentication Routes (Guest)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::get('/', [PortalController::class, 'dashboard'])->name('dashboard');
        
        // Scraper Hub
        Route::get('/scrapers', [PortalController::class, 'scrapers'])->name('scrapers');
        Route::post('/scrapers', [PortalController::class, 'storeScraper'])->name('scrapers.store');
        Route::post('/scrapers/{id}/trigger', [PortalController::class, 'triggerScraper'])->name('scrapers.trigger');
        Route::delete('/scrapers/{id}', [PortalController::class, 'deleteScraper'])->name('scrapers.delete');

        // Properties / Listings
        Route::get('/properties', [PortalController::class, 'properties'])->name('properties');
        Route::post('/properties', [PortalController::class, 'storeProperty'])->name('properties.store');
        Route::delete('/properties/{id}', [PortalController::class, 'deleteProperty'])->name('properties.delete');

        // Listings Map (Full Google Map with Live Property Pins & Clusters)
        Route::get('/map', [PortalController::class, 'map'])->name('map');
        Route::get('/api/map-properties', [PortalController::class, 'mapPropertiesApi'])->name('map.api');

        // Market Intelligence & Valuation Tools (from Python Scraper Tool)
        Route::get('/deals', [PortalController::class, 'deals'])->name('deals');
        Route::get('/cma', [PortalController::class, 'cma'])->name('cma');
        Route::get('/land-estimator', [PortalController::class, 'landEstimator'])->name('land_estimator');

        // Profile & Settings
        Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
        Route::post('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [PortalController::class, 'updatePassword'])->name('password.update');

        // User Management
        Route::get('/users', [PortalController::class, 'users'])->name('users');
        Route::post('/users', [PortalController::class, 'storeUser'])->name('users.store');
        Route::put('/users/{id}', [PortalController::class, 'updateUser'])->name('users.update');
        Route::delete('/users/{id}', [PortalController::class, 'deleteUser'])->name('users.delete');
        Route::post('/users/{id}/toggle-status', [PortalController::class, 'toggleUserStatus'])->name('users.toggle-status');

        // Permission & Access Control
        Route::get('/permissions', [PortalController::class, 'permissions'])->name('permissions');
        Route::post('/permissions/roles', [PortalController::class, 'storeRole'])->name('permissions.roles.store');
        Route::post('/permissions/matrix', [PortalController::class, 'updatePermissionMatrix'])->name('permissions.matrix');
        Route::delete('/permissions/roles/{id}', [PortalController::class, 'deleteRole'])->name('permissions.roles.delete');

        // Theme Preference API
        Route::post('/update-theme', [PortalController::class, 'updateTheme'])->name('theme.update');
    });
});
