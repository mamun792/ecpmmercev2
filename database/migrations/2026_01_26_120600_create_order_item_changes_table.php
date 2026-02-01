<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Comprehensive Order Item Change Tracking
     * Tracks quantity changes, price adjustments, and item modifications
     * Essential for audit compliance and dispute resolution
     */
    public function up(): void
    {
        Schema::create('order_item_changes', function (Blueprint $table) {
            $table->id();

            // References
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();

            // Change Type
            $table->enum('change_type', [
                'quantity_change',  // Quantity modified
                'price_change',     // Price adjusted
                'discount_change',  // Discount modified
                'item_added',       // New item added
                'item_removed',     // Item deleted
                'item_substituted', // Product substitution
                'status_change'     // Item status change
            ]);

            // Before/After Values
            $table->json('before_data')->nullable(); // Data before change
            $table->json('after_data');              // Data after change

            // Quantity Tracking
            $table->integer('quantity_before')->nullable();
            $table->integer('quantity_after')->nullable();
            $table->integer('quantity_difference')->storedAs('quantity_after - COALESCE(quantity_before, 0)');

            // Price Tracking
            $table->decimal('price_before', 10, 2)->nullable();
            $table->decimal('price_after', 10, 2)->nullable();
            $table->decimal('price_difference', 10, 2)->storedAs('price_after - COALESCE(price_before, 0)');

            // Financial Impact
            $table->decimal('total_impact', 10, 2)->default(0); // Net financial impact of change
            $table->string('impact_reason')->nullable();        // Why there's financial impact

            // Context
            $table->string('reason', 255)->nullable();          // Reason for change
            $table->text('notes')->nullable();                  // Detailed notes
            $table->boolean('customer_approved')->default(false); // Customer approved the change

            // Audit
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_type', 50)->default('user'); // 'user', 'system', 'customer'
            $table->string('source', 100)->default('admin_panel');  // Source of change

            // Approval Workflow
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('approved');

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'created_at'], 'order_change_timeline');
            $table->index(['order_item_id', 'change_type'], 'item_changes');
            $table->index(['change_type', 'created_at'], 'change_analytics');
            $table->index(['changed_by', 'created_at'], 'user_changes');
            $table->index('approval_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_item_changes');
    }
};
