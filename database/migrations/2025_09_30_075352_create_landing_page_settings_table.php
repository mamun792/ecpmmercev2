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
        Schema::create('landing_page_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->onDelete('cascade');
            $table->string('primary_color')->nullable();
            $table->string('secondary_color')->nullable();
            $table->boolean('is_enabled_faq')->default(false);

            $table->boolean('is_enabled_review')->default(false);
            $table->boolean('is_enabled_cta')->default(false);

            // add faq and review and cta muti style like mordan and classic etc
            $table->string('faq_style')->nullable();
            $table->string('review_style')->nullable();
            $table->string('cta_style')->nullable();

            // ordaing system  faq and review and cta like 1,2,3,4,5,6,7,8,9,10 etc
            $table->string('faq_order')->nullable();
            $table->string('review_order')->nullable();
            $table->string('cta_order')->nullable();
            $table->string('youtube_video_order')->nullable();
            $table->string('upload_video_order')->nullable();


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_settings');
    }
};
