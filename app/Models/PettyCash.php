<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    protected $fillable = ['cashier_shift_id', 'amount', 'description'];

    public function shift() { return $this->belongsTo(CashierShift::class, 'cashier_shift_id'); }
}