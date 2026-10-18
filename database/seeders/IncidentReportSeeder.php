<?php

namespace Database\Seeders;

use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class IncidentReportSeeder extends Seeder
{
    public function run(): void
    {
        $conservators = User::query()
            ->where('role', 'konservasionis_lapangan')
            ->get();

        $reports = [
            [
                'location' => 'Desa Bukit Lawang',
                'incident_type' => 'Pertemuan Satwa dengan Petugas',
                'description' => 'Seekor orangutan terlihat di dekat pos jaga pada pagi hari. Tim mencatat waktu dan kondisi lingkungan sekitar.',
            ],
            [
                'location' => 'Jalan Lintas Sumatera KM 35',
                'incident_type' => 'Satwa Melintasi Jalan Raya',
                'description' => 'Harimau sumatera menyeberang jalan raya pada malam hari. Informasi diteruskan kepada petugas lintas wilayah.',
            ],
            [
                'location' => 'Perkebunan Kelapa Sawit Kuala Batang',
                'incident_type' => 'Ancaman Within Lahan',
                'description' => 'Jejak aktivitas satwa terdeteksi di dalam lahan perkebunan. Perlu penjagaan terjadwal oleh tim lapangan.',
            ],
            [
                'location' => 'Koridor Sungai Batang Gadis',
                'incident_type' => 'Penjarahan Ilegal Terdeteksi',
                'description' => 'Ditemukan jerat pada jalur koridor ekologi. Bukti diamankan sementara tim melakukan Penyelidikan.',
            ],
            [
                'location' => 'Desa Sungai Sembilan',
                'incident_type' => 'Satwa Terluka',
                'description' => 'Rusa generasi muda ditemukan dengan luka ringan. Hewan dipindahkan ke pos rescue setelah pemeriksaan awal.',
            ],
        ];

        foreach ($reports as $index => $item) {
            $reporter = $conservators->get($index % max($conservators->count(), 1));

            IncidentReport::query()->updateOrCreate(
                [
                    'location' => $item['location'],
                    'incident_type' => $item['incident_type'],
                ],
                [
                    'user_id' => $reporter?->user_id,
                    'description' => $item['description'],
                    'reported_at' => now()->subDays($index + 1)->setTime(9, 30, 0),
                    'media_url' => null,
                ],
            );
        }
    }
}
