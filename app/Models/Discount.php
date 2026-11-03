<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $fillable = [
        'name', 'type', 'scope', 'code', 'discount_type', 
        'amount', 'min_purchase', 'quota', 'start_date', 'end_date', 'is_active'
    ];

    public function voucherCodes() { return $this->hasMany(VoucherCode::class); }
    public function products() { return $this->belongsToMany(Product::class, 'discount_products'); }
}