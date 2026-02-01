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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->string('session_id')->nullable();
            $table->enum('status', ['pending', 'processing', 'cancelled', 'shipped', 'delivered', 'returned', 'incomplete', 'on_hold', 'confirmed'])->default('pending');

            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_name');
            $table->text('shipping_address');
            $table->string('shipping_district')->nullable();
            $table->string('area')->nullable();
            $table->foreignId('cart_id')->nullable()->constrained('carts')->nullOnDelete();

            $table->decimal('shipping_cost', 10, 2)->default(0.00);
            $table->decimal('subtotal', 10, 2)->default(0.00);
            $table->decimal('discount_total', 10, 2)->default(0.00);
            $table->decimal('pos_discount', 10, 2)->default(0.00);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'paid', 'refunded', 'failed'])->default('unpaid');
            $table->string('payment_method');
            $table->string('transaction_id')->nullable();
            $table->string('shipping_method')->nullable();
            $table->string('tracking_number')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();

            //courier
            $table->unsignedBigInteger('city_id')->nullable();
            $table->unsignedBigInteger('zone_id')->nullable();
            $table->unsignedBigInteger('area_id')->nullable();
            $table->string('courier_name')->nullable();
            $table->string('city_name')->nullable();
            $table->string('zone_name')->nullable();
            $table->string('area_name')->nullable();
            $table->boolean('is_courier')->default(false);
            $table->string('consignment_id')->nullable();
            $table->string('delivery_status')->nullable();
            // indexes
            $table->index('order_number');
            $table->index('user_id');
            $table->index('session_id');
            $table->index('status');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
