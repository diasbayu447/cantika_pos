<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'phone', 'address', 'status', 'points'];

    // Relasi ke tabel Transactions (1 Pelanggan punya banyak transaksi belanja)
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}