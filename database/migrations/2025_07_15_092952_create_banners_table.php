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
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('top_banner1')->nullable();
            $table->string('top_banner1_url')->nullable();
            $table->string('top_banner2')->nullable();
            $table->string('top_banner2_url')->nullable();
            $table->string('top_banner3')->nullable();
            $table->string('top_banner3_url')->nullable();
            $table->string('new_arrival_b_banner1')->nullable();
            $table->string('new_arrival_b_banner1_url')->nullable();
            $table->string('new_arrival_b_banner2')->nullable();
            $table->string('new_arrival_b_banner2_url')->nullable();
            $table->longText('random_banners')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banners');
    }
};
