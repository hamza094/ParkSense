<?php

use App\Http\Controllers\ParkController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ParkController::class, 'index'])->name('dashboard');
    Route::post('parks/polygon', [ParkController::class, 'storePolygon'])->name('parks.polygon');
    Route::get('parks/heatmap-status/{activityId}', [ParkController::class, 'checkHeatmapStatus'])->name('parks.heatmap-status');
    Route::get('parks/heatmap-cache/{activityId}', [ParkController::class, 'getCachedHeatmapResult'])->name('parks.heatmap-cache');
    
    // Experiment route (commented out - for testing only)
    // Route::get('dashboard', [ParkController::class, 'getCachedHeatmapResult'])->name('dashboard')->defaults('activityId', '1fa88e7a-941a-48da-a01d-4bc283e87a62');
});

require __DIR__.'/settings.php';
