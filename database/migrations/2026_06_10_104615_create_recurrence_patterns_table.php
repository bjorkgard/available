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
        Schema::create('recurrence_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('congregation_id')->constrained('congregations')->cascadeOnDelete();
            $table->string('frequency', 10);
            $table->date('end_date')->nullable();
            $table->integer('end_count')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurrence_patterns');
    }
};
