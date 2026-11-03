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
        // 1. Tabel Retur Penjualan (Customer -> Toko)
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique(); // Format: RET-JUAL-YYYYMMDD-XXXX
            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Kasir/Admin yang memproses
            $table->enum('action_type', ['refund', 'replace'])->default('replace'); // Refund uang atau Tukar barang
            $table->integer('total_refund_amount')->default(0); // Jika opsi refund uang
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products'); // Barang yang dikembalikan (rusak/salah)
            $table->integer('qty');
            $table->integer('price_per_item');
            $table->integer('subtotal');
            $table->foreignId('replacement_product_id')->nullable()->constrained('products'); // Barang pengganti jika opsi tukar
            $table->timestamps();
        });

        // 2. Tabel Retur Pembelian (Toko -> Supplier)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->string('return_number')->unique(); // Format: RET-BELI-YYYYMMDD-XXXX
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers');
            $table->foreignId('user_id')->constrained('users');
            $table->integer('total_amount')->default(0);
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->integer('qty');
            $table->integer('cost_price');
            $table->integer('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_details');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sale_return_details');
        Schema::dropIfExists('sale_returns');
    }
};
