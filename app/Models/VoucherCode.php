<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoucherCode extends Model
{
    protected $fillable = ['discount_id', 'code', 'is_used', 'used_at', 'transaction_id'];

    public function discount() { return $this->belongsTo(Discount::class); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}