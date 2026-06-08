<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Restructures the users table:
     * - Converts `id` from auto-increment integer to UUID
     * - Renames `current_team_id` to `current_congregation_id` (foreignUuid)
     * - Updates the sessions table FK to reference the new UUID column
     */
    public function up(): void
    {
        // 1. Rebuild users table with UUID primary key and renamed FK
        $users = DB::table('users')->get();

        // Drop the sessions table that references users (will be recreated)
        Schema::dropIfExists('sessions');

        // Drop the old users table
        Schema::dropIfExists('users');

        // Recreate users table with UUID PK and current_congregation_id
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignUuid('current_congregation_id')
                ->nullable()
                ->references('id')
                ->on('congregations')
                ->nullOnDelete();
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();
        });

        // Re-insert users with new UUIDs
        foreach ($users as $user) {
            DB::table('users')->insert([
                'id' => Str::uuid7()->toString(),
                'name' => $user->name,
                'email' => $user->email,
                'email_verified_at' => $user->email_verified_at,
                'password' => $user->password,
                'current_congregation_id' => null,
                'remember_token' => $user->remember_token,
                'two_factor_secret' => $user->two_factor_secret ?? null,
                'two_factor_recovery_codes' => $user->two_factor_recovery_codes ?? null,
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at ?? null,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]);
        }

        // Recreate sessions table with UUID FK
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $users = DB::table('users')->get();

        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->foreignId('current_team_id')
                ->nullable()
                ->constrained('teams')
                ->nullOnDelete();
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }
};
