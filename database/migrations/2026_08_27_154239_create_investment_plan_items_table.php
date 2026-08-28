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
        Schema::create('investment_plan_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('investment_plan_id')
                ->constrained('investment_plans')
                ->cascadeOnDelete();

            $table->foreignId('park_id')
                ->constrained('parks')
                ->cascadeOnDelete();

            $table->string('intervention_type');
            $table->string('scenario')->nullable();

            $table->decimal('quantity', 12, 2);
            $table->string('unit')->nullable();

            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);

            $table->decimal('modeled_benefit', 10, 2);

            $table->string('cost_source')->nullable();
            $table->boolean('cost_is_assumption')->default(true);
            $table->boolean('benefit_is_assumption')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investment_plan_items');
    }
};
