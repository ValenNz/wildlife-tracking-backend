<?php

namespace Database\Seeders;

use App\Models\AIMediaUpload;
use App\Models\AIPrediction;
use App\Models\Species;
use App\Models\User;
use Illuminate\Database\Seeder;

class AiPredictionSeeder extends Seeder
{
    public function run(): void
    {
        $media = AIMediaUpload::query()->orderBy('media_id')->get();
        $species = Species::query()->orderBy('species_id')->get();
        $researchers = User::query()->where('role', 'peneliti_ekologi')->get();

        $confidenceScores = [96.40, 91.75, 88.20, 74.60, 63.15];

        foreach ($media as $index => $item) {
            $predicted = $species->get($index % $species->count());
            $verifier = $researchers->get($index % max($researchers->count(), 1));

            AIPrediction::query()->updateOrCreate(
                ['media_id' => $item->media_id],
                [
                    'predicted_species_id' => $predicted->species_id,
                    'confidence_score' => $confidenceScores[$index % count($confidenceScores)],
                    'predicted_at' => now()->subDays($index + 1)->setTime(10, 20, 0),
                    'verified_by' => $verifier?->user_id,
                    'verified_species_id' => $index === 4 ? $species->first()->species_id : $predicted->species_id,
                ],
            );
        }
    }
}
