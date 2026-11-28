<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['username' => 'admin', 'full_name' => 'Admin Sistem', 'email' => 'admin@wildlife.id', 'role' => 'administrator_sistem', 'address' => 'Kantor Balai Besar TNGL, Medan'],
            ['username' => 'peneliti', 'full_name' => 'Peneliti Ekologi', 'email' => 'peneliti@wildlife.id', 'role' => 'peneliti_ekologi', 'address' => 'Stasiun Riset Ketambe'],
            ['username' => 'lapangan', 'full_name' => 'Konservasionis Lapangan', 'email' => 'lapangan@wildlife.id', 'role' => 'konservasionis_lapangan', 'address' => 'Pos Jaga Bukit Lawang'],
            ['username' => 'loket', 'full_name' => 'Petugas Loket Bukit Lawang', 'email' => 'loket@wildlife.id', 'role' => 'petugas_loket', 'address' => 'Loket Bukit Lawang'],
            ['username' => 'pengunjung', 'full_name' => 'Pengunjung Contoh', 'email' => 'pengunjung@wildlife.id', 'role' => 'pengunjung', 'address' => 'Medan'],
            ['username' => 'kebijakan', 'full_name' => 'Pengambil Kebijakan', 'email' => 'kebijakan@wildlife.id', 'role' => 'pengambil_kebijakan', 'address' => 'KLHK, Jakarta'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['email' => $u['email']], [
                'username' => $u['username'],
                'full_name' => $u['full_name'],
                'password' => 'password123',
                'role_id' => Role::where('name_role', $u['role'])->value('role_id'),
                'address' => $u['address'],
                'status' => 'aktif',
            ]);
        }
    }
}
