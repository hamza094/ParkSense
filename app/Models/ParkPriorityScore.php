<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkPriorityScore extends Model
{
    protected $fillable = [
        'park_id',
        'heatmap_analysis_id',
        'park_heat_analysis_id',
        'environmental_metric_id',
        'satellite_metric_id',
        'heat_severity',
        'environmental_stress',
        'physical_condition',
        'park_importance',
        'intervention_opportunity',
        'priority_score',
        'calculation_data',
        'model_version',
    ];

    protected $casts = [
        'heat_severity' => 'float',
        'environmental_stress' => 'float',
        'physical_condition' => 'float',
        'park_importance' => 'float',
        'intervention_opportunity' => 'float',
        'priority_score' => 'float',
        'calculation_data' => 'array',
    ];

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    public function heatmapAnalysis(): BelongsTo
    {
        return $this->belongsTo(HeatmapAnalysis::class);
    }

    public function parkHeatAnalysis(): BelongsTo
    {
        return $this->belongsTo(ParkHeatAnalysis::class);
    }

    public function environmentalMetric(): BelongsTo
    {
        return $this->belongsTo(EnvironmentalMetric::class);
    }

    public function satelliteMetric(): BelongsTo
    {
        return $this->belongsTo(SatelliteMetric::class);
    }
}
