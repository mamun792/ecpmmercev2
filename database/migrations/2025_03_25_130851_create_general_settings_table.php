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
        Schema::create('general_settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->nullable();
            $table->string('home_page_title')->nullable();
            $table->string('product_page_title')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('address')->nullable();
            $table->string('store_phone_number')->nullable();
            $table->string('store_email')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('x_url')->nullable();
            $table->decimal('shipping_charge_inside_dhaka',10,2)->nullable();
            $table->decimal('shipping_charge_outside_dhaka',10,2)->nullable();
            $table->text('attention_notice')->nullable();
            $table->text('pre_order_notice')->nullable();
            $table->text('top_notice')->nullable();
            $table->text('facebook_iframe')->nullable();
            $table->text('facebook_page_id')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('primary_color')->default('#f0512e')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('general_settings');
    }
};
