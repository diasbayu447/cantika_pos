<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceChangeHistory extends Model
{
    protected $fillable = [
    'product_id', 
    'purchase_id', 
    'user_id', 
    'old_price', 
    'new_price', 
    'old_retail_price', 
    'new_retail_price',
    'old_wholesale_price',
    'new_wholesale_price'
];

    public function product() { return $this->belongsTo(Product::class); }
    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function user() { return $this->belongsTo(User::class); }
}