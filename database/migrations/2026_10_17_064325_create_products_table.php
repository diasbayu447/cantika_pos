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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Relasi ke tabel categories. Jika kategori dihapus, produk tidak hilang (null)
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            
            $table->string('barcode')->unique()->nullable();
            $table->string('name');
            $table->enum('product_type', ['physical', 'service', 'digital'])->default('physical'); // Fisik, Jasa, atau Digital/Pulsa
            $table->string('rack_location')->nullable(); // Posisi rak atau laci
            
            // Struktur Harga & Stok
            $table->integer('cost_price')->default(0); // Harga Modal terkini
            $table->integer('retail_price')->default(0); // Harga Eceran (Normal)
            $table->integer('wholesale_price')->default(0); // Harga Grosir
            $table->integer('min_wholesale_qty')->default(0); // Syarat minimal beli untuk dapat harga grosir
            $table->integer('stock')->default(0); // Stok fisik
            $table->integer('min_stock_alert')->default(0); // Batas peringatan stok menipis
            
            $table->boolean('is_active')->default(true); // Fitur soft delete (arsip) produk
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
