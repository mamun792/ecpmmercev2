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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('path'); // Relative path to image
            $table->string('original_name');
            $table->string('format'); // jpg, png, webp
            $table->integer('width');
            $table->integer('height');
            $table->integer('size'); // File size in bytes
            $table->string('thumbnail_path')->nullable(); // Path to thumbnail
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
