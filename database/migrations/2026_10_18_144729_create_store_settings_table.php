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
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('shop_name')->default('CANTIKA AKSESORIS');
            $table->string('address')->default('Jl. Siliwangi No. 123, Cirebon');
            $table->string('phone')->default('0812-3456-7890');
            $table->string('receipt_footer')->default('Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
