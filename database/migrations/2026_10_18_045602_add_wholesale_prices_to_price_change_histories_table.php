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
            $table->integer('old_wholesale_price')->nullable()->after('new_retail_price');
            $table->integer('new_wholesale_price')->nullable()->after('old_wholesale_price');
        });
    }

    public function down(): void
    {
        Schema::table('price_change_histories', function (Blueprint $table) {
            $table->dropColumn(['old_wholesale_price', 'new_wholesale_price']);
        });
    }
};
