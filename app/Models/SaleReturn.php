<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleReturn extends Model
{
    protected $fillable = ['return_number', 'transaction_id', 'user_id', 'action_type', 'total_refund_amount', 'reason'];

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function details() { return $this->hasMany(SaleReturnDetail::class); }
}