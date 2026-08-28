<?php

use App\Http\Controllers\ParkController;
use App\Http\Controllers\EnvironmentalAnalysisController;
use App\Http\Controllers\SatelliteAnalysisController;
use App\Http\Controllers\ParkPriorityScoreController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\InvestmentController;
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

    // Priority scoring routes
    Route::post('priority-scores/calculate/{heatmapAnalysisId}', [ParkPriorityScoreController::class, 'calculate'])->name('priority-scores.calculate');
    Route::get('priority-scores/{heatmapAnalysisId}', [ParkPriorityScoreController::class, 'index'])->name('priority-scores.index');
    Route::get('priority-scores/show/{id}', [ParkPriorityScoreController::class, 'show'])->name('priority-scores.show');

    // Intervention routes
    Route::post('interventions/generate/{heatmapAnalysisId}', [InterventionController::class, 'generate'])->name('interventions.generate');

    // Investment optimization routes
    Route::post('investments/optimize/{heatmapAnalysisId}', [InvestmentController::class, 'optimize'])->name('investments.optimize');
});

require __DIR__.'/settings.php';
