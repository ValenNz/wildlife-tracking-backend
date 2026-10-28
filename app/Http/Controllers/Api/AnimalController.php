<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnimalResource;
use App\Models\Animal;
use Illuminate\Http\Request;

class AnimalController extends Controller
{
    public function index()
    {
        $animals = Animal::with(['species', 'trackingDevice'])->get();
        return AnimalResource::collection($animals);
    }

    public function show($id)
    {
        $animal = Animal::with(['species', 'trackingDevice', 'animalGeoZones'])->find($id);
        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }
        return new AnimalResource($animal);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'species_id' => 'required|integer|exists:species,species_id',
            'name' => 'required|string|max:100',
            'birth_date' => 'nullable|date',
            'gender' => 'required|in:male,female,unknown',
            'is_active' => 'nullable|boolean',
        ]);
        $animal = Animal::create($validated);
        return new AnimalResource($animal);
    }

    public function update(Request $request, $id)
    {
        $animal = Animal::find($id);
        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }
        $validated = $request->validate([
            'species_id' => 'sometimes|integer|exists:species,species_id',
            'name' => 'sometimes|string|max:100',
            'birth_date' => 'nullable|date',
            'gender' => 'sometimes|in:male,female,unknown',
            'is_active' => 'nullable|boolean',
        ]);
        $animal->update($validated);
        return new AnimalResource($animal);
    }

    public function destroy($id)
    {
        $animal = Animal::find($id);
        if (!$animal) {
            return response()->json(['message' => 'Animal not found'], 404);
        }
        $animal->delete();
        return response()->json(['message' => 'Animal deleted']);
    }
}
