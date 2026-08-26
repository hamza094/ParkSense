<?php

use App\Http\Controllers\ParkController;
use App\Http\Controllers\EnvironmentalAnalysisController;
use App\Http\Controllers\SatelliteAnalysisController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [ParkController::class, 'index'])->name('dashboard');
    Route::post('parks/polygon', [ParkController::class, 'storePolygon'])->name('parks.polygon');
    Route::get('parks/heatmap-status/{activityId}', [ParkController::class, 'checkHeatmapStatus'])->name('parks.heatmap-status');
    Route::get('parks/heatmap-cache/{activityId}', [ParkController::class, 'getCachedHeatmapResult'])->name('parks.heatmap-cache');
    Route::post('parks/run-heat-analysis', [ParkController::class, 'runHeatAnalysis'])->name('parks.run-heat-analysis');
    Route::post('environmental/run-analysis', [EnvironmentalAnalysisController::class, 'runEnvironmentalAnalysis'])->name('environmental.run-analysis');
    Route::get('environmental/status/{activityId}', [EnvironmentalAnalysisController::class, 'checkEnvironmentalStatus'])->name('environmental.status');
    Route::post('satellite/run-analysis', [SatelliteAnalysisController::class, 'runSatelliteAnalysis'])->name('satellite.run-analysis');
    Route::get('satellite/status/{activityId}', [SatelliteAnalysisController::class, 'checkSatelliteStatus'])->name('satellite.status');
});

require __DIR__.'/settings.php';
