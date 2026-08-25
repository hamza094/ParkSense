<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EnvironmentalMetric extends Model
{
    protected $fillable = [
        'park_id',
        'heatmap_analysis_id',
        'activity_id',
        'status',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function park()
    {
        return $this->belongsTo(Park::class);
    }

    public function heatmapAnalysis()
    {
        return $this->belongsTo(HeatmapAnalysis::class);
    }
}
