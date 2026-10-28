<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [1, 'view_data', 'Melihat data satwa, pelacakan, dan konservasi', '2026-10-19 10:00:00'],
            [2, 'create_data', 'Menambahkan data baru', '2026-10-19 10:00:00'],
            [3, 'update_data', 'Mengubah data yang sudah ada', '2026-10-19 10:00:00'],
            [4, 'delete_data', 'Menghapus data', '2026-10-19 10:00:00'],
            [5, 'manage_users', 'Mengelola akun pengguna', '2026-10-19 10:00:00'],
            [6, 'manage_roles', 'Mengelola role dan permission', '2026-10-19 10:00:00'],
            [7, 'view_reports', 'Melihat laporan dan ringkasan', '2026-10-19 10:00:00'],
        ];
        foreach ($permissions as $p) {
            DB::table('permissions')->updateOrInsert(
                ['permission_id' => $p[0]],
                ['permission_name' => $p[1], 'description' => $p[2], 'created_at' => $p[3]]
            );
        }
    }
}
