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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            // foreignId merelasikan log ke tabel users. nullOnDelete agar log tetap ada meski user dihapus permanen.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); 
            $table->string('action'); // Contoh: "Void Transaksi", "Login", "Tambah Barang"
            $table->text('description'); // Detail aksi lengkap
            $table->string('ip_address')->nullable(); // Rekam IP untuk keamanan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
