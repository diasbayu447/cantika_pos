<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturnDetail extends Model
{
    protected $fillable = ['sale_return_id', 'product_id', 'qty', 'price_per_item', 'subtotal', 'replacement_product_id'];

    public function product() { return $this->belongsTo(Product::class, 'product_id'); }
    public function replacementProduct() { return $this->belongsTo(Product::class, 'replacement_product_id'); }
}