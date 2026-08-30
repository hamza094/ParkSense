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
        Schema::table('intervention_recommendations', function (Blueprint $table) {
            // Add the cooling_benefit column (the main missing column)
            if (!Schema::hasColumn('intervention_recommendations', 'cooling_benefit')) {
                $table->json('cooling_benefit')->nullable()->after('justification');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_recommendations', function (Blueprint $table) {
            if (Schema::hasColumn('intervention_recommendations', 'cooling_benefit')) {
                $table->dropColumn('cooling_benefit');
            }
        });
    }
};