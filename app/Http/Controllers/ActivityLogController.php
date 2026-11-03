<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::with('user')->latest()->paginate(25);
        return view('activity_logs.index', compact('logs'));
    }

    public function rollback($id)
    {
        $log = ActivityLog::findOrFail($id);

        $oldValues = is_array($log->old_values) ? $log->old_values : json_decode($log->old_values, true);

        if (!$log->model_type || empty($oldValues) || $log->is_rolled_back) {
            return back()->withErrors(['error' => 'Log ini tidak dapat di-rollback atau sudah pernah di-rollback sebelumnya.']);
        }

        $modelClass = $log->model_type;
        $record = $modelClass::find($log->model_id);

        if (!$record) {
            return back()->withErrors(['error' => 'Data asli sudah tidak ditemukan di database.']);
        }

        DB::beginTransaction();
        try {
            // Pulihkan nilai barang ke kondisi sebelum diedit
            $record->update($oldValues);

            // Tandai log ini sudah berhasil di-rollback
            $log->update(['is_rolled_back' => true]);

            // Catat riwayat rollback ke log
            ActivityLog::record(
                'Rollback Data',
                "Mengembalikan (undo) perubahan pada {$record->name} ke versi sebelumnya via Log #{$log->id}"
            );

            DB::commit();
            return back()->with('success', "Data '{$record->name}' berhasil dikembalikan ke kondisi semula!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal memulihkan data: ' . $e->getMessage()]);
        }
    }
}