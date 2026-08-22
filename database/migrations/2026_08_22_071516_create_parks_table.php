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
        Schema::create('parks', function (Blueprint $table) {
            $table->id();
            $table->string('park_id')->unique();
            $table->string('name');
            $table->string('property_type');
            $table->string('park_type')->nullable();

            $table->decimal('acres', 10, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->boolean('playground')->nullable();
            $table->boolean('splash_pads')->nullable();
            $table->boolean('swimming_pool')->nullable();
            $table->boolean('sports_complex')->nullable();
            $table->boolean('shade_structures')->nullable();
            $table->boolean('recreation_community_center')->nullable();

            // Keep actual park boundary for later spatial work
            $table->json('geometry')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parks');
    }
};
