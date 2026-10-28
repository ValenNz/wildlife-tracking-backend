<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [1, 'Ir. Drajat Mustofa, S.Kom', 'user1@wildlife.id', '$2y$12$dummyhashpassword', 'administrator_sistem', '2026-10-19 14:43:26'],
            [2, 'Adiarja Haryanto', 'user2@wildlife.id', '$2y$12$dummyhashpassword', 'peneliti_ekologi', '2026-10-19 14:43:26'],
            [3, 'Mutia Purnawati', 'user3@wildlife.id', '$2y$12$dummyhashpassword', 'konservasionis_lapangan', '2026-10-19 14:43:26'],
            [4, 'Tgk. Baktianto Pratama, S.Kom', 'user4@wildlife.id', '$2y$12$dummyhashpassword', 'pengambil_kebijakan', '2026-10-19 14:43:26'],
            [5, 'Asmadi Waskita', 'user5@wildlife.id', '$2y$12$dummyhashpassword', 'peneliti_ekologi', '2026-10-19 14:43:26'],
        ];
        foreach ($users as $u) {
            DB::table('users')->updateOrInsert(
                ['user_id' => $u[0]],
                ['name' => $u[1], 'email' => $u[2], 'password' => $u[3], 'role' => $u[4], 'created_at' => $u[5]]
            );
        }
    }
}
