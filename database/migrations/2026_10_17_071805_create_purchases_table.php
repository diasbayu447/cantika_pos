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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Nomor faktur asli dari supplier
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Admin/Owner yang menginput
            $table->date('purchase_date'); // Tanggal barang datang
            $table->enum('payment_method', ['cash', 'tempo'])->default('cash'); // Lunas atau Utang
            $table->date('due_date')->nullable(); // Batas waktu jika tempo
            $table->integer('total_amount')->default(0); // Total nilai faktur
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
