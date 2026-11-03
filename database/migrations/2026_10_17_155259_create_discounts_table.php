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
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Contoh: "Voucher Pelanggan Baru" atau "Promo Lebaran"
            $table->string('code')->nullable()->unique(); // Contoh: "CANTIKA10K" (Bisa kosong jika ini diskon periode)
            $table->enum('type', ['voucher', 'period'])->default('voucher'); 
            $table->enum('discount_type', ['nominal', 'percentage'])->default('nominal'); // Potongan Rp atau %
            $table->integer('amount'); // Nominal potongan (misal: 10000 atau 10%)
            $table->integer('min_purchase')->default(0); // Syarat minimal total belanja
            
            $table->date('start_date')->nullable(); // Tanggal mulai berlaku
            $table->date('end_date')->nullable(); // Tanggal berakhir
            $table->integer('quota')->nullable(); // Batas kuota pemakaian (khusus voucher)
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
