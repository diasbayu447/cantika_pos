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
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Staf yang melakukan pengecekan
            
            $table->integer('system_stock'); // Stok di komputer sebelum disesuaikan
            $table->integer('actual_stock'); // Stok fisik sebenarnya di rak
            $table->integer('difference'); // Selisih (actual - system)
            
            $table->string('reason'); // Alasan (misal: Barang hilang, rusak, salah input)
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete(); // Supervisor yang menyetujui (via PIN)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
