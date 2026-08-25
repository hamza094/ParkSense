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
        Schema::create('environmental_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('park_id')->constrained()->cascadeOnDelete();
            $table->foreignId('heatmap_analysis_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->string('activity_id')->nullable()->index(); // Store API activity_id for status polling
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->json('data')->nullable();
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
        Schema::dropIfExists('environmental_metrics');
    }
};
