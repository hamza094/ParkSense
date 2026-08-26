<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\EnvironmentalMetric;
use App\Models\HeatmapAnalysis;

echo "Analyzing Environmental Metrics Distribution\n";
echo "=============================================\n\n";

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

// Get all environmental metrics for this heatmap
$environmentalMetrics = EnvironmentalMetric::with('park')
    ->where('heatmap_analysis_id', $latestHeatmap->id)
    ->where('status', 'completed')
    ->get();

if ($environmentalMetrics->isEmpty()) {
    echo "No environmental metrics found.\n";
    exit(1);
}

echo "Total Parks with Environmental Data: {$environmentalMetrics->count()}\n\n";

// Analyze each metric
$metrics = [
    'heat_index_celsius' => 'Heat Index (°C)',
    'relative_humidity_percent' => 'Humidity (%)',
    'wet_bulb_temperature_celsius' => 'Wet Bulb (°C)',
    'solar_ghi' => 'Solar GHI (W/m²)',
];

foreach ($metrics as $key => $label) {
    echo "\n{$label}\n";
    echo str_repeat("=", 50) . "\n";
    
    $values = [];
    $parkValues = [];
    
    foreach ($environmentalMetrics as $metric) {
        $data = $metric->data;
        $params = $data['locations'][0]['parameters'] ?? [];
        
        if ($key === 'solar_ghi') {
            // Solar is a single value
            $value = $data['locations'][0]['solar_irradiance']['clear_sky']['ghi'] ?? null;
            if ($value !== null) {
                $values[] = $value;
                $parkValues[$metric->park->name] = $value;
            }
        } else {
            // Extract array values for 08:00-18:00 window (indices 0-10)
            $arrayData = $params[$key] ?? [];
            if (!empty($arrayData)) {
                // Get max for heat index, avg for others
                if ($key === 'heat_index_celsius') {
                    $value = max($arrayData);
                } else {
                    $value = array_sum($arrayData) / count($arrayData);
                }
                $values[] = $value;
                $parkValues[$metric->park->name] = $value;
            }
        }
    }
    
    if (empty($values)) {
        echo "No data available for this metric.\n";
        continue;
    }
    
    $min = min($values);
    $max = max($values);
    $avg = array_sum($values) / count($values);
    $range = $max - $min;
    $variation = ($range / $avg) * 100;
    
    echo "Min: " . number_format($min, 2) . "\n";
    echo "Max: " . number_format($max, 2) . "\n";
    echo "Avg: " . number_format($avg, 2) . "\n";
    echo "Range: " . number_format($range, 2) . "\n";
    echo "Variation: " . number_format($variation, 2) . "%\n\n";
    
    echo "Park-by-Park Values:\n";
    echo str_pad("Park", 30) . str_pad("Value", 15) . "\n";
    echo str_repeat("-", 45) . "\n";
    
    // Sort by value descending
    arsort($parkValues);
    foreach ($parkValues as $parkName => $value) {
        echo str_pad($parkName, 30) . str_pad(number_format($value, 2), 15) . "\n";
    }
    
    // Assessment
    echo "\nAssessment: ";
    if ($variation < 1) {
        echo "⚠️  LOW VARIATION - May not provide meaningful differentiation\n";
    } elseif ($variation < 3) {
        echo "⚠️  MODERATE VARIATION - Limited differentiation\n";
    } else {
        echo "✓ GOOD VARIATION - Provides meaningful differentiation\n";
    }
}

echo "\n\nSummary:\n";
echo "========\n";
echo "Metrics with highest variation should receive higher weights in scoring.\n";
