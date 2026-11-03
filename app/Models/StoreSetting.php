<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreSetting extends Model
{
    protected $fillable = ['shop_name', 'address', 'phone', 'receipt_footer'];

    // Ambil pengaturan toko aktif (otomatis buat default jika tabel masih kosong)
    public static function current()
    {
        return self::firstOrCreate(
            ['id' => 1],
            [
                'shop_name' => 'CANTIKA AKSESORIS',
                'address' => 'Jl. Siliwangi No. 123, Cirebon',
                'phone' => '0812-3456-7890',
                'receipt_footer' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan kecuali ada perjanjian.'
            ]
        );
    }
}