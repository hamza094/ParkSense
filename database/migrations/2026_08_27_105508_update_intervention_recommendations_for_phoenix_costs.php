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
            $table->integer('quantity')->nullable()->after('intervention_key');
            $table->string('scenario')->nullable()->after('quantity');
            $table->string('unit')->nullable()->after('scenario');
            $table->decimal('upfront_cost', 12, 2)->nullable()->after('unit');
            $table->decimal('annual_maintenance_cost', 12, 2)->nullable()->after('upfront_cost');
            $table->decimal('annual_water_cost', 12, 2)->nullable()->after('annual_maintenance_cost');
            $table->string('cost_basis')->nullable()->after('justification');
            $table->string('source')->nullable()->after('cost_basis');
            $table->string('source_url')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('intervention_recommendations', function (Blueprint $table) {
            $table->dropColumn([
                'quantity',
                'scenario',
                'unit',
                'upfront_cost',
                'annual_maintenance_cost',
                'annual_water_cost',
                'cost_basis',
                'source',
                'source_url',
            ]);
        });
    }
};
