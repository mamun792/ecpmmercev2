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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('product_code')->unique();
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('brand_id')->nullable();
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->boolean('is_daily_product')->default(0);
            $table->enum('status', ['Published', 'Unpublished'])->default('Published');
            $table->enum('type', ['simple', 'variable'])->default('simple');
            $table->decimal('price', 10, 2)->default(0.00);
            $table->decimal('previous_price', 10, 2)->nullable();
            $table->string('feature_image')->nullable();
            $table->string('youtube_video')->nullable();
            $table->string('upload_video')->nullable();
            $table->json('gallery_images')->nullable();
            $table->json('description_images')->nullable();
            $table->json('product_tags')->nullable();
            $table->json('specification')->nullable();
            $table->integer('stock')->default(0);
            $table->integer('sold_stock')->nullable()->default(0);
            $table->integer('view_count')->default(0);
            $table->boolean('is_free_delivery')->default(0);
            $table->boolean('is_pre_order')->default(0);
            $table->enum('remarks', ['New', 'Popular', 'Trending', 'Hot', 'Special'])->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
