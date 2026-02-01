<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Update Foreign Key Constraints for Better Data Integrity
     * Prevents orphaned records and handles soft deletes properly
     * Follows big tech practices for referential integrity
     */
    public function up(): void
    {
        // Update Cart Items foreign key constraints
        Schema::table('cart_items', function (Blueprint $table) {
            // Drop existing foreign key constraints
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);

            // Add new constraints with proper cascade behavior
            // Products: RESTRICT delete if cart items exist (prevent accidental deletion)
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('restrict'); // Prevent product deletion if in carts

            // Product Variations: SET NULL (cart can exist without variation)
            $table->foreign('product_variation_id')
                  ->references('id')->on('product_variations')
                  ->onDelete('set null'); // Allow variation deletion, set cart item to null
        });

        // Update Order Items foreign key constraints
        Schema::table('order_items', function (Blueprint $table) {
            // Drop existing constraints
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);

            // Products: RESTRICT (historical orders must preserve product reference)
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('restrict'); // Never allow product deletion if orders exist

            // Product Variations: RESTRICT (preserve historical order data)
            $table->foreign('product_variation_id')
                  ->references('id')->on('product_variations')
                  ->onDelete('restrict'); // Never allow variation deletion if orders exist
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore original constraints
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);

            // Restore original constraints
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('product_variation_id')
                  ->references('id')->on('product_variations')
                  ->onDelete('set null');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['product_variation_id']);

            // Restore original constraints
            $table->foreign('product_id')
                  ->references('id')->on('products')
                  ->onDelete('cascade');

            $table->foreign('product_variation_id')
                  ->references('id')->on('product_variations')
                  ->onDelete('cascade');
        });
    }
};
