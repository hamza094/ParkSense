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
        Schema::table('environmental_metrics', function (Blueprint $table) {
            $table->string('activity_id')->nullable()->after('heatmap_analysis_id')->index();
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending')->after('activity_id');
            $table->json('data')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('environmental_metrics', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropColumn('activity_id');
            $table->json('data')->nullable(false)->change();
        });
    }
};
