<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'invoice_number', 'user_id', 'customer_id', 'payment_method',
        'total_amount', 'discount_amount', 'discount_note',
        'points_earned', 'points_redeemed', 'points_discount_amount',
        'cash_amount', 'non_cash_amount', 'change_amount', 'status'
    ];

    public function details() { return $this->hasMany(TransactionDetail::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}