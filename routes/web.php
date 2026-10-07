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

        // Properties
        Route::get('/properties', [PortalController::class, 'properties'])->name('properties');
        Route::post('/properties', [PortalController::class, 'storeProperty'])->name('properties.store');
        Route::delete('/properties/{id}', [PortalController::class, 'deleteProperty'])->name('properties.delete');

        // Profile & Settings
        Route::get('/profile', [PortalController::class, 'profile'])->name('profile');
        Route::post('/profile', [PortalController::class, 'updateProfile'])->name('profile.update');
        Route::post('/password', [PortalController::class, 'updatePassword'])->name('password.update');

        // Theme Preference API
        Route::post('/update-theme', [PortalController::class, 'updateTheme'])->name('theme.update');
    });
});
