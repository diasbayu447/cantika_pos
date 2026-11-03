<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    public function download()
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host', '127.0.0.1');
        
        $fileName = 'backup_cantika_' . date('Y_m_d_His') . '.sql';
        $storagePath = storage_path('app/public/' . $fileName);
        
        // Siapkan perintah mysqldump (Jika password kosong, abaikan argumen password)
        $passwordArg = !empty($dbPass) ? "--password={$dbPass}" : "";
        $command = "mysqldump --user={$dbUser} {$passwordArg} --host={$dbHost} {$dbName} > \"{$storagePath}\"";
        
        // Eksekusi perintah di latar belakang sistem
        exec($command, $output, $returnVar);
        
        if ($returnVar !== 0 || !File::exists($storagePath)) {
            return back()->with('error', 'Gagal membackup database. Pastikan mysqldump tersedia di environment system (PATH) Anda.');
        }

        // Catat di log bahwa Owner melakukan backup
        \App\Models\ActivityLog::record('Backup Database', 'Mengunduh salinan database ke file ' . $fileName);

        // Unduh file lalu otomatis hapus dari server agar tidak memenuhi penyimpanan
        return response()->download($storagePath)->deleteFileAfterSend(true);
    }
}