<?php

use App\Http\Controllers\ParkController;
use App\Http\Controllers\HeatAnalysisDetailController;
use App\Http\Controllers\EnvironmentalAnalysisController;
use App\Http\Controllers\SatelliteAnalysisController;
use App\Http\Controllers\ParkPriorityScoreController;
use App\Http\Controllers\InterventionController;
use App\Http\Controllers\InvestmentController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

// API endpoint for GeoJSON data (avoids Inertia timeout with large payloads)
Route::get('api/heat-analyses/{id}/geojson', [HeatAnalysisDetailController::class, 'getGeoJson'])->name('heat-analyses.geojson');

// Public routes for hackathon demo - no authentication required
Route::get('dashboard', [ParkController::class, 'index'])->name('dashboard');
Route::get('heat-analyses/{id}', [HeatAnalysisDetailController::class, 'show'])->name('heat-analyses.show');
Route::post('parks/polygon', [ParkController::class, 'storePolygon'])->name('parks.polygon');
Route::get('parks/heatmap-status/{activityId}', [ParkController::class, 'checkHeatmapStatus'])->name('parks.heatmap-status');
Route::post('parks/run-heat-analysis', [ParkController::class, 'runHeatAnalysis'])->name('parks.run-heat-analysis');
Route::post('environmental/run-analysis/{heatmapAnalysisId}', [EnvironmentalAnalysisController::class, 'runEnvironmentalAnalysis'])->name('environmental.run-analysis');
Route::get('environmental/status/{activityId}', [EnvironmentalAnalysisController::class, 'checkEnvironmentalStatus'])->name('environmental.status');
Route::post('satellite/run-analysis/{heatmapAnalysisId}', [SatelliteAnalysisController::class, 'runSatelliteAnalysis'])->name('satellite.run-analysis');
Route::get('satellite/status/{activityId}', [SatelliteAnalysisController::class, 'checkSatelliteStatus'])->name('satellite.status');

// Priority scoring routes
Route::post('priority-scores/calculate/{heatmapAnalysisId}', [ParkPriorityScoreController::class, 'calculate'])->name('priority-scores.calculate');

// Intervention routes
Route::post('interventions/generate/{heatmapAnalysisId}', [InterventionController::class, 'generate'])->name('interventions.generate');

// Investment optimization routes
Route::post('investments/optimize/{heatmapAnalysisId}', [InvestmentController::class, 'optimize'])->name('investments.optimize');

require __DIR__.'/settings.php';
