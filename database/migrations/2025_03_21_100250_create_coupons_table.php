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
        Schema::create(
            'coupons',
            function (Blueprint $table) {
                $table->id();
                $table->string('code', 50)->unique();
                $table->string('name');
                $table->decimal('discount_value', 10, 2);
                $table->enum('discount_type', ['percentage', 'fixed']);
                $table->integer('max_uses')->nullable();
                $table->integer('uses_count')->default(0);
                $table->timestamp('start_date')->useCurrent();
                $table->timestamp('expiry_date')->nullable();
                $table->string('festival_name')->nullable();
                $table->boolean('apply_to_all_products')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            }
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
