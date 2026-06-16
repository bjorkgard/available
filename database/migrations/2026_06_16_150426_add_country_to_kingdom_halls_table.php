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
        Schema::table('kingdom_halls', function (Blueprint $table) {
            $table->string('country', 100)->default('Sverige')->after('city');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdom_halls', function (Blueprint $table) {
            $table->dropColumn('country');
        });
    }
};
