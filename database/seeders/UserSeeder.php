<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'username' => 'admin_btnl',
                'full_name' => 'Administrator Sistem',
                'email' => 'admin@tn-gl.org',
                'password' => 'admin123',
                'role' => 'administrator_sistem',
            ],
            [
                'username' => 'konservasis_bukitlawang',
                'role' => 'konservasionis_lapangan',
            ],
            [
                'username' => 'konservasis_kualabatang',
                'role' => 'konservasionis_lapangan',
            ],
            [
                'username' => 'peneliti_leuser',
                'role' => 'peneliti_ekologi',
            ],
            [
                'username' => 'kepala_balai',
                'role' => 'pengambil_kebijakan',
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['username' => $account['username']],
                [
                    'full_name' => $account['full_name'] ?? fake('id_ID')->name(),
                    'email' => $account['email'] ?? fake('id_ID')->unique()->safeEmail(),
                    'password' => Hash::make($account['password'] ?? 'password'),
                    'role' => $account['role'],
                ],
            );
        }
    }
}
