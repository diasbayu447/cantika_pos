<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CashierShift extends Model
{
    protected $fillable = [
        'user_id', 'start_cash', 'end_cash_system', 'end_cash_actual', 
        'difference', 'status', 'opened_at', 'closed_at'
    ];

    // Beri tahu Laravel bahwa kolom ini adalah format tanggal/waktu
    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function pettyCashes() { return $this->hasMany(PettyCash::class); }
}