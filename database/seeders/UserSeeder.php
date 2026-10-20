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
                'user_id' => 1,
                'name' => 'Ir. Drajat Mustofa, S.Kom',
                'email' => 'user1@wildlife.id',
                'password' => '$2y$12$dummyhashpassword',
                'role' => 'administrator_sistem',
                'created_at' => '2026-10-19 14:43:26',
            ],
            [
                'user_id' => 2,
                'name' => 'Adiarja Haryanto',
                'email' => 'user2@wildlife.id',
                'password' => '$2y$12$dummyhashpassword',
                'role' => 'peneliti_ekologi',
                'created_at' => '2026-10-19 14:43:26',
            ],
            [
                'user_id' => 3,
                'name' => 'Mutia Purnawati',
                'email' => 'user3@wildlife.id',
                'password' => '$2y$12$dummyhashpassword',
                'role' => 'konservasionis_lapangan',
                'created_at' => '2026-10-19 14:43:26',
            ],
            [
                'user_id' => 4,
                'name' => 'Tgk. Baktianto Pratama, S.Kom',
                'email' => 'user4@wildlife.id',
                'password' => '$2y$12$dummyhashpassword',
                'role' => 'pengambil_kebijakan',
                'created_at' => '2026-10-19 14:43:26',
            ],
            [
                'user_id' => 5,
                'name' => 'Asmadi Waskita',
                'email' => 'user5@wildlife.id',
                'password' => '$2y$12$dummyhashpassword',
                'role' => 'peneliti_ekologi',
                'created_at' => '2026-10-19 14:43:26',
            ],
        ];

        foreach ($accounts as $account) {
            User::query()->updateOrCreate(
                ['user_id' => $account['user_id']],
                $account,
            );
        }
    }
}