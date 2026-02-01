<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Google Docs Style Order Edit Locking
     * Prevents concurrent edits and race conditions
     * Supports automatic expiration and force unlock
     */
    public function up(): void
    {
        Schema::create('order_edit_locks', function (Blueprint $table) {
            $table->id();

            // Order Reference (Unique lock per order)
            $table->foreignId('order_id')->unique()->constrained('orders')->onDelete('cascade');

            // Lock Owner
            $table->foreignId('locked_by')->constrained('users')->onDelete('cascade');
            $table->string('locked_by_name');  // Backup name for display
            $table->string('locked_by_email'); // Backup email for notifications

            // Lock Details
            $table->string('lock_type', 50)->default('editing'); // 'editing', 'processing', 'shipping', 'fulfilling'
            $table->text('lock_reason')->nullable();             // Why is it locked

            // Time Management
            $table->timestamp('locked_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();                     // Auto-expire time
            $table->timestamp('last_activity_at')->useCurrent(); // Updated on activity

            // Lock Context
            $table->string('source', 100)->default('admin_panel'); // Where lock originated
            $table->string('session_id')->nullable();              // Browser session
            $table->json('context_data')->nullable();              // Additional context

            // Status
            $table->enum('status', ['active', 'expired', 'released'])->default('active');
            $table->boolean('force_released')->default(false);     // Was force unlocked

            // Activity Tracking
            $table->integer('activity_count')->default(1);         // Number of activities
            $table->timestamp('first_locked_at')->useCurrent();    // Original lock time

            $table->timestamps();

            // Indexes
            $table->index(['status', 'expires_at'], 'active_locks');
            $table->index(['locked_by', 'status'], 'user_locks');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_edit_locks');
    }
};
