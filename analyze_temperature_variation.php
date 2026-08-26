<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ParkHeatAnalysis;
use App\Models\HeatmapAnalysis;

echo "Analyzing Temperature Variation Across Parks\n";
echo "=============================================\n\n";

// Get the most recent heatmap analysis (avoid ORDER BY to prevent memory issues)
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

// Get all park heat analyses for this heatmap
$parkHeatAnalyses = ParkHeatAnalysis::with('park')
    ->where('heatmap_analysis_id', $latestHeatmap->id)
    ->get();

if ($parkHeatAnalyses->isEmpty()) {
    echo "No park heat analyses found.\n";
    exit(1);
}

echo "Total Parks Analyzed: {$parkHeatAnalyses->count()}\n\n";

// Calculate statistics
echo str_pad("Park", 30) . str_pad("Avg", 10) . str_pad("Min", 10) . str_pad("Max", 10) . str_pad("Range", 10) . "\n";
echo str_repeat("-", 70) . "\n";

$allAverages = [];
$allRanges = [];

foreach ($parkHeatAnalyses as $analysis) {
    $avg = $analysis->average_temperature;
    $min = $analysis->min_temperature;
    $max = $analysis->max_temperature;
    $range = $max - $min;
    
    $allAverages[] = $avg;
    $allRanges[] = $range;
    
    $parkName = $analysis->park->name ?? 'Unknown';
    echo str_pad($parkName, 30) 
        . str_pad(number_format($avg, 2), 10) 
        . str_pad(number_format($min, 2), 10) 
        . str_pad(number_format($max, 2), 10) 
        . str_pad(number_format($range, 2), 10) . "\n";
}

echo "\n";
echo "Park-to-Park Variation Analysis:\n";
echo "================================\n";

$overallMin = min($allAverages);
$overallMax = max($allAverages);
$overallRange = $overallMax - $overallMin;
$overallAvg = array_sum($allAverages) / count($allAverages);

echo "Overall Average Temperature: " . number_format($overallAvg, 2) . "°C\n";
echo "Hottest Park: " . number_format($overallMax, 2) . "°C\n";
echo "Coolest Park: " . number_format($overallMin, 2) . "°C\n";
echo "Park-to-Park Range: " . number_format($overallRange, 2) . "°C\n";
echo "Park-to-Park Variation: " . number_format(($overallRange / $overallAvg) * 100, 2) . "%\n\n";

echo "Within-Park Variation Analysis:\n";
echo "===============================\n";

$avgRange = array_sum($allRanges) / count($allRanges);
$maxRange = max($allRanges);
$minRange = min($allRanges);

echo "Average Within-Park Range: " . number_format($avgRange, 2) . "°C\n";
echo "Maximum Within-Park Range: " . number_format($maxRange, 2) . "°C\n";
echo "Minimum Within-Park Range: " . number_format($minRange, 2) . "°C\n\n";

// Assessment
echo "Assessment:\n";
echo "===========\n";

if ($overallRange < 0.5) {
    echo "⚠️  LOW VARIATION: Park-to-park difference is only " . number_format($overallRange, 2) . "°C\n";
    echo "   This may not be sufficient for meaningful differentiation in scoring.\n";
    echo "   Consider reducing the heat_severity weight or investigating API parameters.\n";
} elseif ($overallRange < 1.0) {
    echo "⚠️  MODERATE VARIATION: Park-to-park difference is " . number_format($overallRange, 2) . "°C\n";
    echo "   This provides some differentiation but may be limited.\n";
} else {
    echo "✓ GOOD VARIATION: Park-to-park difference is " . number_format($overallRange, 2) . "°C\n";
    echo "   This provides meaningful differentiation for scoring.\n";
}

if ($avgRange < 0.1) {
    echo "⚠️  LOW WITHIN-PARK VARIATION: Average range is only " . number_format($avgRange, 2) . "°C\n";
    echo "   Parks have very uniform temperatures across their tiles.\n";
} else {
    echo "✓ WITHIN-PARK VARIATION: Average range is " . number_format($avgRange, 2) . "°C\n";
}
