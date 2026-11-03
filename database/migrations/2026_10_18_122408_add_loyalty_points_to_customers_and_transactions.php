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
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('status');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('points_earned')->default(0)->after('discount_note');
            $table->integer('points_redeemed')->default(0)->after('points_earned');
            $table->integer('points_discount_amount')->default(0)->after('points_redeemed');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['points_earned', 'points_redeemed', 'points_discount_amount']);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }
};
