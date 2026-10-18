<?php

namespace Database\Seeders;

use App\Models\AIMediaUpload;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiMediaUploadSeeder extends Seeder
{
    public function run(): void
    {
        $conservators = User::query()
            ->where('role', 'konservasionis_lapangan')
            ->get();

        $reports = IncidentReport::query()->orderBy('report_id')->get();

        for ($index = 0; $index < 5; $index++) {
            $uploader = $conservators->get($index % max($conservators->count(), 1));
            $report = $reports->get($index % max($reports->count(), 1));
            $isVideo = $index % 2 === 1;

            AIMediaUpload::query()->updateOrCreate(
                ['file_path' => sprintf('field-uploads/2026/03/sample-%02d.%s', $index + 1, $isVideo ? 'mp4' : 'jpg')],
                [
                    'uploaded_by' => $uploader?->user_id,
                    'report_id' => $report?->report_id,
                    'media_type' => $isVideo ? 'video' : 'image',
                    'uploaded_at' => now()->subDays($index + 1)->setTime(10, 15, 0),
                ],
            );
        }
    }
}
