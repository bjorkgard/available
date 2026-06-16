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
            $table->unique(
                ['street_address', 'zip_code', 'city', 'country'],
                'kingdom_halls_address_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kingdom_halls', function (Blueprint $table) {
            $table->dropUnique('kingdom_halls_address_unique');
        });
    }
};
