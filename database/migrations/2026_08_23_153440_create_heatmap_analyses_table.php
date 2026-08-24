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
        Schema::create('heatmap_analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('activity_id')->unique();
            $table->json('aoi');
            $table->json('park_ids')->nullable();
            $table->date('start_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedTinyInteger('filter_type');
            $table->unsignedSmallInteger('granularity');
            $table->string('analytic_type')->default('tcm');
            $table->json('map_data');
            $table->json('stats_data')->nullable();
            $table->string('status')->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('heatmap_analyses');
    }
};
