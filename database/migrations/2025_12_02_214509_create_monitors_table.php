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
        Schema::create('monitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('url', 2048);
            $table->integer('check_interval_minutes')->default(5);
            $table->enum('status', ['up', 'down', 'pending'])->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_status_change_at')->nullable();
            $table->timestamps();

            // Indexes for performance optimization
            $table->index('user_id');
            $table->index('status');
            $table->index('last_checked_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monitors');
    }
};
