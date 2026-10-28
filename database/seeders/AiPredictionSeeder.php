<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AiPredictionSeeder extends Seeder
{
    public function run(): void
    {
        $preds = [
            [1, 1, 1, 0.9829, 'YOLOv8-v1', 'completed', '2026-10-19 10:35:00', 'confirmed', 1, 2, '2026-10-19 11:00:00'],
            [2, 2, 2, 0.9456, 'YOLOv8-v1', 'completed', '2026-10-19 09:50:00', 'confirmed', 2, 1, '2026-10-19 10:15:00'],
            [3, 3, 3, 0.8734, 'YOLOv8-v1', 'completed', '2026-10-19 11:25:00', 'pending', null, null, null],
            [4, 4, 4, 0.9123, 'YOLOv8-v1', 'completed', '2026-10-18 15:35:00', 'confirmed', 4, 2, '2026-10-18 16:00:00'],
            [5, 5, 5, 0.7891, 'YOLOv8-v1', 'completed', '2026-10-15 09:10:00', 'rejected', 5, 1, '2026-10-15 10:30:00'],
        ];
        foreach ($preds as $p) {
            DB::table('ai_predictions')->updateOrInsert(
                ['prediction_id' => $p[0]],
                ['media_id' => $p[1], 'predicted_species_id' => $p[2], 'confidence' => $p[3], 'model_version' => $p[4], 'processing_status' => $p[5], 'processed_at' => $p[6], 'verification_status' => $p[7], 'verified_species_id' => $p[8], 'verified_by' => $p[9], 'verified_at' => $p[10]]
            );
        }
    }
}
