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
            $table->dropColumn('planning_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_recommendations', function (Blueprint $table) {
            $table->decimal('planning_cost', 12, 2);
        });
    }
};
