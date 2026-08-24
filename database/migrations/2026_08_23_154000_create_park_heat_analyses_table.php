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
        Schema::create('park_heat_analyses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('park_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('heatmap_analysis_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('average_temperature', 6, 2);
            $table->decimal('min_temperature', 6, 2)->nullable();
            $table->decimal('max_temperature', 6, 2)->nullable();

            $table->unsignedInteger('matched_tile_count')->default(0);

            $table->timestamps();

            $table->unique([
                'park_id',
                'heatmap_analysis_id',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('park_heat_analyses');
    }
};
