<?php

namespace Database\Seeders;

use App\Models\AIMediaUpload;
use Illuminate\Database\Seeder;

class AiMediaUploadSeeder extends Seeder
{
    public function run(): void
    {
        $uploads = [
            [
                'media_id' => 1,
                'uploaded_by' => 1,
                'report_id' => 1,
                'media_type' => 'image',
                'file_path' => 'uploads/media_001.jpg',
                'uploaded_at' => '2026-10-19 10:30:00',
            ],
            [
                'media_id' => 2,
                'uploaded_by' => 2,
                'report_id' => 2,
                'media_type' => 'video',
                'file_path' => 'uploads/media_002.mp4',
                'uploaded_at' => '2026-10-19 09:45:00',
            ],
            [
                'media_id' => 3,
                'uploaded_by' => 3,
                'report_id' => 3,
                'media_type' => 'image',
                'file_path' => 'uploads/media_003.jpg',
                'uploaded_at' => '2026-10-19 11:20:00',
            ],
            [
                'media_id' => 4,
                'uploaded_by' => 1,
                'report_id' => 4,
                'media_type' => 'image',
                'file_path' => 'uploads/media_004.jpg',
                'uploaded_at' => '2026-10-18 15:30:00',
            ],
            [
                'media_id' => 5,
                'uploaded_by' => 2,
                'report_id' => 5,
                'media_type' => 'video',
                'file_path' => 'uploads/media_005.mp4',
                'uploaded_at' => '2026-10-15 09:00:00',
            ],
        ];

        foreach ($uploads as $item) {
            AIMediaUpload::query()->updateOrCreate(
                ['media_id' => $item['media_id']],
                $item,
            );
        }
    }
}