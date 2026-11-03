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
        // 1. Tambah kolom lingkup diskon & pencatatan diskon transaksi
        Schema::table('discounts', function (Blueprint $table) {
            $table->enum('scope', ['cart', 'product'])->default('cart')->after('type'); // Cart total vs Per Produk
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->integer('discount_amount')->default(0)->after('total_amount');
            $table->string('discount_note')->nullable()->after('discount_amount');
        });

        Schema::table('transaction_details', function (Blueprint $table) {
            $table->integer('discount_amount')->default(0)->after('selling_price'); // Potongan per item (cuci gudang)
        });

        // 2. Tabel kupon unik sekali pakai
        Schema::create('voucher_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->boolean('is_used')->default(false);
            $table->timestamp('used_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->timestamps();
        });

        // 3. Tabel produk cuci gudang
        Schema::create('discount_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_products');
        Schema::dropIfExists('voucher_codes');
        Schema::table('transaction_details', function (Blueprint $table) { $table->dropColumn('discount_amount'); });
        Schema::table('transactions', function (Blueprint $table) { $table->dropColumn(['discount_amount', 'discount_note']); });
        Schema::table('discounts', function (Blueprint $table) { $table->dropColumn('scope'); });
    }
};
