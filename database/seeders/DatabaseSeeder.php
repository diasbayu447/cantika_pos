<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Matikan sementara proteksi Foreign Key agar bersih total
        Schema::disableForeignKeyConstraints();

        // 2. Buat Akun Tunggal: OWNER (Titik Nol)
        $userCols = Schema::getColumnListing('users');
        $ownerData = [
            'name'         => 'Pemilik Toko',
            'username'     => 'owner',
            'password'     => Hash::make('password123'),
            'pin'          => '000000',
            'role'         => 'owner',
            'display_name' => 'Owner',
            'is_active'    => true,
        ];

        User::updateOrCreate(
            ['username' => 'owner'],
            array_intersect_key($ownerData, array_flip($userCols))
        );

        // 3. Buat Data Identitas Dasar Toko (Wajib untuk Struk Thermal & WA)
        if (class_exists(StoreSetting::class) && Schema::hasTable('store_settings')) {
            $settingCols = Schema::getColumnListing('store_settings');
            $settingData = [
                'shop_name'      => 'CANTIKA AKSESORIS',
                'address'        => 'Jl. Aksesoris No. 123, Cirebon',
                'phone'          => '+6281234567890',
                'receipt_footer' => 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan.',
            ];

            StoreSetting::updateOrCreate(
                ['id' => 1],
                array_intersect_key($settingData, array_flip($settingCols))
            );
        }

        // 4. Nyalakan kembali proteksi Foreign Key
        Schema::enableForeignKeyConstraints();
    }
}