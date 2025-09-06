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
        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->enum('demo_type', ['general', 'enterprise', 'specific-feature', 'custom'])->default('general');
            $table->datetime('preferred_datetime');
            $table->string('timezone')->default('Africa/Kampala');
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled', 'rescheduled'])->default('pending');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id')->nullable(); // For guest users via chat
            $table->enum('source', ['manual', 'chatbot', 'api'])->default('manual');
            $table->json('metadata')->nullable(); // Additional data from chatbot
            $table->datetime('confirmed_datetime')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['status', 'preferred_datetime']);
            $table->index(['email', 'status']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_requests');
    }
};
