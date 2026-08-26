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
        Schema::create('park_priority_scores', function (Blueprint $table) {
            $table->id();

            $table->foreignId('park_id')
                ->constrained('parks')
                ->cascadeOnDelete();

            $table->foreignId('heatmap_analysis_id')
                ->constrained('heatmap_analyses')
                ->cascadeOnDelete();

            $table->foreignId('park_heat_analysis_id')
                ->constrained('park_heat_analyses')
                ->cascadeOnDelete();

            $table->foreignId('environmental_metric_id')
                ->nullable()
                ->constrained('environmental_metrics')
                ->nullOnDelete();

            $table->foreignId('satellite_metric_id')
                ->nullable()
                ->constrained('satellite_metrics')
                ->nullOnDelete();

            /*
             * Individual component scores (0-100)
             */
            $table->decimal('heat_severity', 6, 2);
            $table->decimal('environmental_stress', 6, 2);
            $table->decimal('physical_condition', 6, 2);
            $table->decimal('park_importance', 6, 2);
            $table->decimal('intervention_opportunity', 6, 2);

            /*
             * Final weighted priority score (0-100)
             */
            $table->decimal('priority_score', 6, 2);

            /*
             * Store calculation evidence for UI transparency
             */
            $table->json('calculation_data')->nullable();

            /*
             * Model version for tracking algorithm changes
             */
            $table->string('model_version')->default('v1');

            $table->timestamps();

            /*
             * Ensure one score per park per heatmap analysis
             */
            $table->unique(['park_id', 'heatmap_analysis_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_priority_scores');
    }
};
