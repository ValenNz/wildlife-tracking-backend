<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiMediaUploadSeeder extends Seeder
{
    public function run(): void
    {
        $media = [
            [1, 1, 1, 'image', 'uploads/media_001.jpg', '2026-10-19 10:30:00'],
            [2, 2, 2, 'video', 'uploads/media_002.mp4', '2026-10-19 09:45:00'],
            [3, 3, 3, 'image', 'uploads/media_003.jpg', '2026-10-19 11:20:00'],
            [4, 1, 4, 'image', 'uploads/media_004.jpg', '2026-10-18 15:30:00'],
            [5, 2, 5, 'video', 'uploads/media_005.mp4', '2026-10-15 09:00:00'],
        ];
        foreach ($media as $m) {
            DB::table('ai_media_uploads')->updateOrInsert(
                ['media_id' => $m[0]],
                ['uploaded_by' => $m[1], 'report_id' => $m[2], 'media_type' => $m[3], 'file_path' => $m[4], 'uploaded_at' => $m[5]]
            );
        }
    }
}
