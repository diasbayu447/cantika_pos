<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    use HasFactory;

    protected $guarded = [];

    // Cast JSON otomatis agar old_values & new_values dibaca sebagai array di Blade dan Controller
    protected $casts = [
        'old_values'     => 'array',
        'new_values'     => 'array',
        'is_rolled_back' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Catat log teks sederhana
    public static function record($action, $description = null)
    {
        return self::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'description' => $description,
        ]);
    }

    // Catat log dengan snapshot data sebelum dan sesudah diedit
    public static function recordWithSnapshot($action, $description, $model, array $oldValues, array $newValues)
    {
        return self::create([
            'user_id'        => Auth::id(),
            'action'         => $action,
            'description'    => $description,
            'model_type'     => get_class($model),
            'model_id'       => $model->id,
            'old_values'     => $oldValues,
            'new_values'     => $newValues,
            'is_rolled_back' => false,
        ]);
    }
}