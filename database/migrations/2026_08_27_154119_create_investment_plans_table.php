<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investment_plans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('heatmap_analysis_id')
                ->constrained('heatmap_analyses')
                ->cascadeOnDelete();

            $table->decimal('budget', 12, 2);
            $table->decimal('allocated_cost', 12, 2);
            $table->decimal('remaining_budget', 12, 2);

            $table->decimal('total_modeled_benefit', 10, 2);
            $table->decimal('modeled_priority_coverage', 6, 2);

            $table->string('model_version')->default('v1');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plans');
    }
};
