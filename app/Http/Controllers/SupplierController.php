<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        // Ambil semua supplier beserta jumlah transaksi kulakan yang pernah dilakukan
        $suppliers = Supplier::withCount('purchases')->latest()->get();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
        ]);

        // Standarisasi nomor ke format +62
        $phone = $this->formatPhone($request->phone);

        $supplier = Supplier::create([
            'name' => $request->name,
            'pic_name' => $request->pic_name,
            'phone' => $phone,
            'address' => $request->address,
            'is_active' => true,
        ]);

        ActivityLog::record('Tambah Supplier', "Menambahkan supplier baru: {$supplier->name}");
        return back()->with('success', "Supplier '{$supplier->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, $id)
    {
        $supplier = Supplier::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'pic_name' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:15',
            'address' => 'nullable|string|max:500',
        ]);

        $phone = $this->formatPhone($request->phone);

        $supplier->update([
            'name' => $request->name,
            'pic_name' => $request->pic_name,
            'phone' => $phone,
            'address' => $request->address,
        ]);

        ActivityLog::record('Edit Supplier', "Memperbarui data supplier: {$supplier->name}");
        return back()->with('success', "Data supplier '{$supplier->name}' berhasil diperbarui.");
    }

    public function toggleStatus($id)
    {
        $supplier = Supplier::findOrFail($id);
        $supplier->update(['is_active' => !$supplier->is_active]);

        $status = $supplier->is_active ? 'Diaktifkan' : 'Dinonaktifkan';
        ActivityLog::record('Ubah Status Supplier', "{$status} sales/supplier: {$supplier->name}");

        return back()->with('success', "Status supplier '{$supplier->name}' berhasil diubah menjadi {$status}.");
    }

    // Fungsi pembersih format nomor HP ke +62
    private function formatPhone($phone)
    {
        if (!$phone) return null;

        // Ambil hanya karakter angka
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // Ubah awalan 0 atau 62 menjadi format standar +62
        if (str_starts_with($digits, '62')) {
            $digits = substr($digits, 2);
        } elseif (str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        // Batasi panjang nomor maksimal 11-12 digit setelah 62 (total 13 digit)
        $digits = substr($digits, 0, 11);

        return '+62' . $digits;
    }

    public function destroy($id)
    {
        $supplier = \App\Models\Supplier::findOrFail($id);
        $namaSupplier = $supplier->name;

        try {
            // Coba hapus supplier
            $supplier->delete();

            // Catat ke Log Aktivitas (jika ada)
            if (class_exists(\App\Models\ActivityLog::class)) {
                \App\Models\ActivityLog::record('Hapus Supplier', "Menghapus data supplier: {$namaSupplier}");
            }

            return back()->with('success', 'Data supplier berhasil dihapus!');
            
        } catch (\Illuminate\Database\QueryException $e) {
            // Tangkap error jika supplier tidak bisa dihapus karena masih terikat data Hutang / Kulakan (Foreign Key Constraint 23000)
            if ($e->getCode() == "23000") {
                return back()->withErrors(['error' => "Gagal menghapus! Supplier '{$namaSupplier}' tidak bisa dihapus karena masih memiliki riwayat transaksi Kulakan atau Hutang."]);
            }
            
            return back()->withErrors(['error' => 'Terjadi kesalahan pada database saat menghapus data.']);
        }
    }
}