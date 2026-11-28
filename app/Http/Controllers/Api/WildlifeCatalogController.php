<?php

namespace App\Http\Controllers\Api;

use App\Models\Species;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class WildlifeCatalogController extends ApiController
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'search' => 'sometimes|string|max:100',
            'taxon_class' => 'sometimes|string|max:60',
            'conservation_status' => 'sometimes|string|max:50',
        ]);

        return $this->list(Species::where('is_public', true)
            ->when($data['search'] ?? null, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('common_name', 'like', "%{$s}%")
                ->orWhere('scientific_name', 'like', "%{$s}%")
                ->orWhere('local_names', 'like', "%{$s}%")))
            ->when($data['taxon_class'] ?? null, fn ($q, $c) => $q->where('taxon_class', $c))
            ->when($data['conservation_status'] ?? null, fn ($q, $c) => $q->where('conservation_status', $c))
            ->orderBy('common_name'));
    }

    public function show(int $id)
    {
        return $this->success(Species::where('is_public', true)->findOrFail($id));
    }

    public function model(int $id)
    {
        $species = Species::where('is_public', true)->findOrFail($id);

        return self::streamModel($species, public: true);
    }

    public static function streamModel(Species $species, bool $public)
    {
        abort_unless($species->model_path && Storage::disk('local')->exists($species->model_path), 404, 'Model 3D belum tersedia.');

        return Storage::disk('local')->response($species->model_path, 'species-'.$species->species_id.'.glb', [
            'Content-Type' => 'model/gltf-binary',
            'Cache-Control' => $public ? 'public, max-age=3600' : 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
