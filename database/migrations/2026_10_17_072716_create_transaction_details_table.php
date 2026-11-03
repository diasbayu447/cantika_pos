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
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            
            $table->integer('qty');
            $table->integer('cost_price'); // PENTING: Kunci harga modal (HPP) saat ini agar laba tidak berubah jika besok harga modal naik
            $table->integer('selling_price'); // Harga jual yang didapat (eceran / grosir)
            $table->boolean('is_wholesale')->default(false); // Penanda apakah item ini dapat harga grosir
            $table->integer('subtotal'); // qty * selling_price
            $table->string('note')->nullable(); // Catatan khusus (contoh: No HP untuk pulsa)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_details');
    }
};
