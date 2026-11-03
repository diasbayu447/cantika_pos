<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnDetail extends Model
{
    protected $fillable = ['purchase_return_id', 'product_id', 'qty', 'cost_price', 'subtotal'];

    public function product() { return $this->belongsTo(Product::class); }
}