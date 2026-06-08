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
        Schema::create('kingdom_halls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('street_address', 255);
            $table->string('zip_code', 20);
            $table->string('city', 100);
            $table->integer('number_of_rooms');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kingdom_halls');
    }
};
