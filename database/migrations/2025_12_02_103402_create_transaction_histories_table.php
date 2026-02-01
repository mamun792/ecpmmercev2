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
        Schema::create('transaction_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique(); // PayStation transaction ID
            $table->string('invoice_number')->nullable(); // PayStation invoice number
            $table->string('order_number')->nullable(); // Our order number
            $table->unsignedBigInteger('order_id')->nullable(); // Reference to orders table
            $table->unsignedBigInteger('user_id')->nullable(); // User who made the payment
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BDT');
            $table->string('payment_method')->nullable(); // bKash, Nagad, etc.
            $table->string('payment_gateway')->default('paystation'); // Gateway name
            $table->enum('status', ['pending', 'processing', 'success', 'failed', 'cancelled'])->default('pending');
            $table->json('gateway_response')->nullable(); // Store full gateway response
            $table->text('notes')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->timestamps();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['status', 'transaction_date']);
            $table->index(['user_id', 'transaction_date']);
            $table->index('order_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_histories');
    }
};
