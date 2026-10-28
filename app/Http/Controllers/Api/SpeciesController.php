<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SpeciesResource;
use App\Models\Species;
use Illuminate\Http\Request;

class SpeciesController extends Controller
{
    public function index()
    {
        $species = Species::all();
        return SpeciesResource::collection($species);
    }

    public function show($id)
    {
        $species = Species::with('animals')->find($id);
        if (!$species) {
            return response()->json(['message' => 'Species not found'], 404);
        }
        return new SpeciesResource($species);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'common_name' => 'required|string|max:100',
            'scientific_name' => 'required|string|max:150|unique:species,scientific_name',
            'conservation_status' => 'required|string|max:50',
        ]);
        $species = Species::create($validated);
        return new SpeciesResource($species);
    }

    public function update(Request $request, $id)
    {
        $species = Species::find($id);
        if (!$species) {
            return response()->json(['message' => 'Species not found'], 404);
        }
        $validated = $request->validate([
            'common_name' => 'sometimes|string|max:100',
            'scientific_name' => 'sometimes|string|max:150|unique:species,scientific_name,' . $id . ',species_id',
            'conservation_status' => 'sometimes|string|max:50',
        ]);
        $species->update($validated);
        return new SpeciesResource($species);
    }

    public function destroy($id)
    {
        $species = Species::find($id);
        if (!$species) {
            return response()->json(['message' => 'Species not found'], 404);
        }
        $species->delete();
        return response()->json(['message' => 'Species deleted']);
    }
}
