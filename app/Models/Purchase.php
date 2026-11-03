<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Purchase extends Model
{
    protected $fillable = [
    'supplier_id', 'user_id', 'invoice_number', 'purchase_date',
    'total_amount', 'payment_method', 'due_date', 'payment_status', 'paid_at'
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'due_date' => 'date',
        'paid_at' => 'datetime',
    ];

    public function details() { return $this->hasMany(PurchaseDetail::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }
    public function user() { return $this->belongsTo(User::class); }
}