<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'barcode', 'name', 'brand', 'product_type', 'rack_location',
        'cost_price', 'retail_price', 'commission_amount', 'wholesale_price', 'min_wholesale_qty',
        'stock', 'min_stock_alert', 'is_active'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function priceHistories()
    {
        return $this->hasMany(PriceChangeHistory::class)->with('user')->latest();
    }

    public function purchaseDetails()
    {
        return $this->hasMany(\App\Models\PurchaseDetail::class);
    }

    public function latestPurchaseDetail()
    {
        return $this->hasOne(\App\Models\PurchaseDetail::class)->latestOfMany();
    }

    public function transactionDetails()
    {
        return $this->hasMany(TransactionDetail::class)->latest();
    }
}