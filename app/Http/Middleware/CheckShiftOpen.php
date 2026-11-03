<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\CashierShift;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckShiftOpen
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fitur buka shift ini khusus untuk role Kasir
        if (Auth::check() && Auth::user()->role === 'cashier') {
            
            // Cek apakah ada shift yang masih "open" untuk kasir ini
            $activeShift = CashierShift::where('user_id', Auth::id())
                                       ->where('status', 'open')
                                       ->first();

            // Jika tidak ada shift aktif, paksa ke halaman Buka Shift
            if (!$activeShift) {
                return redirect()->route('shift.create')->with('warning', 'Anda harus memasukkan Modal Awal (Buka Shift) sebelum melayani pelanggan.');
            }
        }

        return $next($request);
    }
}