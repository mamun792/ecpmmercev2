<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shopify Style Product Options System
     * Supports complex product variations with multiple option types
     * Better than the current attribute system for UI/UX
     */
    public function up(): void
    {
        // Product Options (Color, Size, Material, etc.)
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');

            $table->string('name', 100);           // "Color", "Size", "Material"
            $table->string('display_name', 150);   // "Choose Color", "Select Size"
            $table->integer('position')->default(0); // Order of display

            $table->enum('input_type', [
                'select',     // Dropdown
                'radio',      // Radio buttons
                'checkbox',   // Checkboxes
                'color',      // Color picker
                'text',       // Text input
                'textarea'    // Text area
            ])->default('select');

            $table->boolean('required')->default(true);
            $table->json('validation_rules')->nullable(); // Custom validation
            $table->json('display_settings')->nullable(); // UI specific settings

            $table->timestamps();

            $table->index(['product_id', 'position'], 'product_options_order');
            $table->unique(['product_id', 'name'], 'unique_product_option');
        });

        // Option Values (Red, Blue, Small, Large, etc.)
        Schema::create('product_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_option_id')->constrained('product_options')->onDelete('cascade');

            $table->string('value', 150);          // "Red", "Small", "Cotton"
            $table->string('display_value', 200);  // "Bright Red", "Small (S)", "100% Cotton"
            $table->integer('position')->default(0); // Sort order

            // Visual Properties
            $table->string('color_hex', 7)->nullable();    // #FF0000
            $table->string('image_url', 500)->nullable();   // Option specific image
            $table->decimal('price_modifier', 10, 2)->default(0); // +/- price

            // Availability
            $table->boolean('is_available')->default(true);
            $table->integer('stock_influence')->default(0); // How it affects stock calculation

            $table->timestamps();

            $table->index(['product_option_id', 'position'], 'option_values_order');
            $table->unique(['product_option_id', 'value'], 'unique_option_value');
        });

        // Variation to Option Values mapping (many-to-many)
        Schema::create('product_variation_option_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variation_id')->constrained('product_variations')->onDelete('cascade');
            $table->foreignId('product_option_value_id')->constrained('product_option_values')->onDelete('cascade');

            $table->timestamps();

            $table->unique(['product_variation_id', 'product_option_value_id'], 'unique_variation_option');
            $table->index('product_variation_id', 'variation_options');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variation_option_values');
        Schema::dropIfExists('product_option_values');
        Schema::dropIfExists('product_options');
    }
};
