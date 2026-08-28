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
        Schema::create('intervention_recommendations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('park_priority_score_id')
                ->constrained('park_priority_scores')
                ->cascadeOnDelete();

            $table->foreignId('park_id')
                ->constrained('parks')
                ->cascadeOnDelete();

            $table->foreignId('heatmap_analysis_id')
                ->constrained('heatmap_analyses')
                ->cascadeOnDelete();

            $table->string('intervention_key');
            $table->string('intervention_name');
            $table->string('category');

            $table->decimal('planning_cost', 12, 2);

            $table->string('rule_matched');
            $table->text('justification');

            $table->string('model_version')->default('v1');

            $table->timestamps();

            $table->unique(['park_priority_score_id', 'intervention_key'], 'park_score_intervention_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intervention_recommendations');
    }
};
