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
        Schema::create('bookings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('congregation_id')->constrained('congregations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 255);
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->foreignUuid('recurrence_pattern_id')->nullable()->constrained('recurrence_patterns')->nullOnDelete();
            $table->boolean('is_exception')->default(false);
            $table->dateTime('original_starts_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
