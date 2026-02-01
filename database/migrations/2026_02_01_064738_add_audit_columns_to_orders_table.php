<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds audit and tracking columns to orders table
     * Only adds columns that don't already exist
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Audit columns - only add if not exists
            if (!Schema::hasColumn('orders', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('created_at');
            }

            if (!Schema::hasColumn('orders', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('updated_at');
            }

            // Source tracking
            if (!Schema::hasColumn('orders', 'order_source')) {
                $table->enum('order_source', ['website', 'pos', 'api', 'manual', 'import'])->default('website')->after('session_id');
            }
        });

        // Add indexes if they don't exist
        try {
            Schema::table('orders', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('orders');

                if (!isset($indexes['orders_status_created_at_index'])) {
                    $table->index(['status', 'created_at']);
                }

                if (!isset($indexes['orders_payment_status_created_at_index'])) {
                    $table->index(['payment_status', 'created_at']);
                }

                if (!isset($indexes['orders_customer_phone_index'])) {
                    $table->index('customer_phone');
                }
            });
        } catch (\Exception $e) {
            \Log::info('Index creation skipped: ' . $e->getMessage());
        }

        // Create Order Status History Table if not exists
        if (!Schema::hasTable('order_status_histories')) {
            Schema::create('order_status_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('previous_status')->nullable();
                $table->string('new_status');
                $table->string('changed_by_type')->default('user');
                $table->unsignedBigInteger('changed_by_id')->nullable();
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
            });
        }

        // Create Order Edit Audit Log Table if not exists
        if (!Schema::hasTable('order_edit_logs')) {
            Schema::create('order_edit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->string('field_name');
                $table->text('old_value')->nullable();
                $table->text('new_value')->nullable();
                $table->unsignedBigInteger('edited_by')->nullable();
                $table->string('edit_reason')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamps();

                $table->index(['order_id', 'created_at']);
                $table->index('field_name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_edit_logs');
        Schema::dropIfExists('order_status_histories');

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'created_by')) {
                $table->dropColumn('created_by');
            }

            if (Schema::hasColumn('orders', 'updated_by')) {
                $table->dropColumn('updated_by');
            }

            if (Schema::hasColumn('orders', 'order_source')) {
                $table->dropColumn('order_source');
            }
        });
    }
};
