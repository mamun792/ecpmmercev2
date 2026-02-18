<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enterprise Grade Order Status Audit Trail
     * Tracks every status change for compliance and customer service
     * Supports automated and manual status transitions
     *
     * NOTE: This migration is disabled. Using order_status_histories (plural) instead.
     * See migration: 2026_02_01_064738_add_audit_columns_to_orders_table.php
     */
    public function up(): void
    {
        // Disabled - using order_status_histories (plural) table instead
        return;

        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();

            // Order Reference
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // Status Transition
            $table->string('from_status', 50)->nullable(); // Previous status (null for first status)
            $table->string('to_status', 50);              // New status

            // Transition Context
            $table->string('transition_reason', 255)->nullable(); // Brief reason
            $table->text('notes')->nullable();                   // Detailed notes
            $table->boolean('is_automated')->default(false);     // System vs manual change
            $table->string('trigger_source', 100)->nullable();   // 'admin_panel', 'api', 'webhook', 'cron'

            // User Context
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('changed_by_type', 50)->default('user'); // 'user', 'system', 'customer'
            $table->string('changed_by_name')->nullable();          // Backup name if user deleted

            // Additional Context
            $table->json('context_data')->nullable(); // Extra data (payment info, shipping details, etc)
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Notification Tracking
            $table->boolean('customer_notified')->default(false);
            $table->timestamp('notification_sent_at')->nullable();

            $table->timestamps();

            // Indexes for Performance
            $table->index(['order_id', 'created_at'], 'order_status_timeline');
            $table->index(['to_status', 'created_at'], 'status_changes');
            $table->index(['changed_by', 'created_at'], 'user_changes');
            $table->index(['is_automated', 'created_at'], 'automation_audit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
