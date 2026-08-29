<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterventionRecommendation extends Model
{
    protected $fillable = [
        'park_priority_score_id',
        'park_id',
        'heatmap_analysis_id',
        'intervention_key',
        'scenario',
        'intervention_name',
        'category',
        'quantity',
        'unit',
        'upfront_cost',
        'annual_maintenance_cost',
        'annual_water_cost',
        'cost_basis',
        'source',
        'source_url',
        'rule_matched',
        'justification',
        'cooling_benefit',
        'model_version',
    ];

    protected $casts = [
        'upfront_cost' => 'float',
        'annual_maintenance_cost' => 'float',
        'annual_water_cost' => 'float',
        'cooling_benefit' => 'array',
    ];

    public function getCoolingBenefitAttribute($value)
    {
        return $value ? json_decode($value, true) : null;
    }

    public function park()
    {
        return $this->belongsTo(Park::class);
    }

    public function priorityScore()
    {
        return $this->belongsTo(
            ParkPriorityScore::class,
            'park_priority_score_id'
        );
    }

    public function heatmapAnalysis()
    {
        return $this->belongsTo(HeatmapAnalysis::class);
    }
}
