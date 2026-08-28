<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestmentPlanItem extends Model
{
    protected $fillable = [
        'investment_plan_id',
        'park_id',
        'intervention_type',
        'scenario',
        'quantity',
        'unit',
        'unit_cost',
        'total_cost',
        'modeled_benefit',
        'cost_source',
        'cost_is_assumption',
        'benefit_is_assumption',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'modeled_benefit' => 'decimal:2',
        'cost_is_assumption' => 'boolean',
        'benefit_is_assumption' => 'boolean',
    ];

    public function investmentPlan(): BelongsTo
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    public function park(): BelongsTo
    {
        return $this->belongsTo(Park::class);
    }
}
