<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Netflix Style Audit Trail System
     * Every stock movement is tracked for complete accountability
     * Supports complex inventory reconciliation and analytics
     */
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();

            // Inventory Reference
            $table->foreignId('inventory_stock_id')->constrained('inventory_stocks')->onDelete('cascade');

            // Product References (Denormalized for performance)
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('product_variation_id')->nullable()->constrained('product_variations')->nullOnDelete();

            // Transaction Type (Comprehensive Coverage)
            $table->enum('transaction_type', [
                'purchase',        // Stock incoming from supplier
                'sale',           // Stock outgoing to customer
                'adjustment',     // Manual stock adjustment
                'transfer',       // Inter-location transfer
                'return',         // Customer return
                'damage',         // Damaged/expired stock
                'reservation',    // Cart reservation
                'release',        // Reservation release
                'initial'         // Initial stock setup
            ]);

            // Quantity Changes
            $table->integer('quantity_change');      // +ve for increase, -ve for decrease
            $table->integer('quantity_before');      // Stock before transaction
            $table->integer('quantity_after');       // Stock after transaction

            // Reference Information (Polymorphic)
            $table->string('reference_type', 100)->nullable(); // 'order', 'manual', 'import', 'cart'
            $table->bigInteger('reference_id')->nullable();     // ID of referenced entity
            $table->string('reference_number', 100)->nullable(); // Human readable reference

            // Financial Data
            $table->decimal('unit_cost', 10, 2)->nullable();     // Cost per unit at transaction time
            $table->decimal('total_cost', 10, 2)->nullable();    // Total cost impact

            // Location Info
            $table->string('location_code', 50)->default('MAIN');
            $table->string('from_location', 50)->nullable();     // For transfers
            $table->string('to_location', 50)->nullable();       // For transfers

            // Details & Audit
            $table->string('reason', 255)->nullable();           // Short reason
            $table->text('notes')->nullable();                   // Detailed notes
            $table->json('metadata')->nullable();                // Additional context data

            // User Audit
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_by_type', 50)->default('user'); // 'user', 'system', 'api'
            $table->string('source', 100)->default('manual');       // 'manual', 'order', 'import', 'api'

            $table->timestamps();

            // Indexes for Analytics & Performance
            $table->index(['inventory_stock_id', 'created_at'], 'stock_timeline');
            $table->index(['product_id', 'transaction_type', 'created_at'], 'product_movements');
            $table->index(['reference_type', 'reference_id'], 'reference_lookup');
            $table->index(['transaction_type', 'created_at'], 'type_timeline');
            $table->index(['location_code', 'created_at'], 'location_timeline');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
