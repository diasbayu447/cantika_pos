<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\StoreSetting;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('role')->get();
        $activeSessions = DB::table('sessions')->whereNotNull('user_id')->pluck('user_id')->toArray();

        return view('users.index', compact('users', 'activeSessions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:100',
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'pin'      => 'nullable|digits:6|unique:users,pin',
            'role'     => 'required|in:owner,supervisor,admin,cashier',
        ], [
            'pin.unique' => 'Gagal! PIN tersebut sudah digunakan oleh staf lain. Gunakan kombinasi PIN yang unik.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
        ]);

        User::create([
            'name'         => $request->name,
            'username'     => $request->username,
            'password'     => Hash::make($request->password),
            'pin'          => $request->pin,
            'role'         => $request->role,
            'display_name' => $request->name,
            'is_active'    => true,
        ]);

        if (class_exists(ActivityLog::class)) {
            // LOG DIPERBARUI
            ActivityLog::record('Tambah Staf', "Menambahkan akun staf baru: {$request->name} ({$request->username}) sebagai {$request->role}.");
        }

        return back()->with('success', 'Staf baru berhasil ditambahkan!');
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $currentUser = Auth::user();

        // Proteksi Hierarki: Supervisor tidak boleh mengedit Owner atau menjadikan akun lain sebagai Owner
        if ($currentUser->role === 'supervisor' && ($user->role === 'owner' || $request->role === 'owner')) {
            return back()->withErrors(['error' => 'Supervisor tidak memiliki hak akses untuk mengubah data atau role Owner!']);
        }

        $request->validate([
            'name'      => 'required|string|max:100',
            'username'  => 'required|string|unique:users,username,' . $id,
            'pin'       => 'nullable|digits:6|unique:users,pin,' . $id,
            'role'      => 'required|in:owner,supervisor,admin,cashier',
            'is_active' => 'required|boolean',
        ], [
            'pin.unique' => 'Gagal! PIN tersebut sudah digunakan oleh akun lain. PIN wajib unik.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
        ]);

        $user->name = $request->name;
        $user->username = $request->username;
        $user->role = $request->role;
        $user->pin = $request->pin;
        $user->is_active = $request->is_active;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if (!$user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        if (class_exists(ActivityLog::class)) {
            ActivityLog::record('Edit Staf', "Memperbarui data akun staf: {$user->name}");
        }

        return back()->with('success', 'Data staf berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->role === 'owner' || $user->id === Auth::id()) {
            return back()->withErrors(['error' => 'Tidak bisa menghapus akun Owner atau akun Anda sendiri!']);
        }

        $name = $user->name;
        $username = $user->username;

        try {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            $user->delete();

            if (class_exists(ActivityLog::class)) {
                ActivityLog::record('Hapus Staf', "Menghapus permanen akun staf: {$name} ({$username}).");
            }

            return back()->with('success', 'Akun staf berhasil dihapus.');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangkap error jika akun tidak bisa dihapus karena masih terikat riwayat transaksi/shift
            if ($e->getCode() == "23000") {
                return back()->withErrors(['error' => "Gagal menghapus! Akun '{$name}' tidak bisa dihapus permanen karena sudah memiliki riwayat transaksi atau shift. Silakan ubah Status Akun menjadi 'Nonaktif' (Blokir)."]);
            }
            
            return back()->withErrors(['error' => 'Terjadi kesalahan pada database saat menghapus data.']);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'         => 'required|string|max:100',
            'display_name' => 'nullable|string|max:50',
            'pin'          => 'nullable|digits:6|unique:users,pin,' . $user->id,
            'password'     => 'nullable|string|min:6',
        ], [
            'pin.unique' => 'Gagal! PIN tersebut sudah dipakai oleh pengguna lain. Gunakan PIN yang unik.',
            'pin.digits' => 'PIN harus tepat 6 digit angka.',
        ]);

        $user->name = $request->name;
        if (Schema::hasColumn('users', 'display_name')) {
            $user->display_name = $request->display_name ?: $request->name;
        }
        $user->pin = $request->pin;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        if (class_exists(ActivityLog::class)) {
            ActivityLog::record('Update Profil', "{$user->name} memperbarui profil & keamanan mandiri.");
        }

        return back()->with('success', 'Profil dan pengaturan keamanan berhasil diperbarui!');
    }

    public function updateStoreSetting(Request $request)
    {
        $request->validate([
            'shop_name'      => 'required|string|max:100',
            'address'        => 'required|string|max:255',
            'phone'          => 'required|string|max:20',
            'receipt_footer' => 'nullable|string|max:255',
        ]);

        $cleanDigits = preg_replace('/[^0-9]/', '', $request->phone);
        if (str_starts_with($cleanDigits, '62')) {
            $cleanDigits = substr($cleanDigits, 2);
        } elseif (str_starts_with($cleanDigits, '0')) {
            $cleanDigits = substr($cleanDigits, 1);
        }

        $cleanDigits = substr($cleanDigits, 0, 12);
        $formattedPhone = '+62' . $cleanDigits;

        $setting = StoreSetting::first() ?? new StoreSetting();
        $setting->shop_name = $request->shop_name;
        $setting->address = $request->address;
        $setting->phone = $formattedPhone;
        $setting->receipt_footer = $request->receipt_footer;
        $setting->save();

        if (class_exists(ActivityLog::class)) {
            ActivityLog::record('Update Profil Toko', "Memperbarui identitas toko: {$setting->shop_name} ({$setting->phone})");
        }

        return back()->with('success', 'Identitas toko dan nomor layanan komplain berhasil diperbarui!');
    }

    public function kickSession($id)
    {
        $currentUser = Auth::user();
        $targetUser = User::findOrFail($id);

        if ($currentUser->id === $targetUser->id) {
            return back()->withErrors(['error' => 'Tidak bisa memutus sesi akun sendiri!']);
        }
        if ($targetUser->role === 'owner') {
            return back()->withErrors(['error' => 'Akun Owner kebal dan tidak bisa di-kick!']);
        }
        if ($currentUser->role === 'supervisor' && in_array($targetUser->role, ['owner', 'supervisor'])) {
            return back()->withErrors(['error' => 'Supervisor hanya bisa memutus sesi Kasir dan Admin Gudang!']);
        }

        DB::table('sessions')->where('user_id', $id)->delete();

        if (class_exists(ActivityLog::class)) {
            // LOG DIPERBARUI
            ActivityLog::record('Kick Session', "Memutus paksa sesi login milik {$targetUser->name} ({$targetUser->username}) selaku {$targetUser->role}.");
        }

        return back()->with('success', "Sesi {$targetUser->name} berhasil diputus paksa.");
    }

    public function unlockScreen(Request $request)
    {
        $request->validate([
            'pin' => 'required|digits:6',
        ]);

        $currentUser = Auth::user();
        $inputPin = $request->pin;

        if ($currentUser->pin && $currentUser->pin === $inputPin) {
            return response()->json([
                'success' => true,
                'message' => 'Layar berhasil dibuka.',
            ]);
        }

        $authorizedUser = User::whereIn('role', ['owner', 'supervisor'])
            ->where('pin', $inputPin)
            ->where('is_active', true)
            ->first();

        if ($authorizedUser) {
            if (class_exists(ActivityLog::class)) {
                // LOG DIPERBARUI
                ActivityLog::record(
                    'Buka Paksa Layar',
                    "Layar terkunci dibuka paksa menggunakan otorisasi PIN {$authorizedUser->name} ({$authorizedUser->username}) selaku {$authorizedUser->role}."
                );
            }

            return response()->json([
                'success' => true,
                'message' => "Layar dibuka oleh {$authorizedUser->name} ({$authorizedUser->role}).",
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'PIN salah atau Anda tidak memiliki hak otorisasi!',
        ], 403);
    }
}