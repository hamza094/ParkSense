<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeatmapAnalysis extends Model
{
    protected $fillable = [
        'activity_id',
        'aoi',
        'park_ids',
        'start_date',
        'start_time',
        'end_time',
        'filter_type',
        'granularity',
        'analytic_type',
        'map_data',
        'stats_data',
        'status',
    ];

    protected $casts = [
        'aoi' => 'array',
        'park_ids' => 'array',
        'map_data' => 'array',
        'stats_data' => 'array',
        'start_date' => 'date',
    ];
}
