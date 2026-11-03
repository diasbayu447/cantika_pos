<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\ActivityLog;
use App\Models\User;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // 1. CEK STATUS AKUN (AKTIF / NONAKTIF)
            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors([
                    'username' => 'Akun Anda saat ini dinonaktifkan! Hubungi Owner/Supervisor.',
                ]);
            }

            // 2. CEK ZOMBIE SESSION / DUAL LOGIN
            // Cari apakah user ini punya sesi aktif selain dari sesi percobaan login barusan
            $activeSession = DB::table('sessions')
                ->where('user_id', $user->id)
                ->where('id', '!=', $request->session()->getId())
                ->first();

            if ($activeSession) {
                // Jika login ini menyertakan PIN SPV (sedang mencoba bypass)
                if ($request->filled('spv_pin')) {
                    
                    // Verifikasi apakah PIN valid milik SPV atau Owner
                    $authorizedSpv = User::whereIn('role', ['owner', 'supervisor'])
                        ->where('pin', $request->spv_pin)
                        ->where('is_active', true)
                        ->first();

                    if (!$authorizedSpv) {
                        Auth::logout(); // Batalkan login
                        $request->session()->invalidate();
                        return back()->with('needs_spv_pin', true)
                                     ->withInput($request->only('username'))
                                     ->withErrors(['spv_pin' => 'PIN Supervisor salah atau Anda tidak berwenang!']);
                    }

                    // PIN VALID! Hapus sesi nyangkut (Force Kill)
                    DB::table('sessions')
                        ->where('user_id', $user->id)
                        ->where('id', '!=', $request->session()->getId())
                        ->delete();
                    
                    if (class_exists(ActivityLog::class)) {
                        ActivityLog::record('Force Login', "Sesi tersangkut dibuka paksa menggunakan otorisasi PIN {$authorizedSpv->name} ({$authorizedSpv->username}) selaku {$authorizedSpv->role}.");
                    }

                } else {
                    // BLOKIR LOGIN BARU (Tampilkan peringatan + kolom PIN SPV)
                    Auth::logout();
                    $request->session()->invalidate();
                    return back()->with('needs_spv_pin', true)
                                 ->withInput($request->only('username'))
                                 ->withErrors(['username' => 'Akses ditolak karena akun masih login di browser/perangkat lain! Sesi tidak bisa digandakan.']);
                }
            } else {
                // Login normal, pastikan database bersih dari sampah sesi lama
                DB::table('sessions')
                    ->where('user_id', $user->id)
                    ->where('id', '!=', $request->session()->getId())
                    ->delete();
            }

            // 3. SUKSES LOGIN
            $request->session()->regenerate();
            
            if (class_exists(ActivityLog::class) && !$request->filled('spv_pin')) {
                ActivityLog::record('Login', 'User ' . $user->username . ' login ke sistem.');
            }
            
            if ($user->role === 'cashier') {
                return redirect()->route('shift.create');
            }
            return redirect()->route('dashboard');
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ]);
    }
    
    public function logout(Request $request)
    {
        if (Auth::check() && class_exists(ActivityLog::class)) {
            ActivityLog::record('Logout', 'User ' . Auth::user()->username . ' logout dari sistem.');
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect('/');
    }
}