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
        Schema::table('congregation_invitations', function (Blueprint $table) {
            $table->string('locale', 5)->default('sv')->after('invited_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('congregation_invitations', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
