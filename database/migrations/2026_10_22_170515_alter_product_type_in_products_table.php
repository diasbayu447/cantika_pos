<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ubah kolom product_type menjadi VARCHAR(50) agar menerima tipe prabayar & pascabayar
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE products MODIFY COLUMN product_type VARCHAR(50) NOT NULL DEFAULT 'physical'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE products MODIFY COLUMN product_type ENUM('physical', 'digital', 'service') NOT NULL DEFAULT 'physical'");
    }
};