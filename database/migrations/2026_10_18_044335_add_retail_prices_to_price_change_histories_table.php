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
        Schema::table('price_change_histories', function (Blueprint $table) {
            $table->integer('old_retail_price')->nullable()->after('new_price');
            $table->integer('new_retail_price')->nullable()->after('old_retail_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('price_change_histories', function (Blueprint $table) {
            //
        });
    }
};
