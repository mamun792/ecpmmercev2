<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Amazon Style Stock Reservation System
     * Prevents overselling by reserving stock for carts and pending orders
     * Supports automatic expiration and cleanup
     */
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();

            // Inventory Reference
            $table->foreignId('inventory_stock_id')->constrained('inventory_stocks')->onDelete('cascade');

            // Product References (for quick lookups)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();

            // Reservation Details
            $table->string('reserved_for_type', 50); // 'cart', 'order', 'manual'
            $table->bigInteger('reserved_for_id');    // ID of cart/order/etc
            $table->integer('quantity');             // Quantity reserved

            // Time Management
            $table->timestamp('reserved_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();        // Auto-expiry time
            $table->timestamp('fulfilled_at')->nullable();      // When reservation was used
            $table->timestamp('cancelled_at')->nullable();      // When reservation was cancelled

            // Status Tracking
            $table->enum('status', [
                'active',     // Currently reserved
                'fulfilled',  // Used for order
                'expired',    // Time expired
                'cancelled',  // Manually cancelled
                'partial'     // Partially fulfilled
            ])->default('active');

            // Fulfillment Tracking
            $table->integer('fulfilled_quantity')->default(0);
            $table->integer('remaining_quantity')->storedAs('quantity - fulfilled_quantity');

            // Context Information
            $table->string('reason', 255)->nullable();          // Why reserved
            $table->json('context')->nullable();                // Additional context data

            // Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source', 100)->default('cart');     // Source of reservation

            $table->timestamps();

            // Indexes for Performance
            $table->index(['reserved_for_type', 'reserved_for_id'], 'reservation_lookup');
            $table->index(['status', 'expires_at'], 'active_reservations');
            $table->index(['inventory_stock_id', 'status'], 'stock_reservations');
            $table->index(['product_id', 'status'], 'product_reservations');
            $table->index('expires_at'); // For cleanup jobs
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
