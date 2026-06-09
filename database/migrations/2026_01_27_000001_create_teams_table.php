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
        Schema::create('congregations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('congregation_number', 20)->unique();
            $table->foreignUuid('kingdom_hall_id')->nullable()->constrained('kingdom_halls')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('congregation_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('congregation_id')->constrained('congregations')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role');
            $table->timestamps();

            $table->unique(['congregation_id', 'user_id']);
        });

        Schema::create('congregation_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->foreignUuid('congregation_id')->constrained('congregations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('email', 255);
            $table->string('role');
            $table->foreignUuid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });

        // Add the FK constraint on users now that congregations table exists
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('current_congregation_id')
                ->references('id')
                ->on('congregations')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['current_congregation_id']);
        });

        Schema::dropIfExists('congregation_invitations');
        Schema::dropIfExists('congregation_members');
        Schema::dropIfExists('congregations');
    }
};
