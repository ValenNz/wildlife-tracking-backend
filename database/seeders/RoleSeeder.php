<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->updateOrInsert(
            ['role_id' => 1],
            ['role_name' => 'konservasionis_lapangan', 'description' => 'Petugas konservasi di lapangan', 'created_at' => '2026-10-19 10:00:00']
        );
        DB::table('roles')->updateOrInsert(
            ['role_id' => 2],
            ['role_name' => 'peneliti_ekologi', 'description' => 'Peneliti yang menganalisis data ekologi dan satwa', 'created_at' => '2026-10-19 10:00:00']
        );
        DB::table('roles')->updateOrInsert(
            ['role_id' => 3],
            ['role_name' => 'administrator_sistem', 'description' => 'Administrator yang mengelola sistem dan pengguna', 'created_at' => '2026-10-19 10:00:00']
        );
        DB::table('roles')->updateOrInsert(
            ['role_id' => 4],
            ['role_name' => 'pengambil_kebijakan', 'description' => 'Pengambil kebijakan yang memantau laporan dan ringkasan', 'created_at' => '2026-10-19 10:00:00']
        );
    }
}
