<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionDetail extends Model
{
    protected $fillable = [
        'transaction_id', 'product_id', 'technician_id', 'qty',
        'cost_price', 'selling_price', 'is_wholesale', 'commission_amount',
        'discount_amount', 'subtotal', 'target_number',
    ];

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function technician() { return $this->belongsTo(User::class, 'technician_id'); }
}