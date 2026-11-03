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
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Kasir yang bertugas
            $table->integer('start_cash')->default(0); // Modal awal laci
            $table->integer('end_cash_system')->default(0); // Total uang tunai menurut komputer
            $table->integer('end_cash_actual')->default(0); // Total uang tunai hasil hitung fisik
            $table->integer('difference')->default(0); // Selisih (minus/lebih)
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
