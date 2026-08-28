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
        // Drop foreign key first
        \DB::statement('ALTER TABLE intervention_recommendations DROP FOREIGN KEY intervention_recommendations_park_priority_score_id_foreign');
        
        // Drop old unique constraint
        \DB::statement('ALTER TABLE intervention_recommendations DROP INDEX park_score_intervention_unique');
        
        // Add new unique constraint that includes scenario
        \DB::statement('ALTER TABLE intervention_recommendations ADD UNIQUE INDEX park_score_intervention_scenario_unique (park_priority_score_id, intervention_key, scenario)');
        
        // Re-add foreign key
        \DB::statement('ALTER TABLE intervention_recommendations ADD CONSTRAINT intervention_recommendations_park_priority_score_id_foreign FOREIGN KEY (park_priority_score_id) REFERENCES park_priority_scores(id) ON DELETE CASCADE');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key
        \DB::statement('ALTER TABLE intervention_recommendations DROP FOREIGN KEY intervention_recommendations_park_priority_score_id_foreign');
        
        // Drop new unique constraint
        \DB::statement('ALTER TABLE intervention_recommendations DROP INDEX park_score_intervention_scenario_unique');
        
        // Restore old unique constraint
        \DB::statement('ALTER TABLE intervention_recommendations ADD UNIQUE INDEX park_score_intervention_unique (park_priority_score_id, intervention_key)');
        
        // Re-add foreign key
        \DB::statement('ALTER TABLE intervention_recommendations ADD CONSTRAINT intervention_recommendations_park_priority_score_id_foreign FOREIGN KEY (park_priority_score_id) REFERENCES park_priority_scores(id) ON DELETE CASCADE');
    }
};
