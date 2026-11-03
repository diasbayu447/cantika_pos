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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Contoh: TRX-20261017-0001
            $table->foreignId('user_id')->constrained('users'); // Kasir yang bertugas
            $table->foreignId('customer_id')->nullable()->constrained('customers'); // Bisa null jika pelanggan umum
            
            $table->enum('payment_method', ['cash', 'qris', 'transfer', 'split'])->default('cash');
            $table->integer('total_amount')->default(0); // Total belanja
            $table->integer('cash_amount')->default(0); // Uang tunai yang diterima (untuk laci)
            $table->integer('non_cash_amount')->default(0); // Uang dari QRIS/Transfer (masuk rekening)
            $table->integer('change_amount')->default(0); // Kembalian
            
            $table->enum('status', ['success', 'void'])->default('success'); // Void jika dibatalkan supervisor
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
