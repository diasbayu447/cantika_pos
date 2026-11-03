<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->trustProxies(at: '*');
        
        // Pendaftaran alias middleware kustom
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
            'shift.open' => \App\Http\Middleware\CheckShiftOpen::class,
        ]);

        // Pengalihan otomatis saat user yang SUDAH LOGIN mengakses '/' atau '/login'
        $middleware->redirectUsersTo(function (Request $request) {
            if (Auth::check() && Auth::user()->role === 'cashier') {
                $hasOpenShift = \App\Models\CashierShift::where('user_id', Auth::id())
                    ->where('status', 'open')
                    ->exists();
                
                return $hasOpenShift ? route('pos.index') : route('shift.create');
            }
            return route('dashboard');
        });
        // Pengalihan dan flash alert jika sesi terputus / di-kick
        $middleware->redirectGuestsTo(function (Request $request) {
            session()->flash('alert_kicked', '🛑 Sesi Anda telah berakhir atau diputus paksa. Silakan login kembali.');
            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();