<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $fillable = ['return_number', 'purchase_id', 'supplier_id', 'user_id', 'total_amount', 'reason'];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function details() { return $this->hasMany(PurchaseReturnDetail::class); }
}