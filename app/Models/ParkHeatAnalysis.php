<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParkHeatAnalysis extends Model
{
    protected $fillable = [
        'park_id',
        'heatmap_analysis_id',
        'average_temperature',
        'min_temperature',
        'max_temperature',
        'matched_tile_count',
    ];

    protected $casts = [
        'average_temperature' => 'decimal:2',
        'min_temperature' => 'decimal:2',
        'max_temperature' => 'decimal:2',
        'matched_tile_count' => 'integer',
    ];

    /**
     * Get the park that owns the heat analysis.
     */
    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }

    /**
     * Get the heatmap analysis that owns the park heat analysis.
     */
    public function heatmapAnalysis(): BelongsTo
    {
        return $this->belongsTo(HeatmapAnalysis::class);
    }
}
