<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentPlan extends Model
{
    protected $fillable = [
        'heatmap_analysis_id',
        'budget',
        'allocated_cost',
        'remaining_budget',
        'total_modeled_benefit',
        'modeled_priority_coverage',
        'model_version',
    ];

    protected $casts = [
        'budget' => 'decimal:2',
        'allocated_cost' => 'decimal:2',
        'remaining_budget' => 'decimal:2',
        'total_modeled_benefit' => 'decimal:2',
        'modeled_priority_coverage' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(InvestmentPlanItem::class);
    }
}
