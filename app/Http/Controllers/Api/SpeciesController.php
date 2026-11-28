<?php

namespace App\Http\Controllers\Api;

use App\Models\Species;
use App\Support\GlbValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SpeciesController extends ApiController
{
    public const CONSERVATION_STATUSES = [
        'Least Concern', 'Near Threatened', 'Vulnerable', 'Endangered',
        'Critically Endangered', 'Extinct in the Wild', 'Extinct', 'Data Deficient',
    ];

    public function index(Request $request)
    {
        $query = Species::query()->withCount('animals as animal_count');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('common_name', 'like', "%{$s}%")->orWhere('scientific_name', 'like', "%{$s}%"));
        }
        if ($request->filled('conservation_status')) {
            $query->where('conservation_status', $request->conservation_status);
        }

        $this->applySort($query, $request, ['species_id', 'common_name', 'scientific_name', 'conservation_status', 'created_at'], 'species_id', 'asc');

        return $this->list($query);
    }

    public function show(int $id)
    {
        return $this->success(
            Species::withCount('animals as animal_count')->with('animals')->findOrFail($id)
        );
    }

    public function store(Request $request)
    {
        $species = Species::create($this->validated($request));

        return $this->success($species->loadCount('animals as animal_count'), 'Spesies berhasil dibuat', 201);
    }

    public function update(Request $request, int $id)
    {
        $species = Species::findOrFail($id);
        $species->update($this->validated($request, $species));

        return $this->success($species->fresh()->loadCount('animals as animal_count'), 'Spesies diperbarui');
    }

    public function destroy(int $id)
    {
        $species = Species::withCount('animals')->findOrFail($id);

        if ($species->animals_count > 0) {
            return $this->error("Spesies masih memiliki {$species->animals_count} satwa terkait.", 409);
        }

        $species->delete();

        return $this->success(null, 'Spesies dihapus');
    }

    public function uploadModel(Request $request, int $id)
    {
        $species = Species::findOrFail($id);
        $data = $request->validate([
            'file' => 'required|file|max:'.GlbValidator::MAX_KB,
            'model_source' => ['required', Rule::in(['scan', 'licensed', 'illustrative'])],
            'model_credit' => 'required|string|max:255',
        ]);
        if (strtolower($request->file('file')->getClientOriginalExtension()) !== 'glb') {
            return $this->error('Format harus .glb (glTF biner).', 422, ['file' => ['Format harus .glb (glTF biner).']]);
        }
        GlbValidator::assertValid($request->file('file'));

        $old = $species->model_path;
        $path = $request->file('file')->storeAs('species-models', $species->species_id.'-'.Str::random(12).'.glb', 'local');
        $species->forceFill([
            'model_path' => $path,
            'model_source' => $data['model_source'],
            'model_credit' => strip_tags($data['model_credit']),
            'model_hotspots' => null,
        ])->save();
        if ($old && $old !== $path) {
            Storage::disk('local')->delete($old);
        }

        return $this->success($species->fresh(), 'Model 3D disimpan. Tambahkan titik keterangan bila perlu.');
    }

    public function deleteModel(int $id)
    {
        $species = Species::findOrFail($id);
        if ($species->model_path) {
            Storage::disk('local')->delete($species->model_path);
        }
        $species->forceFill(['model_path' => null, 'model_source' => null, 'model_credit' => null, 'model_hotspots' => null])->save();

        return $this->success($species->fresh(), 'Model 3D dihapus');
    }

    public function model(int $id)
    {
        return WildlifeCatalogController::streamModel(Species::findOrFail($id), public: false);
    }

    private function validated(Request $request, ?Species $species = null): array
    {
        $required = $species ? 'sometimes' : 'required';
        $point = 'regex:/^-?\d{1,4}(\.\d{1,6})?m -?\d{1,4}(\.\d{1,6})?m -?\d{1,4}(\.\d{1,6})?m$/';

        $data = $request->validate([
            'common_name' => "{$required}|string|max:100",
            'scientific_name' => [$required, 'string', 'max:150',
                Rule::unique('species', 'scientific_name')->ignore($species?->species_id, 'species_id')],
            'conservation_status' => [$required, Rule::in(self::CONSERVATION_STATUSES)],
            'local_names' => 'sometimes|nullable|string|max:200',
            'taxon_class' => 'sometimes|nullable|string|max:60',
            'taxon_order' => 'sometimes|nullable|string|max:60',
            'taxon_family' => 'sometimes|nullable|string|max:60',
            'description' => 'sometimes|nullable|string|max:5000',
            'characteristics' => 'sometimes|nullable|array|max:20',
            'characteristics.*' => 'string|max:300',
            'size_weight' => 'sometimes|nullable|string|max:255',
            'lifespan' => 'sometimes|nullable|string|max:100',
            'activity_pattern' => 'sometimes|nullable|string|max:100',
            'diet' => 'sometimes|nullable|string|max:3000',
            'behavior' => 'sometimes|nullable|string|max:5000',
            'habitat' => 'sometimes|nullable|string|max:3000',
            'distribution' => 'sometimes|nullable|string|max:3000',
            'population_estimate' => 'sometimes|nullable|string|max:255',
            'threats' => 'sometimes|nullable|array|max:20',
            'threats.*' => 'string|max:300',
            'fun_facts' => 'sometimes|nullable|array|max:20',
            'fun_facts.*' => 'string|max:300',
            'legal_protection' => 'sometimes|nullable|string|max:255',
            'is_public' => 'sometimes|boolean',
            'model_hotspots' => 'sometimes|nullable|array|max:15',
            'model_hotspots.*.label' => 'required|string|max:60',
            'model_hotspots.*.description' => 'nullable|string|max:300',
            'model_hotspots.*.position' => ['required', 'string', $point],
            'model_hotspots.*.normal' => ['nullable', 'string', $point],
        ]);

        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                $value = trim(strip_tags($value));
            }
        });

        return $data;
    }
}
