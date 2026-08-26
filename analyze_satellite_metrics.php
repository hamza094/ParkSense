<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\SatelliteMetric;
use App\Models\HeatmapAnalysis;

echo "Analyzing Satellite Physical Metrics Distribution\n";
echo "===============================================\n\n";

// Get the most recent heatmap analysis
$latestHeatmap = HeatmapAnalysis::query()
    ->select('id', 'created_at')
    ->orderByDesc('id')
    ->first();

if (!$latestHeatmap) {
    echo "No heatmap analysis found.\n";
    exit(1);
}

echo "Heatmap Analysis ID: {$latestHeatmap->id}\n";
echo "Created: {$latestHeatmap->created_at}\n\n";

// Get all satellite metrics for this heatmap
$satelliteMetrics = SatelliteMetric::with('park')
    ->where('heatmap_analysis_id', $latestHeatmap->id)
    ->where('status', 'completed')
    ->get();

if ($satelliteMetrics->isEmpty()) {
    echo "No satellite metrics found.\n";
    exit(1);
}

echo "Total Parks with Satellite Data: {$satelliteMetrics->count()}\n\n";

// Define satellite class groups
$vegetationClasses = ['tree', 'plant', 'grass'];
$hardSurfaceClasses = ['building', 'road', 'route'];

$parkData = [];

foreach ($satelliteMetrics as $metric) {
    $segments = $metric->data['segmentation']['segments'] ?? [];
    
    $vegetation = 0;
    $hardSurface = 0;
    
    foreach ($segments as $class => $percentage) {
        $classLower = strtolower($class);
        
        // Check vegetation
        foreach ($vegetationClasses as $vegClass) {
            if (str_contains($classLower, $vegClass)) {
                $vegetation += (float) $percentage;
                break;
            }
        }
        
        // Check hard surface
        foreach ($hardSurfaceClasses as $surfaceClass) {
            if (str_contains($classLower, $surfaceClass)) {
                $hardSurface += (float) $percentage;
                break;
            }
        }
    }
    
    $parkData[$metric->park->name] = [
        'vegetation' => $vegetation,
        'hard_surface' => $hardSurface,
        'total' => $vegetation + $hardSurface,
    ];
}

echo "Vegetation Coverage (%)\n";
echo str_repeat("=", 50) . "\n";

$vegetationValues = array_column($parkData, 'vegetation');
$vegMin = min($vegetationValues);
$vegMax = max($vegetationValues);
$vegAvg = array_sum($vegetationValues) / count($vegetationValues);
$vegRange = $vegMax - $vegMin;
$vegVariation = ($vegRange / $vegAvg) * 100;

echo "Min: " . number_format($vegMin, 2) . "%\n";
echo "Max: " . number_format($vegMax, 2) . "%\n";
echo "Avg: " . number_format($vegAvg, 2) . "%\n";
echo "Range: " . number_format($vegRange, 2) . "%\n";
echo "Variation: " . number_format($vegVariation, 2) . "%\n\n";

echo "Park-by-Park Vegetation:\n";
echo str_pad("Park", 30) . str_pad("Vegetation %", 15) . "\n";
echo str_repeat("-", 45) . "\n";

arsort($vegetationValues);
foreach ($vegetationValues as $parkName => $value) {
    echo str_pad($parkName, 30) . str_pad(number_format($value, 2) . '%', 15) . "\n";
}

echo "\nAssessment: ";
if ($vegVariation < 10) {
    echo "⚠️  LOW VARIATION - Limited differentiation\n";
} elseif ($vegVariation < 25) {
    echo "⚠️  MODERATE VARIATION - Some differentiation\n";
} else {
    echo "✓ GOOD VARIATION - Provides meaningful differentiation\n";
}

echo "\n\nHard Surface Coverage (%)\n";
echo str_repeat("=", 50) . "\n";

$hardSurfaceValues = array_column($parkData, 'hard_surface');
$hsMin = min($hardSurfaceValues);
$hsMax = max($hardSurfaceValues);
$hsAvg = array_sum($hardSurfaceValues) / count($hardSurfaceValues);
$hsRange = $hsMax - $hsMin;
$hsVariation = ($hsRange / $hsAvg) * 100;

echo "Min: " . number_format($hsMin, 2) . "%\n";
echo "Max: " . number_format($hsMax, 2) . "%\n";
echo "Avg: " . number_format($hsAvg, 2) . "%\n";
echo "Range: " . number_format($hsRange, 2) . "%\n";
echo "Variation: " . number_format($hsVariation, 2) . "%\n\n";

echo "Park-by-Park Hard Surface:\n";
echo str_pad("Park", 30) . str_pad("Hard Surface %", 15) . "\n";
echo str_repeat("-", 45) . "\n";

arsort($hardSurfaceValues);
foreach ($hardSurfaceValues as $parkName => $value) {
    echo str_pad($parkName, 30) . str_pad(number_format($value, 2) . '%', 15) . "\n";
}

echo "\nAssessment: ";
if ($hsVariation < 10) {
    echo "⚠️  LOW VARIATION - Limited differentiation\n";
} elseif ($hsVariation < 25) {
    echo "⚠️  MODERATE VARIATION - Some differentiation\n";
} else {
    echo "✓ GOOD VARIATION - Provides meaningful differentiation\n";
}

echo "\n\nCombined Physical Condition Score\n";
echo str_repeat("=", 50) . "\n";

$physicalScores = [];
foreach ($parkData as $parkName => $data) {
    $vegetationDeficit = max(0, 100 - $data['vegetation']);
    $score = ($vegetationDeficit * 0.5) + ($data['hard_surface'] * 0.5);
    $score = min(100, $score);
    $physicalScores[$parkName] = $score;
}

$psMin = min($physicalScores);
$psMax = max($physicalScores);
$psAvg = array_sum($physicalScores) / count($physicalScores);
$psRange = $psMax - $psMin;
$psVariation = ($psRange / $psAvg) * 100;

echo "Min: " . number_format($psMin, 2) . "\n";
echo "Max: " . number_format($psMax, 2) . "\n";
echo "Avg: " . number_format($psAvg, 2) . "\n";
echo "Range: " . number_format($psRange, 2) . "\n";
echo "Variation: " . number_format($psVariation, 2) . "%\n\n";

echo "Park-by-Park Physical Scores:\n";
echo str_pad("Park", 30) . str_pad("Score", 15) . "\n";
echo str_repeat("-", 45) . "\n";

arsort($physicalScores);
foreach ($physicalScores as $parkName => $value) {
    echo str_pad($parkName, 30) . str_pad(number_format($value, 2), 15) . "\n";
}

echo "\nAssessment: ";
if ($psVariation < 10) {
    echo "⚠️  LOW VARIATION - Limited differentiation\n";
} elseif ($psVariation < 25) {
    echo "⚠️  MODERATE VARIATION - Some differentiation\n";
} else {
    echo "✓ GOOD VARIATION - Provides meaningful differentiation\n";
}

echo "\n\nSummary:\n";
echo "========\n";
echo "Satellite metrics provide physical differentiation based on land cover.\n";
echo "Higher variation in these metrics suggests they can meaningfully rank parks.\n";
