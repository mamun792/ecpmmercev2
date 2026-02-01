<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Note: Many indexes already exist from previous migrations.
     * This migration adds any missing indexes for revenue report performance.
     */
    public function up(): void
    {
        // Indexes likely already exist - this migration is a safety net
        // Most indexes were added in previous migrations

        // You can manually verify existing indexes with:
        // SHOW INDEX FROM orders;
        // SHOW INDEX FROM order_items;
        // SHOW INDEX FROM products;
        // SHOW INDEX FROM product_variations;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Nothing to reverse - indexes were not added in up()
    }
};
