<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockAdjustment extends Model
{
    // Daftarkan semua kolom yang diizinkan untuk disimpan
    protected $fillable = [
        'product_id', 
        'user_id', 
        'system_stock', 
        'actual_stock', 
        'difference', 
        'reason', 
        'authorized_by'
    ];

    // Relasi ke Master Barang
    public function product() 
    { 
        return $this->belongsTo(Product::class); 
    }

    // Relasi ke User (Staf yang input)
    public function user() 
    { 
        return $this->belongsTo(User::class, 'user_id'); 
    }

    // Relasi ke User (Supervisor yang memberi otorisasi)
    public function supervisor() 
    { 
        return $this->belongsTo(User::class, 'authorized_by'); 
    }
}