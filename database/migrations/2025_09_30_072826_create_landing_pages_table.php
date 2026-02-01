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
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug');
            $table->string('button_text')->nullable();
            $table->string('hero_image')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('upload_video')->nullable();
            $table->longText('product_features_list')->nullable();
            $table->string('feature_image')->nullable();
            $table->string('feature_button_text')->nullable();
            $table->string('cta_title')->nullable();
            $table->string('cta_short_description')->nullable();
            $table->string('cta_button_text')->nullable();
            $table->text('product_gallery')->nullable();
            $table->longText('faqs')->nullable();
            $table->text('review_images')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
