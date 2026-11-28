<?php

namespace Database\Seeders;

use App\Models\Species;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class SpeciesSeeder extends Seeder
{
    public function run(): void
    {
        $profiles = require __DIR__.'/data/species_profiles.php';

        foreach ($profiles as $scientific => $profile) {
            $species = Species::updateOrCreate(['scientific_name' => $scientific], Arr::except($profile, ['model']));

            if (! isset($profile['model']) || ($species->model_source !== null && $species->model_source !== 'illustrative')) {
                continue;
            }

            $glb = __DIR__."/species-models/{$profile['model']}.glb";
            $hotspots = __DIR__."/species-models/{$profile['model']}.hotspots.json";
            if (! is_file($glb)) {
                continue;
            }

            $path = "species-models/{$profile['model']}.glb";
            Storage::disk('local')->put($path, file_get_contents($glb));
            $species->forceFill([
                'model_path' => $path,
                'model_source' => 'illustrative',
                'model_credit' => 'Low-poly buatan tim, bukan hasil pemindaian; bentuk & warna disederhanakan.',
                'model_hotspots' => is_file($hotspots) ? json_decode(file_get_contents($hotspots), true) : null,
            ])->save();
        }
    }
}
