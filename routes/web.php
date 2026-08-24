<?php

use App\Http\Controllers\ParkController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ParkController::class, 'index'])->name('dashboard');
    Route::post('parks/polygon', [ParkController::class, 'storePolygon'])->name('parks.polygon');
    Route::get('parks/heatmap-status/{activityId}', [ParkController::class, 'checkHeatmapStatus'])->name('parks.heatmap-status');
    Route::get('parks/heatmap-cache/{activityId}', [ParkController::class, 'getCachedHeatmapResult'])->name('parks.heatmap-cache');
    Route::post('parks/run-heat-analysis', [ParkController::class, 'runHeatAnalysis'])->name('parks.run-heat-analysis');
});

require __DIR__.'/settings.php';
