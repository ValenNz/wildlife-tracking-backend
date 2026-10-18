<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('user_id')->get();

        $entries = [
            ['action' => 'login', 'details' => 'Login berhasil dari perangkat lapangan.'],
            ['action' => 'create', 'details' => 'Menambahkan data satwa baru ke master data.'],
            ['action' => 'update', 'details' => 'Memperbarui informasi zona geografis.'],
            ['action' => 'export', 'details' => 'Mengekspor data pembacaan GPS ke berkas CSV.'],
            ['action' => 'verify_prediction', 'details' => 'Verifikasi hasil prediksi spesies dari modul AI.'],
        ];

        foreach ($entries as $index => $entry) {
            $user = $users->get($index % max($users->count(), 1));

            ActivityLog::query()->updateOrCreate(
                [
                    'user_id' => $user->user_id,
                    'action' => $entry['action'],
                    'details' => $entry['details'],
                ],
                [
                    'created_at' => now()->subDays($index),
                ],
            );
        }
    }
}
