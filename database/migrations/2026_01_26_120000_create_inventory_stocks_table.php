<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Big Tech Style Centralized Inventory Management
     * This table serves as the SINGLE SOURCE OF TRUTH for all stock data
     * Replaces scattered stock fields across product/variation tables
     */
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();

            // Product Reference (Required)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');

            // Location Support (Multi-warehouse ready)
            $table->string('location_code', 50)->default('MAIN');
            $table->string('location_name')->default('Main Warehouse');

            // Stock Quantities (Amazon/Netflix Style)
            $table->integer('available_quantity')->default(0); // Available for sale
            $table->integer('reserved_quantity')->default(0);  // Reserved for carts/pending orders
            $table->integer('total_quantity')->storedAs('available_quantity + reserved_quantity'); // Computed column

            // Stock Thresholds (Business Logic)
            $table->integer('minimum_threshold')->default(0);   // Low stock alert
            $table->integer('maximum_threshold')->nullable();   // Max stock limit
            $table->integer('reorder_point')->default(0);       // Automatic reorder trigger
            $table->integer('reorder_quantity')->default(0);    // Default reorder amount

            // Cost Tracking
            $table->decimal('average_cost_price', 10, 2)->default(0.00);
            $table->decimal('last_cost_price', 10, 2)->default(0.00);

            // Status & Control
            $table->enum('status', ['active', 'inactive', 'discontinued'])->default('active');
            $table->boolean('track_inventory')->default(true);  // Enable/disable inventory tracking

            // Audit Fields
            $table->integer('adjustment_count')->default(0);    // Count of manual adjustments
            $table->timestamp('last_movement_at')->nullable(); // Last stock movement timestamp
            $table->foreignId('last_updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Indexes for Performance (Big Tech Style)
            $table->unique(['product_id', 'product_variation_id', 'location_code'], 'unique_product_location');
            $table->index(['location_code', 'status']);
            $table->index(['available_quantity', 'minimum_threshold'], 'low_stock_check');
            $table->index('last_movement_at');
            $table->index('track_inventory');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
